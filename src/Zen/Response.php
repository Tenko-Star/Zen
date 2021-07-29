<?php

/**
 * Class Zen_Response
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Response {
    /**
     * http code
     *
     * @access private
     * @var array
     */
    private static $_http_code = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    /**
     * 字符编码
     *
     * @var string
     */
    private $_charset;

    /**
     * 默认字符编码
     */
    const CHARSET = 'UTF-8';

    /**
     * 实例
     *
     * @var Zen_Response
     */
    private static $_instance = NULL;

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Zen_Response
     */
    public static function getInstance() : Zen_Response {
        return (self::$_instance === NULL) ? (self::$_instance = new Zen_Response()) : self::$_instance;
    }

    /**
     * 解析ajax回执的内部函数
     *
     * @param mixed $message 格式化数据
     * @return string
     */
    private function _parseXml($message): string {
        /** 对于数组型则继续递归 */
        if (is_array($message)) {
            $result = '';

            foreach ($message as $key => $val) {
                $tagName = is_int($key) ? 'item' : $key;
                $result .= '<' . $tagName . '>' . $this->_parseXml($val) . '</' . $tagName . '>';
            }

            return $result;
        } else {
            return preg_match("/^[^<>]+$/is", $message) ? $message : '<![CDATA[' . $message . ']]>';
        }
    }

    /**
     * 设置默认回执编码
     *
     * @param string $charset 字符集
     * @return void
     */
    public function setCharset(string $charset = '') {
        $this->_charset = empty($charset) ? self::CHARSET : $charset;
    }

    /**
     * 获取字符集
     *
     * @return string
     */
    public function getCharset(): string {
        if (empty($this->_charset)) {
            $this->setCharset();
        }

        return $this->_charset;
    }

    /**
     * 在http头部请求中声明类型和字符集
     *
     * @param string $contentType 文档类型
     * @return void
     */
    public function setContentType(string $contentType = 'text/html') {
        header('Content-Type: ' . $contentType . '; charset=' . $this->getCharset(), true);
    }

    /**
     * 设置http头
     *
     * @param string $name 名称
     * @param string $value 对应值
     * @return void
     */
    public function setHeader(string $name, string $value) {
        header($name . ': ' . $value, true);
    }

    /**
     * 设置HTTP状态
     *
     * @param integer $code http代码
     * @return void
     */
    public static function setStatus(int $code) {
        if (isset(self::$_http_code[$code])) {
            header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' ' . $code . ' ' . self::$_http_code[$code], true, $code);
        }
    }

    /**
     * 抛出ajax的回执信息
     *
     * @param string $message 消息体
     * @return void
     */
    public function throwXml(string $message) {
        /** 设置http头信息 */
        $this->setContentType('text/xml');

        /** 构建消息体 */
        echo '<?xml version="1.0" encoding="' . $this->getCharset() . '"?>',
        '<response>',
        $this->_parseXml($message),
        '</response>';

        /** 终止后续输出 */
        exit;
    }

    /**
     * 抛出json回执信息
     *
     * @access public
     * @param mixed $message 消息体
     * @return void
     * @throws Zen_Exception
     */
    public function throwJson($message)
    {
        /** 设置http头信息 */
        $this->setContentType('application/json');

        echo Zen_Json::encode($message);

        /** 终止后续输出 */
        exit;
    }

    /**
     * 重定向函数
     *
     * @param string $location 重定向路径
     * @param boolean $isPermanently 是否为永久重定向
     * @return void
     * @throws Zen_Widget_Exception
     */
    public function redirect(string $location, bool $isPermanently = false) {
        $location = Zen_Security::removeUrlXss($location);

        if ($isPermanently) {
            header('Location: ' . $location, false, 301);
        } else {
            header('Location: ' . $location, false, 302);
        }
        exit;
    }

    /**
     * 返回来路
     *
     * @access public
     * @param string $suffix 附加地址
     * @param string $default 默认来路
     * @throws Zen_Widget_Exception
     */
    public function goBack(string $suffix = '', string $default = '') {
        //获取来源
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        //判断来源
        if (!empty($referer)) {
            if (!empty($suffix)) {
                $parts = parse_url($referer);
                $myParts = parse_url($suffix);

                if (isset($myParts['fragment'])) {
                    $parts['fragment'] = $myParts['fragment'];
                }

                if (isset($myParts['query'])) {
                    $args = array();
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $args);
                    }

                    parse_str($myParts['query'], $currentArgs);
                    $args = array_merge($args, $currentArgs);
                    $parts['query'] = http_build_query($args);
                }

                $referer = Zen_Security::buildUrl($parts);
            }

            $this->redirect($referer);
        } else if (!empty($default)) {
            $this->redirect($default);
        }
        exit;
    }

    /**
     * 批量设置cookie
     * 与原setcookie()用法相同
     *
     * @param array $cookies
     * <p>以key-value的形式将需要设置的cookie传入一个数组中</p>
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public function setCookies
    (   array $cookies,
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false  )
    : bool{

        $isFinish = true;
        foreach ($cookies as $key => $value) {
            if(!setcookie($key, $value, $expire, $path, $domain, $secure, $httponly)){
                $isFinish = false;
            }
        }

        return $isFinish;

    }
}