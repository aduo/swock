<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2017/1/10
 * Time: 17:05
 */

namespace Swock\Library;


class AsyncResponse {
    private $_response;
    private $_write_num = 0;
    public function __construct() {

    }

    public function setResponse(\swoole_http_response $response) {
        $this->_response = $response;
    }

    public function getResponse() {
        return $this->_response;
    }

    public function end($str = '') {
        $ob_buffer = ob_get_clean();
        $ob_buffer && $this->_response->write($ob_buffer);
        $str && $this->_response->write($str);
        if(!$ob_buffer && !$str && $this->_write_num == 0) {
            $this->_response->write(" ");
        }
        $this->_response->end();
    }

    public function write($str = '') {
        $this->_response->write($str);
        $this->_write_num++;
    }

    public function __call($name, $arguments) {
        if(method_exists($this->_response, $name)) {
            call_user_func_array([$this->_response, $name], $arguments);
        }
    }


}