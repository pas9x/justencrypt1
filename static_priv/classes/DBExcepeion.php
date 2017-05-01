<?php

class DBExcepeion extends Exception
{
    public $errorInfo;

    public function __construct($message, $errorInfo=null)
    {
        parent::__construct($message);
        $this->errorInfo = $errorInfo;
    }
}
