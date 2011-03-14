<?php
namespace gdb;

abstract class SchemaBuilder
{
    protected $db;
    
    public function __construct(\GDB $db) {
        $this->db = $db;
    }
    
    //
    // Databases
    
    public function create_database($name) { $this->db->x($this->sql_for_create_database($name)); }
    public function drop_database($name) { $this->db->x($this->sql_for_drop_database($name)); }
    
    protected function sql_for_create_database($name) { return 'CREATE DATABASE ' . $this->db->quote_ident($name); }
    protected function sql_for_drop_database($name) { return 'DROP DATABASE ' . $this->db->quote_ident($name); }
    
    //
    // Tables
    
    public function table_exists($table) {
        $r = $this->db->q("SHOW TABLES LIKE " . $this->db->quote_string($table));
        $e = $r->row_count() > 0;
        $r->free();
        return $e;
    }
    
    public function table_names() {
        return $this->db->q("SHOW TABLES")->mode('value', 0)->stack();
    }
    
    public function create_table(TableDefinition $table) {
        $this->db->x($this->sql_for_create_table($table));
    }
    
    public function drop_table($table_name) {
        $this->db->x("DROP TABLE " . $this->db->quote_ident($table_name));
    }
    
    public function rename_table($existing_name, $new_name) {
        $this->db->x("RENAME TABLE {$this->db->quote_ident($existing_name)} TO {$this->db->quote_ident($new_name)}");
    }
    
    //
    // Columns
    
    public function add_column($table, $column_name, $type, $options = array()) {
        $def = $this->column_definition($column_name, $type, $options);
        $this->alter_table($table, "ADD COLUMN $def");
    }
    
    public function remove_column($table, $column_name) {
        $this->alter_table($table, "DROP COLUMN {$this->db->quote_ident($column_name)}");
    }
    
    public function rename_column($table, $existing_column_name, $new_column_name) {
        $def = $this->existing_column_definition($table, $existing_column_name);
        $def = preg_replace('/^\`?.*?\`?\s+/', '', $def);
        $this->alter_table($table, "CHANGE COLUMN {$this->db->quote_ident($existing_column_name)} {$this->db->quote_ident($new_column_name)} $def");
    }
    
    protected function alter_table($table, $sql) {
        $this->db->x("ALTER TABLE {$this->db->quote_ident($table)} {$sql}");
    }
    
    protected function column_definition($name, $type, $options) {
        return $this->db->quote_ident($name) . ' ' . $this->map_native_type($type, $options);
    }
    
    abstract protected function existing_column_definition($table, $name);
    
    //
    // Indexes
    
