<?php
namespace Swock\Hooks;
use Swock\Middleware\AuthCheck;

class OnOpen {
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }

    public function handle(\swoole_websocket_server $swoole_server, \swoole_http_request $request) {

    }

}