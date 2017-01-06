<?php

namespace Swock\Hooks;
use Swock\Control;
use Swock\PipMessage\BasePipMessage;

class OnPipeMessage {
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }

    /**
     * @param \swoole_server $server
     * @param int $from_worker_id
     * @param string $message
     * @return bool
     * @author zhaoduo
     * @date 2016-07-14 10:44:04
     */
    public function handle(\swoole_server $server, $from_worker_id, $message) {
        if(is_subclass_of($message, BasePipMessage::class)){    //如果是pipMessage类则执行handle方法
            try {
                $message->handle($server, $from_worker_id, $message);
                unset($message);
            } catch(\Exception $e) {

            }
        }
        return true;
    }

}