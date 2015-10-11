<?php namespace Router\Parser;

class XMLParser
{
    private $patterns;

    public function __construct($file)
    {
        $xml = simplexml_load_file($file);

        $result = [];
        $element = [];
        foreach($xml->route as $route) {
            $element['method'] = (string) $route->method;
            $element['pattern'] = (string) $route->pattern;
            $element['controller'] = (string) $route->controller;
            $element['action'] = (string) $route->action;
            array_push($result, $element);
        }

        $this->patterns = $result;
    }

    public function getPatterns()
    {
        return $this->patterns;
    }
}