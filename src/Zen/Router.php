<?php

/**
 * Class Zen_Router
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Router {
    /**
     * 所有的组件名
     *
     * @var array
     */
    private static $_widgets = array();

    /**
     * 路由信息
     *
     * @var array
     */
    private static $_map = array();

    /**
     * 忽略的文件
     *
     * @var array
     */
    private static $_ignore = array();

    /**
     * 开始路由
     *
     * @return void
     * @throws Zen_Route_Exception
     */
    public static function route() {
        $request = Zen_Request::getInstance();
        $path_info = $request->getRequestUri();

        if(strpos($path_info, __ZEN_INDEX__) === 0) {
            $path_info = substr($path_info , strlen(__ZEN_INDEX__));
        }

        if(empty(self::$_map)) { self::setRouteTable(); }

        //匹配路由规则
        if($path_info === '/') {
            $match = '/';
        }else {
            $matches = array();
            foreach (self::$_map as $key => $value) {
                if(strpos($path_info, $key) === 0 && $key !== '/') {
                    $matches[] = $key;
                }
            }
            if(empty($matches)) { //未匹配到或只匹配到了根目录
                throw new Zen_Route_Exception('', HTTP_NOT_FOUND);
            }
            $match = max($matches);
        }

        $args = array();
        if(strlen($arg = substr($path_info, strlen($match) - 1)) > 1) {
            $args = preg_split('/\//', $arg, -1, PREG_SPLIT_NO_EMPTY);
        }

        //call function
        if(!self::checkMethod($match)) { //验证请求方法
            throw new Zen_Route_Exception('', METHOD_NOT_ALLOW);
        }
        $cnt = isset(self::$_map[$match]['args']) ? count(self::$_map[$match]['args']) : 0;
        $function_args = array();
        for($i = 0; $i < $cnt; $i++) {
            $key = self::$_map[$match]['args'][$i];
            $function_args[$key] = $args[$i];
        }


        $class_name = self::$_map[$match]['class'];
        $instance = new $class_name();
        $instance->init();
        $call = array(
            $instance,
            self::$_map[$match]['method']
        );
        call_user_func_array($call, $function_args);
    }

    /**
     * 初始化路由表
     *
     * @param array $route_table
     */
    public static function init(array $route_table, array $route_ignore = array(), bool $refresh = false) {
        self::setRouteTable($route_table);

    }

    /**
     * 获取所有的组件信息
     *
     * @return array
     */
    public static function getAllWidget() : array {
        if(empty(self::$_widgets)) { self::setAllWidget(); }

        return self::$_widgets;
    }

    /**
     * 设置所有组件信息
     */
    private static function setAllWidget(string $dir = '', string $prefix = '') {
        if(empty($dir)) {
            $path = __ZEN_WIDGET_PATH__;
        }else {
            $path = $dir;
        }

        $php_files = scandir($path);

        foreach ($php_files as $php_file) {
            $full_path = $path . DIRECTORY_SEPARATOR . $php_file;
            if(is_dir($full_path) && $php_file !== '.' && $php_file !== '..') {
                $path = $path . DIRECTORY_SEPARATOR . $php_file;
                self::setAllWidget($path, (empty($prefix) ? 'Widget_' . $php_file . '_' : $prefix . $php_file . '_'));
            }
            if(preg_match('/.*\.php$/i', $php_file)) {
                self::$_widgets[] = ($prefix === '' ? 'Widget_' : $prefix) . substr($php_file, 0, strpos($php_file, '.php'));
            }
        }

        if(!empty(self::$_ignore)) {
            foreach (self::$_widgets as $key => $value) {
                if(in_array($value, self::$_ignore)) {
                    unset(self::$_widgets[$key]);
                }
            }
        }
    }

    /**
     * 设置路由表信息
     *
     * @param array $table
     * @throws Zen_Route_Exception
     */
    public static function setRouteTable(array $table = array()) {
        if(!empty($table)) {
            self::$_map = $table;
        }

        if(empty(self::$_widgets)) {
            self::setAllWidget();
        }

        try {
            foreach (self::$_widgets as $widget) {
                $class = new ReflectionClass($widget);
                $methods = $class->getMethods();
                $matches = array();
                foreach ($methods as $method) {
                    $doc = $method->getDocComment();

                    if(preg_match(
                        '/@map\s[\'\"](\/[A-Za-z0-9\/\-%]*)(:[A-Za-z0-9:\/]*)?[\'\"]/',
                        $doc,       // 获取到的注解
                        $matches)) {
                        $path = $matches[1];
                        self::$_map[$path]['class'] = $widget;
                        self::$_map[$path]['method'] = $method->name;

                        if(isset($matches[2])) {
                            $args = preg_split('/\//', $matches[2], -1, PREG_SPLIT_NO_EMPTY);
                            $args = array_map(function ($str) {
                                return substr($str, 1);
                            }, $args);
                            self::$_map[$matches[1]]['args'] = $args;
                        }

                        if(preg_match(
                            '/@method\s(GET|POST|PUT|DELETE|HEAD|CONNECT|OPTIONS|TRACE|PATCH)/',
                            $method->getDocComment(),
                            $matches)) {
                            self::$_map[$path]['type'] = $matches[1];
                        }else {
                            self::$_map[$path]['type'] = '';
                        }
                    }
                }
            }
        } catch (ReflectionException $ref) {
            throw new Zen_Route_Exception($ref->getMessage(), HTTP_SERVER_ERROR);
        }
    }

    /**
     * 设置搜索被忽略的文件
     *
     * @param array $route_ignore
     * @param bool $refresh
     */
    public static function setRouteIgnore(array $route_ignore, bool $refresh) {
        if($refresh) {
            foreach ($route_ignore as $key => $value) {
                if(($pos = strpos($value, '.php')) !== false) {
                    $route_ignore[$key] = 'Widget_' . substr(str_replace(array('\\', '/'), '_', $value), 0, $pos);
                }
            }
        }
        self::$_ignore = $route_ignore;

    }

    /**
     * 获取忽略文件表
     *
     * @return array
     */
    public static function getRouteIgnore(): array {
        return self::$_ignore;
    }

    /**
     * 获取路由表信息
     *
     * @return array
     * @throws Zen_Route_Exception
     */
    public static function getRouteTable() : array {
        if(empty(self::$_map)) { self::setRouteTable(); }

        return self::$_map;
    }

    /**
     * 检查请求方法
     *
     * @param string $path
     * @return bool
     * <p>
     * 当这个路径的请求方法没有设置时，默认允许访问这个方法，只有在设置的方法和实际请求的方法不同时才拒绝访问。
     * </p>
     */
    public static function checkMethod(string $path) {
        if(isset(self::$_map[$path]) && empty(self::$_map[$path]['type'])) {    //存在但是没有设定时默认允许执行
            return true;
        }

        if(self::$_map[$path]['type'] === $_SERVER['REQUEST_METHOD']) {
            return true;
        }

        return false;
    }
}