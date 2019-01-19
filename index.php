<?php

/*******************************/

define('CFG_DEBUG', 1); //是否打开调试模式
define('CFG_DATABASE', 'mysql://root:@127.0.0.1:3306/hx186?prefix=yy_&charset=utf8'); //数据库链接，prefix是表名前缀
define('CFG_DEBUG_SQL', 1); //是否打开SQL调试选项
define('CFG_URL_MODE', 2); //URL模式，0:普通 1:pathinfo+get 2:pathinfo
define('CFG_APP_DIR', __DIR__ . '/app');
define('CFG_RUNTIME_DIR', __DIR__ . '/runtime');
define('CFG_AUTOLOAD_DIR', __DIR__);

/*******************************/

// ini_set( 'short_open_tag', '1' );

define('IS_AJAX', (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], '/json') !== false));

//模拟三元操作符
function iif(&$a, &$b)
{
    if (empty($a)) {
        return $b;
    }

    return $a;
}

//获取多个/单个输入参数
function I($name, $default = null, $maxLength = 0)
{
    if (is_array($name)) {
        $values = [];
        $errors = [];
        foreach ($name as $k => &$v) {
            if (is_array($v)) { //可传多个参数
                list($key, $value, $error) = call_user_func_array('II', $v);
                if ($error) {
                    $errors[] = $error;
                }
                $values[$key] = $value;
            } else {
                list($key, $value, $error) = II($v);
                if ($error) {
                    $errors[] = $error;
                }
                $values[$key] = $value;
            }
        }
        if (!empty($errors)) {
            err($errors);exit;
        }
        return $values;
    } else {
        list($key, $value, $error) = II($name, $default, $maxLength);
        if ($error) {
            err($error);exit;
        }
        return $value;
    }
}

//获取单个输入参数
function II($name, $default = null, $maxLength = 0)
{
    preg_match("/(?:(get|post|request|cookie)\.)?([a-zA-Z0-9_-]+)(?:\/(double|float|d|int|string|date|datetime))?(?:\/([0-9\.-]+))?(?:\/(.+))?/i", $name, $matches);
    if (!empty($matches)) {
        $from = strtolower(empty($matches[1]) ? 'request' : $matches[1]);
        $key = isset($matches[2]) ? $matches[2] : null;
        $type = empty($matches[3]) ? 'string' : strtolower($matches[3]);
        $validate = isset($matches[4]) ? $matches[4] : null;
        $alias = isset($matches[5]) ? $matches[5] : $key;

        if (empty($key)) {
            return [$key, $default, null];
        }

        $value = $default;
        switch ($from) {
            case 'get':
                isset($_GET[$key]) && $value = $_GET[$key];
                break;
            case 'post':
                isset($_POST[$key]) && $value = $_POST[$key];
                break;
            case 'cookie':
                isset($_COOKIE[$key]) && $value = $_COOKIE[$key];
                break;
            default:
                isset($_REQUEST[$key]) && $value = $_REQUEST[$key];
                break;
        }

        $min = -1;
        $max = PHP_INT_MAX;
        if (!empty($validate)) {
            //整数或者小数:2，0.3
            preg_match("/^((?:[1-9]\d*|0)(?:\.\d{1,2})?)(\+)?$/i", $validate, $m2);
            if (!empty($m2)) {
                $min = $m2[1] + 0;
                if (empty($m2[2])) {
                    $max = $min;
                }
            }
            //两个整数或者小数:2-10，0.3-0.4，-0.2-0
            preg_match("/^((?:[1-9]\d*|0)(?:\.\d{1,2})?)-((?:[1-9]\d*|0)(?:\.\d{1,2})?)$/i", $validate, $m2);
            if (!empty($m2)) {
                $min = $m2[1] + 0;
                $max = $m2[2] + 0;
            }
        }

        switch ($type) {
            case 'double':
            case 'float':
            case 'd':
            case 'int':
                $value = is_numeric($value) ? $value + 0 : 0;
                if ($type == 'int') {
                    $value = intval($value);
                }

                if (!empty($validate)) {
                    if ($min == $max && $value != $min) {
                        return [$key, $value, $alias . '必须等于' . $min];
                    }
                    if ($value < $min) {
                        return [$key, $value, $alias . '不能小于' . $min];
                    }
                    if ($value > $max) {
                        return [$key, $value, $alias . '不能大于' . $max];
                    }
                }
                break;
            case 'date':
                $time = strtotime($value);
                if ($time === false) {
                    return [$key, null, null];
                }

                return [$key, date('Y-m-d', $time), null];
                break;
            case 'datetime':
                $time = strtotime($value);
                if ($time === false) {
                    return [$key, null, null];
                }

                return [$key, date('Y-m-d H:i:s', $time), null];
                break;
            default:
                if (empty($validate) && $type != 'string') {
                    preg_match("/^((?:\-?[1-9]\d*|0)(?:\.\d{1,2})?)(\+)?$/i", $type, $m2);
                    if (!empty($m2)) {
                        $validate = $type;
                        $type = 'string';
                        $min = $m2[1] + 0;
                        if (empty($m2[2])) {
                            $max = $min;
                        }

                    }
                    preg_match("/^((?:\-?[1-9]\d*|0)(?:\.\d{1,2})?)-((?:\-?[1-9]\d*|0)(?:\.\d{1,2})?)$/i", $type, $m2);
                    if (!empty($m2)) {
                        $validate = $type;
                        $type = 'string';
                        $min = $m2[1] + 0;
                        $max = $m2[2] + 0;
                    }
                }

                // 将字符串分解为单元，解决中文字符长度问题
                preg_match_all('/./us', $value, $mlen);
                // 返回单元个数
                $len = count($mlen[0]);

                if (!empty($validate)) {
                    if ($min != -1 && $min == $max && $len != $min) {
                        return [$key, $value, $len == 0 ? '请填写' . $alias : ($alias . '必须' . $min . '个字符')];
                    }
                    if ($min != -1 && $len < $min) {
                        return [$key, $value, $len == 0 ? '请填写' . $alias : ($alias . '不能少于' . $min . '个字符')];
                    }
                    if ($max != -1 && $len > $max) {
                        return [$key, $value, $alias . '最多' . $max . '个字符'];
                    }
                }
                if ($maxLength > 0 && !empty($value) && $len > $maxLength) {
                    $value = mbsubstr($value, 0, $maxLength);
                }
                break;
        }
        return [$key, $value, null];
    }
    return [$key, $default, null];
}

