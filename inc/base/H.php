<?php
class H
{
    public static function q($html) {
        return htmlentities($html, ENT_QUOTES);
    }
    
    /**
     * Breaks a simple selector down to an array of ID and class name
     *
     * For example: #foo.bar.baz => array('id' => 'foo', 'class' => 'bar baz')
     *
     * @param $selector selector to parse
     * @return array containing string elements for ID and classname(s), or null if not present.
     */
    public static function parse_selector($selector) {
        
        $out = array('id' => null, 'class' => null);
        
        if (preg_match('/^(#([\w-]+))?((\.[\w-]+)+)?$/', $selector, $matches)) {
            if (!empty($matches[2])) $out['id'] = $matches[2];
            if (!empty($matches[3])) $out['class'] = str_replace('.', ' ', substr($matches[3], 1));
        }
        
        return $out;
        
    }
    
    public static function tag($name, $content = '', $attributes = array()) {
        if (is_array($content)) {
            $attributes = $content;
            $content = '';
        }
        return self::start_tag($name, $attributes) . $content . self::end_tag($name);
    }
    
    public static function start_tag($name, $attributes = array()) {
        return "<{$name}" . self::tag_attributes($attributes) . ">";
    }
    
    public static function end_tag($name) {
        return "</{$name}>";
    }
    
    public static function empty_tag($name, $attributes = array()) {
        return "<{$name}" . self::tag_attributes($attributes) . " />";
    }
    
    public static function tag_attributes($array) {
        $out = '';
        foreach ($array as $k => $v) $out .= " $k=\"" . self::q($v) . '"';
        return $out;
    }
}
?>