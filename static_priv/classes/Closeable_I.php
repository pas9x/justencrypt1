<?php

interface Closeable_I
{
    public function closed();
    public function close();
    public function checkClosed();
}
