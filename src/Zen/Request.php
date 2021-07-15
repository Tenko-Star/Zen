<?php

/**
 * Class Zen_Request
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Request {
    /**
     * http参数
     *
     * @var array
     */
    private static $_params = array();

    /**
     * 服务端参数缓存
     *
     * @var array
     */
    private $_server = array();

    /**
     * 客户端ip
     *
     * @var string
     */
    private $_ip = '';

    /**
     *UA
     *
     * @var string
     */
    private $_agent = '';

    /**
     * 来源地址
     *
     * @var string
     */
    private $_referer = '';

    /**
     * 请求路径
     *
     * @var string
     */
    private $_path = '';

    /**
     * @var string
     */
    private $_base_url = '';

    /**
     * Request实例
     *
     * @var Zen_Request
     */
    private static $_instance = NULL;

    /**
     * url前缀
     *
     * @var string
     */
    private static $_url_prefix = '';

    /**
     * 请求的uri
     *
     * @var string
     */
    private $_request_uri = '';

    /**
     * Cookie
     *
     * @var string
     */
    private $_cookie = '';

    /**
     * 获取对象实例
     * 不可在入口使用
     *
     * @return Zen_Request
     */
    public static function getInstance() : Zen_Request{
        return self::$_instance == NULL ? (self::$_instance = new Zen_Request()) : self::$_instance;
    }

    /**
     * 设置一个新的对象实例
     * 仅用于入口，组件中禁止使用
     *
     * @return Zen_Request
     */
    public static function  newInstance() : Zen_Request {
        return (self::$_instance = new Zen_Request());
    }

    /**
     * Zen_Request constructor.
     */
    private function __construct() {
        if(empty(self::$_params)) {
            self::$_params = array_filter(array_merge($_GET, $_POST), array($this, 'checkParams'));
        }
    }

    /**
     * 检查数据是否合法
     *
     * @param string|string[] $params
     * @throws Zen_Widget_Exception
     */
    private function checkParams($params) {
        if(is_array($params)) {
            return array_map(array($this, 'checkParams'), $params);
        }

        if(!empty(__EXTRA_CHECK_STR__)) {
            return Zen_Widget::callWidgetFunction(__EXTRA_CHECK_STR__, $params);
        }
        return call_user_func(array('Zen_Security', 'checkParams'), $params);
    }

    /**
     * 检查ip是否合法
     *
     * @param string $ip
     * @return bool
     */
    private function checkIp(string $ip): bool {
        if (__ZEN_FILTER_SUPPORT__) {
            return false !== (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
        }

        return preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip)
            || preg_match("/^[0-9a-f:]+$/i", $ip);
    }

    /**
     * 检查ua是否合法
     *
     * @param string $agent ua字符串
     * @return boolean
     */
    private function checkAgent(string $agent) : bool {
        return preg_match("/^[_a-z0-9- ,:;=#@.()\/+*?]+$/i", $agent);
    }

    /**
     * 判断是否为https
     *
     * @access public
     * @return boolean
     */
    private function checkProtocol() : bool {
        return (
            (!empty($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS']))   ||
            (!empty($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'])     ||
            __ZEN_SECURITY__
        );
    }

    /**
     * 检测是否是移动端
     *
     * @return bool
     */
    public function checkMobile() : bool {
        $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";

        $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";

        $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";

        $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";

        $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320×320|240×320|176×220";

        $regex_match.=")/i";

        return
            $this->getServer('HTTP_X_WAP_PROFILE') ||
            $this->getServer('HTTP_PROFILE') ||
            preg_match($regex_match, strtolower($this->getAgent())) ||
            __ZEN_MOBILE_MODE__;
    }

    /**
     * 获取url前缀
     *
     * @return string
     */
    public function getUrlPrefix(): string {
        if(empty(self::$_url_prefix)) {
            if(!empty(__ZEN_URL_PREFIX__)) {
                self::$_url_prefix = __ZEN_URL_PREFIX__;
            }else {
                self::$_url_prefix = (self::checkProtocol() ? 'https' : 'http') . '://'
                    . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);
            }
        }

        return self::$_url_prefix;
    }

    /**
     * 设置服务端参数
     *
     * @access public
     * @param string $name 参数名称
     * @param mixed $value 参数值
     * @return void
     */
    public function setServer(string $name, $value = NULL) {
        if (NULL == $value) {
            if (isset($_SERVER[$name])) {
                $value = $_SERVER[$name];
            } else if (isset($_ENV[$name])) {
                $value = $_ENV[$name];
            }
        }

        $this->_server[$name] = $value;
    }

    /**
     * 获取环境变量
     *
     * @access public
     * @param string $name 获取环境变量名
     * @return string
     */
    public function getServer(string $name): string {
        if (!isset($this->_server[$name])) {
            $this->setServer($name);
        }

        return $this->_server[$name];
    }

    /**
     * 设置客户端
     *
     * @access public
     * @param string|null $agent 客户端字符串
     * @return void
     */
    public function setAgent(string $agent = '') {
        $agent = ($agent === '') ? $this->getServer('HTTP_USER_AGENT') : $agent;
        $this->_agent = $this->checkAgent($agent) ? $agent : '';
    }

    /**
     * 获取客户端
     *
     * @access public
     * @return string
     */
    public function getAgent(): string {
        if ($this->_agent === '') {
            $this->setAgent();
        }

        return $this->_agent;
    }

    /**
     * 设置ip地址
     *
     * @author Typecho Team
     * @link https://github.com/typecho/typecho/blob/master/var/Typecho/Request.php
     * @param string $ip
     * @return void
     */
    public function setIp(string $ip = '') {
        if(!empty($ip)) {
            $this->_ip = $ip;
        }else {
            switch (true) {
                case NULL !== $this->getServer('REMOTE_ADDR'):
                    $this->_ip = $this->getServer('REMOTE_ADDR');
                    break;
                case NULL !== $this->getServer('HTTP_CLIENT_IP'):
                    $this->_ip = $this->getServer('HTTP_CLIENT_IP');
                    break;
                default:
                    break;
            }
        }

        if (empty($this->_ip) || !$this->checkIp($this->_ip)) {
            $this->_ip = 'unknown';
        }
    }

    /**
     * 获取ip地址
     *
     * @return string
     */
    public function getIp(): string {
        if ($this->_ip = '') {
            $this->setIp();
        }

        return $this->_ip;
    }

    /**
     * 设置来源页
     *
     * @param string $referer 客户端字符串
     * @return void
     */
    public function setReferer(string $referer = '') {
        $this->_referer = ($referer === '') ? $this->getServer('HTTP_REFERER') : $referer;
    }

    /**
     * 获取来源页
     *
     * @return string
     */
    public function getReferer() : string {
        if ($this->_referer === '') {
            $this->setReferer();
        }

        return $this->_referer;
    }

    /**
     * 判断是否为get请求
     *
     * @return boolean
     */
    public function isGet() : bool {
        return 'GET' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * 判断是否为post请求
     *
     * @return boolean
     */
    public function isPost() : bool {
        return 'POST' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * 判断是否为put请求
     *
     * @return boolean
     */
    public function isPut() : bool {
        return 'PUT' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * 判断是否为ajax
     *
     * @return boolean
     */
    public function isAjax(): bool {
        return 'XMLHttpRequest' == $this->getServer('HTTP_X_REQUESTED_WITH');
    }

    /**
     * 获取cookie变量
     *
     * @param string $name
     * @return string
     */
    public function getCookie(string $name) : string {
        if(empty($this->_cookie)) {
            $this->_cookie = array_filter($_COOKIE, array($this, 'checkParams'));
        }

        if(!isset($this->_cookie[$name])) {
            return '';
        }

        return $this->_cookie[$name];
    }

    /**
     * 获取当前请求url
     *
     * @access public
     * @return string
     */
    public function getRequestUrl(): string {
        return $this->getUrlPrefix() . $this->getRequestUri();
    }

    /**
     * 获取请求地址
     *
     * @author Typecho Team
     * @link https://github.com/typecho/typecho/blob/master/var/Typecho/Request.php
     * @return string
     */
    public function getRequestUri(): string {
        if (!empty($this->_request_uri)) {
            return $this->_request_uri;
        }

        //处理requestUri
        $requestUri = '/';

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            isset($_SERVER['IIS_WasUrlRewritten'])
            && $_SERVER['IIS_WasUrlRewritten'] == '1'
            && isset($_SERVER['UNENCODED_URL'])
            && $_SERVER['UNENCODED_URL'] != ''
        ) {
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $parts       = @parse_url($requestUri);

            if (isset($_SERVER['HTTP_HOST']) && strstr($requestUri, $_SERVER['HTTP_HOST'])) {
                if (false !== $parts) {
                    $requestUri  = (empty($parts['path']) ? '' : $parts['path'])
                        . ((empty($parts['query'])) ? '' : '?' . $parts['query']);
                }
            } elseif (!empty($_SERVER['QUERY_STRING']) && empty($parts['query'])) {
                // fix query missing
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        return ($this->_request_uri = $requestUri);
    }

    /**
     * 获取当前pathinfo
     *
     * @author Typecho Team
     * @link https://github.com/typecho/typecho/blob/master/var/Typecho/Request.php
     * @param string $inputEncoding 输入编码
     * @param string $outputEncoding 输出编码
     * @return string
     */
    public function getPathInfo(string $inputEncoding = '', string $outputEncoding = ''): string {
        /** 缓存信息 */
        if ($this->_path !== '') {
            return $this->_path;
        }

        //参考Zend Framework对pahtinfo的处理, 更好的兼容性
        $pathInfo = '';

        //处理requestUri
        $requestUri = $this->getRequestUri();
        $finalBaseUrl = $this->getBaseUrl();

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (($finalBaseUrl !== '')
            && (($pathInfo = substr($requestUri, strlen($finalBaseUrl))) === false))
        {
            // If substr() returns false then PATH_INFO is set to an empty string
            $pathInfo = '/';
        } elseif ($finalBaseUrl === '') {
            $pathInfo = $requestUri;
        }

        if (!empty($pathInfo)) {
            //针对iis的utf8编码做强制转换
            //参考http://docs.moodle.org/ja/%E5%A4%9A%E8%A8%80%E8%AA%9E%E5%AF%BE%E5%BF%9C%EF%BC%9A%E3%82%B5%E3%83%BC%E3%83%90%E3%81%AE%E8%A8%AD%E5%AE%9A
            if (!empty($inputEncoding) && !empty($outputEncoding) &&
                (stripos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false
                    || stripos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false)) {
                if (__ZEN_MB_SUPPORT__) {
                    $pathInfo = mb_convert_encoding($pathInfo, $outputEncoding, $inputEncoding);
                } else if (function_exists('iconv')) {
                    $pathInfo = iconv($inputEncoding, $outputEncoding, $pathInfo);
                }
            }
        } else {
            $pathInfo = '/';
        }

        return ($this->_path = '/' . ltrim(urldecode($pathInfo), '/'));
    }

    /**
     * getBaseUrl
     *
     * @author Typecho Team
     * @link https://github.com/typecho/typecho/blob/master/var/Typecho/Request.php
     * @return string
     */
    public function getBaseUrl(): string {
        if ($this->_base_url !== '') {
            return $this->_base_url;
        }

        //处理baseUrl
        $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

        if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
            $baseUrl = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path    = $_SERVER['PHP_SELF'] ?? '';
            $file    = $_SERVER['SCRIPT_FILENAME'] ?? '';
            $segs    = explode('/', trim($file, '/'));
            $segs    = array_reverse($segs);
            $index   = 0;
            $last    = count($segs);
            $baseUrl = '';
            do {
                $seg     = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while (($last > $index) && (($pos = strpos($path, $baseUrl)) !== false) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $finalBaseUrl = '';
        $requestUri = $this->getRequestUri();

        if (0 === strpos($requestUri, $baseUrl)) {
            // full $baseUrl matches
            $finalBaseUrl = $baseUrl;
        } else if (0 === strpos($requestUri, dirname($baseUrl))) {
            // directory portion of $baseUrl matches
            $finalBaseUrl = rtrim(dirname($baseUrl), '/');
        } else if (!strpos($requestUri, basename($baseUrl))) {
            // no match whatsoever; set it blank
            $finalBaseUrl = '';
        } else if ((strlen($requestUri) >= strlen($baseUrl))
            && ((($pos = strpos($requestUri, $baseUrl)) !== false) && ($pos !== 0)))
        {
            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return ($this->_base_url = ($finalBaseUrl === '') ? rtrim($baseUrl, '/') : $finalBaseUrl);
    }

    /**
     * 获取参数
     *
     * @param string $name
     * @return string
     */
    public function getParam(string $name): string {
        return (isset(self::$_params[$name])) ? self::$_params[$name] : '';
    }
}