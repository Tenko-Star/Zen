<?php

abstract class Zen_Action_Widget extends Zen_Widget {
    /**
     * 初始化函数
     */
    public function init() { }

    /**
     * 执行组件功能
     *
     * @param Zen_Response $response
     * @param Zen_Request $request
     * @param mixed $args
     */
    public function execute(Zen_Request $request, Zen_Response $response, $args) { }
}