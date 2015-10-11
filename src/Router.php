<?php namespace Router;
/**
 * Class Router
 * @package Router
 */

class Router
{
    private $controllerName;
    private $actionName;

    private $params;

    private $patterns;

    private $suffix = 'Controller';

    /**
     * @param array $patterns Массив с шаблонами маршрутов
     * @param string $query Строка в которой искать совпадения
     */
    function __construct(array $patterns, $query)
    {
        $this->patterns = $patterns;

        $fullURL = isset($query) ?  rtrim($query, '/') : null;
        $partsURL = $fullURL ? explode('/', $fullURL): [];

        $res = $this->matchURL($fullURL, $this->patterns);

        /**
         * TODO: при наличии зарегистрированного роута происходит повторная обработка при прямом доступе контроллер/экшн
         */
        if (!$res) {
            $this->defineControllerAndAction($partsURL);
        }

        if (!empty($partsURL)) {
            $this->defineActionParams($partsURL);
        }
    }

    /**
     * @param array $from Массив с возможными значениями контроллера и действия.
     * Если какого-то элемента нет, определяются значения по умолчанию
     */
    private function defineControllerAndAction(array &$from)
    {
        $this->controllerName = 'Index' . $this->suffix;
        $this->actionName = 'index';
        if (!empty($from)) {
            $this->controllerName = ucfirst(strtolower(array_shift($from))) . $this->suffix;
            if (!empty($from)) {
                $this->actionName = strtolower(array_shift($from));
            }
        }
    }

    /**
     * Определяет параметры для действия
     * @param $params array Параметры для действия
     */
    private function defineActionParams(array &$params)
    {
        $this->params = $params;
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
                    $this->controllerName = $pattern['controller'] . $this->suffix;
                    $this->actionName = $pattern['action'];
                    return true;
                }
            } else if ($pattern['pattern'] === $url) {
                $this->controllerName = $pattern['controller'] . $this->suffix;
                $this->actionName = $pattern['action'];
                return true;
            }
        }
        return false;
    }

    /**
     * Вернет результат работы класса.
     * @return array Контроллер и Метод
     */
    public function getMatches()
    {
        return [
            'controller' => $this->controllerName,
            'action' => $this->actionName,
            'params' => $this->params
        ];
    }
}