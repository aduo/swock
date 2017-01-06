<?php
namespace Swock\Hooks;

use Swock\Task\BaseTask;

class OnTask {
    private $server;
    public function __construct($server) {
        $this->server = $server;
        $this->route = make("route");
    }

    /**
     * 处理
     *
     * @param \swoole_server $server
     * @param int            $task_id 任务id
     * @param int            $from_id 投递的worker id
     * @param mixed          $data 数据
     *
     * @author zhaoduo
     * @date 2016-05-30 14:41:56
     * @return bool
     */
    public function handle(\swoole_server $server, $task_id, $from_id, $data) {
        if(is_subclass_of($data, BaseTask::class)) {
            $data->handle($server, $task_id, $from_id);
            unset($data);
        }
        return true;
    }
}