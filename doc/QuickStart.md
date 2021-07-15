# 快速开始

---

## 文件结构

<pre>
src
├── Extra   =>第三方库
├── Upload  =>上传文件的文件夹
├── Page    =>页面文件
├── Plugin  =>插件
├── Widget  =>组件（模型/控制器）
├── Zen     =>核心文件
├── global.config.php => 全局配置文件
└── index.php =>入口文件
</pre>

- Zen文件夹（必选）内存放的是框架必备的文件，用于实现基础功能。
- Widget文件夹（必选）内存放的是各类组件，包括页面文件渲染组件、模型类、控制器类。
- Plugin文件夹（可选），存放的是各组件的插件类，如无二次扩展需求，可以删除。
- Page文件夹（可选），当使用html与php混写时，将所需的页面文件放在这里，如果使用的项目仅提供接口，该目录可以删除。
- Upload文件夹（可选），当需要上传文件时，该文件夹为默认的上传文件夹，可在[全局设置](Settings.md)中修改上传文件夹的位置。
- Extra文件夹（可选），其他第三方库存放的位置，如果没有使用，可以删除、

---


## 使用方法

### 创建组件类

在Widget文件夹中建立所需的类，可以继承自`Zen_Widget_Action_Widget`以及`Zen_Widget_Page_Widget`。
这两者之间的详细区别见[组件设置](Widget.md)。

以下是组件类的示意。

```php
class Widget_Demo extends Zen_Page_Widget {
    /**
      * @override
      */
    public function init() {
        //组件被创建时的初始化操作
    }
    
    /**
      * @map("/anyPath/:anyVar")
      * @param $anyVar
      */
    public function anyName($anyVar) {
        //实现组件功能
        $this->html("/* 页面文件相对路径 */");
    }
    
    /**
      * @map("/otherpath") 
      */
    public function otherFunction() {
        //实现组件功能
    }
}
```

这样就可以完成一个渲染页面的组件类，**注意**，类名要以`Widget_`开头，根据不同的目录层级在类名中使用`-`来分割。`@map()`注释用于标识路由信息，在路径中一个或多个的`:var`将会被当作参数被传入。

以下是继承`Zen_Widget_Action_Widget`类的组件类示意。

```php
class Widget_Demo extends Zen_Action_Widget {
    /**
      * @override
      */
    public function init() {
        //组件被创建时的初始化操作
    }
    
    /**
     * 执行组件功能
     *
     * @param Zen_Response $response
     * @param Zen_Request $request
     * @param mixed $args
     */
    public function execute(Zen_Request $request, Zen_Response $response, $args) { 
        //实现组件功能
    }
}
```
这样就可以完成一个不渲染页面的组件类。**注意：**该类中不含有路由信息，可以使用`Zen_Widget_Helper::action(/*组件名*/)`来调用execute方法。
如果希望使用路由来完成，需要手动获取`Zen_Request`对象和`Zen_Response`对象。

### 入口文件

入口文件不强制使用index.php，你可以任意指定一个入口文件，但需要在`global.config.php`文件的`__ZEN_INDEX__`常量中设置文件名，否则可能会在未设置url重写的设备上工作不正常。
其次需要在全局设置中配置各个文件夹的位置，如果你有更改文件夹位置的需要的话。

详细设置见[全局设置](Settings.md)。

### 数据库设置

在全局设置`global.config.php`中，变量`__ZEN_DATABASE__`用于设置数据库。该变量为一个数组，可在该变量中填写多个数据库信息，
变量`__ZEN_MULTI_DATABASE__`用于启用多数据库以及读写分离的支持，变量`__ZEN_CACHE_SUPPORT__`用于启用缓存数据库的支持（当前版本暂不可用，缓存数据库指Redis数据库）。

在组件中使用数据库对象的方法：
```php
//获取一个数据库对象
$db = Zen_DB::get();
//获取一个sql构建器
$sql = $db->sql();
//构建一个完整的sql
$sql = $db->select(/* 查询字段，默认为* */)->from("/* 表名 */")->where("id = ?", "/* ?所指的变量 */");
//获得请求资源
$res = $db->query($sql); //或
$res = $db->fetchAll($sql);
```

**注意：**
- 当`__ZEN_MULTI_DATABASE__`设置为`false`而在`__ZEN_DATABASE__`中填写了多个数据库信息，可能会造成不可预期的结果，请尽量避免出现这种情况。
- 在使用`Zen_DB::get()`函数来获取一个数据库对象实例时，默认的权限是只读，哪怕在`__ZEN_MULTI_DATABASE__`设置为`false`时，也只有读取权限。
如需读写权限，请使用`Zen_DB::get(Zen_DB::AUTH_MAIN)`来获取一个具有读写权限的数据库。
- 如要在表前加前缀，使用`Zen_DB::get()`来获取一个带有前缀的数据库类，在使用`from()`时，传入的参数变为`table.表名剩余部分`。
  
更多信息请查看[数据库](Database.md)。