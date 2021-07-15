<?php

//异常常量
const   HTTP_NOT_FOUND      =       404;
const   HTTP_SERVER_ERROR   =       500;

/**
 * 异常类基类
 */
class Zen_Exception extends Exception{

    public function __construct($message, $code = 0)
    {
        parent::__construct();
        $this->message = $message . "\n";
        $this->code = $code;
    }
}