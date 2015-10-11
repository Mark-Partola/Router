<?php

class RouteFactory
{
    private $ext;

    public function __construct($ext = null)
    {
        if (is_null($ext)) {
            $this->defineExtension('/var/www/MVC/libs/Router/');
        } else {
            $this->ext = $ext;
        }

        echo $this->ext;
    }

    private function defineExtension($dir)
    {
        $matches = glob($dir.'routes.*');
        $this->ext = explode('.', $matches[0])[1];
    }
}