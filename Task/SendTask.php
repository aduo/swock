<?php

namespace Swock\Task;

class SendTask extends BaseTask {

    public function __construct($message = '') {
        parent::__construct();
    }

    public function handle(\swoole_server $server, $task_id, $from_id) {
        parent::handle($server, $task_id, $from_id);
        sleep(5);
        echo "do sth";
        return true;

    }


    public function afterHandle(\swoole_server $server, $task_id, $data) {
        parent::afterHandle($server, $task_id, $data);
        echo 'sth done';
    }

}