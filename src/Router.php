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
     * 1. Проверка запроса по регистрированным маршрутам.
     * 2. Определение контроллера и действия, если предыдущий пункт ничего не нашел.
     * 3. Определение аргументов действия.
     * @param array $patterns Массив с шаблонами маршрутов
     * @param string $query Строка в которой искать совпадения
     */
    function __construct(array $patterns, $query)
    {
        $this->patterns = $patterns;

        $fullURL = isset($query) ?  rtrim($query, '/') : null;

        $res = $this->matchURL($fullURL, $this->patterns);

        $partsURL = $fullURL ? explode('/', $fullURL): [];

        if (!$res) {
            $this->defineControllerAndAction($partsURL);
        }

        if (!empty($partsURL)) {
            $this->defineActionParams($partsURL);
        }
    }

    /**
     * Опреление контроллера и его действия. Поиск происходит по шаблону контроллер/действие
     * По умолчанию контроллер равен IndexController, метод - index()
     * Если связка была регистрирована - тогда объявлять ничего не нужно,
     * сделано это для защиты от множественного доступа к одному ресурсу (СЕО)
     * @param array $from Массив с возможными значениями контроллера и действия.
     * Если какого-то элемента нет, определяются значения по умолчанию
     */
    private function defineControllerAndAction(array &$from)
    {
        $this->controllerName = 'Index' . $this->suffix;
        $this->actionName = 'index';

        if ($this->checkRegisteredRoute($from)) {
            return;
        }

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
     * Два варианта поиска по шаблону - регулярное выражение и непосредственный маршрут.
     * Поиск по регулярному выражению должен быть обрамлен символом '#' с двух сторон
     * Чтобы дальнейший поиск параметров в строке не продолжился - обнуляем $url.
     * @param $url String Строка, с которой проверяются совпадения.
     * @param $patterns Array Массив, содержащий шаблоны и соответствующие им контроллеры и методы.
     * @return bool Успех поиска по массиву шаблонов
     */
    private function matchURL(&$url, array $patterns) {
        $found = false;
        if (is_null($url)) $url = '/';
        $partsURL = explode('/', $url);

        foreach($patterns as $pattern) {

            $segments = explode('/', $pattern['pattern']);

            if (count($segments) !== count($partsURL))
                continue;

            for ($i = 0; $i < count($segments); $i++) {

                if ($this->checkSegment($segments[$i], $partsURL[$i])) {
                    $found = true;
                    continue;
                } else {
                    $found = false;
                    break;
                }

            }

            if($found) {
                $this->controllerName = $pattern['controller'] . $this->suffix;
                $this->actionName = $pattern['action'];
                $url = null;
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет маршрут контроллер/действие на его объявленность.
     * @param $route array Части маршрута
     * @return bool Вернет true - если найден в зарегистрированных, иначе - false.
     */
    private function checkRegisteredRoute(array $route)
    {
        $controller = ucfirst(array_shift($route));
        $action = array_shift($route);
        foreach ($this->patterns as $pattern) {
            if($controller === $pattern['controller'] && $action === $pattern['action']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка соответствия сегмента шаблона сегменту строки запроса.
     * Сегмент шаблона может содержать регулярное выражение или простую строку.
     * Шаблон должен быть окружен символами '#'.
     * Если оба сегмента пустые - вернется true.
     * @param $regSegment string Сегмент шаблона регистрированного маршрута (строка или регулярное выражение)
     * @param $querySegment string Сегмент строки где ищется совпадение
     * @return bool Если найдено соответствие возвращатеся true, иначе false
     */
    private function checkSegment($regSegment, $querySegment)
    {
        if(empty($regSegment) && empty($querySegment)) {
            return true;
        }

        $regExp = false;
        if (!empty($regSegment) &&
            $regSegment{0} === '#' &&
            $regSegment{ strlen($regSegment) - 1 } === '#') {
            $regExp = true;
        }

        if ($regExp) {
            if (preg_match($regSegment, $querySegment)) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($regSegment === $querySegment) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Вернет результат работы класса.
     * @return array Контроллер, Метод и его параметры
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