<?php
date_default_timezone_set('PRC');
define('root_path', dirname(__DIR__));  //根目录
require root_path . '/vendor/autoload.php';
use Swock\Control;
Control::setPidFile(root_path.'/pid');
Control::doIt(function($opt){
    $log = new \Monolog\Logger("swock_logger");
    $log->pushHandler(new \Monolog\Handler\StreamHandler(root_path.'/Storage/Logs/info.log'));
//    $log->pushHandler(new \Monolog\Handler\RotatingFileHandler(root_path.'/Storage/Logs/info.log', 30,\Monolog\Logger::DEBUG));
    $server = new \Swock\Server\SocketServer(); //new socket server
    $server->loadConfig(root_path.'/config.ini');   //加载ini配置
    $server->setLog($log);
    $server->setServer();
//    $listen_port = $server->setCtrlPort(19502, SWOOLE_SOCK_TCP);
    $server->run($opt);
});