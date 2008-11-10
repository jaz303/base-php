<?php
class Arrays
{
    private static $sort_key = null;
    
    public static function kvsort(&$array, $key) {
        self::$sort_key = $key;
        usort($array, array('Arrays', 'key_compare'));
    }
    
    public static function kvrsort(&$array, $key) {
        self::$sort_key = $key;
        usort($array, array('Arrays', 'key_compare_rev'));
    }
    
    private static function key_compare($l, $r) {
        return $l[self::$sort_key] - $r[self::$sort_key];
    }
    
    private static function key_compare_rev($l, $r) {
        return $r[self::$sort_key] - $l[self::$sort_key];
    }
}
?>