<?php namespace Router;

class RouteFactory
{
    private $ext;

    public function __construct($dir, $ext = null)
    {
        if (is_null($ext)) {
            $this->defineExtension($dir);
        } else {
            $this->ext = $ext;
        }

        switch($this->ext) {
            case 'xml': {
                new Parser\XMLParser();
                break;
            }
        }
    }

    private function defineExtension($dir)
    {
        $matches = glob($dir.'routes.*');
        if (!$matches) {
            throw new RouteException("File with routes not found");
        }
        $this->ext = explode('.', $matches[0])[1];
    }
}