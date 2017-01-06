<?php
namespace Swock\Library;
class MySQL {

    protected $config         = [];
    protected $pool_size      = 0;   //连接数
    protected $idle_pool      = [];   //闲置连接池
    protected $connection_num = 0;


    public function __construct(array $config, $pool_size = 100) {
        $this->configmd5 = md5(json_encode($config));
        if(empty($config['host']) || empty($config['database']) || empty($config['user']) || empty($config['password'])) {
            throw new \Exception("require host, database, user, password config.");
        }
        if(empty($config['port'])) {
            $config['port'] = 3306;
        }
        $this->config = $config;
        $this->pool_size = $pool_size;
        $this->wait_queue = new \SplQueue();
    }


    public function setPoolSize($pool_size) {
        if($this->pool_size == $pool_size) {
            return true;
        }

        if($this->pool_size < $pool_size) {
            for($i = 0; $i < $pool_size - $this->pool_size; $i++) {
                //create connect
                $this->createConnection();
            }
        } else {

        }
        $this->pool_size = $pool_size;
    }

    public function createConnection() {
        $config = $this->config;
        $db = new \swoole_mysql;
        $db->connect($config, function(\swoole_mysql $db, $result) {
            if($result == true) {
                $this->freeConnection($db);
                $this->connection_num++;
            } else {
                echo "mysql connect error with code {$db->connect_errno}, message '{$db->connect_error}'";
            }
        });
    }


    /**
     * 执行sql
     *
     * @param string   $sql
     * @param callable $callback
     */
    public function query($sql, callable $callback) {
        if(count($this->idle_pool) == 0) {  //空闲连接池空. 创建新连接
            if($this->connection_num < $this->pool_size) {
                $this->doConnectionQuery($sql, $callback);  //异步创建连接并执行sql
            } else {    //连接数已经达到最大允许连接数. 加入队列等待空闲连接
                $this->waitQuery($sql, $callback);
            }
        } else {    //拿取空闲连接池中连接. 执行sql.
            $this->doIdleQuery($sql, $callback);
        }
    }

    public function waitQuery($sql, callable $callback) {
        $this->wait_queue->push([
            'sql' => $sql,
            'callback' => $callback
        ]);
    }

    /**
     * 新建连接并执行sql查询
     *
     * @param string   $sql
     * @param callable $callback
     */
    public function doConnectionQuery($sql, callable $callback) {
        $this->connection_num++;
        $config = $this->config;
        $db = new \swoole_mysql;
        $db->connect($config, function(\swoole_mysql $db, $result) use ($sql, $callback) {
            if($result == true) {
                $this->doQuery($db, $sql, $callback);
            } else {
                $this->connection_num--;
                echo "mysql connect error with code {$db->connect_errno}, message '{$db->connect_error}'";
                return false;
            }
        });
    }


    /**
     * 从空闲连接池中拿去连接执行sql
     *
     * @param string   $sql sql语句
     * @param callable $callback 回调函数
     */
    public function doIdleQuery($sql, callable $callback) {
        $db = array_pop($this->idle_pool);
        $this->doQuery($db, $sql, $callback);
    }


    /**
     * 从等待队列中拿取sql执行
     * @param $db
     */
    public function doWaitQuery($db) {
        $sql_task = $this->wait_queue->shift();
        $this->doQuery($db, $sql_task['sql'], $sql_task['callback']);
    }

    /**
     * 执行sql
     *
     * @param \swoole_mysql $db
     * @param string        $sql
     * @param callable      $callback
     */
    public function doQuery(\swoole_mysql $db, $sql, callable $callback) {
        $db->query($sql, function(\swoole_mysql $db, $result) use ($callback, $sql) {
            if($result === false) {
                //如果错误编码为2013 or 2006 则说明连接超时, 被mysql强制断开. 则需要重新建立连接
                if($db->errno == 2013 or $db->errno == 2006 or (isset($db->_errno) and $db->_errno == 2006)) {
                    $this->closeConnection($db);    //关闭已经失效连接
                    $this->doConnectionQuery($sql, $callback);  //新建连接查询
                    return;
                }
            }

            try {
                call_user_func_array($callback, [$db, $result]);
                $this->freeConnection($db);
            } catch(\Exception $e) {
                $this->freeConnection($db);
            }
        });
    }

    /**
     * 释放连接到空闲连接池
     *
     * @param \swoole_mysql $db
     */
    public function freeConnection(\swoole_mysql $db) {
        //检查队列中是否存在等待执行的sql
        if(count($this->wait_queue > 0)) {
            $this->doWaitQuery($db);
        } else {
            array_push($this->idle_pool, $db);
        }
    }

    /**
     * 关闭连接. 连接数--
     *
     * @param \swoole_mysql $db
     */
    public function closeConnection(\swoole_mysql $db) {
        $db->close();
        $this->connection_num--;
    }

}