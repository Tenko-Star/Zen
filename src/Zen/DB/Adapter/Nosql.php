<?php

abstract class Zen_DB_Adapter_Nosql implements Zen_DB_Adapter {

    /**
     * 检查适配器是否可用
     */
    abstract public static function isAvailable(): bool;

    /**
     * 数据库连接函数
     *
     * @param Zen_Config $config 设置类对象
     * @return resource
     * @throws Zen_DB_Adapter_Exception
     */
    abstract public function connect(Zen_Config $config);

    /**
     * 关闭数据库连接
     *
     * @return void
     */
    abstract public function close();

    /**
     * 测试数据库连接
     *
     * @return bool
     */
    abstract public function test(): bool;

    /**
     * 获取数据库版本
     *
     * @return string version
     */
    abstract public function getVersion(): string;

    /**
     * 数据库请求
     *
     * @param mixed $query 数据库查询语句
     * @param mixed $handle 数据库句柄
     * @param string $action 数据库动作
     * @param string table 数据表
     * @return resource
     * @throws Zen_DB_Query_Exception
     */
    abstract public function query($query, $handle, string $action = '', string $table = '');

    /**
     * 取出获取的数据的一行
     *
     * @param resource $resource 查询的数据资源
     * @return array
     */
    abstract public function fetch($resource): array;

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object
     */
    abstract public function fetchObject($resource);

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue(string $string): string
    {
        // TODO: Implement quoteValue() method.
    }

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        // TODO: Implement quoteColumn() method.
    }

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    final public function parseSelect(array $sql): string {
        return '';
    }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    final public function affectedRows($resource, $handle): int {
        return 0;
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    final public function lastInsertId($resource, $handle): int {
        return 0;
    }
}