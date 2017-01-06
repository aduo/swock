<?php
namespace Swock\Controllers;
use Swock\Task\SendTask;

class BaseController {
    public $swoole_server;
    public function __construct($server) {
        $this->swoole_server = $server;
    }

    /**
     * 给单个用户发送消息
     * @param integer $fd client id
     * @param string $message 消息
     * @author zhaoduo
     * @date 2016-06-02 11:48:40
     */
    public function send($fd, $message) {
        if(is_array($message)) {
            $message = json_encode($message);
        }
        if($fd != "" && $this->swoole_server->exist($fd)) {
            $this->swoole_server->push($fd, $message);
        }
    }

    /**
     * @param $fd
     * @param $method
     * @param $data
     * @param int $status
     * @param int $id
     * @author zhaoduo
     * @date 2016-06-05 16:35:00
     */
    public function sendData($fd, $method, $data, $status = 1, $id = 0) {
        $this->send($fd, [
            "path" => $method,
            "status" => $status,
            "id" => $id,
            "data" => $data,
        ]);
    }

    /**
     * @param $fd_array
     * @param $method
     * @param $data
     * @param int $status
     * @author zhaoduo
     * @date 2016-06-05 16:34:43
     * @return bool
     */
    public function sendAnyData($fd_array, $method, $data, $status = 1) {
        return $this->sendAny($fd_array, [
            "method" => $method,
            "status" => $status,
            "data" => $data
        ]);
    }

    /**
     * @param $fd_array
     * @param $message
     * @author zhaoduo
     * @date 2016-06-05 16:34:47
     * @return bool
     */
    public function sendAny($fd_array, $message) {
        if(!$fd_array) {
            return false;
        }
        if(is_array($message)) {
            $message = json_encode($message);
        }
        $this->task("send.send_any", [
            'fd_array' => $fd_array,
            'message' => $message
        ]);
    }

    /**
     * @param $method
     * @param $data
     * @param int $status
     * @author zhaoduo
     * @date 2016-06-05 16:34:53
     */
    public function sendAllData($method, $data, $status = 1) {
        $this->sendAll([
            "status" => $status,
            "method" => $method,
            "data" => $data
        ]);
    }


    /**
     * 给全体用户发送消息. 投递到task
     * @author zhaoduo
     * @date 2016-06-02 11:50:42
     * @param string|array $message 投递的message. 如果是array则会处理成json字符串
     */
    public function sendAll($message) {
        if(is_array($message)) {
            $message = json_encode($message);
        }
        //投递任务到task
        $this->task("send.send_all", [
            'message' => $message
        ]);
    }


    public function jz_log($key, $data, $type = 'big') {
        $this->task("log.jz_log", [
            'key' => 'qdb#'.$key,
            'data' => $data,
            'type' => $type
        ]);
    }


    /**
     * 向task投递任务
     * @param string $task 任务类型
     * @param array $data 数据
     * @author zhaoduo
     * @date 2016-05-30 20:27:20
     */
    public function task($task, $data = []) {
        $task_data = [
            'method' => $task,
            'data' => $data
        ];
        $this->swoole_server->task($task_data);
    }

    /**
     * log日志
     * @param $message
     * @author zhaoduo
     * @date 2016-06-02 11:51:22
     */
    public function log($message) {

    }

    public function sendToTab($tab, $message = '') {
        return task(new SendTask('tab', $tab, $message));
    }

}