<?php
namespace Swock\Library;
class Swyac {
    private static $yac_client;
    private static $prifix = '';
    private function __construct() {

    }

    /**
     * 初始化对象
     * @param string $prefix
     * @return \Yac yac对象
     * @author zhaoduo
     * @date 2016-06-05 12:00:23
     */
    public static function yac($prefix = "") {
        $prefix || $prefix = config('yac.prefix');
        return self::$yac_client = new \Yac($prefix);
    }

    /**
     * @param string|array $key key value 设置单个. 传入数组则批量设置
     * @param string $value value. 只能是字符串
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:01:20
     */
    public static function set($key, $value = "") {
        $yac = self::getYac();
        if(is_array($key)) {
            return $yac->set($key);
        }
        return $yac->set($key, $value);
    }

    /**
     * 保存数组格式
     * @param string $key key
     * @param string|array $item 数组键名或者数组
     * @param string $value 数组value
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:15:06
     */
    public static function aSet($key, $item, $value = "") {
        $a_value = self::aGet($key);
        if(is_array($item)) {
            $a_value = $item + $a_value;
        } else {
            $a_value[$item] = $value;
        }
        return self::set($key, json_encode($a_value));
    }

    /**
     * @param string|array $key 获取缓存. 传入string为单个. array为批量
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:02:00
     */
    public static function get($key) {
        $yac = self::getYac();
        return $yac->get($key);
    }

    /**
     * 获取数组或其中某项的值
     * @param string $key
     * @param string $item
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:32:26
     */
    public static function aGet($key, $item = "") {
        $value = json_decode(self::get($key), true);
        if($item != "") {
            return isset($value[$item]) ? $value[$item] : "" ;
        }
        return $value ? $value : [];
    }

    /**
     * @param string $key 删除
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:02:42
     */
    public static function delete($key) {
        $yac = self::getYac();
        return $yac->delete($key);
    }

    /**
     * 删除数组中某键
     * @param $key
     * @param $item
     * @param bool $return
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-08 20:18:38
     */
    public static function aDelete($key, $item, $return = false) {
        $a_value = self::aGet($key);
        $value = $a_value[$item];
        unset($a_value[$item]);
        self::delete($key);
        if($return) {
            self::aSet($key, $a_value);
            return $value;
        }
        return self::aSet($key, $a_value);
    }



    /**
     * 清空
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:02:53
     */
    public static function flush() {
        $yac = self::getYac();
        return $yac->flush();
    }

    /**
     * 获取实例
     * @return mixed
     * @author zhaoduo
     * @date 2016-06-05 12:02:59
     */
    public static function getYac() {
        if(self::$yac_client == "") {
            self::yac(config('yac.prefix'));
        }
        return self::$yac_client;
    }

    public function __clone() {

    }
}