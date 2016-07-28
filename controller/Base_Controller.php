<?php
namespace controller;

use lib\Request;
use lib\Response;

class Base_Controller {
    protected $request;
    protected $response;
    public function __construct($method) {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }
}