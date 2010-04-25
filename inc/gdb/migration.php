<?php
namespace gdb;

class Migration
{
    protected $db;
    protected $builder;
    
    public function __construct() {
        $this->db = GDB::instance();
        $this->builder = $this->db->new_schema_builder();
    }
    
    public function up() {
        throw new UnsupportedOperationException;
    }
    
    public function down() {
        throw new UnsupportedOperationException;
    }
    
    //
    //
    
    protected function create_table($name, $options, $block = null) {
        if ($block === null) {
            $block = $options;
            $options = array();
        }
        $table = new TableDefinition($name, $options);
        $block($table);
        $this->schema_builder->create_table($table);
    }
    
    protected function drop_table($name) {
        $this->schema_builder->drop_table($name);
    }
    
    protected function add_column($table, $column_name, $type, $options) {
        $this->schema_builder->add_column($table, $column_name, $type, $options);
    }
    
    protected function remove_column($table, $column_name) {
        $this->schema_builder->remove_column($table, $column_name);
    }
    
    protected function rename_column($table, $existing_column_name, $new_column_name) {
        $this->schema_builder->rename_column($table, $existing_column_name, $new_column_name);
    }
}
?>