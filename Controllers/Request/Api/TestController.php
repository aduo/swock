<?php

namespace Swock\Controllers\Request\Api;


use Swock\Controllers\Request\RequestBaseController;

class TestController extends RequestBaseController {

    public function __construct($server) {
        parent::__construct($server);
    }


    public function index() {
        return json_encode([
            "status" => 1,
            "message" =>  "this is a test api",
            "data" => []
        ]);
    }

}