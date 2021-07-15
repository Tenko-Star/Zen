<?php

/**
 * Class Zen_Security
 * 
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Security {
    /**
     * 过滤url中非法字符串
     *
     * @param string $location
     * @return string
     * @throws Zen_Widget_Exception
     */
    public static function removeUrlXss(string $location) : string {
        /** url过滤支持 */
        if(!__ZEN_SECURITY__ && !empty(__EXTRA_SAFETY_URL__)) {
            return Zen_Widget::callWidgetFunction(__EXTRA_SAFETY_URL__, [$location]);
        }

        $url = parse_url(str_replace(array("\r", "\n", "\t", ' '), '', $location));

        /** 禁止非法的协议跳转 */
        if (isset($url['scheme'])) {
            if (!in_array($url['scheme'], array('http', 'https'))) {
                return '/';
            }
        }

        /** 过滤解析串 */
        $url = array_map(function($string) {
            $string = str_replace(array('%0d', '%0a'), '', strip_tags($string));
            return preg_replace(array(
                "/\(\s*(\"|')/i",           //函数开头
                "/(\"|')\s*\)/i",           //函数结尾
            ), '', $string);
        }, $url);
        return self::buildUrl($url);
    }

    /**
     * 从数组合成url
     *
     * @author Typecho Team
     * @link https://github.com/typecho/typecho/blob/master/var/Typecho/Common.php
     * @param array $url_array
     * @return string
     */
    public static function buildUrl(array $url_array): string {
        return (isset($url_array['scheme']) ? $url_array['scheme'] . '://' : NULL)
            . (isset($url_array['user']) ? $url_array['user'] . (isset($url_array['pass']) ? ':' . $url_array['pass'] : NULL) . '@' : NULL)
            . (isset($url_array['host']) ? $url_array['host'] : NULL)
            . (isset($url_array['port']) ? ':' . $url_array['port'] : NULL)
            . (isset($url_array['path']) ? $url_array['path'] : NULL)
            . (isset($url_array['query']) ? '?' . $url_array['query'] : NULL)
            . (isset($url_array['fragment']) ? '#' . $url_array['fragment'] : NULL);
    }

    /**
     * 处理XSS跨站攻击的过滤函数
     *
     * @param string $val 需要处理的字符串
     * @return string
     * @author kallahar@kallahar.com
     * @link http://kallahar.com/smallprojects/php_xss_filter_function.php
     * @access public
     */
    public static function removeXSS(string $val): string {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // &#x0040 @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // &#00064 @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags

                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }

        return $val;
    }

    /**
     * 字符串XSS检查
     *
     * @author kallahar@kallahar.com
     * @link http://kallahar.com/smallprojects/php_xss_filter_function.php
     * @param string $str
     * @return bool
     */
    public static function checkXss(string $str) : bool {
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // &#x0040 @ search for the hex values
            $str = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $str); // with a ;
            // &#00064 @ 0{0,7} matches '0' zero to seven times
            $str = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $str); // with a ;
        }

        return !preg_match('/(\(|\)|\\\|"|<|>|[\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19]|' . "\r|\n|\t" . ')/', $str);
    }

    /**
     * Alpha
     *
     * @access public
     * @param string $str
     * @return boolean
     */
    public static function alpha(string $str): bool {
        return (bool)preg_match("/^([a-z])+$/i", $str);
    }

    /**
     * Alpha-numeric
     *
     * @access public
     * @param string $str
     * @return boolean
     */
    public static function alphaNumeric(string $str): bool {
        return preg_match("/^([a-z0-9])+$/i", $str);
    }

    /**
     * Alpha-numeric with underscores and dashes
     *
     * @access public
     * @param string
     * @return boolean
     */
    public static function alphaDash(string $str): bool {
        return (bool)preg_match("/^([_a-z0-9-])+$/i", $str);
    }

    /**
     * Numeric
     *
     * @access public
     * @param string $str
     * @return boolean
     */
    public static function isFloat(string $str): bool {
        return preg_match("/^[0-9.]+$/", $str);
    }

    /**
     * Is Numeric
     *
     * @access public
     * @param mixed $data
     * @return boolean
     */
    public static function isInteger($data): bool {
        return is_numeric($data);
    }

    /**
     * Commonly used characters in the password
     *
     * @param string $str
     * @return bool
     */
    public static function password(string $str) : bool {
        return (bool)preg_match("/^([a-z0-9@\$!%*#_~?&^.<>(){}+=,:;-])+$/i", $str);
    }

    /**
     * Check E-mail
     *
     * @param string $str
     * @return bool
     */
    public static function email(string $str) : bool {
        return (bool)preg_match("/^([_a-z0-9-])+@([_a-z0-9-.])+([_a-z0-9-])+$/i", $str);
    }

    /**
     * 检查参数
     *
     * @param string $param
     * @return false|int
     */
    public function checkParams(string $param) {
        if(__ZEN_MB_SUPPORT__) {
            return mb_check_encoding($param);
        }else {
            return preg_match('//u', $param);
        }
    }
}