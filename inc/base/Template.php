<?php
/**
 * Templating class using raw PHP for templates.
 *
 * I've borrowed some terminology from Rails (layouts, partials, content_for)
 *
 * Features:
 *  * layouts
 *  * eval PHP code or load templates from files
 *  * capture rendered templates or output directly
 *  * before/after filter chains
 *  * capture named blocks for later re-use
 *  * implement custom template lookup in subclasses
 *
 * A possible use-case for before filters is to search for additional stylesheets
 * based on the current URL hierarchy, append their contents to a block then dump
 * it all into a <style></style> tag in the layout.
 *
 * @todo layout rendering
 * @todo filter system
 *
 * @author Jason Frame
 * @package BasePHP
 */
class Template
{
    private static $active      = array();
    
    public static function active() {
        if (($c = count(self::$active)) == 0) {
            return null;
        } else {
            return self::$active[$c - 1];
        }
    }
    
    //
    // Locals
    
    private $__extract_locals   = false;
    
    public function extract_locals($extract = null) {
        if ($extract !== null) $this->__extract_locals = (bool) $extract;
        return $this->__extract_locals;
    }
    
    //
    // Layout handling
    
    private $__layout           = null;
    
    public function layout($layout) {
        $this->__layout = $layout;
    }
    
    //
    // Filters
    
    private $__filters          = array('before' => array(), 'after' => array());
    
    public function before($callback) {
        $this->__filters['before'][] = $callback;
    }
    
    public function after($callback) {
        $this->__filters['after'][] = $callback;
    }
    
    public function run_before_filters() {
        foreach ($this->filters['before'] as $callback) {
            $this->invoke_callback($callback, $this);
        }
    }
    
    public function run_after_filters($content) {
        foreach ($this->filters['after'] as $callback) {
            $content = $this->invoke_callback($callback, $content);
        }
    }
    
    protected function invoke_callback($callback, $argument) {
        // TODO: implement
    }
    
    //
    // Settings handling
    
    private $__settings         = array();
    
    public function has($k) {
        return array_key_exists($this->__settings, $k);
    }
    
    public function is($k, $default = false) {
        return $this->has($k) ? (bool) $this->__settings[$k] : $default;
    }
    
    public function get($k, $default = null) {
        return $this->has($k) ? $this->__settings[$k] : $default;
    }
    
    public function set($k, $v = true) {
        $this->__settings[$k] = $v;
        return $this;
    }
    
    //
    // Content blocks
    
    private $__content          = array();
    private $__active           = array();
    
    public function start($block) {
        $this->__active[] = $block;
        ob_start();
    }
    
    public function end() {
        $this->append(array_pop($this->__active), ob_get_clean());
    }
    
    public function append($block, $content) {
        if (!isset($this->__content[$block])) $this->__content[$block] = '';
        $this->__content[$block] .= $content;
    }
    
    public function has_content($block) {
        return isset($this->__content[$block]);
    }
    
    public function content_for($block) {
        return isset($this->__content[$block]) ? $this->__content[$block] : '';
    }
    
    //
    // Rendering
    
    private $__depth            = 0;
    
    public function render_php($php, $locals = array()) {
        ob_start();
        $this->display_php($php, $locals);
        return ob_get_clean();
    }
    
    public function display_php($__php__, $locals = array()) {
        self::$active[] = $this;
        foreach ($this as $__k__ => $__v__) {
            if (substr($__k__, 0, 2) != '__') $$__k__ = $__v__;
        }
        if ($this->extract_locals()) extract($locals);
        eval("?>$__php__");
        array_pop(self::$active);
    }
    
    public function render_file($file, $locals = array()) {
        ob_start();
        $this->display_file($file, $locals);
        return ob_get_clean();
    }
    
    public function display_file($__file__, $locals = array()) {
        self::$active[] = $this;
        foreach ($this as $__k__ => $__v__){
            if (substr($__k__, 0, 2) != '__') $$__k__ = $__v__;
        }
        if ($this->extract_locals()) extract($locals);
        require $__file__;
        array_pop(self::$active);
    }
    
    public function render_template($template) {
        ob_start();
        $this->display_template($template);
        return ob_get_clean();
    }
    
    public function display_template($__template__) {
        $this->display_file($this->resolve_template_file($__template__));
    }
    
    //
    // Stuff to override
    
    protected function resolve_template_file($template) {
        throw new Exception();
    }
    
    protected function should_apply_filters() {
        return $this->__depth == 1;
    }
}
?>