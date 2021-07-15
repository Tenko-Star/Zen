<?php

/**
 * Abstract Class Zen_Page_Widget
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
abstract class Zen_Page_Widget extends Zen_Widget {

    private $_base_path = __PAGE_PATH__ . DIRECTORY_SEPARATOR;

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
                $this->_base_path = __PAGE_PATH__ . $path. DIRECTORY_SEPARATOR;
            }else {
                $this->_base_path = __PAGE_PATH__ . DIRECTORY_SEPARATOR . $path. DIRECTORY_SEPARATOR ;
            }
        }
    }

    /**
     * 输出html
     *
     * @param string $file
     * @throws Zen_Widget_Exception
     */
    public function html(string $file) {
        $real_file = $this->_base_path . DIRECTORY_SEPARATOR . $file;
        if(file_exists($real_file)){
            @include_once $real_file;
        }else {
            throw new Zen_Widget_Exception("No such file.", HTTP_NOT_FOUND);
        }
    }
}