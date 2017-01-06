<?php
namespace Swock\Server;

interface SocketServerInterface {

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame);

    public function run();

}
