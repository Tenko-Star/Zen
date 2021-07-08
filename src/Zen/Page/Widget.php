<?php

abstract class Zen_Page_Widget extends Zen_Widget {

    private $_base_path = __PAGE_PATH__;

    /**
     * 组件初始化
     */
    public function init() { }

    /**
     * 设置默认路径
     *
     * @param string $path
     */
    public function setBasePath(string $path) {
        if(strpos($path, __PAGE_PATH__) === 0) {
            $this->_base_path = $path;
        }else {
            if(strpos('/', $path) === 0) {
                $this->_base_path = __PAGE_PATH__ . $path;
            }else {
                $this->_base_path = __PAGE_PATH__ . DIRECTORY_SEPARATOR . $path;
            }
        }
    }

    /**
     * 输出html
     *
     * @param string $file
     */
    public function html(string $file) {
        @include_once $this->_base_path . DIRECTORY_SEPARATOR . $file;
    }
}