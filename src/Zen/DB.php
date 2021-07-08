<?php

/**
 * 数据库连接类
 *
 * @author tenko_
 */
class Zen_DB {

    /** 升序方式 */
    const SORT_ASC = 'ASC';

    /** 降序方式 */
    const SORT_DESC = 'DESC';

    /** 表内连接方式 */
    const INNER_JOIN = 'INNER';

    /** 表外连接方式 */
    const OUTER_JOIN = 'OUTER';

    /** 表左连接方式 */
    const LEFT_JOIN = 'LEFT';

    /** 表右连接方式 */
    const RIGHT_JOIN = 'RIGHT';

    /** 数据库查询操作 */
    const SELECT = 'SELECT';

    /** 数据库更新操作 */
    const UPDATE = 'UPDATE';

    /** 数据库插入操作 */
    const INSERT = 'INSERT';

    /** 数据库删除操作 */
    const DELETE = 'DELETE';

    const AND = 'AND';

    const OR = 'OR';

    const GET = 'GET';

    const SET = 'SET';

    /* 读取数据库缓存 */
    const CACHE = 'CACHE';

    /* 非关系型数据库 */
    const NOSQL = 'NOSQL'; //未启用

    /* 数据库操作常量 */
    //template
    private const       POOL_INFO         =   0;
    private const       AUTH_NULL       =   0;
    const               AUTH_READ       =   1;
    const               AUTH_WRITE      =   2;
    const               AUTH_CACHE      =   3;
    const               AUTH_NOSQL      =   4;
    const               AUTH_MAIN       =   5;
    private const       RESERVED        =   5;
    //pool_info
    const       HAVE_MAIN       =   0;
    const       HAVE_CACHE      =   1;
    const       TOTAL           =   2;
    const       TOTAL_READ      =   3;
    const       TOTAL_WRITE     =   4;
    const       TOTAL_CACHE     =   5;
    const       TOTAL_NOSQL     =   6;
    //db_info
    private const       ADAPTER_NAME    =   0;
    private const       HANDLE          =   1;
    private const       CONFIG          =   2;

    /**
     * @var Zen_DB_Adapter
     */
    private $_adapter;

    /**
     * @var string 当前对象句柄
     */
    private $_handle;

    private $_index;

    /**
     * 数据库池模板
     * <p>
     * POOL_INFO    ->      数据库池信息表              <br>
     * AUTH_READ    ->      权限为READ的数据库表        <br>
     * AUTH_WRITE   ->      权限为WRITE的数据库表       <br>
     * AUTH_CACHE   ->      权限为CACHE的数据库表       <br>
     * AUTH_NOSQL   ->      权限为NOSQL的数据库表       <br>
     * </p>
     */
    private static $_template = array(
        self::POOL_INFO => array(
            self::HAVE_MAIN => false,
            self::HAVE_CACHE => false,
            self::TOTAL => 0,
            self::TOTAL_READ => 0,
            self::TOTAL_WRITE => 0,
            self::TOTAL_CACHE => 0,
            self::TOTAL_NOSQL => 0
        ),                                      //POOL_INFO
        self::AUTH_READ => array(),             //READ
        self::AUTH_WRITE => array(),            //WRITE
        self::AUTH_CACHE => array(),            //CACHE
        self::AUTH_NOSQL => array(),            //RESERVED
        5 => self::RESERVED                     //RESERVED
    );

    /**
     * 数据库池
     *
     * @var array
     */
    private static $_available = array();

    /**
     * 表前缀
     *
     * @var string
     */
    private $_prefix;

    /**
     * 该对象读写权限
     *
     * @var int
     */
    private $_authority = self::AUTH_NULL;

    /**
     * Zen_DB constructor.
     * @param int $authority
     * @param string $prefix
     * @throws Zen_DB_Exception
     */
    private function __construct(int $authority, string $prefix) {
        $this->_authority = $authority;
        $this->_prefix = $prefix;

        // MAIN => WRITE transfer
        $authority = ($this->_authority === self::AUTH_MAIN) ? self::AUTH_WRITE : $this->_authority;
        $index = 0;

        if(__ZEN_MULTI_DATABASE__ || __ZEN_CACHE_SUPPORT__) {
            switch($authority) {
                case self::AUTH_NOSQL:
                    $index = rand(0, self::$_available[self::POOL_INFO][self::TOTAL_NOSQL] - 1);
                    break;
                case self::AUTH_READ:
                    $index = rand(0, self::$_available[self::POOL_INFO][self::TOTAL_READ] - 1);
                    break;
                case self::AUTH_CACHE:
                    $index = rand(0, self::$_available[self::POOL_INFO][self::TOTAL_CACHE] - 1);
                    break;
                case self::AUTH_WRITE:
            }

            $adapter_name = self::$_available[$authority][$index][self::ADAPTER_NAME];
        }else {
            $adapter_name = self::$_available[self::AUTH_WRITE][$index][self::ADAPTER_NAME];
        }

        if(!call_user_func(array($adapter_name, 'isAvailable'))) {
            throw new Zen_DB_Exception("{$adapter_name} is not available.");
        }
        $this->_index = $index;
        $this->_adapter = new $adapter_name();
        $this->_handle = self::$_available[$authority][$index][self::HANDLE];
    }

