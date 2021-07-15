# 全局设置

`global.config.php`文件为框架的全局设置文件。

---

## 基础路径设置

全局设置中包含有以下六个路径常量，该常量会在框架核心中使用到，如果需要修改某些路径的位置可以在这些设置中修改。
```php
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

/* 页面文件的路径 */
const __PAGE_PATH__ = __ZEN_CORE_PATH__ . DIRECTORY_SEPARATOR . 'Page';
```
- `__ZEN_CORE_PATH__`路径为自动设置的路径，默认为该全局设置文件所在的文件夹，修改该设置将会影响下面五个路径。

## 数据库配置
数据库设置中包含三项全局常量，分别为：
```php
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
```
- `__ZEN_MULTI_DATABASE__`为`true`时，将会开启多数据库支持。
- `__ZEN_CACHE_SUPPORT__`为`true`时，将会开启缓存数据库的支持。
- `__ZEN_DATABASE__`常量中以数组的形式保存数据库信息，数据库配置需要对应的信息，可以传入多个配置信息的数组来使用多数据库。
  **注意：在`__ZEN_MULTI_DATABASE__`设置为`false`时传入多个数据库配置将会造成不可预知的错误。**

详细信息参考[数据库](Database.md)。

## 参数配置
一些Zen核心参数的配置。
```php
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
```
- `__ZEN_SECURITY__`配置设置为`true`时将会使用Zen核心的安全组件方法，如需使用其他第三方安全组件可以将其设置为`false`。
- `__ZEN_MOBILE_MODE__`将会影响`Zen_Request`下的`isMobile()`方法的返回值，当`__ZEN_MOBILE_MODE__`设置为`true`时，将总是返回`true`。
- `__ZEN_URL_PREFIX__ `将会影响`Zen_Request`下的`getUrlPrefix()`方法，如果设置了该项，则会返回设置的值代替自动获取的值。在获取当前请求的url时，前缀将变为设置的值。
- `__ZEN_WIDGET_RET_OBJ__`设置为`true`时，在使用`Zen_Widget_Helper::factory()`方法时，将会直接返回生成的对象，否则会返回`Zen_Widget_Helper`对象。
- `__ZEN_INDEX__`需要设置为您的唯一入口文件的文件名，否则将在开启了url重写的http服务器上工作不正常。该设置仅会影响路由。
- `__ZEN_ROUTER_ANNOTATION__`设置为`true`时会通过注解来设置路由表。**注意：该选项当前不可用，这个版本仅支持通过注解来设置路由表。**

## 第三方组件支持
**注意：以下设置仅影响Zen框架核心，如需在其他组件内使用第三方类的方法，直接使用`Zen_Widget::callWidgetFunction()`方法。**

```php
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
```
设置内容同`Zen_Widget::callWidgetFunction()`，设置一个字符串以[类名]@[函数名]的方式。该第三方类需要在放在`__ZEN_EXTRA_PATH__`路径下。