<?php

/**
 * 数据库PDOMysql适配器
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
abstract class Zen_DB_Adapter_Pdo implements Zen_DB_Adapter
{
    /**
     * 数据库对象
     *
     * @access protected
     * @var PDO
     */
    protected $_object = NULL;

    /**
     * 最后一次操作的数据表
     *
     * @access protected
     * @var string
     */
    protected $_last_table = '';

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable() : bool {
        return class_exists('PDO');
    }

    /**
     * 数据库连接函数
     *
     * @param Zen_Config $config 数据库配置
     * @throws Zen_DB_Exception
     * @return PDO
     */
    public function connect(Zen_Config $config) : PDO {
        try {
            $this->_object = $this->init($config);
            $this->_object->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->_object;
        } catch (PDOException $e) {
            /** 数据库异常 */
            throw new Zen_DB_Adapter_Exception($e->getMessage(), HTTP_SERVER_ERROR);
        }
    }

    /**
     * 关闭数据库连接
     *
     * @return void
     */
    public function close() {

    }

    /**
     * 测试数据库连接
     *
     * @return bool
     */
    public function test() : bool {
        return ($this->_object !== NULL);
    }

    /**
     * 获取数据库版本
     *
     * @return string
     */
    public function getVersion() : string {
        return 'pdo:' . $this->_object->getAttribute(PDO::ATTR_DRIVER_NAME)
            . ' ' . $this->_object->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * 初始化数据库
     *
     * @param Zen_Config $config 数据库配置
     * @abstract
     * @access public
     * @return PDO
     */
    abstract public function init(Zen_Config $config) : PDO;

    /**
     * 执行数据库查询
     *
     * @param mixed $query 数据库查询SQL字符串
     * @param string $handle 连接对象
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @return false|PDOStatement
     *@throws Zen_DB_Exception
     */
    public function query($query, $handle, string $action = NULL, string $table = NULL) {
        try {
            $this->_last_table = $table;
            if($query instanceof Zen_DB_Query) {
                $resource = $this->_object->prepare($query->combine());
            }else {
                $resource = $this->_object->prepare($query);
            }
            $resource->execute();
        } catch (PDOException $e) {
            /** 数据库异常 */
            throw new Zen_DB_Query_Exception($e->getMessage(), $e->getCode());
        }

        return $resource;
    }

    /**
     * 开启事务
     *
     * @return bool
     * <p>
     * 如果开启事务失败，返回false。成功返回true。
     * </p>
     */
    public function begin(): bool {
        return $this->_object->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit(): bool {
        return $this->_object->commit();
    }

    /**
     * 回滚事务
     *
     * @return bool
     */
    public function rollback(): bool {
        return $this->_object->rollBack();
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param object $resource 查询返回资源标识
     * @return array
     */
    public function fetch($resource) : array {
        if(($res = $resource->fetch(PDO::FETCH_ASSOC)) === false) {
            return array();
        }
        return $res;
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param object $resource 查询的资源数据
     * @return object
     */
    public function fetchObject($resource) {
        return $resource->fetchObject();
    }

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue(string $string) : string {
        return $this->_object->quote($string);
    }

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string) : string { return ''; }

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql) : string { return ''; }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param object $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle) : int {
        return $resource->rowCount();
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle) : int {
        return $this->_object->lastInsertId();
    }
}
