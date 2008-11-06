<?php
/**
 * Basic Helper Class
 * 
 * Features static methods for:
 *
 * 1. HTML quoting
 * 2. Tag generation
 * 3. Parsing simple selectors ("#foo.bar.baz") into ID/class
 *
 * @package BasePHP
 * @author Jason Frame
 */
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

/**
 * HTML Table Class
 * 
 * Usage:
 * $table = new H_Table('#my-table.bar');
 * $table->cycle('foo', 'bar', 'baz')
 *       ->add($my_row_1)
 *       ->add($my_row_2)
 *       ->add($my_row_3)
 *       ->if_empty("No rows found", ".empty");
 * echo $table->to_html();
 *
 * @package BasePHP
 * @author Jason Frame
 */
class H_Table
{
    private $table_attribs  = array();
    private $cycle          = null;
    
    private $pad            = null;
    private $pad_attribs    = array();
    
    private $caption        = null;
    
    private $column_keys    = null;
    private $column_headers = null;
    private $data           = array();
    
    private $empty_message  = null;
    private $empty_attribs  = array();
    
    public function __construct($selector = '') {
        $this->table_attribs = H::parse_selector($selector);
    }
    
    public function add_class($class) {
        $this->table_attribs['class'] .= " $class";
        return $this;
    }
    
    public function set_id($id) {
        $this->table_attribs['id'] = $id;
        return $this;
    }
    
    public function if_empty($message, $attribs = '') {
        $this->empty_message    = $message;
        $this->empty_attribs    = H::parse_selector($attribs);
        return $this;
    }
    
    public function cycle($cycle) {
        if (func_num_args() > 1) {
            $this->cycle = func_get_args();
        } else {
            $this->cycle = $cycle;
        }
        return $this;
    }
    
    public function pad($pad, $attribs = '') {
        $this->pad          = $pad;
        $this->pad_attribs  = H::parse_selector($attribs);
        return $this;
    }
    
    public function caption($caption) {
        $this->caption = $caption;
        return $this;
    }
    
    public function column_headers($headers) {
        $this->column_keys = null;
        if (!is_array($headers)) {
            $this->column_headers = func_get_args();
        } else {
            $this->column_headers = $headers;
        }
        return $this;
    }
    
    public function columns($columns) {
        $this->column_keys = array_keys($columns);
        $this->column_headers = array_values($columns);
        return $this;
    }
    
    public function add($row) {
        $this->data[] = $row;
        return $this;
    }
    
    public function to_html() {
        
        $cols  = $this->column_count();
        
        $html  = H::start_tag('table', $this->table_attribs);
        $html .= $this->render_header();
        
        if ($this->row_count() == 0) {
            if ($this->empty_message !== null) {
                $html .= H::start_tag('tr', $this->empty_attribs);
                $html .= H::tag('td', $this->empty_message, array('colspan' => $cols));
                $html .= H::end_tag('tr');
            }
        } else {
            
            $i = 0;
            foreach ($this->data as $row) {

                $row_attribs = array();
                if ($this->cycle) {
                    $row_attribs['class'] = $this->cycle[$i % count($this->cycle)];
                }

                $html .= H::start_tag('tr', $row_attribs);
                
                if ($this->column_keys) {
                    foreach ($this->column_keys as $k) {
                        $html .= H::tag('td', $row[$k]);
                    }
                } else {
                    foreach ($row as $cell) {
                        $html .= H::tag('td', $cell);
                    }
                }
                
                $html .= H::end_tag('tr');

                $i++;

            }
            
            if ($this->pad !== null) {
                while ($i < $this->pad) {
                    $html .= H::tag('tr', H::tag('td', '&nbsp;', array('colspan' => $cols)), $this->pad_attribs);
                    $i++;
                }
            }
            
        }
        
        $html .= $this->render_footer();
        $html .= H::end_tag('table');
        
        return $html;
        
    }
    
    public function column_count() {
        if ($this->column_headers === null) {
            return count($this->data[0]);
        } else {
            return count($this->column_headers);
        }
    }
    
    public function row_count() {
        return count($this->data);
    }
    
    protected function render_header() {
      
        $html = '';
      
        if ($this->caption !== null) {
            $html .= H::tag('caption', $this->caption);
        }
        
        if ($this->column_headers !== null) {
            $html .= "<thead><tr>";
            foreach ($this->column_headers as $title) {
                $html .= H::tag('th', $title);
            }
            $html .= "</tr></thead>";
        }
        
        return $html;
        
    }
    
    protected function render_footer() {
        return '';
    }
}
?>