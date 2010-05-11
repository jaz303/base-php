<?php
namespace gdb;

class SchemaBuilder
{
    private $db;
    
    public function __construct(\GDB $db) {
        $this->db = $db;
    }
    
    public function create_table(TableDefinition $table) {
        $this->db->x($this->sql_for_table($table));
    }
    
    public function sql_for_table(TableDefinition $table) {
        
        $sql  = "CREATE TABLE " . $this->db->quote_ident($table->get_name()) . "\n";
        $sql .= "(\n";
        
        $chunks = array();
        foreach ($table->get_columns() as $column) {
            $chunks[] = $this->column_definition($column['name'],
                                                $column['type'],
                                                $column['options']);
        }
        
        if ($pk = $table->get_primary_key()) {
            $chunks[] = "PRIMARY KEY (" . implode(', ', array_map(array($this->db, 'quote_ident'), (array) $pk)) . ")";
        }
        
        $sql .= "  " . implode(",\n  ", $chunks);
        $sql .= "\n)\n";
        
        $raw_options = $table->get_options();
        $table_options = array();
        
        if (isset($raw_options['mysql.engine'])) {
            $table_options[] = 'ENGINE = ' . $raw_options['mysql.engine'] . "\n";
        }
        
        $sql .= implode(', ', $table_options);
        
        return $sql;
        
    }
    
    public function drop_table($table_name) {
        $this->db->x("DROP TABLE " . $this->db->quote_ident($table_name));
    }
    
    public function add_column($table, $column_name, $type, $options = array()) {
        $def = $this->column_definition($column_name, $type, $options);
        $this->db->x("ALTER TABLE " . $this->db->quote_ident($table) . " ADD COLUMN $def");
    }
    
    public function remove_column($table, $column_name) {
        $this->db->x("ALTER TABLE " . $this->db->quote_ident($table) . "
                      DROP COLUMN " . $this->db->quote_ident($column_name));
    }
    
    public function rename_column($table, $existing_column_name, $new_column_name) {
        // TODO
    }
    
    public function add_index($table, $column_names, $options = array()) {
        $sql = 'CREATE';
        if (@$options['unique'])    $sql .= ' UNIQUE';
        if (@$options['fulltext'])  $sql .= ' FULLTEXT';
        if (@$options['spatial'])   $sql .= ' SPATIAL';
        $sql .= ' INDEX ';
        
        if (!isset($options['name'])) {
            $cols = (array) $column_names;
            sort($cols);
            $options['name'] = implode('_', $cols) . '_index';
        }
        
        $sql .= $this->db->quote_ident($options['name']);
        
        $sql .= ' ON ' . $this->db->quote_ident($table);
        $sql .= ' (' . implode(', ', array_map(array($this->db, 'quote_ident'), (array) $column_names)) . ')';
        
        $this->db->x($sql);
    }
    
    public function remove_index($table, $index_name) {
        $sql = 'DROP INDEX ' . $this->db->quote_ident($index_name) . ' ON ' . $this->db->quote_ident($table);
        $this->db->x($sql);
    }
    
    protected function column_definition($name, $type, $options) {
        return $this->db->quote_ident($name) . ' ' . $this->map_native_type($type, $options);
    }
    
    protected function map_native_type($type, $options) {
        switch ($type) {
            case 'blob':        return $this->map_blob($options);
            case 'boolean':     return $this->map_boolean($options);
            case 'date':        return $this->map_date($options);
            case 'datetime':    return $this->map_datetime($options);
            case 'float':       return $this->map_float($options);
            case 'integer':     return $this->map_integer($options);
            case 'serial':      return $this->map_serial($options);
            case 'string':      return $this->map_string($options);
            case 'text':        return $this->map_text($options);
            default:            throw new IllegalArgumentException("unknown column type - $type");
        }
    }
    
    protected function map_blob($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYBLOB'; break;
            case 'default': $type = 'BOLB'; break;
            case 'medium':  $type = 'MEDIUMBLOB'; break;
            case 'long':    $type = 'LONGBLOB'; break;
            default:        throw new IllegalArgumentException("unknown MySQL size for blob column");
        }
        return $type . $this->default_options('text', $options);
    }
    
    protected function map_boolean($options) {
        return 'TINYINT(1)' . $this->default_options('boolean', $options);
    }
    
    protected function map_date($options) {
        return 'DATE' . $this->default_options('date', $options);
    }
    
    protected function map_datetime($options) {
        return 'DATETIME' . $this->default_options('datetime', $options);
    }
    
    protected function map_float($options) {
        return 'FLOAT' . $this->default_options('float', $options);
    }
    
    protected function map_integer($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYINT'; break;
            case 'small':   $type = 'SMALLINT'; break;
            case 'medium':  $type = 'MEDIUMINT'; break;
            case 'default': $type = 'INT'; break;
            case 'big':     $type = 'BIGINT'; break;
            default:        throw new IllegalArgumentException("unknown MySQL size for int column");
        }
        if (isset($options['limit'])) {
            $type .= '(' . $options['limit'] . ')';
        }
        return $type . $this->default_options('integer', $options);
    }
    
    protected function map_serial($options) {
        return 'INTEGER NOT NULL AUTO_INCREMENT';
    }
    
    protected function map_string($options) {
        $limit = isset($options['limit']) ? (int) $options['limit'] : 255;
        return "VARCHAR($limit)" . $this->default_options('string', $options);
    }
    
    protected function map_text($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYTEXT'; break;
            case 'default': $type = 'TEXT'; break;
            case 'medium':  $type = 'MEDIUMTEXT'; break;
            case 'long':    $type = 'LONGTEXT'; break;
            default:        throw new IllegalArgumentException("unknown MySQL size for text column");
        }
        return $type . $this->default_options('text', $options);
    }
    
    protected function default_options($type, $options) {
        
        $native_options = '';
        
        if (isset($options['unsigned'])) {
            if ($type == 'integer' || $type == 'float') {
                if ($options['unsigned']) {
                    $native_options .= ' UNSIGNED';
                }
            } else {
                throw new IllegalArgumentException("unsigned is only supported by numeric types");
            }
        }
        
        if (isset($options['null'])) {
            if ($options['null']) {
                $native_options .= ' NULL';
            } else {
                $native_options .= ' NOT NULL';
            }
        }
        
        if (array_key_exists('default', $options)) {
            $native_options .= ' DEFAULT ' . $this->db->quote($type, $options['default']);
        }
        
        return $native_options;
        
    }
}
?>