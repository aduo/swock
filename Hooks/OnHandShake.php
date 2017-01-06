<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 16/7/14
 * Time: 下午4:37
 */
namespace Swock\Hooks;
use Swock\Control;
use Swock\Library\Swyac;
use Swock\Middleware\WsShakeMiddleware;

class OnHandShake {
    const GUID                      = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const WEBSOCKET_VERSION         = 13;
    private $server;
    private $request;
    private $response;
    private $swoole_server;

    public function __construct($server) {
        $this->server = $server;
        $this->swoole_server = Control::getServer();
        $this->middleware = new WsShakeMiddleware();
    }

    /**
     * socket握手
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @return boolean
     * @author zhaoduo
     * @date 2016-07-14 16:44:48
     */
    public function handle(\swoole_http_request $request, \swoole_http_response $response) {
        $this->request = $request;
        $this->response = $response;

        if(!isset($request->header['sec-websocket-key'])) {
            return $this->badHandShake();
        }
        $key = $request->header['sec-websocket-key'];
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key) || 16 !== strlen(base64_decode($key))) {
            return $this->badHandShake();
        }

        try {
            //身份校验
            if(false === $this->middleware->handle($request)) {
                return $this->badHandShake($this->middleware->message);
            }
            //触发onOpen
            $fd = $request->fd;
            $this->swoole_server->defer(function() use($fd, $request){
                $this->middleware->defer($fd, $request, $this->swoole_server);
            });

            //握手成功
            $response->status(101);
            $response->header('Upgrade', 'websocket');
            $response->header('Connection', 'Upgrade');
            $response->header('Sec-WebSocket-Accept', base64_encode(sha1($key . static::GUID, true)));
            $response->header('Sec-WebSocket-Version', self::WEBSOCKET_VERSION);
            $response->end();
            return true;
        } catch(\Exception $e) {
            $this->badHandShake($e->getMessage());
        }

    }

    /**
     * @param $message
     *
     * @return bool
     * @author zhaoduo
     * @date 2016-12-29 15:12:01
     */
    private function badHandShake($message = '') {
        $this->response->status(500);
        $this->response->header('Upgrade', 'websocket');
        $this->response->header('Connection', 'Upgrade');
        $this->response->header('Sec-WebSocket-Version', self::WEBSOCKET_VERSION);
        $this->response->header('Message', $message ? $message : "handshake error");
        $this->response->end();
        return false;
    }

}