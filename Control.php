<?php
namespace Swock;

use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionPrinter\ConsoleOptionPrinter;

class Control {
    private static $_tree = [];  //注册树
    private static $_server;    //swoole server进程
    private static $_config;
    private static $_instance;  //实例
    static $star_time;
    static $options;
    static $optionKit;
    static $defaultOptions = [
        'd|daemonize' => '启用守护进程模式',
        'f|force' => '强制关闭server',
        'h|host?' => '指定监听地址',
        'p|port?' => '指定监听端口',
        'help' => '显示帮助界面',
        'b|base' => '使用BASE模式启动',
        'w|worker?' => '设置Worker进程的数量',
        'r|thread?' => '设置Reactor线程的数量',
        't|tasker?' => '设置Task进程的数量'];
    static $pidFile;

    private function __construct() {

    }

    /**
     * 执行启动程序
     * @author zhaoduo
     * @date 2016-05-26 16:50:06
     * @param $startFunction
     * @throws \Exception
     * @throws \GetOptionKit\Exception
     * @throws \GetOptionKit\Exception\InvalidOptionException
     * @throws \GetOptionKit\Exception\RequireValueException
     */
    static function doIt($startFunction) {
        if(empty(self::$pidFile)) {
            throw new \Exception("require pidFile.");
        }
        $pid_file = self::$pidFile;
        if(is_file($pid_file)) {
            $server_pid = file_get_contents($pid_file);
        } else {
            $server_pid = 0;
        }
        if(!self::$optionKit) {
            self::$optionKit = new OptionCollection();
        }
        $kit = self::$optionKit;
        foreach(self::$defaultOptions as $k => $v) {
            $kit->add($k, $v);
        }
        global $argv;
        $parser = new OptionParser($kit);
        $opt = $parser->parse($argv)->toArray();
        if(empty($argv[1]) or isset($opt['help'])) {
            goto usage;
        } elseif($argv[1] == 'reload') {
            if(empty($server_pid)) {
                exit("Server is not running \r\n");
            }
            posix_kill($server_pid, SIGUSR1);
            exit;
        } elseif($argv[1] == 'stop') {
            if(empty($server_pid)) {
                exit("Server is not running \r\n");
            }
            if($opt['force']) {
                posix_kill($server_pid, SIGKILL);
                self::killProcessByName('start.php');
            } else {
                posix_kill($server_pid, SIGTERM);
            }
            exit;
        } elseif($argv[1] == 'start') {
            //已存在ServerPID，并且进程存在
            if(!empty($server_pid) and posix_kill($server_pid, 0)) {
                exit("Server is already running. \r\n");
            }
        } else {
            usage:
            $printer = new ConsoleOptionPrinter();
//            $printer->render("php {$argv[0]} start|stop|reload");
            echo "==================================================\r\n";
            echo "     php {$argv[0]} start|stop|reload             \r\n";
            echo "==================================================\r\n";
            echo $printer->render($kit);
//            $kit->specs->printOptions("php {$argv[0]} start|stop|reload");
            exit;
        }
        self::$options = $opt;
        $opt['pid_file'] = self::$pidFile;
        $startFunction($opt);
    }

    /**
     * 设置PID文件
     * @param $pidFile
     */
    static function setPidFile($pidFile) {
        self::$pidFile = $pidFile;
    }

    /**
     * 杀死所有进程
     * @param $name
     * @param int $signo
     * @return string
     */
    static function killProcessByName($name, $signo = 9) {
//        $cmd = 'ps -eaf |grep "' . $name . '" | grep -v "grep"| awk "{print $2}"|xargs kill -' . $signo;
        $cmd = "ps -eaf |grep '" . $name . "' | grep -v 'grep'| awk '{print $2}'|xargs kill -" . $signo;
        return exec($cmd);
    }

    /**
     *
     * $opt->add( 'f|foo:' , 'option requires a value.' );
     * $opt->add( 'b|bar+' , 'option with multiple value.' );
     * $opt->add( 'z|zoo?' , 'option with optional value.' );
     * $opt->add( 'v|verbose' , 'verbose message.' );
     * $opt->add( 'd|debug'   , 'debug message.' );
     * $opt->add( 'long'   , 'long option name only.' );
     * $opt->add( 's'   , 'short option name only.' );
     *
     * @param $specString
     * @param $description
     * @throws ServerOptionException
     */
    static function addOption($specString, $description) {
        if(!self::$optionKit) {
            self::$optionKit = new OptionCollection();
        }
        foreach(self::$defaultOptions as $k => $v) {
            if($k[0] == $specString[0]) {
                throw new ServerOptionException("不能添加系统保留的选项名称");
            }
        }
        self::$optionKit->add($specString, $description);
    }

    /**
     * 获取单例
     * @return Control
     * @author zhaoduo
     * @date 2016-05-27 20:51:52
     */
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * 绑定server对象.
     * @param \swoole_server $server
     * @author zhaoduo
     * @date 2016-05-30 14:11:35
     */
    public static function bindServer(\swoole_server $server) {
        self::$_server = $server;
    }

    /**
     * 保存配置信息
     * @param $config
     * @author zhaoduo
     * @date 2016-06-05 11:36:33
     */
    public static function setConfig($config) {
        self::$_config = $config;
    }

    /**
     * 获取server实例
     * @return \swoole_server $server
     * @author zhaoduo
     * @date 2016-06-05 11:22:53
     */
    public static function getServer() {
        return self::$_server;
    }

    /**
     * 获取配置
     * @param string $name 配置名
     * @author zhaoduo
     * @date 2016-06-05 11:23:28
     */
    public static function config($name = '') {
        if($name == "") {
            return self::$_config;
        }
        //切割
        $config_name = explode(".", trim($name));
        $main_config = self::$_config[$config_name[0]];
        if(!isset($config_name[1])) {
            return $main_config;
        } else {
            return $main_config[$config_name[1]];
        }
    }


    /**
     * 注册实例
     * @param $name
     * @param $callback
     * @return object 注册对象
     * @author zhaoduo
     * @date 2016-05-29 11:18:10
     */
    public static function singleton($name, $callback) {
        $class = self::make($name);
        if($class === false) {
            $class = self::$_tree[md5($name)] = call_user_func($callback, self::getInstance());
        }
        return $class;
    }

    /**
     * 获取实例
     * @param $name
     * @return bool 如果实例存在返回实例, 不存在则返回false
     * @author zhaoduo
     * @date 2016-05-30 10:39:12
     */
    public static function make($name) {
        $name = md5($name);
        return isset(self::$_tree[$name]) ? self::$_tree[$name] : false;
    }

    /**
     * 清空注册树
     * @author zhaoduo
     * @date 2016-07-14 15:46:23
     */
    public static function clearTree() {
        self::$_tree = [];
    }


    public static function useMemory() {
        return memory_get_usage();
//        print_r(memory_get_usage().PHP_EOL);
    }



    /**
     * 防止单例被clone
     * @author zhaoduo
     * @date 2016-05-30 10:39:18
     */
    public function __clone() {

    }
}