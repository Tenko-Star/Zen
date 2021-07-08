<?php

class Zen_Plugin {
    /**
     * 所有插件
     *
     * @var array
     */
    private static $_plugins = array();

    /**
     * 绑定的api函数
     *
     * @var array
     */
    private static $_api = array();

    /**
     * 当前插件句柄
     * 存放widget的名称
     *
     * @var string
     */
    private $_handle;

    /**
     * 实例化插件对象
     *
     * @var array
     */
    private static $_instance = array();

    /**
     * Zen_Plugin constructor.
     * @param $handle
     */
    private function __construct($handle) {
        $this->_handle = $handle;
    }

    /**
     * 工厂方法 生成插件类对象
     *
     * @param string $handle
     * @return Zen_Plugin
     */
    public static function factory(string $handle) : Zen_Plugin {
        return self::$_instance[$handle] ?? (self::$_instance[$handle] = new Zen_Plugin($handle));
    }

    /**
     * 初始化函数
     *
     * @param array $api
     */
    public static function init(array $api = array()) { self::$_api = $api; }

    /**
     * 检查插件系统是否初始化
     *
     * @return bool
     */
    public static function isInit() : bool { return !empty(self::$_api); }

    /**
     * 调用api
     *
     * @param string $name
     * @param array $args
     * @return mixed|null
     * @throws Zen_Plugin_Exception
     */
    public function __call(string $name, array $args) {
        /* 检查插件是否注册 */
        $func_name = $this->_handle . '@' . $name;
        if(!isset(self::$_api[$func_name])) { return NULL; }

        /* 检查插件文件是否存在 */
        $php_path = __ZEN_PLUGIN_PATH__ . DIRECTORY_SEPARATOR . self::$_api[$func_name][0] . DIRECTORY_SEPARATOR . 'Plugin.php';
        if(!file_exists($php_path)) {
            throw new Zen_Plugin_Exception("Plugin file not found. In: {$php_path}. \n");
        }
        @include_once $php_path;

        return @call_user_func_array(self::$_api[$func_name], $args);
    }

    /**
     * 刷新插件列表
     */
    private static function refresh() {
        $file_list = scandir(__ZEN_PLUGIN_PATH__, SCANDIR_SORT_NONE);
        $plugin_name = array();

        /* 处理目录文件信息 */
        foreach ($file_list as $file) {
            //跳过特殊文件夹
            if(($file === '.' || $file === '..')) {
                continue;
            }

            $file_path = __ZEN_PLUGIN_PATH__ . DIRECTORY_SEPARATOR . $file;
            $config_path = __ZEN_PLUGIN_PATH__ . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'config.xml';
            $php_path = __ZEN_PLUGIN_PATH__ . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'Plugin.php';

            //跳过文件
            if(!is_dir($file_path)) { continue; }

            if(file_exists($config_path) && file_exists($php_path)) {
                $plugin_name[] = $file;
            }
        }

        self::$_plugins = array();
        self::$_plugins = $plugin_name;
    }

    /**
     * 获取插件列表
     *
     * @return array
     */
    public static function getPluginList() : array{
        if(empty(self::$_plugins)) {
            self::refresh();
        }

        return self::$_plugins;
    }

    /**
     * 获取绑定的函数列表
     *
     * @return array
     */
    public static function getApiList() : array { return self::$_api; }

    /**
     * 启用插件
     *
     * @param string $plugin
     * @throws Zen_Plugin_Exception
     */
    public static function enable(string $plugin) {
        if(empty(self::$_plugins)) {
            self::refresh();
        }

        if(!in_array($plugin, self::$_plugins)) {
            throw new Zen_Plugin_Exception("Error: This plugin is not available.\n");
        }

        $info = self::getPluginInfo($plugin);
        if(!self::checkInfo($info)) {
            throw new Zen_Plugin_Exception("Error: Incomplete plugin info.\n");
        }

        /* 处理依赖 */
        if(isset($info['depends'])){
            $depends = $info['depends'];
            foreach ($depends as $depend) {
                $version = floatval($depend['version']);
                $widget = $depend['widgetName'];

                if(floatval(call_user_func(array($widget, 'version'))) > $version) {
                    throw new Zen_Plugin_Exception("Error: Widget version is too low. Widget name: {$widget}, plugin name: {$plugin}.\n");
                }
            }
        }


        /* 处理函数绑定 */
        $functions = $info['functions'];
        /**
         * 约定$_api[$api_func]中的第一个一定为类名，第二个为函数名
         */
        foreach ($functions as $func) {
            $api_func = $func['widgetName'] . '@' . $func['api']; //合成函数句柄

            if(array_key_exists($api_func, self::$_api)) {
                /* 如果存在冲突则抛出异常 */
                $conflict_class = self::$_api[$api_func][0];
                throw new Zen_Plugin_Exception("Error: Plugin conflict. Plugin name: {$conflict_class}.\n");
            }

            self::$_api[$api_func][0] = $plugin;
            self::$_api[$api_func][1] = $func['function'];

        }
    }

