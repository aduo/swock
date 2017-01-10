<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2017/1/9
 * Time: 14:57
 */

namespace Swock\Library;


use Swock\Control;

class FireErrors {

    public function __construct() {



    }


    public function register() {

        set_error_handler([$this, 'errorHandler'], E_ALL & E_NOTICE);
        register_shutdown_function([$this, 'shutdownFunction']);
    }

    public function shutdownFunction() {
        $server = Control::getServer();

    }

    public function errorHandler($level, $message, $file, $line, $context) {
        return false;
//        throw new \Exception('123212313');
    }

}