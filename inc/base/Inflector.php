<?php
class Inflector
{
    public static function humanise($string) {
        return ucfirst(strtolower(str_replace(array('_', '-'), ' ', $string)));
    }
    
    /**
     * CamelCase -> camel_case
     * already_underscored -> already_underscored
     */
    public static function underscore($string) {
        return strtolower(preg_replace_callback('/([^^])([A-Z])/', array('Inflector', 'underscore_callback'), $string));
    }
    
    /**
     * foo_bar -> FooBar
     * FooBar -> FooBar
     */
    public static function camelize($string) {
        return preg_replace_callback('/(^|_)([a-z])/', array('Inflector', 'camelize_callback'), $string);
    }
  
    private static function underscore_callback($m) {
        return $m[1] . '_' . $m[2];
    }
    
    private static function camelize_callback($m) {
        return strtoupper($m[2]);
    }
}
?>