<?php

namespace Swock\Middleware;

/**
 * socket 握手时处理
 * Class WsShakeMiddleware
 * @package Swock\Middleware
 */
class WsShakeMiddleware {
    public $message = '';
    public function __construct() {
    }

    /**
     * 握手时验证信息. 返回false 则直接连接失败. $this->message 可填写失败原因.
     * @return bool
     * @author zhaoduo
     * @date 2016-12-29 15:01:52
     */
    public function handle(\swoole_http_request $request) {
        $this->message = '';    //重置错误信息
        //do something here

        //return true or false
        return true;
    }


    /**
     * 握手成功后延时执行
     *
     * @param int                  $fd 连接fd
     * @param \swoole_http_request $request 连接请求对象
     * @param                      $swoole_server
     */
    public function defer($fd, \swoole_http_request $request, $swoole_server) {
        $swoole_server->exist($fd) && $swoole_server->push($fd, json_encode([
            'path' => "sys",
            "data" => [
                "title" => '系统消息',
                "message" => "连接消息服务器成功"
            ]
        ]));
    }

}