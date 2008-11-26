<?php
/**
 * Lightweight templating class using raw PHP for templates.
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
 * @author Jason Frame
 * @package BasePHP
 */
class Template
{
    private static $active      = array();
    
    /**
     * Returns a reference to the currently rendering template instance.
     * Use this method in the unlikely event you're rendering more than
     * one template at once and you need to access the active template
     * from outwith its scope.
     *
     * @return the currently active template
     */
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
    
    /**
     * Sets whether or not to extract local variables. Defaults to false.
     * If locals are not extracted, they will be available in templates via
     * the <var>$locals</var> array.
     *
     * @param $extract no-op if null. Otherwise set whether to extract locals.
     * @return new setting of extract_locals()
     */
    public function extract_locals($extract = null) {
        if ($extract !== null) $this->__extract_locals = (bool) $extract;
        return $this->__extract_locals;
    }
    
    //
    // Layout handling
    
    private $__layout           = null;
    
    /**
     * Sets the layout used to wrap output from the <var>render_page()</var> and
     * <var>display_page()</var> methods.
     *
     * @param $layout new layout
     */
    public function layout($layout) {
        $this->__layout = $layout;
    }
    
    //
    // Filters
    
    private $__filters          = array('before' => array(), 'after' => array());
    
    public function before($arg1, $arg2 = null) {
        $this->__filters['before'][] = Callback::create($arg1, $arg2);
    }
    
    public function after($arg1, $arg2 = null) {
        $this->__filters['after'][] = Callback::create($arg1, $arg2);
    }
    
    protected function before_filter() {}
    protected function after_filter($content) { return $content; }
    
    protected function run_before_filters() {
        $this->before_filter();
        foreach ($this->__filters['before'] as $callback) {
            $callback($this);
        }
    }
    
    protected function run_after_filters($content) {
        foreach ($this->__filters['after'] as $callback) {
            $content = $callback($content);
        }
        $content = $this->after_filter($content);
        return $content;
    }
    
    //
    // Settings handling
    
    private $__settings         = array();
    
    /**
     * Returns <var>true</var> if a setting with key <var>$k</var> exists.
     *
     * @return true if a setting with key <var>$k</var> exists
     */
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
    
    private $__performed        = false;
    private $__page             = null;
    
    /** 
     * Renders (returns as a string) the output of some arbitrary HTML/PHP code.
     * Unlike calls to <var>eval()</var>, PHP code should be enclosed within
     * PHP start/end delimiters.
     *
     * @param $__php__ PHP/HTML code to eval and render
     * @param $locals array of local variables to be assigned to template
     */
    public function render_php($__php__, $locals = array()) {
        
        ob_start();
        self::$active[] = $this;
        
        foreach ($this as $__k__ => $__v__) {
            if (substr($__k__, 0, 2) != '__') $$__k__ = $__v__;
        }
        if ($this->extract_locals()) extract($locals);
        
        eval("?>$__php__");
        
        array_pop(self::$active);
        $output = ob_get_clean();
        
        return $output;
    }

    /** 
     * Displays (echoes) the output of some arbitrary HTML/PHP code.
     * Unlike calls to <var>eval()</var>, PHP code should be enclosed within
     * PHP start/end delimiters.
     *
     * @param $__php__ PHP/HTML code to eval and display
     * @param $locals array of local variables to be assigned to template
     */    
    public function display_php($php, $locals = array()) {
        echo $this->render_php($php, $locals);
    }
    
    public function render_file($__file__, $locals = array()) {
        
        ob_start();
        self::$active[] = $this;
        
        foreach ($this as $__k__ => $__v__){
            if (substr($__k__, 0, 2) != '__') $$__k__ = $__v__;
        }
        if ($this->extract_locals()) extract($locals);
        
        require $__file__;
        
        array_pop(self::$active);
        $output = ob_get_clean();
        
        return $output;
        
    }
    
    public function display_file($file, $locals = array()) {
        echo $this->display_file($file, $locals);
    }
    
    /**
     * Renders (returns as a string) a template. This method is identical to
     * <var>reneder_file()</var> except <var>$template</var> will first be
     * translated to a file path.
     *
     * @param $template template reference to render
     * @param $locals local variables to be assigned to template
     */
    public function render_template($template, $locals = array()) {
        return $this->render_file($this->resolve_template_path($template), $locals);
    }
    
    /**
     * Displays (echoes) a template. This method is identical to <var>display_file()</var>
     * except <var>$template</var> will first be translated to a file path.
     *
     * @param $template template reference to render
     * @param $locals local variables to be assigned to template
     */
    public function display_template($template, $locals = array()) {
        echo $this->render_template($template, $locals);
    }
    
    /**
     * Renders (returns as a string) a page. The specified page will be translated
     * to a template path as per <var>render_template()</var>. In addition, filters
     * will be applied to the template and its output, and the output will be
     * wrapped in a layout, if set.
     *
     * @param $page page reference to be displayed
     * @return rendered page
     */
    public function render_page($page = null) {
        
        if ($this->performed()) {
            throw new Error_IllegalState("Templates can only render pages once!");
        }
        
        $this->__page = $this->resolve_template_path($page);
        $this->run_before_filters();
        
        $output = $this->render_file($this->__page);
        
        if ($this->__layout) {
            $this->content_for_layout = $output;
            $output = $this->render_file($this->resolve_layout_path($this->__layout));
        }
        
        $output = $this->run_after_filters($output);
        
        return $output;
    
    }
    
    /** 
    * Displays (echoes) a page. The specified page will be translated
    * to a template path as per <var>display_template()</var>. In addition, filters
    * will be applied to the template and its output, and the output will be
    * wrapped in a layout, if set.
     *
     * @param $page page reference to be displayed
     */
    public function display_page($page = null) {
        echo $this->render_page($page);
    }
    
    /**
     * Returns the page that was rendered.
     *
     * @return the page that was rendered
     */
    public function page() {
        return $this->__page;
    }
    
    /**
     * Indicate that this template has performed without actually rendering
     * anything.
     */
    public function no_op() {
        $this->__page = true;
    }
    
    /**
     * Returns true if this template has rendered a page, false otherwise.
     *
     * @return true if this template has rendered a page, false otherwise
     */
    public function performed() {
        return $this->__page !== null;
    }
    
    //
    // Stuff to override
    
    /**
     * Translates an application specific template reference - which can be
     * any primitive/object - to an absolute file path to the template.
     *
     * @param $template template reference to translate
     * @return filesystem path to PHP template
     */
    protected function resolve_template_path($template) {
        return $template;
    }

    /**
     * Translates an application specific layout reference - which can be
     * any primitive/object - to an absolute file path to the layout.
     *
     * @param $template layout reference to translate
     * @return filesystem path to PHP template
     */
    protected function resolve_layout_path($template) {
        return $this->resolve_template_path($template);
    }
}
?>