    /**
     * 禁用插件
     *
     * @param string $plugin
     * @throws Zen_Plugin_Exception
     */
    public static function disable(string $plugin) {
        if(empty(self::$_plugins)) {
            self::refresh();
        }

        if(!in_array($plugin, self::$_plugins)) {
            throw new Zen_Plugin_Exception("Error: This plugin is not available.\n");
        }

        $info = self::getPluginInfo($plugin);
        if(!self::checkInfo($info)) {
            throw new Zen_Plugin_Exception("Error: Incomplete plugin info.\n");
        }
        $functions = $info['functions'];

        /* 删除api映射 */
        foreach ($functions as $func) {
            $api_func = $func['widgetName'] . '@' . $func['api']; //合成函数句柄
            if(array_key_exists($api_func, self::$_api)) {
                if(self::$_api[$api_func][0] === $plugin) {
                    unset(self::$_api[$api_func]);
                }
            }
        }
    }

    /**
     * 获取插件信息
     *
     * @param $plugin
     * @return array
     * @throws Zen_Plugin_Exception
     */
    public static function getPluginInfo($plugin) : array {
        $config_file = __ZEN_PLUGIN_PATH__ . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . 'config.xml';
        $doc = new DOMDocument();
        $info = array();


        if(empty(self::$_plugins)) {
            self::refresh();
        }

        if(!in_array($plugin, self::$_plugins)) {
            throw new Zen_Plugin_Exception("This plugin is not available. Class name: {$plugin}.\n");
        }

        /* 加载配置文件 */
        $doc->load($config_file);

        /* 处理xml文件 */
        $info['className'] = @$doc->getElementsByTagName('className')->item(0)->nodeValue;
        $info['displayName'] = @$doc->getElementsByTagName('displayName')->item(0)->nodeValue;
        $info['version'] = @$doc->getElementsByTagName('version')->item(0)->nodeValue;
        $info['description'] = @$doc->getElementsByTagName('description')->item(0)->nodeValue;
        $depends = @$doc->getElementsByTagName('depend');
        $functions = @$doc->getElementsByTagName('function');

        /* 处理依赖 */
        foreach ($depends as $widget) {
            $widgetName = 'Widget_' . @$widget->getElementsByTagName('widgetName')->item(0)->nodeValue;
            $dependVersion = @$widget->getElementsByTagName('dependVersion')->item(0)->nodeValue;
            $info['depends'][] = array(
                'widgetName' => $widgetName,
                'version' => $dependVersion
            );
        }

        /* 处理api绑定 */
        foreach ($functions as $function) {
            $widgetName = 'Widget_' . @$function->getElementsByTagName('widgetName')->item(0)->nodeValue;
            $api = @$function->getElementsByTagName('api')->item(0)->nodeValue;
            $func = @$function->getElementsByTagName('funcName')->item(0)->nodeValue;
            $info['functions'][] = array(
                'widgetName' => $widgetName,
                'api' => $api,
                'function' => $func
            );
        }

        return $info;
    }

    /**
     * 检查插件信息是否正确
     *
     * @param array $plugin_info
     * @return bool => 正确返回true 错误返回false
     */
    protected static function checkInfo(array &$plugin_info) : bool{
        if(empty($plugin_info)) { return false; }

        if(empty($plugin_info['className'])) { return false; }

        if(empty($plugin_info['displayName'])) { $plugin_info['displayName'] = $plugin_info['className']; }

        if(empty($plugin_info['version'])) { return false; }

        if(!isset($plugin_info['depends'])) {
            foreach ($plugin_info['depends'] as $depend) {
                if($depend['widgetName'] === 'Widget_' || empty($depend['version'])) { return false; }
            }
        }

        if(!isset($plugin_info['functions'])) { return false; }
        if(isset($plugin_info['functions'])) {
            foreach ($plugin_info['functions'] as $function) {
                if(
                    empty($function['function']) ||
                    empty($function['widgetName']) ||
                    empty($function['api'])
                ){ return false; }
            }
        }

        return true;
    }
}