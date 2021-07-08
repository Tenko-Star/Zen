<?php

class Widget_init extends Zen_Action_Widget {
    /**
     * 初始化函数
     */
    public function init() {
        Zen_DB::init(__ZEN_DATABASE__);
        Zen_Plugin::enable('Demo');
    }
}