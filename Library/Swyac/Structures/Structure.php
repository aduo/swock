<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2016/9/13
 * Time: 上午11:08
 */
namespace Swock\Library\Swyac\Structures;
class Structure {
    protected $_client;
    protected $_data;
    protected $_key;
    public function __construct($client, $key, array $data) {
        $this->_client = $client;
        $this->_key = $key;
        $this->_data = $data;
    }

    public function push($item) {
        array_push($this->_data, $item);
        return $this;
    }

    /**
     * 弹出第一个
     * @return mixed
     * @author zhaoduo
     * @date 2016-09-13 10:59:42
     */
    public function shift($num = 1) {
        return array_shift($this->_data);
    }


    public function has($key) {
        return isset($this->_data[$key]) ? true : false;
    }

    /**
     * 存储
     * @return $this
     * @author zhaoduo
     * @date 2016-09-13 10:56:00
     */
    public function save() {
        $this->_client->set($this->_key, serialize($this));
        return $this;
    }

    /**
     * 返回数组
     * @return array
     * @author zhaoduo
     * @date 2016-09-13 10:56:10
     */
    public function toArray() {
        return $this->_data;
    }

    /**
     * 长度计数
     * @return int
     * @author zhaoduo
     * @date 2016-09-13 11:19:33
     */
    public function length() {
        return sizeof($this->_data);
    }

    /**
     * 清空
     * @author zhaoduo
     * @date 2016-09-13 11:44:37
     */
    public function flush() {
        $this->_data = [];
        return $this;
    }

    /**
     * 返回所有数据并清空
     * @author zhaoduo
     * @date 2016-09-13 11:44:44
     */
    public function getFlush() {
        $data = $this->_data;
        $this->flush();
        return $data;
    }

}