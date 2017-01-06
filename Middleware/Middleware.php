<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 16/7/18
 * Time: 下午5:28
 */
namespace Swock\Middleware;
class Middleware implements MiddlewareInterface{
    protected $request;
    protected $response;
    protected $server;
    protected $request_method;
    protected $request_params;
    public function __construct($server) {
        $this->server = $server;
    }

    /**
     * 中间件处理函数. 如果不返回true则会中断
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @author zhaoduo
     * @date 2016-07-20 10:43:54
     */
    public function handle(\swoole_http_request $request, \swoole_http_response $response) {
        $this->request = $request;
        $this->response = $response;
        $this->parseRequest($request);
    }

    /**
     * 处理请求对象
     * @param \swoole_http_request $request
     * @author zhaoduo
     * @date 2016-07-20 10:44:23
     */
    protected function parseRequest(\swoole_http_request $request) {
        $this->request_method = strtolower($request->server['request_method']);
        $this->request_params = $request->{$this->request_method};
    }


    public function send_back($status = 1, $data = [], $message = '') {
        $this->response->end(json_encode([
            'status' => $status,
            'data' => $data,
            'message' => $message
        ]));
        return false;
    }

}