//utf8分割字符串
function mbsubstr($str, $start, $len)
{
    $strlen = $start + $len;
    for ($i = 0; $i < $strlen; $i++) {
        if (ord(substr($str, $i, 1)) > 0xa0) {
            $tmpstr .= substr($str, $i, 2);
            $i++;
        } else {
            $tmpstr .= substr($str, $i, 1);
        }
    }
    return $tmpstr;
}

//路由生成函数
function U($uri, $params = [], $mode = null)
{
    if ($mode === null) {
        $mode = CFG_URL_MODE;
    }

    $module = MODULE;
    $controller = CONTROLLER;
    $action = ACTION;

    if (is_string($params)) {
        $tmp = [];
        parse_str($params, $tmp);
        $params = $tmp;
    }

    if (!(empty($uri) || $uri == '.' || $uri == './' || $uri == '?')) {
        list($module, $controller, $action, $_get) = parseUri($uri);
        $params = array_merge($_get, $params);
    } else {
        $params = array_merge($_GET, $params);
    }

    if ($mode == 2) { //例：/news/index/page/3.html
        $get_params = [];
        $url = '/' . strtolower($controller) . '/';
        if ($module != 'home') {
            $url = '/' . $module . $url;
        }
        if (empty($params)) {
            if ($action != 'index') {
                $url .= $action . '.html';
            }
        } else {
            $url .= $action . '/';
            $n = 0;
            foreach ($params as $k => &$v) {
                if ($n > 0) {
                    $url .= '/';
                }
                if (strpos($v, '/') !== false) {
                    $get_params[$k] = $v;
                    continue;
                }

                $url .= $k . '/' . $v;
                $n++;
            }
            $url = $url . '.html';
        }
        return $url . (!empty($get_params) ? '?' . http_build_query($get_params) : '');
    }

    if ($mode == 1) { //例：/news/index.html?page=3
        $url = '/' . strtolower($controller) . '/';
        if ($module != 'home') {
            $url = '/' . $module . $url;
        }
        if ($action != 'index') {
            $url .= $action . '.html';
        }
        return $url . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    //例：/?m=home&c=news&a=index&page=3
    $get['m'] = $module;
    $get['c'] = strtolower($controller);
    $get['a'] = $action;

    $params = array_merge($get, $params);
    return '/?' . http_build_query($params);
}

//解析路由函数
function parseUri($uri, $flat = false)
{
    $pathsLen = 0;
    if (!empty($uri)) {
        if ($uri[0] == '/') {
            $uri = substr($uri, 1);
        }
        $paths = explode("/", $uri);
        $pathsLen = count($paths);
    }

    $module_name = 'home';
    $controller_name = 'index';
    $action_name = 'index';
    $get = [];
    if ($pathsLen >= 3) {
        $module_name = $paths[0];
        $controller_name = $paths[1];
        $action_name = basename($paths[2], '.html');

        $getOffset = 3;
        if (!file_exists(CFG_APP_DIR . '/' . $module_name)) {
            $module_name = 'home';
            $controller_name = $paths[0];
            $action_name = basename($paths[1], '.html');
            $getOffset = 2;
        }
        $name = null;
        for ($i = $getOffset; $i < $pathsLen; ++$i) {
            if ($pathsLen - 1 == $i) {
                $v = basename($paths[$i], '.html');
            } else {
                $v = $paths[$i];
            }
            if (!empty($name)) {
                $get[$name] = $v;
                if ($flat) {
                    $_REQUEST[$name] = $_GET[$name] = $v;
                }
                $name = null;
            } else {
                $name = $v;
            }
        }
        if (!empty($name)) {
            $get['__SLUG__'] = $name;
            if ($flat) {
                $_REQUEST['__SLUG__'] = $_GET['__SLUG__'] = $v;
            }
        }
    } else if ($pathsLen == 2) {
        $module_name = 'home';
        $controller_name = $paths[0];
        $action_name = basename($paths[1], '.html');
    } else if ($pathsLen == 1) {
        $action_name = basename($paths[0], '.html');
    } else if ($flat) {
        if (!empty($_GET['m'])) {
            $module_name = $_GET['m'];
        }
        if (!empty($_GET['c'])) {
            $controller_name = $_GET['c'];
        }
        if (!empty($_GET['a'])) {
            $action_name = $_GET['a'];
        }
    }

    if (CFG_URL_MODE == 0 && $flat) {
        if (!empty($_GET['m'])) {
            $module_name = $_GET['m'];
        }
        if (!empty($_GET['c'])) {
            $controller_name = $_GET['c'];
        }
        if (!empty($_GET['a'])) {
            $action_name = $_GET['a'];
        }
    }

    if (empty($action_name)) {
        $action_name = 'index';
    }

    $module_name = strtolower($module_name);
    $controller_name = strtolower($controller_name);
    $action_name = strtolower($action_name);

    if ($flat) {
        define('MODULE', $module_name);
        define('CONTROLLER', ucfirst($controller_name));
        define('ACTION', $action_name);
    }

    return [
        $module_name,
        $controller_name,
        $action_name,
        $get,
    ];
}

//cookie读写函数
function cookie($key, $value = '', $expire = null, $path = '/', $domain = null)
{
    if ('' === $value) {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
    }
    setcookie($key, $value, $expire, $path, $domain);
}

//抛出致命异常
function fatal($msg, $code = -1)
{
    if (defined('CFG_DEBUG') && CFG_DEBUG) {
        err($msg, $code);
    } else {
        err('请求出错，请重试或返回', $code);
    }
    exit;
}

//抛出普通异常
function err($msg, $code = -1)
{
    global $self;
    if (!empty($self)) {
        $self->_err($msg, $code);
        exit;
    } else {
        if (IS_AJAX) {
            $output = array('status' => $code, 'msg' => $msg);
            header("Content-type: text/json; charset=utf-8");
            exit(json_encode($output, JSON_UNESCAPED_UNICODE));
        } else {
            header("Content-type: text/html; charset=utf-8");
            exit('<pre>错误码：' . $code . '<br />错误信息：<br />' . $msg . '</pre><a href="javascript:history.length>1?history.go(-1):window.close();">返回上一页</a><br /><a href="/">返回首页</a>');
        }
    }
}

//获取当前控制器的视图文件路径
function getViewFile($view)
{
    return CFG_APP_DIR . '/' . MODULE . '/' . VIEW . '/' . $view . '.php';
}

//导入其他模板
function import($_view, $_view_params = null)
{
    global $self;
    extract($self->data);
    !empty($_view_params) && extract($_view_params);
    include getViewFile($_view);
}

function customError($errno, $errstr, $errfile, $errline)
{
    $errno = $errno & error_reporting();
    if ($errno == 0) {
        return;
    }

    if (!defined('E_STRICT')) {
        define('E_STRICT', 2048);
    }

    if (!defined('E_RECOVERABLE_ERROR')) {
        define('E_RECOVERABLE_ERROR', 4096);
    }
    $err = '';
    switch ($errno) {
        case E_ERROR:
            $err .= "Error";
            break;
        case E_WARNING:
            $err .= "Warning";
            break;
        case E_PARSE:
            $err .= "Parse Error";
            break;
        case E_NOTICE:
            $err .= "Notice";
            break;
        case E_CORE_ERROR:
            $err .= "Core Error";
            break;
        case E_CORE_WARNING:
            $err .= "Core Warning";
            break;
        case E_COMPILE_ERROR:
            $err .= "Compile Error";
            break;
        case E_COMPILE_WARNING:
            $err .= "Compile Warning";
            break;
        case E_USER_ERROR:
            $err .= "User Error";
            break;
        case E_USER_WARNING:
            $err .= "User Warning";
            break;
        case E_USER_NOTICE:
            $err .= "User Notice";
            break;
        case E_STRICT:
            $err .= "Strict Notice";
            break;
        case E_RECOVERABLE_ERROR:$err .= "Recoverable Error";
            break;
        default:$err .= "Unknown error ($errno)";
            break;
    }
    $err .= ": $errstr in $errfile on line $errline" . PHP_EOL;
    if (function_exists('debug_backtrace')) {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        foreach ($backtrace as $i => $l) {
            $err .= "[$i] in function " . (isset($l['class']) ? $l['class'] : '') . "" . (isset($l['type']) ? $l['type'] : '') . "{$l['function']}";
            if (isset($l['file']) && $l['file']) {
                $err .= " in {$l['file']}";
            }

            if (isset($l['line']) && $l['line']) {
                $err .= " on line {$l['line']}";
            }

            $err .= PHP_EOL;
        }
    }

    file_put_contents(CFG_RUNTIME_DIR . '/error.log', PHP_EOL . date('m-d H:i:s') . PHP_EOL . $err, FILE_APPEND);

    // global $__view_rendering;
    // if($__view_rendering){
    //     return;
    // }

    if (defined('CFG_DEBUG') && CFG_DEBUG) {
        err($err);
    }
}
set_error_handler("customError");

function __autoload($classname)
{
    $filename = CFG_AUTOLOAD_DIR . '/' . str_replace('\\', '/', strtolower($classname)) . ".php";
    include_once $filename;
}

//控制器基类
abstract class Controller
{
    public $data = [];
    public $output = [
        "status" => 1,
    ];

    public $body_sent = false;
    public $db;

    public function __construct()
    {
        $this->db = new DB();
    }

    public function _initialize()
    {

    }

    protected function assign($name, $value = '')
    {
        $this->data[$name] = $value;
    }

    public function _err($msg = '', $code = -1)
    {
        return $this->err($msg, $code);
    }

    protected function err($msg = '', $code = -1)
    {
        $this->output['msg'] = $msg;
        $this->output['status'] = $code;
        $this->output();
    }

    protected function ok($msg = '')
    {
        $this->output['msg'] = $msg;
        $this->output['status'] = 1;
        $this->output();
    }

    protected function ajaxReturn($output)
    {
        if (empty($output['msg']) && !empty($this->output['msg'])) {
            $output['msg'] = $this->output['msg'];
        }

        header("Content-type: text/json; charset=utf-8");
        exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    }

    public function _output()
    {
        return $this->output();
    }

    protected function output($_view_file = null)
    {
        if (IS_AJAX) {
            $this->outputJson();
        } else {
            $this->outputHtml($_view_file);
        }
    }

    protected function outputJson()
    {
        $this->body_sent = true;
        $this->output['result'] = empty($this->data) ? new StdClass() : $this->data;
        $this->ajaxReturn($this->output);
    }

    protected function outputHtml($_view_file = null)
    {
        global $__view_rendering;
        $this->body_sent = true;
        if ($this->output['status'] != 1) {
            if (file_exists(getViewFile('public/error'))) {
                extract($this->output);
                $__view_rendering = 1;
                include getViewFile('public/error');
                $__view_rendering = 0;
                return;
            }
            exit($this->output['msg'] . "<br /><a href=\"javascript:history.length>1?history.go(-1):window.close();\">返回上一页</a>");
        }

        extract($this->data);

        if ($_view_file == null) {
            $_view_file = strtolower(CONTROLLER) . '/' . ACTION;
        }
        if (!file_exists(getViewFile($_view_file))) {
            if (!empty($this->output['msg'])) {
                if (file_exists(getViewFile('public/success'))) {
                    extract($this->output);
                    $__view_rendering = 1;
                    include getViewFile('public/success');
                    $__view_rendering = 0;
                    return;
                }
            }
            fatal('对应的 view 不存在:' . getViewFile($_view_file), -410);
        }
        $__view_rendering = 1;
        include getViewFile($_view_file);
        $__view_rendering = 0;
    }
}

//控制器衍生类，默认以json格式输出数据
abstract class Api extends Controller
{
    protected function output($_view_file = null)
    {
        $this->outputJson();
    }
}

//控制器衍生类，默认以模板文件渲染数据
abstract class Page extends Controller
{
    protected function output($_view_file = null)
    {
        $this->outputHtml($_view_file);
    }
}

//分页器
/**
 * @author Jason Grimes
 * @source https://github.com/jasongrimes/php-paginator
 */
class Pager
{
    const NUM_PLACEHOLDER = '__PAGE__';
    protected $totalItems;
    protected $numPages;
    protected $itemsPerPage;
    protected $currentPage;
    protected $urlPattern;
    protected $maxPagesToShow = 5;
    protected $previousText = '上一页';
    protected $nextText = '下一页';
    /**
     * @param int $totalItems The total number of items.
     * @param int $itemsPerPage The number of items per page.
     * @param int $currentPage The current page number.
     * @param string $urlPattern A URL for each page, with (:num) as a placeholder for the page number. Ex. '/foo/page/(:num)'
     */
    public function __construct($totalItems, $itemsPerPage, $currentPage, $urlPattern = '')
    {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = $currentPage;
        $this->urlPattern = $urlPattern;
        $this->updateNumPages();
    }
    protected function updateNumPages()
    {
        $this->numPages = ($this->itemsPerPage == 0 ? 0 : (int) ceil($this->totalItems / $this->itemsPerPage));
    }
    /**
     * @param int $maxPagesToShow
     * @throws \InvalidArgumentException if $maxPagesToShow is less than 3.
     */
    public function setMaxPagesToShow($maxPagesToShow)
    {
        if ($maxPagesToShow < 3) {
            throw new \InvalidArgumentException('maxPagesToShow cannot be less than 3.');
        }
        $this->maxPagesToShow = $maxPagesToShow;
    }
    /**
     * @return int
     */
    public function getMaxPagesToShow()
    {
        return $this->maxPagesToShow;
    }
    /**
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }
    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
    /**
     * @param int $itemsPerPage
     */
    public function setItemsPerPage($itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
        $this->updateNumPages();
    }
    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }
    /**
     * @param int $totalItems
     */
    public function setTotalItems($totalItems)
    {
        $this->totalItems = $totalItems;
        $this->updateNumPages();
    }
    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
    /**
     * @return int
     */
    public function getNumPages()
    {
        return $this->numPages;
    }
    /**
     * @param string $urlPattern
     */
    public function setUrlPattern($urlPattern)
    {
        $this->urlPattern = $urlPattern;
    }
    /**
     * @return string
     */
    public function getUrlPattern()
    {
        return $this->urlPattern;
    }
    /**
     * @param int $pageNum
     * @return string
     */
    public function getPageUrl($pageNum)
    {
        return str_replace(self::NUM_PLACEHOLDER, $pageNum, $this->urlPattern);
    }
    public function getNextPage()
    {
        if ($this->currentPage < $this->numPages) {
            return $this->currentPage + 1;
        }
        return null;
    }
    public function getPrevPage()
    {
        if ($this->currentPage > 1) {
            return $this->currentPage - 1;
        }
        return null;
    }
    public function getNextUrl()
    {
        if (!$this->getNextPage()) {
            return null;
        }
        return $this->getPageUrl($this->getNextPage());
    }
    /**
     * @return string|null
     */
    public function getPrevUrl()
    {
        if (!$this->getPrevPage()) {
            return null;
        }
        return $this->getPageUrl($this->getPrevPage());
    }
    /**
     * Get an array of paginated page data.
     *
     * Example:
     * array(
     *     array ('num' => 1,     'url' => '/example/page/1',  'isCurrent' => false),
     *     array ('num' => '...', 'url' => NULL,               'isCurrent' => false),
     *     array ('num' => 3,     'url' => '/example/page/3',  'isCurrent' => false),
     *     array ('num' => 4,     'url' => '/example/page/4',  'isCurrent' => true ),
     *     array ('num' => 5,     'url' => '/example/page/5',  'isCurrent' => false),
     *     array ('num' => '...', 'url' => NULL,               'isCurrent' => false),
     *     array ('num' => 10,    'url' => '/example/page/10', 'isCurrent' => false),
     * )
     *
     * @return array
     */
    public function getPages()
    {
        $pages = array();
        if ($this->numPages <= 1) {
            return array();
        }
        if ($this->numPages <= $this->maxPagesToShow) {
            for ($i = 1; $i <= $this->numPages; $i++) {
                $pages[] = $this->createPage($i, $i == $this->currentPage);
            }
        } else {
            // Determine the sliding range, centered around the current page.
            $numAdjacents = (int) floor(($this->maxPagesToShow - 3) / 2);
            if ($this->currentPage + $numAdjacents > $this->numPages) {
                $slidingStart = $this->numPages - $this->maxPagesToShow + 2;
            } else {
                $slidingStart = $this->currentPage - $numAdjacents;
            }
            if ($slidingStart < 2) {
                $slidingStart = 2;
            }

            $slidingEnd = $slidingStart + $this->maxPagesToShow - 3;
            if ($slidingEnd >= $this->numPages) {
                $slidingEnd = $this->numPages - 1;
            }

            // Build the list of pages.
            $pages[] = $this->createPage(1, $this->currentPage == 1);
            if ($slidingStart > 2) {
                $pages[] = $this->createPageEllipsis();
            }
            for ($i = $slidingStart; $i <= $slidingEnd; $i++) {
                $pages[] = $this->createPage($i, $i == $this->currentPage);
            }
            if ($slidingEnd < $this->numPages - 1) {
                $pages[] = $this->createPageEllipsis();
            }
            $pages[] = $this->createPage($this->numPages, $this->currentPage == $this->numPages);
        }
        return $pages;
    }
    /**
     * Create a page data structure.
     *
     * @param int $pageNum
     * @param bool $isCurrent
     * @return Array
     */
    protected function createPage($pageNum, $isCurrent = false)
    {
        return array(
            'num' => $pageNum,
            'url' => $this->getPageUrl($pageNum),
            'isCurrent' => $isCurrent,
        );
    }
    /**
     * @return array
     */
    protected function createPageEllipsis()
    {
        return array(
            'num' => '...',
            'url' => null,
            'isCurrent' => false,
        );
    }
    /**
     * Render an HTML pagination control.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->numPages <= 1) {
            return '';
        }
        $html = '<ul class="pager">';
        if ($this->getPrevUrl()) {
            $html .= '<li><a href="' . $this->getPrevUrl() . '">&laquo; ' . $this->previousText . '</a></li>';
        }
        foreach ($this->getPages() as $page) {
            if ($page['url']) {
                $html .= '<li' . ($page['isCurrent'] ? ' class="active"' : '') . '><a href="' . $page['url'] . '">' . $page['num'] . '</a></li>';
            } else {
                $html .= '<li class="disabled"><span>' . $page['num'] . '</span></li>';
            }
        }
        if ($this->getNextUrl()) {
            $html .= '<li><a href="' . $this->getNextUrl() . '">' . $this->nextText . ' &raquo;</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }
    public function __toString()
    {
        return $this->toHtml();
    }
    public function getCurrentPageFirstItem()
    {
        $first = ($this->currentPage - 1) * $this->itemsPerPage + 1;
        if ($first > $this->totalItems) {
            return null;
        }
        return $first;
    }
    public function getCurrentPageLastItem()
    {
        $first = $this->getCurrentPageFirstItem();
        if ($first === null) {
            return null;
        }
        $last = $first + $this->itemsPerPage - 1;
        if ($last > $this->totalItems) {
            return $this->totalItems;
        }
        return $last;
    }
    public function setPreviousText($text)
    {
        $this->previousText = $text;
        return $this;
    }
    public function setNextText($text)
    {
        $this->nextText = $text;
        return $this;
    }
}

//数据库操作类
class DB
{
    private $config;
    private $sth;
    private $dbh;

    public $lastInsertId;
    public $affectRowCount = 0;
    public $lastSQL = '';
    public $lastError = '';

    public function DB($config = null)
    {
        if (!$config) {
            if (empty(CFG_DATABASE)) {
                fatal('找不到数据库配置');
            }
            $database = CFG_DATABASE;
            if (is_array($database)) {
                $this->config = $database;
            } else if (is_string($database)) {
                $urlInfo = parse_url($database);
                $urlQuery = [];
                if (isset($urlInfo['query'])) {
                    parse_str($urlInfo['query'], $urlQuery);
                }

                $this->config = [
                    'type' => $urlInfo["scheme"],
                    'host' => $urlInfo["host"],
                    'port' => $urlInfo["port"],
                    'dbname' => substr($urlInfo["path"], 1),
                    'username' => $urlInfo["user"],
                    'password' => $urlInfo["pass"],
                    'prefix' => isset($urlQuery['prefix']) ? $urlQuery['prefix'] : '',
                    'charset' => isset($urlQuery['charset']) ? $urlQuery['charset'] : 'utf8',
                ];
            } else {
                fatal('数据库配置不正确');
            }
        } else {
            $this->config = $config;
        }
    }

    private function connect()
    {
        if (!$this->dbh) {
            $config = $this->config;

            if ($config['type'] == 'sqlite') {
                $dsn = "sqlite:{$config['file']}";
            } else if ($config['type'] == 'pgsql') {
                $dsn = "pgsql:dbname={$config['dbname']};host={$config['host']};port={$config['port']}";
            } else {
                $dsn = "mysql:dbname={$config['dbname']};host={$config['host']};port={$config['port']};charset={$this->config['charset']}";
            }

            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->config['charset'],
            );
            try {
                $this->dbh = new \PDO($dsn, $this->config['username'], $this->config['password'], $options);
                $this->dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); //在数据库prepare
                $this->dbh->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING); //null转为空字符串
                $this->dbh->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            } catch (\PDOException $e) {
                fatal("数据库连接失败" . $e->getMessage());
            }
        }
    }

    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function inTransaction()
    {
        return $this->dbh->inTransaction();
    }

    public function rollBack()
    {
        return $this->dbh->rollBack();
    }

    public function commit()
    {
        return $this->dbh->commit();
    }

    //查询单行数据
    public function find($sql, $parameters = [])
    {
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        $ret = $this->sth->fetch(\PDO::FETCH_ASSOC);
        if ($ret === false) {
            return null;
        }

        return $ret;
    }

    //查询多行数据，不分页
    public function findAll($sql, $parameters = [])
    {
        $this->connect();
        $result = [];
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        while ($result[] = $this->sth->fetch(\PDO::FETCH_ASSOC)) {}
        array_pop($result);
        return $result;
    }

    //按分页查询多行数据，并自动生成分页器对象
    public function findAndPage($sql, $parameters = [], $pageSize = 0)
    {
        global $self;

        if (is_integer($parameters)) {
            $pageSize = $parameters;
            $parameters = [];
        }
        $page = I('request.page/int');
        $result = $this->findAndCountAll($sql, $parameters, $page, $pageSize);

        if (IS_AJAX || $self instanceof Api) {
            //AJAX REQUEST
        } else {
            $pager = new \Pager($result['rowCount'], $result['pageSize'], $result['page'], U('', ['page' => '__PAGE__']));
            $result['pager'] = $pager;
        }
        return $result;
    }

    //按分页查询多行数据，并计算总数据条数
    public function findAndCountAll($sql, $parameters = [], $page = 0, $pageSize = 0)
    {
        if (is_integer($parameters)) {
            $pageSize = $page;
            $page = $parameters;
            $parameters = [];
        }
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);

        $rowCount = $this->sth->rowCount();
        $pageCount = 1;

        $limit = '';
        if ($pageSize > 0) {
            $page = intval($page);
            if ($page < 1) {
                $page = 1;
            }
            $pageCount = ceil($rowCount / $pageSize);
            if ($page > $pageCount) {
                $page = $pageCount;
            }

            $offset = ($page - 1) * $pageSize;

            if (count($parameters) > 0 && array_key_exists(0, $parameters)) {
                $sql = $sql . ' LIMIT ? OFFSET ?';
                $parameters[] = $pageSize;
                $parameters[] = $offset;
            } else {
                $sql = $sql . ' LIMIT :__limit OFFSET :__offset';
                $parameters['__limit'] = $pageSize;
                $parameters['__offset'] = $offset;
            }

            $this->sth = $this->prepare($sql);
            $this->sth->execute($parameters);
        }

        $result = [];
        $n = 0;
        while ($result[] = $this->sth->fetch(\PDO::FETCH_ASSOC)) {
            //最大行数是1万
            if ($n++ > 10000) {
                break;
            }
        }
        array_pop($result);
        return [
            'rowCount' => $rowCount,
            'pageCount' => $pageCount,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $result,
        ];
    }

    //判断数据是否存在
    public function exists($sql, $parameters = [])
    {
        $this->lastSQL = $sql;
        $data = $this->fetch($sql, $parameters);
        return !empty($data);
    }

    //执行SQL查询，返回影响的行数
    public function query($sql, $parameters = [])
    {
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        $rowCount = $this->sth->rowCount();
        if ($rowCount) {
            $this->affectRowCount = $rowCount;
        }

        return $rowCount;
    }

    //执行SQL查询，返回原始数据
    public function fetch($sql, $parameters = [], $type = \PDO::FETCH_ASSOC)
    {
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        return $this->sth->fetch($type);
    }

    //查询单行数据的单个字段，之间返回该字段值
    public function findField($sql, $parameters = [], $position = 0)
    {
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        $ret = $this->sth->fetch(\PDO::FETCH_COLUMN, $position);
        if ($ret === false) {
            return null;
        }

        return $ret;
    }

    public function fetchAllColumn($sql, $parameters = [], $position = 0)
    {
        $this->connect();
        $result = [];
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        while ($result[] = $this->sth->fetch(\PDO::FETCH_COLUMN, $position)) {}
        array_pop($result);
        return $result;
    }

    //删除
    public function delete($table, $condition = [])
    {
        $sql = "DELETE FROM $table";
        $pdo_parameters = [];

        $where = '';
        if (is_string($condition)) {
            $where = $condition;
        } else if (is_array($condition)) {
            $fields = [];
            foreach ($condition as $field => $value) {
                $fields[] = '`' . $field . '`=:condition_' . $field;
                $pdo_parameters['condition_' . $field] = $value;
            }
            $where = implode(' AND ', $fields);
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        return $this->query($sql, $pdo_parameters);
    }

    //更新
    public function update($table, $parameters = [], $condition = [])
    {
        $sql = "UPDATE $table SET ";
        $fields = [];
        $pdo_parameters = [];
        foreach ($parameters as $field => $value) {
            $fields[] = '`' . $field . '`=:field_' . $field;
            $pdo_parameters['field_' . $field] = $value;
        }
        $sql .= implode(',', $fields);
        $where = '';
        if (is_string($condition)) {
            $where = $condition;
        } else if (is_array($condition)) {
            $fields = [];
            foreach ($condition as $field => $value) {
                $fields[] = '`' . $field . '`=:condition_' . $field;
                $pdo_parameters['condition_' . $field] = $value;
            }
            $where = implode(' AND ', $fields);
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        $this->connect();
        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($pdo_parameters);
        $rowCount = $this->sth->rowCount();
        if ($rowCount) {
            $this->affectRowCount = $rowCount;
        }

        return $rowCount;
    }

    //插入
    public function insert($table, $parameters = [])
    {
        $this->connect();
        $sql = "INSERT INTO $table";
        $fields = [];
        $placeholder = [];
        foreach ($parameters as $field => $value) {
            $placeholder[] = ':' . $field;
            $fields[] = '`' . $field . '`';
        }
        $sql .= '(' . implode(",", $fields) . ') VALUES (' . implode(",", $placeholder) . ')';

        $this->lastSQL = $sql;
        $this->sth = $this->prepare($sql);
        $this->sth->execute($parameters);
        $this->lastInsertId = $this->dbh->lastInsertId();
        $rowCount = $this->sth->rowCount();
        if ($rowCount) {
            $this->affectRowCount = $rowCount;
        }

        return $rowCount;
    }

    //根据$condition是否为空来判断执行插入还是更新操作
    public function save($table, $parameters = [], $condition = [])
    {
        if (!empty($condition)) {
            return $this->update($table, $parameters, $condition);
        } else {
            return $this->insert($table, $parameters);
        }
    }

    //根据锁提供数据中主键的值是否为空来判断执行插入还是更新操作
    public function replace($table, $parameters = [], $primary_key)
    {
        $condition = empty($parameters[$primary_key]) ? null : [$primary_key => $parameters[$primary_key]];
        unset($parameters[$primary_key]);
        return $this->save($table, $parameters, $condition);
    }

    private function prepare($sql)
    {
        $sql = str_replace('__PREFIX__', $this->config['prefix'], $sql);
        $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/", function ($matches) {
            return '`' . $this->config['prefix'] . strtolower($matches[1]) . '`';
        }, $sql);
        //print($sql."\n");
        $sth = $this->dbh->prepare($sql);
        if (!$sth) {
            $this->lastError = "DB_ERROR:" . $this->dbh->errorInfo()[2] . '(' . $this->dbh->errorCode() . ')';
            if (defined('CFG_DEBUG_SQL') && CFG_DEBUG_SQL) {
                $this->lastError .= ' SQL:' . $sql;
            }
            err($this->lastError);
        }
        return $sth;
    }

    //上一次查询返回的数据库异常信息
    public function errorInfo()
    {
        return $this->sth->errorInfo();
    }

    //上一次查询返回的数据库异常错误码
    public function errorCode()
    {
        return $this->sth->errorCode();
    }
}

/******************程序主入口**************************/

if (!is_writable(CFG_RUNTIME_DIR)) {
    if (!mkdir(CFG_RUNTIME_DIR, 644, true)) {
        exit('runtime目录不可写');
    }
}

parseUri(!empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), true);

