<?php

namespace Swock\Controllers\Request;
use Swock\Controllers\BaseController;

class RequestBaseController extends BaseController{
    public function __construct($server) {
        parent::__construct($server);
    }


    public function sendBack($status = 1, $message = "", $data = []) {
        return json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

}