<?php
namespace Swock\Library\Swyac\Structures;
class Sarray extends Structure {
    public function __construct($client, $key, $data = []) {
        parent::__construct($client, $key, $data);
    }

    public function set($key, $val) {
        $this->_data[$key] = $val;
        return $this;
    }

    public function setBatch($data, $replace = true) {
        if($replace) {
            $this->_data = $data;
        } else {
            $this->_data = array_merge($this->_data, $data);
        }
        return $this;
    }

    public function get($key) {
        if(isset($this->_data[$key]))
            $item = $this->_data[$key];
        else
            $item = null;
        return $item;
    }

    public function delete($key) {
        unset($this->_data[$key]);
        return $this;
    }

    public function getDelete($key) {
        $item = $this->_data[$key];
        $this->delete($key);
        return $item;
    }
}