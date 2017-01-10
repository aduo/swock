<?php
namespace Swock\Library;

use Swock\Control;

/**
 * Class Route
 * @package Swock\Library
 */
class Route {
    /**
     * @var
     */
    private $_class;
    public $parsed_middleware;
    private $middleware;
    private $server;
    public $parsed_namespace;

    public function __construct() {
        $this->server = Control::getServer();
    }


    public function setClass($class) {


    }


    public function getClass($class) {

    }


    public function checkRoute($method, $path) {
        return isset($this->{strtoupper($method)}[$path]);
    }


    public function getRouteInfo($method, $path) {
        if(!$this->checkRoute($method, $path)) {
            return false;
        }
        return $this->{strtoupper($method)}[$path];
    }

    /**
     * 路由分组
     *
     * @param array    $params
     * @param \Closure $callback
     *
     * @author zhaoduo
     * @date 2016-07-18 15:08:18
     */
    public function group($params = [], \Closure $callback) {
        if($params) {
            foreach($params as $item => $val) {
                $this->{$item}[] = $val;
                $item == 'prefix' && $this->parseUrlPrefix();
                $item == 'middleware' && $this->parseMiddleware();
                $item == 'namespace' && $this->parseNamespace();
            }
        }
        call_user_func_array($callback, [$this]);
        if($params) {
            foreach($params as $item => $val) {
                array_pop($this->{$item});
                $item == 'prefix' && $this->parseUrlPrefix();
                $item == 'middleware' && $this->parseMiddleware();
                $item == 'namespace' && $this->parseNamespace();
            }
        }
    }


    public function parseNamespace() {
        $this->parsed_namespace = implode("\\", $this->namespace);
        $this->parsed_namespace && $this->parsed_namespace . '\\';
    }

    /**
     *
     */
    public function parseUrlPrefix() {
        $this->prefix_url = implode("/", $this->prefix);
        $this->prefix_url && $this->prefix_url . '/';
    }


    /**
     *
     */
    public function parseMiddleware() {
        $this->parsed_middleware = [];
        for($i = 0; $i < sizeof($this->middleware); $i++) {
            $this->parsed_middleware = array_merge($this->parsed_middleware, $this->middleware[$i]);
        }
    }

    /**
     * 路由归类
     *
     * @param $name
     * @param $arguments
     *
     * @author zhaoduo
     * @date 2016-05-31 15:08:08
     * @return bool
     */
    public function __call($name, $arguments) {
        if($arguments[0] == "" || $arguments[1] == "") {
            return false;
        }
        $name = strtoupper($name);
        !isset($this->{$name}) && $this->{$name} = [];
        $url = (isset($this->prefix_url) && $this->prefix_url != "") ? '/' . $this->prefix_url . '/' . trim($arguments[0], '/') : '/' . trim($arguments[0], '/');
        if(is_callable($arguments[1])) {
            $this->{$name}[$url]['callback'] = $arguments[1];
        } else {
            $path = explode("@", $arguments[1]);
            $class = (isset($this->parsed_namespace) && $this->parsed_namespace != "") ? '\\' . $this->parsed_namespace . '\\' . trim($path[0], '\\') : '\\' . trim($path[0], '\\');
            $this->{$name}[$url]['class'] = $class;
            $this->{$name}[$url]['function'] = $path[1];
            $this->{$name}[$url]['middleware'] = $this->parsed_middleware;
        }
    }