    /**
     * Zen_DB destructor.
     */
    public function __destruct() {
        $this->_adapter->close();
    }

    /**
     * Get a Zen_DB instance
     *
     * @param int $authority
     * @param string $prefix
     * @return Zen_DB
     */
    public static function get(int $authority = self::AUTH_READ, string $prefix = '') : Zen_DB{
        return new Zen_DB($authority, $prefix);
    }

    /**
     * 数据库初始化
     *
     * @param array $configs
     * @return bool
     */
    public static function init(array $configs): bool {
        self::$_available = self::$_template;

        if(!__ZEN_MULTI_DATABASE__ && !__ZEN_CACHE_SUPPORT__) {
            //没有启用多数据库支持时
            $config = current($configs);
            return self::addServer(Zen_Config::get($config));
        }

        $isFinished = true;
        foreach($configs as $config) {
            if(!self::addServer(Zen_Config::get($config))) {
                $isFinished = false;
            }
        }

        return $isFinished;
    }

    /**
     * 添加一组服务器配置
     *
     * @param Zen_Config $config
     * @return bool
     */
    public static function addServer(Zen_Config $config): bool {
        $info = array();

        if(!isset($config->adapter_name)) {
            return false;
        }else {
            $info[self::ADAPTER_NAME] = 'Zen_DB_Adapter_' . $config->adapter_name;
            unset($config->adapter_name);
        }

        if(!isset($config->handle)) {
            $handle = Zen_UUID::create();
        }else {
            $handle = $config->handle;
            unset($config->handle);
        }

        $info[self::HANDLE] = $handle;

        if(__ZEN_MULTI_DATABASE__ || __ZEN_CACHE_SUPPORT__) {
            $auth = $config->authority;
            unset($config->authority);
            $info[self::CONFIG] = $config;
            switch($auth) {
                case self::AUTH_READ:
                    // Read Only
                    self::$_available[self::AUTH_READ][] = $info;
                    self::$_available[self::POOL_INFO][self::TOTAL_READ]++;
                    break;
                case self::AUTH_WRITE:
                    // Write Only If Available
                    if(self::$_available[self::POOL_INFO][self::HAVE_MAIN]){
                        return false;
                    }
                    self::$_available[self::AUTH_WRITE][] = $info;
                    self::$_available[self::POOL_INFO][self::HAVE_MAIN] = true;
                    self::$_available[self::POOL_INFO][self::TOTAL_WRITE]++;
                    break;
                case self::AUTH_CACHE:
                    // Cache Only
                    self::$_available[self::AUTH_CACHE][] = $info;
                    self::$_available[self::POOL_INFO][self::HAVE_CACHE] = true;
                    self::$_available[self::POOL_INFO][self::TOTAL_CACHE]++;
                    break;
                case self::AUTH_NOSQL:
                    self::$_available[self::AUTH_NOSQL][] = $info;
                    self::$_available[self::POOL_INFO][self::TOTAL_NOSQL]++;
                    break;
                case self::AUTH_MAIN:
                    if(self::$_available[self::POOL_INFO][self::HAVE_MAIN]){
                        return false;
                    }
                    self::$_available[self::AUTH_READ][] = $info;
                    self::$_available[self::AUTH_WRITE][] = $info;
                    self::$_available[self::POOL_INFO][self::HAVE_MAIN] = true;
                    self::$_available[self::POOL_INFO][self::TOTAL_READ]++;
                    self::$_available[self::POOL_INFO][self::TOTAL_WRITE]++;
                    break;
                default:
                    self::$_available[self::AUTH_READ] = $info;
                    self::$_available[self::POOL_INFO][self::TOTAL_READ]++;
            }
        }else {
            unset($config->authority);
            $info[self::CONFIG] = $config;
            self::$_available[self::AUTH_READ][] = $info;
            self::$_available[self::AUTH_WRITE][] = $info;
            self::$_available[self::POOL_INFO][self::HAVE_MAIN] = true;
            self::$_available[self::POOL_INFO][self::TOTAL_READ]++;
            self::$_available[self::POOL_INFO][self::TOTAL_WRITE]++;
        }

        self::$_available[self::POOL_INFO][self::TOTAL]++;

        return true;
    }

    /**
     * 数据库连接
     *
     * @throws Zen_DB_Exception
     */
    private function connect() {
        // MAIN => WRITE transfer
        $authority = ($this->_authority === self::AUTH_MAIN) ? self::AUTH_WRITE : $this->_authority;
        $this->_adapter->connect(self::$_available[$authority][$this->_index][self::CONFIG]);
    }

