<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 16/5/27
 * Time: 下午6:05
 */
namespace Swock\Hooks;

class OnRequest {
    private $server;
    private $response;
    private $request;
    public function __construct($server) {
        $this->server = $server;
        $this->route = make('route');
    }

    public function handle(\swoole_http_request $request, \swoole_http_response $response) {
        $this->request = $request;
        $this->response = $response;
        $this->route->requestHandle($request, $response);
    }

}