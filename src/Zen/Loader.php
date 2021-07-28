<?php

class Zen_Loader {
    /**
     * @var array
     */
    private static $_class_map = array();

    /**
     * Zen_Loader constructor.
     */
    private function __construct() {

    }

    /**
     * 添加类映射
     *
     * @param array $map
     */
    public static function addClassMap(array $map) {
        self::$_class_map = array_merge(self::$_class_map, $map);
    }

    /**
     * 加载核心类
     *
     * @param string $class_name
     * @throws Zen_Loader_Exception
     */
    public static function loadZenCore(string $class_name) {
        if(empty(__ZEN_CORE_PATH__)) {
            throw new Zen_Loader_Exception("Error: Zen Core Path is not found.\n", HTTP_SERVER_ERROR);
        }

        $file = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class_name) . '.php';
        @include_once $file;
    }

    /**
     * 加载额外类
     *
     * @param string $class_name
     */
    public static function loadExtra(string $class_name) {
        if(empty(__ZEN_EXTRA_PATH__)) {
            throw new Zen_Loader_Exception("Error: Extra Path is not found.\n", HTTP_SERVER_ERROR);
        }

        $file = self::$_class_map[$class_name];
        @include_once $file;
    }

    /**
     * 添加额外的加载器
     *
     * @param array $map
     */
    public static function enableAutoload(array $map) {
        foreach ($map as $item) {
            spl_autoload_register($item);
        }
    }

    /**
     * 初始化加载器
     */
    public static function init() {
        spl_autoload_register(array('Zen_Loader', 'loadZenCore'));
        spl_autoload_register(array('Zen_Loader', 'loadExtra'));
    }
}

Zen_Loader::init();