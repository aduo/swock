<?php
namespace Swock\Middleware;


interface MiddlewareInterface {
    public function __construct($server);
    public function handle(\swoole_http_request $request, \swoole_http_response $response);
}