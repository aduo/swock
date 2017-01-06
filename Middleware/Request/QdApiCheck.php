<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 16/7/18
 * Time: 下午5:26
 */
namespace Swock\Middleware\Request;
use Swock\Middleware\Middleware;
class QdApiCheck extends Middleware{
    private $qd_key = '0a76232240ad1bf2051d5996c815ea7c';
    public function __construct($server) {
        parent::__construct($server);
    }

    public function handle(\swoole_http_request $request, \swoole_http_response $response) {
        parent::handle($request, $response);
        if(!isset($this->request_params['token'])) {
            $this->send_back(0, [], '数据校验失败');
            return false;
        }

        if(!check_token($this->request_params, $this->qd_key)) {
            $this->send_back(0, [], '数据校验失败');
            return false;
        }
        return true;
    }

}