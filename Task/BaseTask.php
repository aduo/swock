<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2016/12/29
 * Time: 20:03
 */

namespace Swock\Task;


class BaseTask implements TaskInterface {
    protected $dst_worker_id = -1;

    public function __construct() {

    }

    public function handle(\swoole_server $server, $task_id, $from_id) {

    }

    public function afterHandle(\swoole_server $server, $task_id, $data) {

    }

    public function getDstWorkerId() {
        return $this->dst_worker_id;
    }
}