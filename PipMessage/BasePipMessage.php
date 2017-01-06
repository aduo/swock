<?php
/**
 * Created by PhpStorm.
 * User: zhaoduo
 * Date: 2016/12/30
 * Time: 11:07
 */

namespace Swock\PipMessage;


class BasePipMessage implements PipMessageInterface {

    public $worker_id = [];

    public function __construct() {

    }

    public function handle(\swoole_server $server, $from_worker_id) {

    }
}