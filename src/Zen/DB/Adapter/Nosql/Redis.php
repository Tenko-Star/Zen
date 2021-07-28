<?php

class Zen_DB_Adapter_Nosql_Redis extends Zen_DB_Adapter_Nosql {
    /**
     * @var Redis
     */
    private $_instance = NULL;

    /**
     * 检查适配器是否可用
     */
    public static function isAvailable(): bool {
        return class_exists('Redis');
    }

    /**
     * 数据库连接函数
     *
     * @param Zen_Config $config 设置类对象
     */
    public function connect(Zen_Config $config) {
        $host = $config->host;
        $port = ($config->port) ?? 6379;

        $this->_instance = new Redis();
        $this->_instance->connect($host, $port);
//        $this->_instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $this->_instance;
    }

    /**
     * 测试数据库连接
     *
     * @return bool
     */
    public function test(): bool {
        if($this->_instance === NULL) { return false; }
        return $this->_instance->isConnected();
    }

    /**
     * 获取数据库版本
     *
     * @return string version
     */
    public function getVersion(): string {
        return '';
    }

    /**
     * 取出获取的数据的一行
     *
     * @param resource $resource 查询的数据资源
     * @return array
     */
    public function fetch($resource): array {
        return $resource[0];
    }

    /**
     * 关闭数据库连接
     *
     * @return void
     */
    public function close() {
        if($this->_instance === NULL) { return; }
        $this->_instance->close();
    }

    /**
     * 数据库请求
     *
     * @param Zen_DB_Query $query 数据库查询语句
     * @param mixed $handle 数据库句柄
     * @param string $action 数据库动作
     * @param string table 数据表
     * @throws Zen_DB_Query_Exception
     */
    public function query($query, $handle, string $action = '', string $table = '') {
        switch($query->getAttribute('action')) {
            case 'GET':
                if(($key = $query->getAttribute('key')) === NULL) { return false; }
                return $this->_instance->get($key);
            case 'SET':
                $key = $query->getAttribute('key');
                $value = $query->getAttribute('value');
//                $expire = ($expire = $query->getAttribute('expire')) === NULL ? 0 : (int)$expire;

                return $this->_instance->set($key, $value);
        }
        return false;
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object
     */
    public function fetchObject($resource) {
        return (object)$resource;
    }
}