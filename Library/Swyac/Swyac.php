<?php
namespace Swock\Library\Swyac;
use Swock\Library\Swyac\Structures\Queue;
use Swock\Library\Swyac\Structures\Sarray;

class Swyac {
    private $config = [];
    private $prefix = "";
    private $client;
    public function __construct($config = []) {
        if(!extension_loaded('yac')) {
            throw new \Exception('缺少Yac扩展');
            return false;
        }
        $this->config = $config;
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        $this->client = new \Yac($this->prefix);
    }

    /**
     * 队列对象. 不存在则创建
     * @param string    $key        存储key
     * @param int       $length     长度
     * @param array     $data       初始化数据
     * @return Queue
     * @author zhaoduo
     * @date 2016-09-12 19:39:56
     */
    public function queue($key, $length = 10, $data = []) {
        if(!$key) {
            self::throwMessage("缺少队列标识");
        }
        $queue = $this->client->get($key);
        if(!$queue) {
            $queue = new Queue($this->client, $key, $length, $data);
        } else {
            //反序列化
            $queue = unserialize($queue);
        }
        return $queue;
    }

    /**
     * 数组
     * @param $key
     * @param $data
     * @return mixed|Sarray
     * @author zhaoduo
     * @date 2016-09-13 11:38:46
     */
    public function sarray($key, array $data = []) {
        if(!$key) {
            self::throwMessage("缺少数组标识");
        }
        $sarray = $this->client->get($key);
        if(!$sarray) {
            $sarray = new Sarray($this->client, $key, $data);
        } else {
            //反序列化
            $sarray = unserialize($sarray);
        }
        return $sarray;
    }

    /**
     * 返回yac对象
     * @return \Yac
     * @author zhaoduo
     * @date 2016-09-12 20:03:09
     */
    public function getClient() {
        return $this->client;
    }


    /**
     * 返回配置项.
     * @param $item
     * @return array|mixed|null
     * @author zhaoduo
     * @date 2016-09-12 19:26:23
     */
    public function getConfig($item = '') {
        if($item) {
            $config = isset($this->config[$item]) ? $this->config[$item] : null;
        } else {
            $config = $this->config;
        }
        return $config;
    }

    /**
     * 抛出错误
     * @param string $message
     * @author zhaoduo
     * @date 2016-09-12 19:38:14
     * @throws \Exception
     */
    static function throwMessage($message = '') {
        throw new \Exception($message);
    }

}