if (!file_exists(CFG_APP_DIR . '/' . MODULE)) {
    fatal('模块：' . MODULE . ' 不存在', -401);
    exit;
}

if (file_exists(CFG_APP_DIR . '/' . MODULE . '/config.php')) {
    include_once CFG_APP_DIR . '/' . MODULE . '/config.php';
}

if (!defined('VIEW')) {
    define('VIEW', 'view');
}

$controller_class = '\\app\\' . MODULE . '\\controller\\' . CONTROLLER;
$controller_file = 'app/' . strtolower(MODULE) . '/controller/' . strtolower(CONTROLLER) . ".php";
if (!file_exists(__DIR__ . '/' . $controller_file)) {
    header("HTTP/1.1 404 Not Found");
    fatal("控制器文件：{$controller_file} 不存在", -402);
    exit;
}

if (!class_exists($controller_class)) {
    header("HTTP/1.1 404 Not Found");
    fatal("控制器类：{$controller_class} 不存在", -403);
    exit;
}

if (strpos(ACTION, '_') === 0) {
    header("HTTP/1.1 404 Not Found");
    fatal('非法动作：' . CONTROLLER . '.' . ACTION, -405);
    exit;
}

$self = new $controller_class();

$_method = ACTION;

if (!method_exists($self, ACTION)) {
    if (method_exists($self, '_404')) {
        $_method = '_404';
    } else {
        header("HTTP/1.1 404 Not Found");
        fatal('动作：' . CONTROLLER . '.' . ACTION . ' 不存在', -404);
        exit;
    }
}

$self->_initialize();
$__view_rendering = 0;
$self->$_method();
if (!$self->body_sent) {
    $self->_output();
}