    public function add_index($table, $column_names, $options = array()) {
        
        $sql = 'CREATE';
        
        // Some of these might be MySQL specific but I'm not sure it's worth the 
        // added complexity of moving it out to a subclass.
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
    
    //
    // Helpers
    
    public function sql_for_create_table(TableDefinition $table) {
        
        $sql  = "CREATE TABLE " . $this->db->quote_ident($table->get_name()) . "\n";
        $sql .= "(\n";
        
        $chunks = array();
        foreach ($table->get_columns() as $col) {
            $chunks[] = $this->column_definition($col['name'], $col['type'], $col['options']);
        }
        
        if ($pk = $table->get_primary_key()) {
            $chunks[] = "PRIMARY KEY (" . implode(', ', array_map(array($this->db, 'quote_ident'), (array) $pk)) . ")";
        }
        
        $sql .= "  " . implode(",\n  ", $chunks);
        $sql .= "\n)";
        
        $options = $this->create_table_options($table);
        if (count($options)) {
            $sql .= ' ' . implode(', ', $options);
        }
        
        return $sql;
        
    }
    
    protected function create_table_options(TableDefinition $table) {
        return array();
    }
    
    //
    // Type mapping
    
    protected function map_native_type($type, $options) {
        switch ($type) {
            case 'blob':        return $this->map_blob($options);
            case 'boolean':     return $this->map_boolean($options);
            case 'date':        return $this->map_date($options);
            case 'date_time':   return $this->map_date_time($options);
            case 'float':       return $this->map_float($options);
            case 'integer':     return $this->map_integer($options);
            case 'serial':      return $this->map_serial($options);
            case 'string':      return $this->map_string($options);
            case 'text':        return $this->map_text($options);
            default:            throw new \InvalidArgumentException("unknown column type - $type");
        }
    }
    
    protected function map_blob($options) { return $this->map_simple('BLOB', $options); }
    protected function map_boolean($options) { return $this->map_simple('BOOLEAN', $options); }
    protected function map_date($options) { return $this->map_simple('DATE', $options); }
    protected function map_date_time($options) { return $this->map_simple('DATETIME', $options); }
    protected function map_float($options) { return $this->map_simple('FLOAT', $options); }
    protected function map_integer($options) { return $this->map_simple('INTEGER', $options); }
    protected function map_serial($options) { return $this->map_simple('SERIAL', $options); }
    
    protected function map_string($options) {
        $options += array('limit' => 255);
        return "VARCHAR({$options['limit']})" . $this->default_column_options('string', $options);
    }
    
    protected function map_text($options) { return $this->map_simple('TEXT', $options); }
    
    protected function map_simple($type, $options) {
        return strtoupper($type) . $this->default_column_options($type, $options);
    }
    
    protected function default_column_options($type, $options) {
        
        $type           = strtolower($type);
        $native_options = '';
        
        if (isset($options['unsigned'])) {
            if ($type == 'integer' || $type == 'float') {
                if ($options['unsigned']) {
                    $native_options .= ' UNSIGNED';
                }
            } else {
                throw new \InvalidArgumentException("unsigned is only supported by numeric types");
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

class MySQLSchemaBuilder extends SchemaBuilder
{
    protected function existing_column_definition($table, $name) {
        $row = $this->db->q("SHOW CREATE TABLE {$this->db->quote_ident($table)}")->first_row();
        $def = $row['Create Table'];
        foreach (explode("\n", $def) as $line) {
            if (preg_match('/^\s*\`?' . preg_quote($name) . '\`?/', $line)) {
                return trim($line, "\r\n\t ,");
            }
        }
        return null;
    }
    
    protected function create_table_options(TableDefinition $table) {
        $raw_options = $table->get_options();
        $table_options = array();
        
        if (isset($raw_options['mysql.engine'])) {
            $table_options[] = 'ENGINE = ' . $raw_options['mysql.engine'];
        }
        
        return $table_options;
    }
    
    protected function map_blob($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYBLOB'; break;
            case 'default': $type = 'BLOB'; break;
            case 'medium':  $type = 'MEDIUMBLOB'; break;
            case 'long':    $type = 'LONGBLOB'; break;
            default:        throw new \InvalidArgumentException("unknown MySQL size for blob column");
        }
        return $type . $this->default_column_options('text', $options);
    }
    
    protected function map_boolean($options) {
        return 'TINYINT(1)' . $this->default_column_options('boolean', $options);
    }
    
    protected function map_integer($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYINT'; break;
            case 'small':   $type = 'SMALLINT'; break;
            case 'medium':  $type = 'MEDIUMINT'; break;
            case 'default': $type = 'INT'; break;
            case 'big':     $type = 'BIGINT'; break;
            default:        throw new \InvalidArgumentException("unknown MySQL size for int column");
        }
        if (isset($options['limit'])) {
            $type .= '(' . $options['limit'] . ')';
        }
        return $type . $this->default_column_options('integer', $options);
    }
    
    protected function map_serial($options) {
        return 'INTEGER NOT NULL AUTO_INCREMENT';
    }
    
    protected function map_text($options) {
        $options += array('mysql.size' => 'default');
        switch ($options['mysql.size']) {
            case 'tiny':    $type = 'TINYTEXT'; break;
            case 'default': $type = 'TEXT'; break;
            case 'medium':  $type = 'MEDIUMTEXT'; break;
            case 'long':    $type = 'LONGTEXT'; break;
            default:        throw new InvalidArgumentException("unknown MySQL size for text column");
        }
        return $type . $this->default_column_options('text', $options);
    }
    
    // TODO: implement this stuff
    
    // protected function columns_for_table($table) {
    //     $cols = array();
    //     foreach ($this->db->q("DESCRIBE {$this->db->quote_ident($table)}") as $ary) {
    //         $cols[] = $this->decode_column($ary);
    //     }
    //     return $cols;
    // }
    // 
    // protected function column_for_table($table, $column) {
    //     return $this->decode_column($this->db->q("DESCRIBE {$this->db->quote_ident($table)} {$this->db->quote_ident($column)}")->first_row());
    // }
    // 
    // protected function decode_column($column) {
    //     $cd = new ColumnDefinition;
    //     $cd->set_name($column['Field']);
    //     if (strpos($column['Extra'], 'auto_increment')) {
    //         $cd->set_type('serial');
    //     } else {
    //         if (preg_match('/^([a-z]+)(\((\d+)\))?$/i', $column['Type'], $matches)) {
    //             $cd->set_type($matches[1]);
    //             $cd->set_length($matches[2] ? ((int) $matches[3]) : null);
    //         } else {
    //             throw new ColumnParseException("could parse column type '{$column['Type']}'. This is probably a bug in GDB.");
    //         }
    //     }
    //     $cd->set_allows_null(strtoupper($column['Null']) == 'YES');
    //     $cd->set_default($column['Default']);
    //     $td->add_column($cd);
    //     if (strtoupper($column['Key']) == 'PRI') {
    //         $primary_key[] = $column['Field'];
    //     }
    // }
}
?>