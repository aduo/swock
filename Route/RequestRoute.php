<?php
$route->group(['prefix' => 'listen', 'middleware' => ['QdApiCheck']], function($route){
    $route->get('/connections', 'ListenController@connections');
});


$route->group(['prefix' => 'api', 'namespace' => 'Api'], function($route){
    $route->group(['prefix' => 'test'], function($route){
        $route->get('/index', 'TestController@index');
    });
});


$route->group(['prefix' => 'php'], function($route){
    $route->get('server', function(){
        echo "<pre>";
        print_r($_SERVER);
        echo "</pre>";
    });
    $route->get('info', function(){
        echo "<pre>";
        phpinfo();
        echo "</pre>";
    });
    $route->get('error', function(){
        explode();
    });
});

$route->group(['prefix' => 'task'], function($route){
    $route->get('send', function(){
        $server = \Swock\Control::getServer();
        task(new \Swock\Task\SendTask(1));
    });
});


$route->group(['prefix' => 'pip'], function($route){
    $route->get('send', function(){
        sendMessage(new \Swock\PipMessage\SendMessage("测试啊测试啊测试啊"));
    });
});

