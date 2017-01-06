<?php

namespace Swock\Server;


use Swock\Controllers\MessageHandle;
use Swock\Hooks\OnHandShake;
use Swock\Hooks\OnMessage;
use Swock\Hooks\OnOpen;
use Swock\Middleware\AuthCheck;
use swoole_http_request;
use swoole_websocket_server;
use swoole_server;
use Whoops\Exception\ErrorException;

class SocketServer extends Base implements SocketServerInterface {
    public function __construct() {

    }

    /**
     * 设置server
     * @param swoole_websocket_server $server
     * @author zhaoduo
     * @date 2016-05-26 10:24:18
     * @return swoole_websocket_server
     */
    public function setServer(swoole_websocket_server $server = null) {
        if($server == null) {   //没有注入server则使用默认server
            $server_config = $this->config['server'];
            $server = new swoole_websocket_server($server_config['host'], $server_config['port']);
        }
        return $this->server = $server;
    }


    public function setCtrlPort($port, $type = SWOOLE_SOCK_TCP) {
        return $this->server->listen('0.0.0.0', $port, $type);
    }

    /**
     * master启动.
     * @param swoole_server $server
     * @author zhaoduo
     * @date
     */
    public function onStart(swoole_server $server) {
        parent::onStart($server);
    }

    /**
     * worker task 启动
     * @param swoole_server $server
     * @param int $worker_id worker 和task进程id.
     * @author zhaoduo
     * @date 2016-05-26 18:47:21
     */
    public function onWorkerStart(swoole_server $server, $worker_id) {
        parent::onWorkerStart($server, $worker_id);
    }

    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response) {
        $key = md5(OnHandShake::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnHandShake($this);
        }
        return $this->{$key}->handle($request, $response);
    }


    /**
     * 完成握手后
     * @param swoole_websocket_server $server
     * @param swoole_http_request $request
     * @author zhaoduo
     * @date 2016-05-26 19:36:59
     */
    public function onOpen(swoole_websocket_server $server, swoole_http_request $request) {
        $key = md5(OnOpen::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnOpen($this);
        }

        try{
            $this->{$key}->handle($server, $request);
        } catch(ErrorException $e) {
            $this->close($request->fd);
            $this->errorLog($e);
        }
    }


    /**
     * socket收到消息
     * @param swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * @author zhaoduo
     * @date 2016-05-26 19:11:09
     */
    public function onMessage(swoole_websocket_server $server, \swoole_websocket_frame $frame) {
        $key = md5(OnMessage::class);
        if($this->{$key} == null) {
            $this->{$key} = new OnMessage($this);
        }

        try{
            $this->{$key}->handle($server, $frame);
        }catch(ErrorException $e) {
            $this->errorLog($e);
        }

    }

    public function run($setting = []) {
        $this->setCallback($this->server, ['message', 'open', 'handShake']);
        parent::run($setting);
    }

    /**
     * 发送单个消息
     * @param $fd
     * @param $message
     * @author zhaoduo
     * @date 2016-05-30 20:15:11
     */
    public function send($fd, $message) {
        if(is_array($message)) {
            $message = json_encode($message);
        }
        $this->server->push($fd, $message);
    }

}