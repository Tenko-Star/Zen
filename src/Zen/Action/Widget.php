<?php

/**
 * Abstract Class Zen_Action_Widget
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
abstract class Zen_Action_Widget extends Zen_Widget {
    /**
     * 执行组件功能
     *
     * @param Zen_Response $response
     * @param Zen_Request $request
     * @param mixed $args
     */
    public function execute(Zen_Request $request, Zen_Response $response, $args) { }
}