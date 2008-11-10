<?php
/**
 * @package BasePHP
 * @author Jason Frame
 */
class Arrays
{
    /**
     * Returns a random element for an array.
     *
     * @param $array array from which to return random element.
     * @param $if_empty value to return if $array is empty.
     * @return a random element from $array, or $default if $array is empty
     */
    public static function random($array, $if_empty = null) {
        return count($array) ? $array[array_rand($array)] : $if_empty;
    }
    
    private static $sort_key = null;
    
    /**
     * Sort an array of arrays by comparing values found at a specific index.
     *
     * @param $array array to sort
     * @param $key key from which to retrieve sort value
     * @param true on success, false on failure
     */
    public static function kvsort(&$array, $key) {
        self::$sort_key = $key;
        return usort($array, array('Arrays', 'key_compare'));
    }
    
    /**
     * Sort an array of arrays in reverse order by comparing values found at a
     * specific index.
     *
     * @param $array array to sort in reverse order
     * @param $key key from which to retrieve sort value
     * @param true on success, false on failure
     */
    public static function kvrsort(&$array, $key) {
        self::$sort_key = $key;
        return usort($array, array('Arrays', 'key_compare_rev'));
    }
    
    private static function key_compare($l, $r) {
        return $l[self::$sort_key] - $r[self::$sort_key];
    }
    
    private static function key_compare_rev($l, $r) {
        return $r[self::$sort_key] - $l[self::$sort_key];
    }
}
?>