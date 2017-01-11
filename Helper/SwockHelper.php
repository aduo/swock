<?php
/**
 * helper functions
 * User: zhaoduo
 * Date: 16/5/25
 * Time: 下午8:39
 */

if(!function_exists("load_config")) {
    function load_config() {

    }
}



if(!function_exists("camel_case")) {
    function camel_case($str) {
        return lcfirst(str_replace(" ", "", ucwords(str_replace("_", " ", $str))));
    }
}


if(!function_exists("getInstance")) {
    /**
     * 全局单例
     * @return \Swock\Control
     * @author zhaoduo
     * @date 2016-05-27 20:53:53
     */
    function getInstance() {
        return \Swock\Control::getInstance();
    }
}



if(!function_exists("singleton")) {
    /**
     * 注册实例
     * @param $name
     * @param $callback
     * @return object
     * @author zhaoduo
     * @date 2016-05-29 15:54:58
     */
    function singleton($name, $callback) {
        $app = getInstance();
        return $app::singleton($name, $callback);
    }
}



if(!function_exists("make")) {
    /**
     * 获取实例
     * @param $name
     * @return bool
     * @author zhaoduo
     * @date 2016-05-29 15:52:45
     */
    function make($name) {
        $app = getInstance();
        return $app::make($name);
    }
}


if(!function_exists("getServer")) {
    /**
     * 获取swoole server实例
     * @return mixed
     * @author zhaoduo
     * @date 2016-05-30 14:17:35
     */
    function getServer() {
        $app = getInstance();
        return $app::getServer();
    }
}


if(!function_exists("view")) {
    /**
     * 获取view
     * @param string $path view路径. 相对/Resource/views
     * @param array $params 参数
     * @return mixed html
     * @author zhaoduo
     * @date 2016-05-30 10:29:02
     */
    function view($path, $params = []) {
        $view = singleton("view", function(){
            return new duncan3dc\Laravel\BladeInstance(root_path.'/Resource/views', root_path.'/Storage/views');
        });
        return $view->render($path, $params);
    }
}


if(!function_exists("swockDie")) {
    /**
     * 抛出错误信息, 打断执行栈. 替代die/exit方法.
     * @param string $message 错误信息
     * @author zhaoduo
     * @date 2016-05-30 14:04:14
     * @throws \Swock\Library\SwockException
     */
    function swockDie($message) {
        throw new \Swock\Library\SwockException($message);
    }
}


//if(!function_exists("task")) {
//    function task($method, $data, $task_id = '-1') {
//        $swoole_server = getServer();
//        $swoole_server->task([
//            'method' => $method,
//            'data' => $data
//        ], $task_id);
//    }
//}


if(!function_exists("config")) {
    /**
     * 获取配置信息
     * @param string $name 配置名称 redis.host
     * @return string
     * @author zhaoduo
     * @date 2016-06-05 11:15:08
     */
    function config($name) {
        $app = getInstance();
        return $app::config($name);
    }
}


if(!function_exists('create_token')) {
    /**
     * 创建token
     * @param $data
     * @param string $secret
     * @return string
     * @author zhaoduo
     * @date 2016-07-20 13:59:12
     */
    function create_token($data, $secret = '') {
        unset($data['token']);
        ksort($data);
        $str = urldecode(http_build_query($data, '', '&', 2)).$secret;
        return md5($str);
    }
}


if(!function_exists('check_token')) {
    /**
     * 校验token
     * @param $data
     * @param string $secret
     * @return bool
     * @author zhaoduo
     * @date 2016-07-20 13:59:55
     */
    function check_token($data, $secret = '') {
        return $data['token'] == create_token($data, $secret);
    }
}

if(!function_exists('task')) {
    /**
     * 像task 中投递任务.
     * @param \Swock\Task\BaseTask $task
     *
     * @return mixed
     * @author zhaoduo
     * @date 2016-12-30 11:20:26
     */
    function task(\Swock\Task\BaseTask $task) {
        $server = \Swock\Control::getServer();
        if($task->getDelayTime() > 0) { //设置延时执行
            $server->after($task->getDelayTime() * 1000, function() use($server, $task) {
                $server->task($task, $task->getDstWorkerId(), function(\swoole_server $server, $task_id, $data) use($task){
                    $task->afterHandle($server, $task_id, $data);
                });
            });
            return true;
        } else {
            return $server->task($task, $task->getDstWorkerId(), function(\swoole_server $server, $task_id, $data) use($task){
                $task->afterHandle($server, $task_id, $data);
            });
        }
    }
}


if(!function_exists('sendMessage')) {

    function sendMessage(\Swock\PipMessage\BasePipMessage $message, $worker_id = null) {
        $server = \Swock\Control::getServer();
        if(null === $worker_id) {
            $worker_id = $message->worker_id;
        }

        if(!is_array($worker_id)) {
            $worker_id = [$worker_id];
        }

        if(empty($worker_id)) {
            throw new Exception("send pip message must have a worker id");
        }

        foreach($worker_id as $id) {
            $server->sendMessage($message, $id);
        }
    }
}

