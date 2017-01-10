<?php

namespace Swock\Library;


use Swock\Control;

class Session {

    public function __construct() {

    }

    public function init() {
        $server = Control::getServer();
        $this->request = $server->request;
        var_dump($this->request);
    }


}