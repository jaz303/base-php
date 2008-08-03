<?php
class Inflector
{
    public static function humanize($string) {
        return ucfirst(strtolower(str_replace(array('_', '-'), ' ', $string)));
    }
    
    /**
     * CamelCase -> camel_case
     * already_underscored -> already_underscored
     */
    public static function underscore($string) {
        return strtolower(preg_replace('/([^^])([A-Z])/e', '"$1" . "_" . "$2"', $string));
    }
    
    /**
     * foo_bar -> FooBar
     * FooBar -> FooBar
     */
    public static function camelize($string) {
        return preg_replace('/(^|_)([a-z])/ie', 'strtoupper("$2")', $string);
    }
}
?>