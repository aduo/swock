<?php
namespace Swock\Hooks;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Predis\Client as PredisClient;
use Swock\Control;
use Swock\Library\Route;
use Swock\Library\Swyac;
use Swock\Middleware\RequestRoute;
use Swock\Middleware\TaskRoute;
use swoole_server;
use Swock\Library\MySQL;
use Swock\Middleware\MessageRoute;
use Vinelab\Http\Client;

/**
 * worker start 钩子
 * Class OnWorkerStart
 * @package Swock\Hooks
 */
class OnWorkerStart {
    private $server;

    public function __construct($server) {
        $this->server = $server;
    }

    public function handle(swoole_server $swoole_server, $worker_id) {
        Control::$star_time = date("Y-m-d H:i:s", time());
        $app = getInstance();
        //绑定swoole server
        $app::bindServer($this->server->server);
        //注册Yac
        $yac_conf = $this->server->config['yac'];
        Swyac::yac($yac_conf['prefix']);
        //
        $app::singleton('swyac', function() use ($yac_conf) {
            return new Swyac\Swyac($yac_conf);
        });

        //注册路由
        $route = $app::singleton("route", function() {
            return new Route();
        });

        //创建路由和mysql线程池
        if($worker_id < $swoole_server->setting['worker_num']) {   //worker进程
            //加载路由配置
            require root_path . '/Route/RequestRoute.php';
            require root_path . '/Route/MessageRoute.php';

            if($worker_id == 0) {
                $mysql = $app::singleton('mysql', function() {
                    $config = config('mysql');
                    return new MySQL($config, 10);
                });
            }

        } else {    //task进程
            require root_path . '/Route/TaskRoute.php';

            //注册同步mysql
            $app::singleton('mysql', function() use($worker_id, $swoole_server){   //异步mysql只能在worker中使用
                $config = config('mysql');
                $db = new Capsule();
                $db->addConnection([
                    'driver'    => 'mysql',
                    'host'      => $config['host'],
                    'database'  => $config['database'],
                    'username'  => $config['user'],
                    'password'  => $config['password'],
                ]);
                //                $db->setEventDispatcher(new Dispatcher(new Container));
                $db->setAsGlobal();
                //                $db->bootEloquent();
                return $db;
            });

            $app::singleton("predis", function() {
                $redis_config = $this->server->config['redis'];
                $redis = new PredisClient($redis_config, $redis_config);
                return $redis;
            });
            $app::singleton("http_client", function() {
                return new Client();
            });
        }
    }

}