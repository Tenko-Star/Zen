<?php

/**
 * Class Zen_Json
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_Json{
    /**
     * 编码Json数据
     *
     * @param $data
     * @param bool $assoc
     * @return mixed
     * @throws Zen_Exception
     * @throws Zen_Widget_Exception
     */
    public static function encode($data, bool $assoc = false) {
        if(__ZEN_JSON_SUPPORT__) {
            return json_encode($data, $assoc);
        }else if(!empty(__EXTRA_JSON_ENCODE__)){
            return Zen_Widget::callWidgetFunction(__EXTRA_JSON_ENCODE__, $data);
        }else {
            throw new Zen_Exception('Error: No Json support.', 500);
        }
    }

    /**
     * 解码Json数据
     *
     * @param string $str
     * @return mixed
     * @throws Zen_Exception
     */
    public static function decode(string $str) {
        if(__ZEN_JSON_SUPPORT__) {
            return json_decode($str);
        }else if(!empty(__EXTRA_JSON_DECODE__)) {
            return Zen_Widget::callWidgetFunction(__EXTRA_JSON_DECODE__, [$str]);
        }else {
            throw new Zen_Exception('Error: No Json support.', 500);
        }
    }
}