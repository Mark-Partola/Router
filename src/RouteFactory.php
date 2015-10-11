<?php namespace Router;

class RouteFactory
{
    private $dir;
    private $ext;

    public function __construct($dir, $ext = null)
    {
        $this->dir = $dir;
        if (is_null($ext)) {
            $this->defineExtension($dir);
        } else {
            $this->ext = $ext;
        }
    }

    public function getParser()
    {
        switch($this->ext) {
            case 'xml': {
                return new Parser\XMLParser($this->dir.'routes.xml');
            }
            default: {
                throw new RouteException("Wrong extension of file with routes.");
            }
        }
    }

    private function defineExtension($dir)
    {
        $matches = glob($dir . 'routes.*');
        if (!$matches) {
            throw new RouteException("File with routes not found.");
        }
        $this->ext = explode('.', $matches[0])[1];
    }
}