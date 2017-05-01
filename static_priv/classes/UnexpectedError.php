<?php

class UnexpectedError extends Exception
{
    protected $errno;
    protected $errstr;
    protected $errfile;
    protected $errline;
    protected $allowRead = [
        'errno',
        'errstr',
        'errfile',
        'errline'
    ];

    public function __construct($errno, $errstr, $errfile = null, $errline = null)
    {
        $this->errno = $errno;
        $this->errstr = $errstr;
        $this->errfile = $errfile;
        $this->errline = $errline;
        $this->message = 'Unexpected Error: ' . $errstr;
        if (is_string($errfile) && is_int($errline)) {
            $this->message .= " at $errfile ($errline)";
        }
    }

    public function __get($var)
    {
        if (in_array($var, $this->allowRead, true)) {
            return $this->$var;
        } else {
            throw new Exception('Attempt to read inaccessible property');
        }
    }

    public function __isset($var)
    {
        return in_array($var, $this->allowRead);
    }
}
