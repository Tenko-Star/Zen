<?php

/**
 * 组件类抽象类
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
abstract class Zen_Widget {
    /**
     * 默认版本号
     *
     * @var float
     */
    const DEFAULT_VERSION = '1.0';

    /**
     * 组件初始化
     */
    public function init() { }

    /**
     * 获取组件对象
     *
     * @param string $widget_name
     * @param array $args
     * @return object
     * @throws Zen_Widget_Exception
     */
    protected function widget(string $widget_name, array $args = array()) {
        return Zen_Widget_Helper::factory($widget_name, $args);
    }

    /**
     * 获取插件类对象 设置api函数
     *
     * @param string $handle
     * @return Zen_Plugin
     */
    protected function api(string $handle = '') : Zen_Plugin {
        return empty($handle) ? Zen_Plugin::factory(get_class($this)) : Zen_Plugin::factory($handle);
    }

    /**
     * 获取安全组件（停用）
     *
     * @return Zen_Security
     */
//    protected function security(): Zen_Security {
//        return new Zen_Security();
//    }

    /**
     * 返回版本号
     *
     * @return string
     */
    public static function version(): string {
        return self::DEFAULT_VERSION;
    }

    /**
     * 调用第三方组件函数
     *
     * @param string $callback
     *      format: "[class]@[function]"(without square brackets)
     * @param array $func_args
     * @param bool $direct_arg
     * @return false|mixed
     * @throws Zen_Widget_Exception
     */
    public static function callWidgetFunction(string $callback, array $func_args = array(), bool $direct_arg = false) {
        if(empty($callback)) { return false; }

        //直接传递一个完整数组而不被拆分
        $args = ($direct_arg) ? array($func_args) : $func_args;

        $function_array = preg_split('/@/', $callback);
        if(count($function_array) !== 2) {
            return false;
        }else {
            $name = $function_array[0];
            $func = $function_array[1];

            return call_user_func_array(array(Zen_Widget_Helper::factory($name), $func), $args);
        }
    }
}