<?php namespace Router;
/**
 * Class Router
 * @package Router
 */

class Router
{
    private $controllerName;
    private $actionName;

    private $patterns;

    /**
     * @param $patterns
     * @param $query
     */
    function __construct($patterns, $query)
    {
        $this->patterns = $patterns;

        $url = isset($query) ?  rtrim($query, '/') : '/';

        $res = $this->matchURL($url, $this->patterns);

        if(!$res) {
            $this->defineControllerAndAction(explode('/', $url));
        }

        echo $this->controllerName;
    }

    /**
     * @param array $from Массив с возможными значениями контроллера и действия.
     * Если какого-то элемента нет, определяются значения по умолчанию
     */
    private function defineControllerAndAction($from)
    {
        $this->controllerName = 'IndexController';
        $this->actionName = 'index';
        if (!empty($from[0])) {
            $this->controllerName = ucfirst(strtolower($from[0])).'Controller';
            if (!empty($from[1])) {
                $this->actionName = strtolower($from[1]);
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
        foreach($patterns as $pattern) {
            if ($pattern['pattern']{0} === '#' &&
                $pattern['pattern'][ strlen($pattern['pattern'] ) - 1] === '#') {
                if (preg_match($pattern['pattern'], $url)) {
                    $this->controllerName = $pattern['controller'].'Controller';
                    $this->actionName = $pattern['action'];
                    return true;
                }
            } else if ($pattern['pattern'] === $url) {
                $this->controllerName = $pattern['controller'].'Controller';
                $this->actionName = $pattern['action'];
                return true;
            }
        }
        return false;
    }

    public function getMatches()
    {
        return [
            'controller' => $this->controllerName,
            'action' => $this->actionName
        ];
    }
}