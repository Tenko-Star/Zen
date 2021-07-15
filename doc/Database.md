# 数据库

Zen框架中的包含一个数据库类以及一个数据库语句构筑器，通过使用构筑器来快速安全地构筑语句。

数据库类的[详细信息](#数据库类)，语句构筑器的[详细信息](#构筑器类)。

---

在全局设置`global.config.php`中，变量`__ZEN_DATABASE__`用于设置数据库。该变量为一个数组，可在该变量中填写多个数据库信息，
变量`__ZEN_MULTI_DATABASE__`用于启用多数据库以及读写分离的支持，变量`__ZEN_CACHE_SUPPORT__`用于启用缓存数据库的支持（当前版本暂不可用，缓存数据库指Redis数据库）。

在组件中使用数据库对象的方法：
```php
//获取一个数据库对象
$db = Zen_DB::get();
//获取一个sql构筑器
$sql = $db->sql();
//构筑一个完整的sql
$sql = $db->select(/* 查询字段，默认为* */)->from("/* 表名 */")->where("id = ?", "/* ?所指的变量 */");
//获得请求资源
$res = $db->query($sql); //或
$res = $db->fetchAll($sql);
```

## 数据库类

<pre>
class Zen_DB {
    /* 数据库常量 */
    const SORT_ASC = 'ASC';         /* 升序方式 */
    const SORT_DESC = 'DESC';       /* 降序方式 */ 
    const INNER_JOIN = 'INNER';     /* 表内连接方式 */ 
    const OUTER_JOIN = 'OUTER';     /* 表外连接方式 */ 
    const LEFT_JOIN = 'LEFT';       /* 表左连接方式 */
    const RIGHT_JOIN = 'RIGHT';     /* 表右连接方式 */
    const SELECT = 'SELECT';        /* 数据库查询操作 */
    const UPDATE = 'UPDATE';        /* 数据库更新操作 */
    const INSERT = 'INSERT';        /* 数据库插入操作 */
    const DELETE = 'DELETE';        /* 数据库删除操作 */
    const AND = 'AND';
    const OR = 'OR';
    const GET = 'GET';              /* NOSQL获取数据 */
    const SET = 'SET';              /* NOSQL设置数据 */
    const CACHE = 'CACHE';          /* 读取数据库缓存 */
    const NOSQL = 'NOSQL';          /* 非关系型数据库 */

    /* 数据库操作常量 */
    //数据库权限
    const               AUTH_READ       =   1;
    const               AUTH_WRITE      =   2;
    const               AUTH_CACHE      =   3;
    const               AUTH_NOSQL      =   4;
    const               AUTH_MAIN       =   5;
    
    //数据库池常量
    const       HAVE_MAIN       =   0;
    const       HAVE_CACHE      =   1;
    const       TOTAL           =   2;
    const       TOTAL_READ      =   3;
    const       TOTAL_WRITE     =   4;
    const       TOTAL_CACHE     =   5;
    const       TOTAL_NOSQL     =   6;
    
    /* 数据库类方法 */
    public static function get(int $authority = self::AUTH_READ, string $prefix = '') : Zen_DB
    public static function init(array $configs): bool
    public function unsafe($data = NULL, ...) : Zen_DB_Adapter
    public static function addServer(Zen_Config $config): bool
    public function getPrefix() : string
    public function setPrefix(string $prefix)
    public function getVersion() : string
    public static function getDatabaseInfo() : array
    public function getHandle()
    public function sql() : Zen_DB_Query
    public function nosql() : Zen_DB_Query
    public function cache(string $key, $value = NULL, int $expire = 0) : Zen_DB_Query
    public function select(...) : Zen_DB_Query
    public function update(string $table) : Zen_DB_Query
    public function delete(string $table) : Zen_DB_Query
    public function insert(string $table) : Zen_DB_Query
    public function query(Zen_DB_Query $query)
    public function fetchAll(Zen_DB_Query $query, callable $filter = NULL) : array
    public function fetchRow(Zen_DB_Query $query, callable $filter = NULL)
    public function fetchObject(Zen_DB_Query $query, callable $filter = NULL)
}
</pre>

### get方法

*静态方法，用于获取一个数据库对象，该数据库对象所指向的数据库和该数据库的读写权限是不可更改的。*

#### 参数

- `int $authority`为需要获取的数据库权限，用作多数据库的读写分离。当仅有一个数据库且需要对该数据库进行读写操作时，可传递`AUTH_MAIN`。
详细内容见[数据库权限](#数据库权限)。
- `string $prefix`为该数据库表的前缀，在使用其他方法时，如`from()`方法需要传递一个表名时，传递`table.[表名]`可以转换为`[前缀][表名]`的形式。

#### 返回值
`Zen_DB` - 该方法返回一个数据库类对象。如果该数据库不可用，将会抛出`Zen_DB_Exception`类异常。

---

### init方法
*静态方法，用于初始化数据库。*
#### 参数
- `array $configs`为一个二维数组，结构如下：
```
const __ZEN_DATABASE__ = array(
    [
        //数据库一
        'adapter_name' => 'Pdo_Mysql',
        'database' => 'test',
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'test',
        'password' => 'test',
        'charset' => 'utf-8',
        'authority' => Zen_DB::AUTH_MAIN
    ],
    [
        //数据库二
    ],
    ...
);
```

#### 返回值
`bool` - 如果所有的数据库信息都无误且数据库类初始化成功，返回`true`，否则返回`false`。

---

### addServer方法
*静态方法，该方法传入一个`Zen_Config`类对象来添加一个数据库配置。*
#### 参数
- `Zen_Config $config`为数据库配置信息，使用`Zen_Config::get(array)`可以生成一个`Zen_Config`类对象。

#### 返回值
`bool` - 如果成功返回`true`，否则返回`false`。

---

### getDatabaseInfo方法
*静态方法，获取数据库信息表*
#### 返回值
`array` - 该方法返回一个数组，该数组内保存有配置的数据库信息。数组结构如下：
```
array(
    [Zen_DB::HAVE_MAIN]       =>   [bool],    //是否存在主数据库（可读写）
    [Zen_DB::HAVE_CACHE]      =>   [bool],    //是否存在缓存数据库
    [Zen_DB::TOTAL]           =>   [int],     //数据库数量总计
    [Zen_DB::TOTAL_READ]      =>   [int],     //只读数据库数量
    [Zen_DB::TOTAL_WRITE]     =>   [int],     //只写或可读写数据库数量
    [Zen_DB::TOTAL_CACHE]     =>   [int],     //缓存数据库数量
    [Zen_DB::TOTAL_NOSQL]     =>   [int]      //NoSQL数据库数量
)
```

---

### getPrefix方法
*获取表前缀*
#### 返回值
`string` - 返回数据库表前缀。

### setPrefix方法
*更改表前缀*
#### 参数
- `string $prefix`为需要更改的表前缀

---

### getVersion方法
*获取数据库版本*
#### 返回值
`string` - 数据库版本信息。

---

### getHandle方法
*该方法返回数据库句柄。*
#### 返回值
`mixed` - 数据库句柄。

---

### sql方法
*该方法返回一个sql语句构筑器。*
#### 返回值
`Zen_DB_Query` - sql语句构筑器。

### nosql方法
*该方法返回一个nosql语句构筑器。*
#### 返回值
`Zen_DB_Query` - nosql语句构筑器。

*`sql()`与`nosql()`方法的区别见[构筑器](#构筑器类)。*

---

### select方法
*数据库`select`语句构筑方法*
#### 参数
- `可变长参数 string`构筑sql中的`select`语句，参数可以为多个string类型的参数，该参数代表数据库中的字段名。默认值为`*`。
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

### insert/delete/update方法
<p id="idu">数据库<code>insert / delete / update</code>语句构筑方法.</p>

#### 参数
- `string $table`需要`insert / delete / update`的表名
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---

### cache方法
*缓存数据库语句构筑器，适用于Redis数据库。*
#### 参数
- `string $key`需要获取或写入的键名
- `mixed $value`需要写入的键值
- `int $expire`有效期
#### 返回值
`Zen_DB_Query` - 该方法返回一个nosql构筑器。
#### 备注
如果只传入了第一个参数或第二个参数为NULL时，将会构建一个用于获取键值的语句。如果需要做更多操作请使用`nosql()`方法来获取一个构筑器。

---

### query方法
*数据库请求方法*
#### 参数
- `Zen_DB_Query $query`传入一个请求构筑器来请求数据库。
#### 返回值
- `int`- 当传入的构筑器构建了一个`UPDATE / DELETE`语句，将会返回受到语句影响的行数。当传入的构筑器构建了一个`INSERT`语句，将会返回最后一个插入的id。
- `resource` - 当传入的构筑器构建了一个`SELECT`语句或`NOSQL`语句，将会返回对应的资源。

---

### fetchAll方法
*传入一个请求构筑器来获取请求返回资源的所有内容，以数组的形式。*
#### 参数
- `Zen_DB_Query $query`传入一个请求构筑器来获取请求返回的内容。
- `callback $filter`过滤器方法。过滤器方法需要接受一个参数来进行处理，这个函数会将获取到的所有数据传入该过滤器方法中。
#### 返回值
`array` - 返回所请求的资源的所有内容。如果存在过滤器则对每一个数据进行过滤，返回过滤后的数据。

### fetchRow方法
*类似fetchAll方法，但是仅获取一行。如果存在过滤器函数，则将获取的这一行数据传入过滤器函数中。*

### fetchObject方法
*同fetchAll方法，但会返回一个对象。具体对象信息见所使用的数据库文档。*

---
### unsafe方法
*需要执行特殊语句时使用该方法，但该方法不进行任何检查，可能会发生严重错误。*
#### 参数
- `string | callable $data`该参数为unsafe方法所需要执行的必要参数。
- `...`可变长参数，当`$data`为callable类型时，可传递参数。
#### 返回值
- `mixed`当`$data`为string类型的语句时，返回该语句请求的数据。callable类型返回所调用的函数的返回值。

---

### 数据库权限
数据库有五种不同的权限类型。
1. `AUTH_READ`表示这是一个只读的数据库，只有`SELECT`语句会被执行，其他语句将被拒绝执行。
2. `AUTH_WRITE`表示这是一个只写的数据库，只有`SELECT`语句和`CACHE`相关语句不会被执行。
3. `AUTH_CACHE`表示这是一个缓存数据库，只有`CACHE`相关语句会被执行。
4. `AUTH_NOSQL`表示这是一个NOSQL数据库。
5. `AUTH_MAIN`表示这是一个可读写的数据库，除缓存和NOSQL操作外，任何操作都可以被执行。

## 构筑器类
```
class Zen_DB_Query {
    /* 请求构筑器常量 */
    const TYPE_SQL          =       0;
    const TYPE_NOSQL        =       1;

    /* 请求构筑器方法 */
    public function __construct(Zen_DB_Adapter $adapter, string $prefix, int $type = self::TYPE_SQL)
    public static function setDefault(array $default)
    public function combine() : string
    public function select(string $field = '*') : Zen_DB_Query
    public function from(string $table) : Zen_DB_Query 
    public function update(string $table) : Zen_DB_Query 
    public function delete(string $table) : Zen_DB_Query 
    public function insert(string  $table) : Zen_DB_Query 
    public function join(string $table, string $condition, string $op = Zen_DB::INNER_JOIN) : Zen_DB_Query 
    public function where(string $condition) : Zen_DB_Query 
    public function orWhere(string $condition) : Zen_DB_Query
    public function limit(int $limit) : Zen_DB_Query 
    public function offset(int $offset) : Zen_DB_Query 
    public function page(int $page, int $pageSize) : Zen_DB_Query 
    public function rows(array $rows) : Zen_DB_Query 
    public function order(string $order, string $sort = Zen_DB::SORT_ASC) : Zen_DB_Query 
    public function group(string $key) : Zen_DB_Query 
    public function having(string $condition) : Zen_DB_Query
    public function getAttribute(string $attributeName) : ?string 
    public function cleanAttribute(string $attributeName) : Zen_DB_Query 
    public function set(string $key, $value, int $expire = 0) : Zen_DB_Query
    public function get(string $key): Zen_DB_Query
}
```
### __construct方法
*构造函数*
#### 参数
- `Zen_DB_Adapter $adapter`数据库适配器类
- `string $prefix`表前缀
- `int $type`请求类型
#### 备注
当`type`为`TYPE_NOSQL`时，不会进行构造器初始化。

---
### setDefault方法
*静态方法，设置默认字段表*
#### 参数
- `array $default`以键值对的形式设置传入一个数组。

---
### combine方法
*合成最终查询语句*
#### 返回值
`string` - 合成的查询语句。当`type`为`TYPE_NOSQL`时，不会进行语句的合成，返回值为''。

---
### select / update / delete / insert 方法
*与<a href="#idu">这里</a>相同。*

---
### from方法
*设置选择查询的表*
#### 参数
- `string $table`需要查询的表
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### where / orWhere方法
*设置查询条件*
#### 参数
- `string $condition`查询条件，格式为`[字段名] = ?`。
- `...`可变长参数，传入的参数为第一个参数中`?`所指代的值。
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### limit方法
*设置查询的最大数量*
#### 参数
- `int $limit`查询的最大行数
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

### offset方法
*设置查询的偏移位置*
#### 参数
- `int $offset`查询的偏移位置
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### page方法
*分页查询*
#### 参数
- `int $page`查询的页码
- `int $page_size`每页行数
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### rows方法
*指定需要写入的字段和数值*
#### 参数
- `array $rows`包含有需要写入的字段和数值。格式如下
```
array(
    '[字段]' => '[值]',
    ...
)
```
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### order方法
*设置排序顺序*
#### 参数
- `string $order`排序的列名 / 表达式 / 位置
- `string $sort`排序的方式

当`$sort`为`Zen_DB::SORT_ASC`时为升序，为`Zen_DB::SORT_DESC`时为降序。
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### group方法
*设置聚合*
#### 参数
- `string $key`以该键值分组
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### having方法
*指定一组行或聚合的过滤条件*
### 参数
- `string $condition`聚合条件，与where格式相同
- `...`可变长参数，与where格式相同
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### getAttribute方法
*获取查询字串属性值*
#### 参数
- `string $attributeName`属性名称
#### 返回值
`string | NULL` - 当查询的属性不存在时返回NULL，否则返回查询的属性字符串

---
### cleanAttribute方法
*清除查询字串属性值*
#### 参数
- `string $attributeName`属性名称
#### 返回值
`Zen_DB_Query` - 该方法返回一个sql构筑器。

---
### set / get方法
*见数据库类中的set / get方法*