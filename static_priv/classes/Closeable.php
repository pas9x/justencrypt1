<?php

abstract class Closeable implements Closeable_I
{
    private $state = false;

    public function closed()
    {
        return $this->state;
    }

    public function close()
    {
        $this->state = true;
    }

    public function checkClosed()
    {
        if ($this->closed()) {
            $className = get_class($this);
            throw new AlreadyClosedException("This $className object already closed. You cannot use it anymore.");
        }
    }
}
