<?php
namespace Swock\Task;
interface TaskInterface {

    public function handle(\swoole_server $server, $task_id, $from_id);

    public function afterHandle(\swoole_server $server, $task_id, $data);
}