    /**
     * @param \swoole_http_request  $request request
     * @param \swoole_http_response $response response
     *
     * @throws \Exception
     * @date 2016-12-28 15:39:12
     * @author zhaoduo
     */
    public function requestHandle(\swoole_http_request $request, \swoole_http_response $response) {
        Control::setCurrentRequest($request);
        Control::setCurrentResponse($response);
        ob_start();
        $this->request = $request;
        $this->response = $response;
        $path_info = str_replace("//", "/", $request->server["path_info"]);
        $request_method = $request->server['request_method'];
        $route_info = $this->getRouteInfo($request_method, $path_info);

        //加载中间件
        for($i = 0; $i < sizeof($route_info['middleware']); $i++) {
            $middle_ware = 'Swock\Middleware\Request\\' . $route_info['middleware'][$i];
            $key = md5($middle_ware);
            if($this->{$key} == null) {
                if(class_exists($middle_ware)) {
                    $this->{$key} = new $middle_ware($this->server);
                } else {
                    throw new \Exception("中间件" . $middle_ware . '不存在');
                }
            }
            //执行handle
            $middle_ware_result = $this->{$key}->handle($request, $response);
            if(false === $middle_ware_result) {
                throw new \Exception("中间件中断");
            }
        }
        if(isset($route_info['callback']) && !empty($route_info['callback'])) {
            try{
                $resp = call_user_func($route_info['callback']);
                //                var_dump($resp);
            } catch(\Exception $e) {
                $this->page500($e);
            }
        } else {
            $class = 'Swock\Controllers\Request\\' . trim($route_info['class'], '\\');
            $class_key = md5($class);
            if($this->{$class_key} == null) {
                if(!class_exists($class)) {
                    $this->page404();
                }
                $this->{$class_key} = new $class($this->server);
                $this->{$class_key}->reflection_class = new \ReflectionClass($class);
            }
            $function = $route_info['function'];
            if(!method_exists($this->{$class_key}, $function)) {
                $this->page404();
            }

            $function_key = md5($class . '#' . $function);
            if(!isset($this->{$class_key}->{$function_key})) {
                $reflection = $this->{$class_key}->reflection_class;
                $reflection_function = $reflection->getMethod($function);
                $params = $reflection_function->getParameters();
                $params_class = [];
                if($params) {
                    foreach($params as $param) {
                        try{
                            $params_class[] = $param->getClass()->name;
                        } catch(\Exception $e) {
                            $this->page500($e);
                        }
                    }
                }
                $this->{$class_key}->{$function_key} = $params_class;
            } else {
                $params_class = $this->{$class_key}->{$function_key};
            }
            $function_params = [];
            if(sizeof($params_class) > 0) {
                foreach($params_class as $item) {
                    if($item == "swoole_http_request") {
                        $function_params[] = $request;
                        continue;
                    } else if($item == "swoole_http_response") {
                        $function_params[] = $response;
                        continue;
                    } else {
                        $function_params[] = "";
                    }
                }
            }
            try{
                $resp = call_user_func_array(array($this->{$class_key}, $function), $function_params);
            } catch(\Exception $e) {
                $this->page500($e);
            }
        }
        if(is_object($resp) && is_a($resp, AsyncResponse::class)) { //如果是存在异步任务. 则返回AsyncResponse对象. 不会马上执行response->end()
            $resp->setResponse($response);
        } else {
            $ob_buffer = ob_get_clean();
            $end_str = $ob_buffer . $resp;
            $response->end($end_str ? $end_str : " ");
        }
    }

    /**
     * show 404
     * @throws \Exception
     */
    protected function page404() {
        $view = view("404");
        $this->response->status(404);
        $this->response->end($view);
        throw new \Exception("404 not found"); //跳出
    }


    protected function page500(\Exception $e) {
        $view = view('500', [
            'error_message' => $e->getMessage() . "@" . $e->getFile() . '@Line'. $e->getLine(),
            'trace' => $e->getTrace()
        ]);
        $this->response->status(500);
        $this->response->end($view);
        throw new \Exception("error with 500"); //跳出
    }


    /**
     * @param \swoole_websocket_frame $frame
     *
     * @return bool
     * @author zhaoduo
     * @date 2016-12-28 16:24:43
     */
    public function wsHandle(\swoole_websocket_frame $frame) {
        $message = json_decode($frame->data, true);
        //校验是否合法
        if(json_last_error() != JSON_ERROR_NONE || !isset($message['path']) || $message['path'] == "") {
            //如果不是json字符串则返回错误
            return $this->pushError($frame->fd);
        }
        $route_info = $this->getRouteInfo('WS', $message['path']);
        $class = 'Swock\Controllers\Message\\' . trim($route_info['class'], '\\');
        $class_key = md5($class);
        if(!isset($this->{$class_key})) {
            if(!class_exists($class)) {
                //                return $this->pushError($frame->fd);
            }
            $this->{$class_key} = new $class($this->server);
        }
        //
        $function = $route_info['function'];
        if(!method_exists($this->{$class_key}, $function)) {
            return $this->pushError($frame->fd);
        }
        $resp = call_user_func_array(array($this->{$class_key}, $function), [
            $message['data'], $frame->fd, $message['id'], $frame
        ]);
        return $resp;
    }


    /**
     * @param int    $fd
     *
     * @param string $message
     *
     * @return bool
     */
    private function pushError($fd, $message = "") {
        $this->server->push($fd, json_encode([
            'path'    => "error",
            'status'  => '-1',
            'message' => $message ? $message : 'invalid'
        ]));
        return false;
    }

}
