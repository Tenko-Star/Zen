<?php
/**
 * 助手类 可以用于获取额外引入的第三方类,或获取Widget类，暂不支持只有函数的第三方php代码。
 */

/* 自动加载第三方类 */
spl_autoload_register(function($class_name) {
    $file = __ZEN_EXTRA_PATH__ . DIRECTORY_SEPARATOR . str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class_name) . '.php';
    if(file_exists($file)) {
        @include_once $file;
    }
});

/**
 * Class Zen_Widget_Helper
 * @author tenko_
 * @version 1.0
 * @package ZEN_CORE
 */
class Zen_Widget_Helper {
    /**
     * 函数栈
     *
     * @var array
     */
    private $_functions = array();

    /**
     * 对象实例
     *
     * @var object
     */
    private $_instance;

    /**
     * 对象类名
     *
     * @var string
     */
    private $_name;

    /**
     * 反射对象
     *
     * @var object
     */
    private $_refection = NULL;

    /**
     * Zen_Widget_Helper constructor.
     * @param string $class
     * @param array $args
     * @throws Zen_Widget_Exception
     */
    private function __construct(string $class, array $args) {
        $this->_name = $class;

        try{
            $this->_refection = new ReflectionClass($class);
            if(empty($args)) {
                $this->_instance = $this->_refection->newInstance();
            }else {
                $this->_instance = $this->_refection->newInstanceArgs($args);
            }

            if(__ZEN_WIDGET_RET_OBJ__ === false) {
                $functions = $this->_refection->getMethods();

                foreach ($functions as $function) {
                    $this->_functions[] = $function->getName();
                }
            }
        }catch (ReflectionException $ref) {
            throw new Zen_Widget_Exception($ref->getMessage());
        }

    }

    /**
     * 工厂方法
     *
     * @param string $widget_name
     * @param array $args
     * @return object
     * @throws Zen_Widget_Exception
     */
    public static function factory(string $widget_name, array $args = array()) {
        if(strpos($widget_name, 'Widget_') === 0) {
            $widget = new $widget_name();
            $widget->init();
            return $widget;
        }

        $widget = new Zen_Widget_Helper($widget_name, $args);

        if(__ZEN_WIDGET_RET_OBJ__) {
            return $widget->getInstance();
        }else {
            return $widget;
        }
    }

    /**
     * 获取对象实例
     *
     * @return object
     */
    public function getInstance() {
        return $this->_instance;
    }

    /**
     * 获取函数列表
     *
     * @return array
     */
    public function getMethods() : array { return $this->_functions; }

    /**
     * 函数调用
     *
     * @param string $function
     * @param array $args
     * @return false|mixed
     */
    public function __call(string $function, array $args) {
        if(!in_array($function, $this->_functions)) { return false; }

        return call_user_func_array(array($this->_instance, $function), $args);
    }

    /**
     * 获取对象类名
     *
     * @return string
     */
    public function getClassName() : string { return $this->_name; }
}