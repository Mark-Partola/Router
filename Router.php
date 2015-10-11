<?php

class Router
{
    private $controllerName;
    private $actionName;

    private $patternsURL = [['qwe', 'Controller->Method'], ['@^[0-9]+$', 'c->m']];

    function __construct()
    {
        $url = isset($_GET['url']) ?  rtrim($_GET['url'], '/') : null;

        /**
         * TODO: Должен возвращать контроллер и метод, а не записывать его в поля класса.
         */
        $res = $this->matchURL($url, $this->patternsURL);

        if(!$res) {
            $this->defineControllerAndAction(explode('/', $url));
        }

        echo $this->controllerName;
        echo $this->actionName;
    }

    /**
     * @param array $from Массив с возможными значениями контроллера и действия.
     * Если какого-то элемента нет, определяются значения по умолчанию
     */
    private function defineControllerAndAction($from)
    {
        $this->controllerName = 'Index';
        $this->actionName = 'index';
        if (!empty($from[0])) {
            $this->controllerName = ucfirst($from[0]);
            if (!empty($from[1])) {
                $this->actionName = $from[1];
            }
        }
    }

    /**
     * Проверка совпадений переданной строки с каждым элементом массива шаблонов.
     * Выбирается контроллер и метод при успешном поиске.
     * @param $url String Строка, с которой проверяются совпадения.
     * @param $patterns Array Массив, содержащий шаблоны и соответствующие им контроллеры и методы.
     * @return bool Успех поиска по массиву шаблонов
     */
    private function matchURL($url, array $patterns) {
        $result =  array_map(function($pattern) use($url) {
            if (is_array($pattern)) {
                if ($pattern[0][0] === '@') {
                    $resPattern = ltrim($pattern[0], '@');
                    if (preg_match('#'.$resPattern.'#', $url)) {
                        $parts = explode('->', $pattern[1]);
                        $this->controllerName = $parts[0];
                        $this->actionName = $parts[1];
                        return true;
                    }
                } else if ($pattern[0] === $url) {
                    $parts = explode('->', $pattern[1]);
                    $this->controllerName = $parts[0];
                    $this->actionName = $parts[1];
                    return true;
                }
                return false;
            } else {
                /*if ($pattern === $url) {
                    $this->controllerName
                    $this->actionName
                    return true;
                }*/
                return false;
            }
        }, $patterns);

        if (in_array(true, $result))
            return true;
        else
            return false;
    }
}