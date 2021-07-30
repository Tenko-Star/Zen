<?php

/**
 * 数据库适配器接口
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
interface Zen_DB_Adapter {

    /**
     * 检查适配器是否可用
     */
    public static function isAvailable() : bool;

    /**
     * 数据库连接函数
     *
     * @param Zen_Config $config 设置类对象
     * @return resource
     * @throws Zen_DB_Adapter_Exception
     */
    public function connect(Zen_Config $config);

    /**
     * 关闭数据库连接
     *
     * @return void
     */
    public function close();

    /**
     * 测试数据库连接
     *
     * @return bool
     */
    public function test() : bool;

    /**
     * 获取数据库版本
     *
     * @return string version
     */
    public function getVersion() : string;

    /**
     * 数据库请求
     * 
     * @param mixed $query 数据库查询语句
     * @param mixed $handle 数据库句柄
     * @param string $action 数据库动作
     * @param string table 数据表
     * @throws Zen_DB_Query_Exception
     */
    public function query($query, $handle, string $action = '', string $table = '');

    /**
     * 开启事务
     *
     * @return bool
     * <p>
     * 如果开启事务失败，返回false。成功返回true。
     * </p>
     */
    public function begin(): bool;

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * 回滚事务
     *
     * @return bool
     */
    public function rollback(): bool;

    /**
     * 取出获取的数据的一行
     * 
     * @param resource $resource 查询的数据资源
     * @return array
     */
    public function fetch($resource) : array;

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object
     */
    public function fetchObject($resource);

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue(string $string) : string;

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string) : string;

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql) : string;

    /**
     * 取出最后一次查询影响的行数
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle) : int;

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle) : int;
}