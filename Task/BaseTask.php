<?php
namespace Swock\Task;


class BaseTask implements TaskInterface {
    protected $dst_worker_id = -1;
    protected $delay_time = 0;

    public function __construct() {

    }

    public function handle(\swoole_server $server, $task_id, $from_id) {

    }

    public function afterHandle(\swoole_server $server, $task_id, $data) {

    }

    public function getDstWorkerId() {
        return $this->dst_worker_id;
    }

    /**
     * @param int $dst_worker_id
     *
     * @return $this
     */
    public function setDstWorkerId($dst_worker_id) {
        $this->dst_worker_id = $dst_worker_id;
        return $this;
    }


    /**
     * @return int
     */
    public function getDelayTime() {
        return $this->delay_time;
    }


    /**
     * @param int $delay_time
     *
     * @return $this
     */
    public function setDelayTime($delay_time) {
        $this->delay_time = $delay_time;
        return $this;
    }

    /**
     * alias for setDelayTime
     *
     * @param $delay_time
     *
     * @return $this
     */
    public function delay($delay_time) {
        return $this->setDelayTime($delay_time);
    }

}