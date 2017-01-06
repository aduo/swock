<?php
namespace Swock\Hooks;
class OnMessage {
    private $server;
    public function __construct($server) {
        $this->server = $server;
        $this->route = make("route");

    }

    /**
     * 路由分发
     * @param \swoole_webhttp://t.cn/RIEVJwTsocket_server $swoole_server
     * @param \swoole_websocket_frame $frame
     * @author zhaoduo
     * @date 2016-05-29 16:15:38
     */
    public function handle(\swoole_websocket_server $swoole_server, \swoole_websocket_frame $frame) {
        $this->route->wsHandle($frame);
//        $this->route->handle($frame);
    }

}