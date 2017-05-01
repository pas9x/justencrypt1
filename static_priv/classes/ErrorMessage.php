<?php

class ErrorMessage extends Exception {
    protected $messages;

    public function __construct($messages) {
        if (is_scalar($messages)) {
            $messages = [strval($messages)];
        } elseif (!is_array($messages)) {
            throw new Exception('Invalid type of $messages argument: ' . $messages);
        }
        $this->messages = $messages;
        $this->message = implode(";\n", $this->messages);
    }

    public function getMessages() {
        return $this->messages;
    }
}
