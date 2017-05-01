<?php

namespace app;

interface ExporterI
{
    public static function getName();
    public static function getTitle();
    public static function optionsFromUserForm();
    public static function exampleFinalCommand();
}