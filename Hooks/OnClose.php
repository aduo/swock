<?php
namespace Swock\Hooks;

class OnClose {
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }

    /**
     * 用户连接关闭
     * @param \swoole_server $server server
     * @param int $fd 连接id
     * @param int $from_id
     * @author zhaoduo
     * @date 2016-06-03 18:17:34
     */
    public function handle(\swoole_server $server, $fd, $from_id) {
        //删除用户连接信息
    }

}