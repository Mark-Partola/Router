<?php namespace Router;

/**
 * Class Router
 * @package Router
 */
class Router
{
    private $config = [];

    private $controllerName;

    private $actionName;

    private $params = [];

    private $patterns;

    private $fullURL;

    private $next;

    /**
     * 1. Проверка запроса по регистрированным маршрутам.
     * 2. Определение контроллера и действия, если предыдущий пункт ничего не нашел.
     * 3. Определение аргументов действия.
     * @param array $config Конфигурация маршрутизатора
     * @param string $query Строка в которой искать совпадения
     * @param array $patterns Массив с шаблонами маршрутов
     */
    function __construct(array $config, $query, array $patterns = [])
    {
        $this->setConfig($config);
        $this->fullURL = isset($query) ?  explode('/', rtrim($query, '/')) : [];
        $this->patterns = $patterns;

        $this->matchURL($this->fullURL, $this->patterns)
                ->defineControllerAndAction($this->fullURL)
                    ->defineActionParams($this->fullURL);
    }

    /**
     * Определяет конфигурацию или дефолтные настройки.
     * @param array $config конфигурация роутера
     */
    private function setConfig(array $config)
    {
        $this->config['defaultController'] =
            isset($config['defaultController'])
                ? ucfirst($config['defaultController'])
                : 'Index';
        $this->config['defaultAction'] =
            isset($config['defaultAction'])
                ? $config['defaultAction']
                : 'index';
        $this->config['defaultSuffix'] =
            isset($config['defaultSuffix'])
                ? ucfirst($config['defaultSuffix'])
                : 'Controller';
        $this->config['allowRegister'] =
            isset($config['allowRegister'])
                ? $config['allowRegister']
                : true;
        $this->config['allowAutoDetect'] =
            isset($config['allowAutoDetect'])
                ? $config['allowAutoDetect']
                : false;
    }

    /**
     * Опреление контроллера и его действия. Поиск происходит по шаблону контроллер/действие
     * По умолчанию контроллер равен IndexController, метод - index()
     * Если связка была регистрирована - тогда объявлять ничего не нужно,
     * сделано это для защиты от множественного доступа к одному ресурсу (СЕО)
     * @param array $from Массив с возможными значениями контроллера и действия.
     * Если какого-то элемента нет, определяются значения по умолчанию
     * @return $this
     */
    private function defineControllerAndAction(array &$from)
    {
        if (!$this->next) return $this;

        if (!$this->config['allowAutoDetect']) return $this;

        $this->controllerName = $this->config['defaultController'] . $this->config['defaultSuffix'];
        $this->actionName = $this->config['defaultAction'];

        if ($this->config['allowRegister']) {
            if ($this->checkRegisteredRoute($from)) {
                return $this;
            }
        }

        if (!empty($from[0])) {
            $this->controllerName = ucfirst(strtolower(array_shift($from))) . $this->config['defaultSuffix'];
            if (!empty($from)) {
                $this->actionName = strtolower(array_shift($from));
            }
        } else {
            unset($from[0]);
            unset($from[1]);
        }

        return $this;
    }

    /**
     * Добавляет параметры для действия из массива
     * @param $params array Параметры для действия
     */
    private function defineActionParams(array $params)
    {
        foreach ($params as $param) {
            array_push($this->params, $param);
        }
    }

    /**
     * Проверка совпадений переданной строки с каждым элементом массива шаблонов.
     * Выбирается контроллер и метод при успешном поиске.
     * Два варианта поиска по шаблону - регулярное выражение и непосредственный маршрут.
     * Поиск по регулярному выражению должен быть обрамлен символом '#' с двух сторон
     * Чтобы дальнейший поиск параметров в строке не продолжился - обнуляем $this->fullURL.
     * Для продолжения цепочки возвращает $this. И определяет флаг $this->next.
     * @param $partsURL array Строка, с которой проверяются совпадения.
     * @param $patterns Array Массив, содержащий шаблоны и соответствующие им контроллеры и методы.
     * @return $this Продолжение цепочки. При успешном выполнении $this->next = false
     */
    private function matchURL(array $partsURL, array $patterns)
    {
        if (!$this->config['allowRegister']) {
            $this->next = true;
            return $this;
        }

        $found = false;

        foreach($patterns as $pattern) {

            $segments = explode('/', $pattern['pattern']);

            if ($_SERVER['REQUEST_METHOD'] !== $pattern['method']) {
                continue;
            }

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
                $this->controllerName = $pattern['controller'] . $this->config['defaultSuffix'];
                $this->actionName = $pattern['action'];
                $this->fullURL = [];
                $this->next = false;
                return $this;
            }
        }

        $this->next = true;
        return $this;
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
                $this->defineActionParams([$querySegment]);
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