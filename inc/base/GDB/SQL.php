<?php
class GDB_SQL
{
    private $gdb;
    
    public function __construct($gdb) {
        $this->gdb = $gdb;
    }
    
    //
    // SQL Convenience Methods
    
    /**
     * Selects a single value from the database.
     *
     * @param $sql SQL query
     * @param $offset column offset to return; some adapters (e.g. MySQL) may
     *        also allowa fieldname to be supplied.
     */
    public function select_value($sql, $offset = 0) {
        $res = $this->gdb->q($sql);
        $val = $res->value($offset);
        $res->free();
        return $val;
    }
    
    /**
     * Selects a single row from the database.
     *
     * Works with the following combinations of parameters (stuff in parentheses is optional):
     * Raw SQL query
     * Parameterized SQL query, array of substitutions
     * Table name, WHERE clause (, null, names of columns to project)
     * Table name, field name, pre-quoted value (, names of columns to project)
     *
     * Returns an array if a row was found, false otherwise.
     */
    public function select_row($sql_or_table, $field_or_args = null, $value = null, $project = '*') {
        $res = $this->gdb->q($this->conditional_sql_for_select($sql_or_table, $field_or_args, $value, $project));
        $out = false;
        foreach ($res as $row) {
            $out = $row;
            break;
        }
        $res->free();
        return $out;
    }
    
    public function select_object($class, $sql_or_table, $field_or_args = null, $value = null) {
        $row = $this->select_row($sql_or_table, $field_or_args, $value);
        if ($row) {
            return new $class($row);
        } else {
            return null;
        }
    }
    
    /**
     * Selects all rows from the database.
     *
     * Works with the following combinations of parameters (stuff in parentheses is optional):
     * Raw SQL query
     * Parameterized SQL query, array of substitutions
     * Table name, WHERE clause (, null, names of columns to project)
     * Table name, field name, pre-quoted value (, names of columns to project)
     *
     * Returns an array of arrays if a row was found, an empty array otherwise.
     */
    public function select_all($sql_or_table, $field_or_args = null, $value = null, $project = '*') {
        $res = $this->gdb->q($this->conditional_sql_for_select($sql_or_table, $field_or_args, $value, $project));
        $out = $res->stack();
        $res->free();
        return $out;
    }
    
    /**
     * Inserts a bunch of values into a table
     * 
     * @param $table table into which data should be inserted
     * @param $values field => value map of data to be inserted. Values <b>must be pre-quoted</b>.
     * @return Row ID of last insert, if any.
     */
    public function insert($table, $values) {
        $this->gdb->x($this->sql_for_insert($table, $values));
        return $this->gdb->last_insert_id();
    }
    
    /**
     * Updates a bunch of values into a table.
     *
     * @param $table table to update.
     * @param $values field => value map of data to be updated. Values <b>must be pre-quoted</b>.
     * @param $field this can either be a WHERE clause or, if $value is provided, a fieldname.
     * @param $value pre-quoted value that $field must match.
     */
    public function update($table, $values, $field, $value = null) {
        $this->gdb->x($this->sql_for_update($table, $values, $field, $value));
    }
    
    /**
     * Conditionally perform an insert or update operation.
     *
     * Assuming <tt>$this->id</tt> is your primary key, and is <tt>null</tt> when your
     * record is unsaved, this method allows you to do the following:
     *
     * $this->id = $gdb->insert_or_update('my_table', array(...), 'id', $this->id);
     *
     * So you've got a single line handling both your insert and update.
     *
     * @param $table table to insert/update
     * @param $values field => value map of data. Values <b>must be pre-quoted</b>.
     * @param $field fieldname to match against
     * @param $value pre-quoted value that $field must match.
     * @return $value if $value is not null, ID of inserted row otherwise.
     */
    public function insert_or_update($table, $values, $field, $value) {
        if ($value === null) {
            return $this->insert($table, $values);
        } else {
            $this->update($table, $values, $field, $value);
            return $value;
        }
    }
    
    public function replace($table, $attribs) {
        $this->gdb->x($this->sql_for_replace($table, $attribs));
    }
    
    public function delete($table, $field, $value = null) {
        $this->gdb->x($this->sql_for_delete($table, $field, $value));
    }
    
    //
    // SQL Generation
    
    public function sql_for_select_value($table, $column, $field, $value = null) {
        return "SELECT $column FROM $table WHERE {$this->build_condition($field, $value)}";
    }
    
    public function sql_for_select_rows($table, $field, $value = null, $project = '*') {
        return "SELECT $project FROM $table WHERE {$this->build_condition($field, $value)}";
    }
    
    public function sql_for_insert($table, $attribs) {
        $fields = implode(',', array_keys($attribs));
        $values = implode(',', array_values($attribs));
        return "INSERT INTO $table ($fields) VALUES ($values)";
    }
    
    public function sql_for_update($table, $attribs, $field, $value = null) {
        $updates = array();
        foreach ($attribs as $f => $v) $updates[] = "$f = $v";
        $updates = implode(',', $updates);
        return "UPDATE $table SET $updates WHERE {$this->build_condition($field, $value)}";
    }
    
    public function sql_for_delete($table, $field, $value = null) {
        return "DELETE FROM $table WHERE {$this->build_condition($field, $value)}";
    }
    
    public function sql_for_replace($table, $attribs) {
        $fields = implode(',', array_keys($attribs));
        $values = implode(',', array_values($attribs));
        return "REPLACE INTO $table ($fields) VALUES ($values)";
    }
    
    //
    //
    
    private function conditional_sql_for_select($sql_or_table, $field_or_args = null, $value = null, $project = '*') {
        if (is_array($field_or_args)) {
            return $this->auto_quote($sql_or_table, $field_or_args);
        } elseif ($field_or_args === null) {
            return $sql_or_table;
        } else {
            return $this->sql_for_select_rows($sql_or_table, $field_or_args, $value, $project);
        }
    }
    
    private function build_condition($field, $value) {
        return $value === null ? $field : "$field = $value";
    }
}
?>