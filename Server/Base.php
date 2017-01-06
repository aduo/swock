<?php
namespace Swock\Server;
use CHH\Optparse\Exception;
use Monolog\Logger;
use Swock\Hooks\OnClose;
use Swock\Hooks\OnPipeMessage;
use Swock\Hooks\OnRequest;
use Swock\Hooks\OnTask;
use Swock\Hooks\OnWorkerStart;
use Swock\Library\SwockException;
use swoole_server;
use swoole_http_request;
use swoole_http_response;
use Whoops\Exception\ErrorException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Base {
    public $server;
    public $config = [];
    private $log;
    public $runtimeSetting = [];

    public function __construct() {

    }

    /**
     * 加载配置文件
     * @param $ini_file
     * @author zhaoduo
     * @date 2016-05-26 14:43:16
     */
    public function loadConfig($ini_file) {
        if(!is_file($ini_file)) {
            exit("配置文件错误($ini_file)\n");
        }
        $config = array_merge($this->config, parse_ini_file($ini_file, true));
        if(!empty($config['server']['keepalive'])) {
            $this->keepalive = true;
        }
        //是否压缩
        if(!empty($config['server']['gzip_open']) and function_exists('gzdeflate')) {
            $this->gzip = true;
            //default level
            if(empty($config['server']['gzip_level'])) {
                $config['server']['gzip_level'] = 1;
            } //level [1, 9]
            elseif($config['server']['gzip_level'] > 9) {
                $config['server']['gzip_level'] = 9;
            }
        }
        if(!is_array($this->config)) {
            $this->config = array();
        }
        if(!empty($config['server']['daemonize'])) {
            $this->daemonize = $config['server']['daemonize'];
        }
        $this->runtimeSetting = $config['server'];
        $this->config = $config;
    }

    /**
     * 设置log驱动
     * @param $log
     * @author zhaoduo
     * @date 2016-05-26 11:01:32
     */
    public function setLog($log) {
        $this->log = $log;
    }

    public function log($message, $level = 'info') {
        if(is_array($message)) {
            $message = json_encode($message);
        }
        if($this->server->setting["daemonize"] == true) {
            $this->log->log($level, $message);
        } else {
            echo $message.PHP_EOL;
        }
    }


    public function jz_log($key, $message, $type = 'big') {
        $this->server->task([
            'method' => 'log.jz_log',
            'data' => [
                'key' => 'qdb#'.$key,
                'data' => $message,
                'type' => $type
            ]
        ]);
    }

    /**
     * master线程启动
     * @param swoole_server $server
     * @author zhaoduo
     * @date 2016-05-26 14:45:47
     */
    public function onStart(swoole_server $server) {
        //增加监控端口监听
        if(!empty($this->runtimeSetting['pid_file'])) {     //记录master 线程id
            file_put_contents($this->runtimeSetting['pid_file'], $server->master_pid);
        }
    }

    /**
     * master/task 线程启动
     * @param swoole_server $server
     * @param $worker_id
     * @author zhaoduo
     * @date 2016-05-26 13:59:15
     */
    public function onWorkerStart(swoole_server $server, $worker_id) {
        if(function_exists('opcache_reset')) {
            opcache_reset();
        }
        $app = getInstance();
        //绑定swoole server
        $app::bindServer($this->server);
        $app::setConfig($this->config);
        //注册错误处理类
        $app::singleton("whoops", function() {
            $whoops = new Run();
            $whoops->pushHandler(new PrettyPageHandler());
            $whoops->register();
            return $whoops;
        });

        try {
            $worker_start_hook = new OnWorkerStart($this);
            $worker_start_hook->handle($server, $worker_id);
        } catch(ErrorException $e) {
            $this->errorLog($e);
        }
    }

    /**
     * onTask回调
     * @param swoole_server $server server
     * @param int $task_id 任务id
     * @param int $from_id 投递任务的workerid
     * @param mixed $data 数据
     * @return string   返回worker数据
     * @author zhaoduo
     * @date 2016-05-26 14:16:21
     */
    public function onTask(swoole_server $server, $task_id, $from_id, $data) {
        $key = md5(OnTask::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnTask($this);
        }

        try {
            return $this->{$key}->handle($server, $task_id, $from_id, $data);
        } catch(ErrorException $e) {
            $this->errorLog($e);
            return false;
        }
    }

    /**
     * 任务结束回调
     * @param swoole_server $server server
     * @param $task_id      任务id
     * @param $data         任务执行后返回数据
     * @author zhaoduo
     * @date 2016-05-26 14:21:25
     */
    public function onFinish(swoole_server $server, $task_id, $data) {

    }

    /**
     * @param swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param string $data
     * @author zhaoduo
     * @date 2016-05-26 19:16:01
     */
    public function onReceive(swoole_server $server, $fd, $from_id, $data) {

    }

    /**
     * http请求接口
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     * @author zhaoduo
     * @date 2016-05-27 18:10:08
     */
    public function onRequest(swoole_http_request $request, swoole_http_response $response) {
        $key = md5(OnRequest::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnRequest($this);
        }

        try{
            $this->{$key}->handle($request, $response);
        }catch(\Exception $e) {
            $this->errorLog($e);
            if($this->server->exist($request->fd)) {
                if($e->getMessage() != "404 not found") {
                    $response->status(500);
                    $error_500 = view('500');
                    $response->end($error_500);
                }
            }
        }
    }

    /**
     * 连接
     * @param swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @author zhaoduo
     * @date 2016-05-26 19:26:13
     */
    public function onConnect(swoole_server $server, $fd, $from_id) {

    }

    /**
     * 进程间通信
     * @param swoole_server $server
     * @param $from_worker_id
     * @param $message
     * @author zhaoduo
     * @date 2016-05-26 14:37:01
     */
    public function onPipeMessage(swoole_server $server, $from_worker_id, $message) {
        $key = md5(OnPipeMessage::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnPipeMessage($this);
        }

        try{
            return $this->{$key}->handle($server, $from_worker_id, $message);
        }catch(ErrorException $e) {
            $this->errorLog($e);
        }
    }

    /**
     * worker进程关闭
     * @param swoole_server $server
     * @param int $worker_id worker进程id
     * @author zhaoduo
     * @date 2016-05-26 14:31:02
     */
    public function onWorkerStop(swoole_server $server, $worker_id) {
        $this->log("worker#$worker_id#shutdown");
    }

    /**
     * client关闭
     * @param swoole_server $server
     * @param $fd
     * @param $from_id
     * @author zhaoduo
     * @date 2016-06-02 20:25:41
     */
    public function onClose(swoole_server $server, $fd, $from_id) {
        $key = md5(OnClose::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnClose($this);
        }

        try{
            return $this->{$key}->handle($server, $fd, $from_id);
        }catch(ErrorException $e) {
            $this->errorLog($e);
        }
    }


    /**
     * server关闭
     * @param swoole_server $server
     * @author zhaoduo
     * @date 2016-05-26 14:29:41
     */
    public function onShutdown(swoole_server $server) {

    }

    /**
     * 启动server
     * @author zhaoduo
     * @date 2016-05-26 13:19:01
     * @param $setting
     */
    public function run($setting = []) {
        $this->runtimeSetting = array_merge($this->runtimeSetting, $setting);
        $this->server->set($this->runtimeSetting);
        $this->setCallback($this->server, ['start', 'workerStart', 'task', 'finish', 'workerStop', 'shutdown',
            'pipeMessage', 'receive', 'request', 'Connect', 'close']);  //设置回调
        $this->server->start();
    }

    /**
     * 给server设置回调函数
     * @param object $server server
     * @param array|string $callback 回调函数. 支持数组
     * @author zhaoduo
     * @date 2016-05-26 11:14:23
     */
    public function setCallback(&$server, $callback) {
        if(is_array($callback)) {
            foreach($callback as $val) {
                $method = 'on' . ucfirst($val);
                if(method_exists($this, $method)) {
                    $server->on($val, array($this, $method));
                }
            }
        } else {
            $method = 'on' . ucfirst($callback);
            if(method_exists($this, $method)) {
                $server->on($callback, array($this, $method));
            }
        }
    }


    public function setListenCallback(&$server, $name, $callback) {
        $server->on($name, $callback);
    }


    /**
     * 关闭连接
     * @param int $fd 连接id
     * @author zhaoduo
     * @date 2016-05-30 19:35:37
     */
    public function close($fd) {
        $this->server->close($fd);
    }

    public function errorLog(\Exception $e) {
        $error_message = $e->getMessage() . " at {$e->getFile()} line {$e->getLine()}";
        $this->log($error_message);
    }

}