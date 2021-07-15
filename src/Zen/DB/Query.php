<?php

/**
 * 数据库请求类
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_DB_Query {
    /**
     * SQL关键字
     */
    private const KEYWORDS = '*PRIMARY|AND|OR|LIKE|BINARY|BY|DISTINCT|AS|IN|IS|NULL';

    const TYPE_SQL          =       0;
    const TYPE_NOSQL        =       1;

    /**
     * @var Zen_DB_Adapter
     */
    private $_adapter;

    /**
     * @var string
     */
    private $_prefix;

    /**
     * @var array
     */
    private $_params = array();

    /**
     * @var int
     */
    private $_type = self::TYPE_SQL;

    /**
     * sql预处理列表
     *
     * @var array
     */
    private $_query_builder = array();

    /**
     * 默认字段表
     *
     * @var array
     */
    private static $_default = array(
        'action'    =>      NULL,
        'table'     =>      NULL,
        'fields'    =>      '*',
        'join'      =>      array(),
        'where'     =>      NULL,
        'limit'     =>      NULL,
        'offset'    =>      NULL,
        'order'     =>      NULL,
        'group'     =>      NULL,
        'having'    =>      NULL,
        'rows'      =>      array()
    );

    /**
     * Zen_DB_Query constructor.
     *
     * @param Zen_DB_Adapter $adapter
     * @param string $prefix
     * @param int $type
     */
    public function __construct(Zen_DB_Adapter $adapter, string $prefix, int $type = self::TYPE_SQL) {
        $this->_prefix = $prefix;
        $this->_adapter = $adapter;
        $this->_type = $type;
        if($type === self::TYPE_SQL){
            $this->_query_builder = self::$_default;
        }
    }

    /**
     * 设置默认参数
     *
     * @param array $default
     */
    public static function setDefault(array $default) {
        self::$_default = array_merge(self::$_default, $default);
    }

    /**
     * 合成最终查询语句
     *
     * @return string
     */
    public function combine() : string {
        $params = $this->_params;
        $adapter = $this->_adapter;
        $query = '';
        switch ($this->_query_builder['action']) {
            case Zen_DB::SELECT:
                $query = $this->_adapter->parseSelect($this->_query_builder);
                break;
            case Zen_DB::INSERT:
                $query = 'INSERT INTO '
                    . $this->_query_builder['table']
                    . '(' . implode(' , ', array_keys($this->_query_builder['rows'])) . ')'
                    . ' VALUES '
                    . '(' . implode(' , ', array_values($this->_query_builder['rows'])) . ')'
                    . $this->_query_builder['limit'];
                break;
            case Zen_DB::DELETE:
                $query = 'DELETE FROM '
                    . $this->_query_builder['table']
                    . $this->_query_builder['where'];
                break;
            case Zen_DB::UPDATE:
                $columns = array();
                if (isset($this->_query_builder['rows'])) {
                    foreach ($this->_query_builder['rows'] as $key => $val) {
                        $columns[] = "$key = $val";
                    }
                }
                $query = 'UPDATE '
                    . $this->_query_builder['table']
                    . ' SET ' . implode(' , ', $columns)
                    . $this->_query_builder['where'];
                break;
            case Zen_DB::CACHE:
            case Zen_DB::NOSQL:
                return '';
            default:
        }

        return preg_replace_callback("/#param:([0-9]+)#/", function ($matches) use ($params, $adapter) {

            if (array_key_exists($matches[1], $params)) {
                return $adapter->quoteValue($params[$matches[1]]);
            } else {
                return $matches[0];
            }
        }, $query) . ';';
    }

    /**
     * 选择查询字段
     *
     * @param string $field
     * @return Zen_DB_Query
     */
    public function select(string $field = '*') : Zen_DB_Query{
        $this->_query_builder['action'] = Zen_DB::SELECT;
        $args = func_get_args();

        $this->_query_builder['fields'] = $this->getColumnFromParameters($args);
        return $this;
    }

    /**
     * 确定查询表项
     *
     * @param string $table
     * @return Zen_DB_Query
     */
    public function from(string $table) : Zen_DB_Query {
        $this->_query_builder['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 更新记录操作(UPDATE)
     *
     * @param string $table 需要更新记录的表
     * @return Zen_DB_Query
     */
    public function update(string $table) : Zen_DB_Query {
        $this->_query_builder['action'] = Zen_DB::UPDATE;
        $this->_query_builder['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 删除记录操作(DELETE)
     *
     * @param string $table 需要删除记录的表
     * @return Zen_DB_Query
     */
    public function delete(string $table) : Zen_DB_Query {
        $this->_query_builder['action'] = Zen_DB::DELETE;
        $this->_query_builder['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 插入记录操作(INSERT)
     *
     * @param string $table 需要插入记录的表
     * @return Zen_DB_Query
     */
    public function insert(string  $table) : Zen_DB_Query {
        $this->_query_builder['action'] = Zen_DB::INSERT;
        $this->_query_builder['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 连接表
     *
     * @param string $table 需要连接的表
     * @param string $condition 连接条件
     * @param string $op 连接方法(LEFT, RIGHT, INNER)
     * @return Zen_DB_Query
     */
    public function join(string $table, string $condition, string $op = Zen_DB::INNER_JOIN) : Zen_DB_Query {
        $this->_query_builder['join'][] = array($this->filterPrefix($table), $this->filterColumn($condition), $op);
        return $this;
    }

    /**
     * AND条件查询语句
     *
     * @param string $condition 格式化字符串
     * @param mixed 参数
     * @return Zen_DB_Query
     */
    public function where(string $condition) : Zen_DB_Query {
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_query_builder['where']) ? ' WHERE ' : ' AND';

        if (func_num_args() <= 1) {
            $this->_query_builder['where'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_query_builder['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * OR条件查询语句
     *
     * @param string $condition 格式化字符串
     * @param mixed 参数
     * @return Zen_DB_Query
     */
    public function orWhere(string $condition) : Zen_DB_Query {
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_query_builder['where']) ? ' WHERE ' : ' OR';

        if (func_num_args() <= 1) {
            $this->_query_builder['where'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_query_builder['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 查询行数限制
     *
     * @param integer $limit 需要查询的行数
     * @return Zen_DB_Query
     */
    public function limit(int $limit) : Zen_DB_Query {
        $this->_query_builder['limit'] = intval($limit);
        return $this;
    }

    /**
     * 查询行数偏移量
     *
     * @param integer $offset 需要偏移的行数
     * @return Zen_DB_Query
     */
    public function offset(int $offset) : Zen_DB_Query {
        $this->_query_builder['offset'] = intval($offset);
        return $this;
    }

    /**
     * 分页查询
     *
     * @param integer $page 页数
     * @param integer $page_size 每页行数
     * @return Zen_DB_Query
     */
    public function page(int $page, int $page_size) : Zen_DB_Query {
        $page_size = intval($page_size);
        $this->_query_builder['limit'] = $page_size;
        $this->_query_builder['offset'] = (max(intval($page), 1) - 1) * $page_size;
        return $this;
    }

    /**
     * 指定需要写入的字段和数值
     *
     * @param array $rows
     * @return Zen_DB_Query
     */
    public function rows(array $rows) : Zen_DB_Query {
        foreach ($rows as $key => $row) {
            $this->_query_builder['rows'][$this->filterColumn($key)] = is_null($row) ? 'NULL' : $this->_adapter->quoteValue($row);
        }
        return $this;
    }

    /**
     * 排序顺序
     *
     * @param string $order 排序的索引
     * @param string $sort 排序的方式(ASC, DESC)
     * @return Zen_DB_Query
     */
    public function order(string $order, string $sort = Zen_DB::SORT_ASC) : Zen_DB_Query {
        if (empty($this->_query_builder['order'])) {
            $this->_query_builder['order'] = ' ORDER BY ';
        } else {
            $this->_query_builder['order'] .= ', ';
        }

        $this->_query_builder['order'] .= $this->filterColumn($order) . (empty($sort) ? NULL : ' ' . $sort);
        return $this;
    }

    /**
     * 集合聚集
     *
     * @param string $key 聚集的键值
     * @return Zen_DB_Query
     */
    public function group(string $key) : Zen_DB_Query {
        $this->_query_builder['group'] = ' GROUP BY ' . $this->filterColumn($key);
        return $this;
    }

    /**
     * HAVING
     *
     * @param string $condition
     * @return Zen_DB_Query
     */
    public function having(string $condition) : Zen_DB_Query {
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_query_builder['having']) ? ' HAVING ' : ' AND';

        if (func_num_args() <= 1) {
            $this->_query_builder['having'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_query_builder['having'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 转换为sql字符串
     *
     * @return string | null
     */
    public function __toString() : ?string {
//        switch ($this->_query_builder['action']) {
//            case Zen_DB::SELECT:
//                return $this->_adapter->parseSelect($this->_query_builder);
//            case Zen_DB::INSERT:
//                return 'INSERT INTO '
//                    . $this->_query_builder['table']
//                    . '(' . implode(' , ', array_keys($this->_query_builder['rows'])) . ')'
//                    . ' VALUES '
//                    . '(' . implode(' , ', array_values($this->_query_builder['rows'])) . ')'
//                    . $this->_query_builder['limit'];
//            case Zen_DB::DELETE:
//                return 'DELETE FROM '
//                    . $this->_query_builder['table']
//                    . $this->_query_builder['where'];
//            case Zen_DB::UPDATE:
//                $columns = array();
//                if (isset($this->_query_builder['rows'])) {
//                    foreach ($this->_query_builder['rows'] as $key => $val) {
//                        $columns[] = "$key = $val";
//                    }
//                }
//                return 'UPDATE '
//                    . $this->_query_builder['table']
//                    . ' SET ' . implode(' , ', $columns)
//                    . $this->_query_builder['where'];
//            default:
//                return NULL;
//        }
        return '';
    }

    /**
     * 从参数中合成查询字段
     *
     * @access private
     * @param array $parameters
     * @return string
     */
    private function getColumnFromParameters(array $parameters) : string {
        $fields = array();

        foreach ($parameters as $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $fields[] = $key . ' AS ' . $val;
                }
            } else {
                $fields[] = $value;
            }
        }

        return $this->filterColumn(implode(' , ', $fields));
    }

    /**
     * 过滤数组键值
     *
     * @access private
     * @param string $str 待处理字段值
     * @return string
     */
    private function filterColumn(string $str) : string{
        $str = $str . ' 0';
        $length = strlen($str);
        $lastIsAlnum = false;
        $result = '';
        $word = '';
        $split = '';
        $quotes = 0;

        for ($i = 0; $i < $length; $i ++) {
            $cha = $str[$i];

            if (ctype_alnum($cha) || false !== strpos('_*', $cha)) {
                if (!$lastIsAlnum) {
                    if ($quotes > 0 && !ctype_digit($word) && '.' != $split
                        && false === strpos(self::KEYWORDS, strtoupper($word))) {
                        $word = $this->_adapter->quoteColumn($word);
                    } else if ('.' == $split && 'table' == $word) {
                        $word = $this->_prefix;
                        $split = '';
                    }

                    $result .= $word . $split;
                    $word = '';
                    $quotes = 0;
                }

                $word .= $cha;
                $lastIsAlnum = true;
            } else {

                if ($lastIsAlnum) {

                    if (0 == $quotes) {
                        if (false !== strpos(' ,)=<>.+-*/', $cha)) {
                            $quotes = 1;
                        } else if ('(' == $cha) {
                            $quotes = -1;
                        }
                    }

                    $split = '';
                }

                $split .= $cha;
                $lastIsAlnum = false;
            }

        }

        return $result;
    }

    /**
     * 过滤表前缀,表前缀由table.构成
     *
     * @param string $string 需要解析的字符串
     * @return string
     */
    private function filterPrefix(string $string) : string {
        return (0 === strpos($string, 'table.')) ? substr_replace($string, $this->_prefix, 0, 6) : $string;
    }

    /**
     * 转义参数
     *
     * @param array $values
     * @return array
     */
    private function quoteValues(array $values): array {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = '(' . implode(',', array_map(array($this, 'quoteValue'), $value)) . ')';
            } else {
                $value = $this->quoteValue($value);
            }
        }

        return $values;
    }

    /**
     * 延迟转义
     *
     * @param $value
     * @return string
     */
    private function quoteValue($value): string {
        $this->_params[] = $value;
        return '#param:' . (count($this->_params) - 1) . '#';
    }

    /**
     * 获取查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return string | NULL
     */
    public function getAttribute(string $attributeName) : ?string {
        return $this->_query_builder[$attributeName] ?? NULL;
    }

    /**
     * 清除查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return Zen_DB_Query
     */
    public function cleanAttribute(string $attributeName) : Zen_DB_Query {
        if (isset($this->_query_builder[$attributeName])) {
            $this->_query_builder[$attributeName] = self::$_default[$attributeName];
        }
        return $this;
    }

    /**
     * 设置一个缓存条目
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return Zen_DB_Query
     */
    public function set(string $key, $value, int $expire = 0) : Zen_DB_Query{
        if($this->_type !== self::TYPE_NOSQL) {
            return $this;
        }

        $this->_query_builder['action'] = Zen_DB::SET;
        $this->_query_builder['key'] = $key;
        $this->_query_builder['value'] = $value;
        $this->_query_builder['$expire'] = $expire;

        return $this;
    }

    /**
     * 读取一个缓存条目
     *
     * @param string $key
     * @return Zen_DB_Query
     */
    public function get(string $key): Zen_DB_Query{
        if($this->_type !== self::TYPE_NOSQL) {
            return $this;
        }

        $this->_query_builder['action'] = Zen_DB::GET;
        $this->_query_builder['key'] = $key;

        return $this;
    }
}