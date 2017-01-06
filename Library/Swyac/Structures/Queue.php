<?php
namespace Swock\Library\Swyac\Structures;
class Queue extends Structure {
    private $_length = 10;
    public function __construct($yac, $key, $length = 10, array $data = []) {
        parent::__construct($yac, $key, $data);
        $this->_length = $length;
        //重写data. 保证数据为数字键值
        $this->_data = array_values($data);
    }

    /**
     * 压入一条数据
     * @param $item
     * @return $this
     * @author zhaoduo
     * @date 2016-09-13 10:47:48
     */
    public function push($item) {
        parent::push($item);
        $sub_length = sizeof($this->_data) - $this->_length;
        if($sub_length > 0) {
            $this->_data = array_slice($this->_data, $sub_length);
        }
        return $this;
    }

}