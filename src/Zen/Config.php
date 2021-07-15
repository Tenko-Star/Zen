<?php

/**
 * Zen设置类
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Config implements Iterator{
    /**
     * 配置列表
     *
     * @var array
     */
    private $_list = array();

    /**
     * 实例化一个当前配置
     *
     * @access public
     * @param array $config 配置列表
     */
    public function __construct(array $config = array()) {
        /** 初始化参数 */
        $this->init($config);
    }

    /**
     * Get a Zen_Config instance
     *
     * @param array $config
     * @return Zen_Config
     */
    public static function get(array $config) : Zen_Config{
        return new Zen_Config($config);
    }

    /**
     * 初始化的配置
     *
     * @access public
     * @param string | array $config 配置信息
     * @param bool $replace 是否替换已经存在的信息
     * @return void
     */
    public function init($config, bool $replace = false) {
        if (empty($config)) {
            return;
        }

        /** 初始化参数 */
        if (is_string($config)) {
            parse_str($config, $params);
        } else {
            $params = $config;
        }

        /** 重置参数 */
        foreach ($params as $name => $value) {
            if ($replace || !array_key_exists($name, $this->_list)) {
                $this->_list[$name] = $value;
            }
        }
    }

    /**
     * Magic function isset
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) : bool{
        return isset($this->_list[$name]);
    }

    public function __unset($name) {
        unset($this->_list[$name]);
    }

    /**
     * @param bool | string | int $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->_list[$name] = $value;
    }

    /**
     * @param $name
     * @return bool | string | int
     */
    public function __get($name) {
        if(!isset($this->_list[$name])) {
            return '';
        }
        return $this->_list[$name];
    }

    public function setAttr($name, $value) {
        $this->_list[$name] = $value;
    }

    public function getAttr($name) {
        if(!isset($this->_list[$name])) {
            return '';
        }
        return $this->_list[$name];
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->_list);
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->_list);
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return string|float|int|bool|null scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->_list);
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() : bool
    {
        return $this->current() !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->_list);
    }
}