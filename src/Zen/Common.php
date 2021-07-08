<?php

/**
 * 通用组件
 * 含有一些基本函数
 */
class Zen_Common extends Zen_Widget{
    /**
     * 检查参数
     *
     * @param string $param
     * @return false|int
     */
    public function checkParams(string $param) {
        if(__ZEN_MB_SUPPORT__) {
            return mb_check_encoding($param);
        }else {
            return preg_match('//u', $param);
        }
    }
}