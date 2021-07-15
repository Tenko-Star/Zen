<?php

/**
 * Class Zen_Render
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Render {
    /**
     * string map
     *
     * @var array
     */
    private $_map = array();

    private $_delimiter = ':';

    private static $_instances = array();

    /**
     * Print string by format.
     *
     * @param string $format
     */
    public function printVar(string $format) {
        $map = $this->_map;
        echo preg_replace_callback("/{(\w*)}/", function($matches) use ($map) {
            if(isset($map[$matches[1]])) {
                if(is_array($map[$matches[1]])) {
                    $ret = '<p>';
                    foreach ($map[$matches[1]] as $key => $value) {
                        $ret .= "<span class=\"zen-key\">" . $key . "</span>";
                        $ret .= "<span class=\"zen-colon\">" . $this->_delimiter. "</span>";
                        $ret .= "<span class=\"zen-value\">" . $value . "</span>";
                        $ret .= "<br>";
                    }
                    $ret .= '</p>';
                }else {
                    $ret = $map[$matches[1]];
                }
                return $ret;
            }

            return '';
        }, $format);
    }

    /**
     * Set var to string mapper.
     *
     * @param array | string
     *      If a array is passed in, the key-value pair in this array will be added.
     *      Multiple parameters can be passed in.
     * @throws Zen_Widget_Exception
     */
    public function setVar(string $name, $value) {
        if(__ZEN_SECURITY__) {
            $value = Zen_Security::removeXSS($value);
        }elseif(!empty(__EXTRA_REMOVE_XSS__)) {
            $value = Zen_Widget::callWidgetFunction(__EXTRA_REMOVE_XSS__, $value);
        }
        $this->_map[$name] = $value;
    }

    /**
     * Set an array to string mapper.
     *
     * @param string $name
     * @param array $values
     * @throws Zen_Widget_Exception
     */
    public function setArray(string $name, array $values) {
        $result = array();
        foreach ($values as $key => $value) {
            if(__ZEN_SECURITY__) {
                $key = Zen_Security::removeXSS($key);
                $value = Zen_Security::removeXSS($value);
            }elseif(!empty(__EXTRA_REMOVE_XSS__)) {
                $key = Zen_Widget::callWidgetFunction(__EXTRA_REMOVE_XSS__, [$key]);
                $value = Zen_Widget::callWidgetFunction(__EXTRA_REMOVE_XSS__, [$value]);
            }
            $result[$key] = $value;
        }
        $this->_map[$name] = $result;
    }

    public function setDelimiter(string $delimiter) {
        $this->_delimiter = str_replace(" ", "&nbsp", $delimiter);
    }

    /**
     * bind render class and page file.
     *
     * @param string $handle
     * @return Zen_Render
     *      use this object to set and print
     */
    public static function bind(string $handle) : Zen_Render {
        return self::$_instances[$handle] ?? (self::$_instances[$handle] = new Zen_Render());
    }

    /**
     * Zen_Render constructor.
     */
    private function __construct() { }

    /**
     * Magic function invoke
     *
     * @param string $format
     */
    public function __invoke(string $format) {
        $this->printVar($format);
    }

    /**
     * Magic Function set
     *
     * @param $name
     * @param $value
     * @throws Zen_Render_Exception
     */
    public function __set($name, $value) {
        try{
            if(is_array($value)) {
                if($this->isMultiDimensionalArray($value)){
                    $this->setVar($name, Zen_Json::encode($value));
                }else {
                    $this->setArray($name, $value);
                }
            }else {
                $this->setVar($name, $value);
            }
        }catch (Zen_Widget_Exception $e) {
            throw new Zen_Render_Exception("Widget: " . $e->getMessage());
        }catch (Zen_Exception $e) {
            throw new Zen_Render_Exception($e->getMessage(), HTTP_SERVER_ERROR);
        }
    }

    /**
     * 判断多维数组
     *
     * @param array $arr
     * @return bool
     */
    private function isMultiDimensionalArray(array $arr) : bool{
        if(count($arr) === count($arr, COUNT_RECURSIVE)){
            return false;
        }else {
            return true;
        }
    }

}