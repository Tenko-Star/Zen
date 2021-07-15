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
    public static function init(array $route_table) { self::setRouteTable($route_table); }

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
                self::setAllWidget($path, ($prefix === '' ? 'Widget_' . $php_file . '_' : $prefix . $php_file . '_'));
            }
            if(preg_match('/.*\.php$/i', $php_file)) {
                self::$_widgets[] = ($prefix === '' ? 'Widget_' : $prefix) . substr($php_file, 0, strpos($php_file, '.php'));
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
                    if(preg_match(
                        '/@map\([\'\"](\/[A-Za-z0-9\/\-%]*)(:[A-Za-z0-9:\/]*)?[\'\"]\)/',
                        $method->getDocComment(),
                        $matches)) {
                        self::$_map[$matches[1]]['class'] = $widget;
                        self::$_map[$matches[1]]['method'] = $method->name;

                        if(isset($matches[2])) {
                            $args = preg_split('/\//', $matches[2], -1, PREG_SPLIT_NO_EMPTY);
                            $args = array_map(function ($str) {
                                return substr($str, 1);
                            }, $args);
                            self::$_map[$matches[1]]['args'] = $args;
                        }
                    }
                }
            }
        } catch (ReflectionException $ref) {
            throw new Zen_Route_Exception($ref->getMessage(), HTTP_SERVER_ERROR);
        }
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
}