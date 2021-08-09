<?php
/**
 * 助手类 可以用于获取额外引入的第三方类,或获取Widget类，暂不支持只有函数的第三方php代码。
 */

/**
 * Class Zen_Widget_Helper
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
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
            throw new Zen_Widget_Exception($ref->getMessage(), 500);
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
     * 执行动作（停用）
     *
     * @param string $widget_name
     * @return bool <p> 如果执行成功返回true，出现错误返回false </p>
     */
    public static function action(string $widget_name): bool {
//        $argc = func_num_args();
//        $args = array();
//        if($argc >= 2) {
//            $args = func_get_args();
//        }
//
//        /** @var Zen_Action_Widget $widget */
//        $widget = self::factory($widget_name);
//        if(!($widget instanceof Zen_Action_Widget)) {
            return false;
//        }
//        $widget->init();
//        $widget->execute(Zen_Request::getInstance(), Zen_Response::getInstance(), $args);
//        return true;
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