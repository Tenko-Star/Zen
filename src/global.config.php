<?php

/*
 * Zen 全局配置
 * 入口文件需要引入该配置
 */

/* 核心文件位置 */
define('__ZEN_CORE_PATH__', dirname(__FILE__));

/* 组件目录 */
const __ZEN_WIDGET_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Widget';

/* 额外组件目录 */
const __ZEN_EXTRA_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Extra';

/* 插件目录 */
const __ZEN_PLUGIN_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Plugin';

/* 文件上传目录 */
const __UPLOAD_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Upload';

/* Page Path */
const __PAGE_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Page';

/* 设置include路径 */
@set_include_path(
    get_include_path() . PATH_SEPARATOR .
    __ZEN_CORE_PATH__ . PATH_SEPARATOR .
    __ZEN_EXTRA_PATH__
);

/* 数据库配置
 *
 * __ZEN_MULTI_DATABASE__ 多数据库支持开关
 * __ZEN_CACHE_SUPPORT__  缓存数据库开关
 */
const __ZEN_MULTI_DATABASE__ = false;
const __ZEN_CACHE_SUPPORT__ = false;

const __ZEN_DATABASE__ = array(
    [
        'adapter_name' => 'Pdo_Mysql',
        'database' => 'test',
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'test',
        'password' => 'test',
        'charset' => 'utf-8',
        'authority' => Zen_DB::AUTH_MAIN
    ]
);

/* 参数配置 */
/* 是否开启安全模块 */
const __ZEN_SECURITY__ = true;
/* 是否开启仅移动端模式 */
const __ZEN_MOBILE_MODE__ = false;
/* URL前缀 */
const __ZEN_URL_PREFIX__ = '';
/* Helper是否直接返回第三方类实例 */
const __ZEN_WIDGET_RET_OBJ__ = true;
/* 入口文件 */
const __ZEN_INDEX__ = '/index.php';
/* 开启注解路由 */
const __ZEN_ROUTER_ANNOTATION__ = true;

/* 第三方组件函数绑定(仅Zen核心组件绑定) */
/* 用于做http参数检查 */
const __EXTRA_CHECK_STR__ = '';
/* 第三方JSON支持 */
const __EXTRA_JSON_ENCODE__ = 'Json@encode';
const __EXTRA_JSON_DECODE__ = 'Json@decode';
/* 防止针对URL的XSS注入 */
const __EXTRA_SAFETY_URL__ = '';
/* 第三方UUID算法支持 */
const __EXTRA_UUID_SUPPORT__ = '';
/* 第三方XSS安全函数 */
const __EXTRA_REMOVE_XSS__ = '';

/* 自动参数配置 */
define('__ZEN_MB_SUPPORT__', function_exists('mb_get_info') && function_exists('mb_regex_encoding'));
define('__ZEN_FILTER_SUPPORT__', function_exists('filter_var'));
define('__ZEN_JSON_SUPPORT__',(function_exists('json_encode') && function_exists('json_decode')));
define('__ZEN_OPENSSL_SUPPORT__', (function_exists('openssl_decrypt') && function_exists('openssl_encrypt')));