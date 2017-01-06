<?php
namespace Swock\PipMessage;
interface PipMessageInterface {

    public function handle(\swoole_server $server, $from_worker_id);

}