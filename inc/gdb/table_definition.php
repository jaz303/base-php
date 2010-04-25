<?php
namespace gdb;

class TableDefinition
{
    private $name;
    private $options        = array();
    private $primary_key    = null;
    private $columns        = array();
    
    public function __construct($name, $options = array()) {
        $this->name = $name;
        $this->options = $options + array('no_id' => false);
        if (!$this->options['no_id']) {
            $this->serial('id');
            $this->set_primary_key('id');
        }
    }
    
    public function get_name() { return $this->name; }
    public function get_options() { return $this->options; }
    public function get_primary_key() { return $this->primary_key; }
    public function get_columns() { return $this->columns; }
    
    public function set_primary_key($primary_key) {
        $this->primary_key = $primary_key;
    }
    
    public function column($name, $type, $options) {
        $this->columns[] = array(
            'name'      => $name,
            'type'      => $type,
            'options'   => $options
        );
    }
    
    //
    // Primitives
    
    public function blob($name, $options = array()) {
        $this->column($name, 'blob', $options);
    }
    
    public function boolean($name, $options = array()) {
        $this->column($name, 'boolean', $options);
    }
    
    public function date($name, $options = array()) {
        $this->column($name, 'date', $options);
    }
    
    public function datetime($name, $options = array()) {
        $this->column($name, 'datetime', $options);
    }
    
    public function float($name, $options = array()) {
        $this->column($name, 'float', $options);
    }
    
    public function integer($name, $options = array()) {
        $this->column($name, 'integer', $options);
    }

    public function serial($name, $options = array()) {
        $this->column($name, 'serial', $options);
    }
    
    public function string($name, $options = array()) {
        $this->column($name, 'string', $options);
    }
    
    public function text($name, $options = array()) {
        $this->column($name, 'text', $options);
    }
}
?>