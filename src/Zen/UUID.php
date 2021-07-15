<?php

/**
 * Class Zen_UUID
 *
 * @author Tenko-Star
 * @license GNU Lesser General Public License 2.1
 */
class Zen_UUID {
    /**
     * UUID生成
     *
     * @param string $prefix 前缀
     * @return string
     */
    public static function create(string $prefix = '')  : string {
        if(empty(__EXTRA_UUID_SUPPORT__)) {
            return self::uuid($prefix);
        }else {
            try{
                return Zen_Widget::callWidgetFunction(__EXTRA_UUID_SUPPORT__);
            }catch (Zen_Widget_Exception $e) {
                return self::uuid($prefix);
            }

        }
    }

    /**
     * UUID generator
     *
     * @param string $prefix 前缀
     * @return string
     */
    private static function uuid(string $prefix) : string {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $prefix.$uuid ;
    }
}