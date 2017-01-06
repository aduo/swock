<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 16/6/22
 * Time: 上午11:02
 */
namespace Swock\Controllers\Message;
class SocketController extends MessageBaseController{

    public function __construct($server) {
        parent::__construct($server);
    }

    public function heartbeat($data, $fd, $id, $frame) {
        $this->sendData($fd, "heartbeat", [], 1, $id);
    }
}