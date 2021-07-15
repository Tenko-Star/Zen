# 组件

---

## 简介
组件用于MVC结构中的模型和控制器，组件可以通过继承抽象类`Zen_Widget`、`Zen_Page_Widget`或`Zen_Action_Widget`类获取一些常用的方法。

## 普通组件类
<pre>
abstract class Zen_Widget {
    /* 默认的组件版本 */
    const DEFAULT_VERSION = 1.0;
    
    /* 成员方法 */
    public function init()
    protected function widget(string $widget_name, array $args)
    protected function api(string $handle = '') : Zen_Plugin
    public static function version(): string
    public static function callWidgetFunction(string $callback, array $args = array(), bool $direct_arg = false)
}
</pre>

- `init()`方法会在该组件被创建时自动调用，默认实现为空，如果有需要在组件创建时做一些初始化，请重写该方法。
- `widget(string, array)`方法返回一个其他的组件类，也可以是一个位于`__ZEN_EXTRA_PATH__`下的第三方类。第二个参数传递组件构造函数需要的参数，以数组的形式。
- `api(string)`方法返回一个句柄为`$handle`的插件类，通过使用`$this->api()->func()`的方式来设置一个可供插件注册的api函数。
- `version()`方法返回当前组件的版本，用于检查插件依赖。
- `callWidgetFunction(string, array, bool)`方法用于调用位于`__ZEN_EXTRA_PATH__`下的函数，第一个参数为"类名@函数名"，第二个参数为函数所需的参数，以数组的形式传递。
如果需要将第二个参数作为一个完整的数组传入，可以将参数用`array()`包含或将第三个参数设置为`true`。
  
## 页面组件类
```
abstract class Zen_Page_Widget extends Zen_Widget {
    /* 默认的路径 */
    private $_base_path = __PAGE_PATH__ . DIRECTORY_SEPARATOR;

    /* 成员方法 */
    public function setBasePath(string $path)
    public function html(string $file)
}
```
- `setBasePath(string)`方法用于设置该组件默认的页面路径，如果设置的路径中不包含`__PAGE_PATH__`会自动将`__PAGE_PATH__`加入至路径，
如传入的参数为`/Page`，最后将会合成为`__PAGE_PATH__/Page/`。
- `html(string)`方法将会include`$file`所代表的文件。完整路径为`$_base_path`和`$file`的组合。如：传入参数为`index.php`，实际include的文件为
`__PAGE_PATH__/Page/index.php`。