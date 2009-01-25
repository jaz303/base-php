<?php
class Inflector
{
    public static function pluralize($count, $singular, $plural = null) {
        if ($count == 1) {
            return $singular;
        } else {
            return $plural === null ? ($singular . 's') : $plural;
        }
    }
    
    public static function humanize($string) {
        return ucfirst(strtolower(str_replace(array('_', '-'), ' ', $string)));
    }
    
    /**
     * CamelCase -> camel_case
     * already_underscored -> already_underscored
     */
    public static function underscore($string) {
        return strtolower(preg_replace('/([^^])([A-Z])/e', '\1_\2', $string));
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