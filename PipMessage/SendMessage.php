<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2016/12/30
 * Time: 11:08
 */

namespace Swock\PipMessage;


class SendMessage extends BasePipMessage {
    public $worker_id = [2, 3, 4];
    public function __construct($message = '') {
        parent::__construct();
        $this->message = $message;
    }

    public function handle(\swoole_server $server, $from_worker_id) {
        parent::handle($server, $from_worker_id);
        echo "this is server ", $server->worker_id, " & the message is ", $this->message;
    }

}