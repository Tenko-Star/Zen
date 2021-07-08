<?php

if(!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'global.config.php')) {
    die('server error');
}

require_once 'global.config.php';

Zen_Widget_Helper::factory("Widget_Init");

Zen_Router::route();