    /**
     * 获取前缀
     *
     * @return string
     */
    public function getPrefix() : string {
        return $this->_prefix;
    }

    /**
     * 获取数据库版本
     *
     * @return string
     */
    public function getVersion() : string {
        return $this->_adapter->getVersion();
    }

    public static function getDatabaseInfo() : array {
        return self::$_available[self::POOL_INFO];
    }

    /**
     * 获取sql构筑器
     *
     * @return Zen_DB_Query
     */
    private function sql() : Zen_DB_Query {
        return new Zen_DB_Query($this->_adapter, $this->_prefix);
    }

    /**
     * 获取非sql数据库的构造器
     *
     * @return Zen_DB_Query
     */
    public function nosql() : Zen_DB_Query {
        return new Zen_DB_Query($this->_adapter, $this->_prefix, Zen_DB_Query::TYPE_NOSQL);
    }

    /**
     * 返回缓存数据库构建器
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return Zen_DB_Query
     */
    public function cache(string $key, $value = NULL, int $expire = 0) : Zen_DB_Query {
        if($value === NULL) {
            return $this->nosql()->get($key);
        }else {
            return $this->nosql()->set($key, $value, $expire);
        }
    }

    /**
     * 选择查询字段
     *
     * @param mixed
     * @return Zen_DB_Query
     */
    public function select() : Zen_DB_Query {
        $args = func_get_args();
        return call_user_func_array(array($this->sql(), 'select'), $args ? $args : array('*'));
    }

    /**
     * 更新操作
     *
     * @param string $table
     * @return Zen_DB_Query
     */
    public function update(string $table) : Zen_DB_Query {
        return $this->sql()->update($table);
    }

    /**
     * 删除操作
     *
     * @param string $table
     * @return Zen_DB_Query
     */
    public function delete(string $table) : Zen_DB_Query {
        return $this->sql()->delete($table);
    }

    /**
     * 插入操作
     *
     * @param string $table
     * @return Zen_DB_Query
     */
    public function insert(string $table) : Zen_DB_Query {
        return $this->sql()->insert($table);
    }

    /**
     * @param Zen_DB_Query $query
     * @return int|resource
     * @throws Zen_DB_Exception
     */
    public function query(Zen_DB_Query $query) {
        $action = $query->getAttribute('action');

        switch($this->_authority) {
            case self::AUTH_CACHE:
                if($action !== self::CACHE){
                    return false;
                }
                break;
            case self::AUTH_READ:
                if($action !== self::SELECT){
                    return false;
                }
                break;
            case self::AUTH_WRITE:
                if($action === self::SELECT){
                    return false;
                }
                break;
            case self::AUTH_MAIN:
                break;
            default:
                return false;
        }

        if(!$this->_adapter->test()) {
            $this->connect();
        }

        $res =  $this->_adapter->query($query, $this->_handle);
        switch ($action) {
            case self::UPDATE:
            case self::DELETE:
                return $this->_adapter->affectedRows($res, $this->_handle);
            case self::INSERT:
                return $this->_adapter->lastInsertId($res, $this->_handle);
            case self::SELECT:
            case self::CACHE:
            default:
                return $res;
        }
    }

    /**
     * 一次取出所有行
     *
     * @param Zen_DB_Query $query 查询对象
     * @param callable|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     * @return array
     * @throws Zen_DB_Exception
     */
    public function fetchAll(Zen_DB_Query $query, callable $filter = NULL) : array {
        //执行查询
        $resource = $this->query($query);
        $result = array();

        //取出每一行
        while ($rows = $this->_adapter->fetch($resource)) {
            //判断是否有过滤器
            $result[] = $filter ? call_user_func($filter, $rows) : $rows;
        }

        return $result;
    }

    /**
     * 一次取出一行
     *
     * @param Zen_DB_Query $query 查询对象
     * @param array|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     * @return mixed
     * @throws Zen_DB_Exception
     */
    public function fetchRow(Zen_DB_Query $query, array $filter = NULL) {
        $resource = $this->query($query);

        /** 取出过滤器 */
        if ($filter) {
            list($object, $method) = $filter;
        }

        return ($rows = $this->_adapter->fetch($resource)) ?
            ($filter ? $object->$method($rows) : $rows) :
            array();
    }

    /**
     * 一次取出一个对象
     *
     * @param mixed $query 查询对象
     * @param array|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     * @return object
     * @throws Zen_DB_Exception
     */
    public function fetchObject($query, array $filter = NULL) : Zen_DB_Query {
        $resource = $this->query($query);

        /** 取出过滤器 */
        if ($filter) {
            list($object, $method) = $filter;
        }

        return ($rows = $this->_adapter->fetchObject($resource)) ?
            ($filter ? $object->$method($rows) : $rows) :
            new stdClass();
    }
}