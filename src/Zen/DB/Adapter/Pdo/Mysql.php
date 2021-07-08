<?php

/**
 * Pdo_Mysql适配器
 *
 */
class Zen_DB_Adapter_Pdo_Mysql extends Zen_DB_Adapter_Pdo
{
    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable() : bool {
        return parent::isAvailable() && in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * 初始化数据库
     *
     * @param Zen_Config $config 数据库配置
     * @access public
     * @return PDO
     */
    public function init(Zen_Config $config) : PDO {
        $pdo = new PDO(!empty($config->dsn) ? $config->dsn :
            "mysql:dbname={$config->database};host={$config->host};port={$config->port}", $config->user, $config->password);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $charset = empty($config->charset) ? 'utf-8' : $config->charset;
        $pdo->exec("SET NAMES '{$charset}'");
        return $pdo;
    }

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string) : string {
        return '`' . $string . '`';
    }

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue(string $string) : string {
        return '\'' . str_replace(array('\'', '\\'), array('\'\'', '\\\\'), $string) . '\'';
    }

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql) : string {
        if (!empty($sql['join'])) {
            foreach ($sql['join'] as $val) {
                list($table, $condition, $op) = $val;
                $sql['table'] = "{$sql['table']} {$op} JOIN {$table} ON {$condition}";
            }
        }

        $sql['limit'] = (0 == strlen($sql['limit'])) ? NULL : ' LIMIT ' . $sql['limit'];
        $sql['offset'] = (0 == strlen($sql['offset'])) ? NULL : ' OFFSET ' . $sql['offset'];

        return 'SELECT ' . $sql['fields'] . ' FROM ' . $sql['table'] .
            $sql['where'] . $sql['group'] . $sql['having'] . $sql['order'] . $sql['limit'] . $sql['offset'];
    }
}
