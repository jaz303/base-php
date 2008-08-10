<?php
/**
 * GDB parent exception
 */
class GDB_Exception extends Exception {}

/**
 * Exception (optionally) thrown when a transaction is rolled back
 */
class GDB_RollbackException extends GDB_Exception {}

/**
 * Exception thrown when a query results in an error
 */
class GDB_QueryException extends GDB_Exception {}

/**
 * General exception for an integrity constraint violation
 */
class GDB_IntegrityConstraintViolation extends GDB_QueryException {}

/**
 * Thrown when a foreign key violation takes place
 */
class GDB_ForeignKeyViolation extends GDB_IntegrityConstraintViolation {}

/** 
 * Thrown when a unique index violation occurs
 */
class GDB_UniqueViolation extends GDB_IntegrityConstraintViolation {}

/**
 * Thrown when a CHECK constraint is violated
 */
class GDB_CheckViolation extends GDB_IntegrityConstraintViolation {}

/**
 *  GDB is a lightweight database wrapper providing the following:
 * 
 *  1. Lazy connecting singleton access to any number of DB instances.
 *  2. Query wrappers which catch constraint-based errors and convert them to
 *     meaningful subclasses of GDB_QueryException.
 *  3. Alternative mechanism for automatic query value quoting (including
 *     auto insertion of =, <>, IS NULL, IS NOT NULL, IN and NOT IN)
 *  4. Crude emulation of nested transactions with support for queued file-
 *     system operations.
 *  5. Automatic data-type conversions to native boolean and date/datetime
 *     types.
 *  6. SQL generation for common queries (select, insert, update, delete).
 *  7. Object instantiation from result sets with support for static- and
 *     instance-based factory methods.
 *  8. Really simple pagination (TM).
 * 
 *  ** GDB IS NOT AN ATTEMPT TO CREATE A UNIFIED DATABASE API **
 * 
 *  The original version of GDB was based on PDO but for various reasons this
 *  was too inflexible so it has now been rewritten to work with the standard
 *  MySQL extension.
 * 
 *  We currently only support MySQL but a services layer for a new RDBMS can be
 *  implemented easily (~100 lines, mainly boilerplate stuff).
 * 
 *  To use GDB, define a global variable:
 *  $_GDB['my_instance'] = array('driver' => 'MySQL',
 *                               'host' => 'localhost',
 *                               'username' => 'root',
 *                               'password' => '');
 * 
 *  You can then get access to this instance by doing:
 *  GDB::instance('my_instance');
 *  GDB::instance(); // implies 'default'
 * 
 *  @todo data-mapping for currency values. Not too sure how to do this.
 *  I like the typical way Rails achieves it using foo_cents and foo_currency
 *  but this convention-based approach may be too restrictive given that GDB is
 *  designed to be equally compatible with legacy databases and greenfield
 *  projects. Interested to hear people's thoughts on this one...
 *  
 * @package BasePHP
 * @author Jason Frame
 */
abstract class GDB
{
    //
    // Statics

    private static $instances   = array();
    private static $query_count = 0;

    public static function instance($name = 'default') {
        
        if (!isset(self::$instances[$name])) {
            
            global $_GDB;
            
            if (!isset($_GDB[$name])) {
                throw new GDBException("Can't find configuration for GDB connection '$name'");
            }
            
            $config = $_GDB[$name];
            $class  = "GDB{$config['driver']}";
            
            self::$instances[$name] = new $class($config);
            
        }
        
        return self::$instances[$name];
        
    }
    
    public static function get_query_count() {
        return self::$query_count;
    }

    //
    // Blah blah blah
    
    protected $config;
    
    private $replace_args;
    private $replace_pos;
    
    private $tx_count           = 0;
    private $tx_state           = 'out'; // out | valid | invalid
    private $fs_queue           = null;
    private $use_robust_queue   = false;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    public function __destruct() {
        if ($this->tx_state == 'valid') $this->rollback();
    }
    
    protected abstract function connect();
    
    /**
     * Execute a query and return a result object
     *
     * @param $sql SQL query
     * @param $params optional argument(s) for auto quoting/interpolation
     * @return result object
     */
    public function q($sql) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $sql = call_user_func_array(array($this, 'auto_quote'), $args);
        }
        self::$query_count++;
        return $this->perform_query($sql);
    }
    
    /**
     * Execute a query and return the number of affected rows
     *
     * @param $sql SQL query
     * @param $params optional argument(s) for auto quoting/interpolation
     * @return result object
     */
    public function x($sql) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $sql = call_user_func_array(array($this, 'auto_quote'), $args);
        }
        self::$query_count++;
        return $this->perform_exec($sql);
    }
    
    public abstract function last_insert_id($seq = null);
    
    protected abstract function perform_query($sql);
    protected abstract function perform_exec($sql);
    
    //
    // Transaction Support
    
    /**
     * Begins a new transaction.
     *
     * @throws GDB_Exception if transaction is already in progress
     * @return FS_Queue object allowing filesystem operations to be queued until
     *         end of transaction.
     */
    public function begin() {
        if ($this->tx_state == 'valid') throw new GDB_Exception("Can't begin transaction");
        $this->perform_begin();
        // $this->fs_queue = new FS_Queue($this->use_robust_queue);
        $this->tx_count = 1;
        $this->tx_state = 'valid';
        return $this->fs_queue;
    }
    
    /**
     * Requires that a transaction be in progress, beginning a new one if none
     * is currently active. You are required to call commit() once for every
     * call to require_transaction() as the underlying implementation uses a
     * counter to determine when to call commit() on the underlying PDO
     * instance.
     *
     * @return FS_Queue object allowing filesystem operations to be queued until
     *         end of transaction.
     */
    public function require_transaction() {
        if ($this->tx_state == 'out') {
            return $this->begin();
        } elseif ($this->tx_state == 'invalid') {
            throw new GDB_Exception("Can't require transaction - has been rolled back");
        } elseif ($this->rx_state == 'valid') {
            $this->tx_count++;
            return $this->fs_queue;
        }
    }
    
    /**
     * Before committing the database-level transaction, we attempt to commit
     * any pending file system operations in the queue. If this fails, the
     * transaction will be rolled back and the queue exception will be
     * re-thrown.
     */
    public function commit() {
        
        if ($this->tx_state == 'out') {
            throw new GDB_Exception("Can't commit - no active transaction");
        } elseif ($this->tx_state == 'invalid') {
            throw new GDB_Exception("Can't commit - transaction has been rolled back");
        }
        
        if (--$this->tx_count == 0) {
            $this->tx_state = 'out';
            try {
                // $this->fs_queue->exec();
                $this->fs_queue = null;
            } catch (Exception $e) {
                $this->perform_rollback();
                throw $e;
            }
            $this->perform_commit();
        }
        
    }
    
    /**
     * Rolls back the transaction.
     * If nested transaction emulation is being used further attempts to
     * rollback this transaction will be silently ignored and any attempt
     * to commit this transaction will cause an error.
     *
     * @param $throw if true, a RollbackException will be thrown
     */
    public function rollback($throw = false) {
        if ($this->tx_state == 'out') {
            throw new GDB_Exception("Can't rollback - no transaction");
        } elseif ($this->tx_state != 'invalid') {
            $this->tx_state = 'invalid';
            $this->perform_rollback();
        }
        if ($throw) throw new GDB_RollbackException;
    }
    
    protected function perform_begin() { $this->x('BEGIN WORK'); }
    protected function perform_commit() { $this->x('COMMIT'); }
    protected function perform_rollback() { $this->x('ROLLBACK'); }
    
    //
    // Input escaping/formatting/quoting
    
    /**
     * Perform auto-quoting
     * First parameter should be raw SQL containing placeholders.
     */
    public function auto_quote() {
        
        $args = func_get_args();
        $sql = array_shift($args);
         
        $c = count($args);
        if ($c == 0) {
            return $sql;
        } elseif ($c == 1 && is_array($args[0])) {
            $this->replace_args = $args[0];
        } else {
            $this->replace_args = $args;
        }
        
        $this->replace_pos = 0;
        
        return preg_replace_callback('/{(!?=)?(i|f|b|s|x|dt?)(:(\w+))?}/',
                                     array($this, 'do_replace'),
                                     $sql);
    
    }
    
    private function do_replace($m) {
        
        $index = isset($m[4]) ? $m[4] : $this->replace_pos++;
        
        if (!array_key_exists($index, $this->replace_args)) {
            throw new GDB_Exception("Can't replace index '{$index}'");
        }
        
        $value = $this->replace_args[$index];
        
        switch ($m[2]) {
            case 'i':   $func = 'quote_int';        break;
            case 'f':   $func = 'quote_float';      break;
            case 'b':   $func = 'quote_bool';       break;
            case 's':   $func = 'quote_string';     break;
            case 'x':   $func = 'quote_binary';     break;
            case 'd':   $func = 'quote_date';       break;
            case 'dt':  $func = 'quote_datetime';   break;
        }
        
        if (!$m[1]) {
            $out = '';
        } elseif ($m[1] == '=') {
            if ($value === null) {
                $out = 'IS ';
            } elseif (is_array($value)) {
                $out = 'IN ';
            } else {
                $out = '= ';
            }
        } elseif ($m[1] == '!=') {
            if ($value === null) {
                $out = 'IS NOT ';
            } elseif (is_array($value)) {
                $out = 'NOT IN ';
            } else {
                $out = '<> ';
            }
        }
        
        if (is_array($value)) {
            if (count($value) == 0) $value[] = null;
            $out .= '(' . implode(',', array_map(array($this, $func), $value)) . ')';
        } else {
            $out .= $this->$func($value);
        }
        
        return $out;
        
    }
    
    //
    // Value quoting
    
    public abstract function quote_string($s);
    public abstract function quote_bool($b);
    public abstract function quote_binary($b);
    
    public function quote_int($n) {
        return $n === null ? 'NULL' : (int) $n;
    }
    
    public function quote_float($n) {
        if ($n === null) return 'NULL';
        if (preg_match('/^-?\d+(\.\d+)?$/', $n)) return $n;
        return (float) $n;
    }
    
    public function quote_date($v) {
        if ($v === null) return 'NULL';
        if (!($v instanceof Date)) {
            try {
                $v = new Date($v);
            } catch (Exception $e) {
                throw new GDB_Exception("Can't quote date value $v");
            }
        }
        return $this->quote_string($v->to_iso_date());
    }
    
    public function quote_datetime($v) {
        if ($v === null) return 'NULL';
        if (!($v instanceof Date)) {
            try {
                $v = new Date_Time($v);
            } catch (Exception $e) {
                throw new GDB_Exception("Can't quote date value $v");
            }
        }
        return $this->quote_string($v->to_iso_datetime());
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
        $res = $this->q($sql);
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
        $res = $this->q($this->conditional_sql_for_select($sql_or_table, $field_or_args, $value, $project));
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
        $res = $this->q($this->conditional_sql_for_select($sql_or_table, $field_or_args, $value, $project));
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
        $this->x($this->sql_for_insert($table, $values));
        return $this->last_insert_id();
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
        $this->x($this->sql_for_update($table, $values, $field, $value));
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
        $this->x($this->sql_for_replace($table, $attribs));
    }
    
    public function delete($table, $field, $value = null) {
        $this->x($this->sql_for_delete($table, $field, $value));
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

class GDBMySQL extends GDB
{
    private $link;
    
    protected function connect() {
        if (!$this->link = mysql_connect($this->config['host'],
                                         $this->config['username'],
                                         $this->config['password'])) {
            throw new GDB_Exception("Error connecting to MySQL");
        }
        
        if (!mysql_select_db($this->config['database'], $this->link)) {
            throw new GDB_Exception("Error selecting database '{$this->database}'");
        }
    }
    
    public function last_insert_id($seq = null) {
        return mysql_insert_id($this->link);
    }
    
    protected function handle_error($sql = null) {
        
        $error  = mysql_error($this->link);
        $code   = mysql_errno($this->link);
        
        switch ($code) {
            
            case 1022:
            case 1062:
            case 1169: throw new GDB_UniqueViolation($error);
            
            case 1216:
            case 1217: throw new GDB_ForeignKeyViolation($error);
            
            default:
            
                $msg  = "Query Error\n";
                $msg .= "-----------\n";
                $msg .= "Database said: $error (code $code)";
                
                if ($sql) {
                    $msg .= "\n\nOffending Query\n";
                    $msg .= "---------------\n\n";
                    $msg .= $sql;
                }
                
                throw new GDB_QueryException($msg);
            
        }
    
    }
    
    protected function perform_query($sql) {
        if (!$res = mysql_query($sql)) {
            $this->handle_error($sql); // will throw
        } else {
            return new GDBResultMySQL($res);
        }
    }
    
    protected function perform_exec($sql) {
        if (!$res = mysql_query($sql)) {
            $this->handle_error(); // will throw
        } else {
            return mysql_affected_rows();
        }
    }
    
    protected function execute_query($sql) {
        return mysql_query($sql, $this->link);
    }
    
    public function quote_string($s) {
        return $s === null ? 'NULL' : "'" . mysql_real_escape_string($s, $this->link) . "'";
    }
    
    public function quote_bool($b) {
        return $b === null ? 'NULL' : ($b ? 1 : 0);
    }
    
    public function quote_binary($b) {
        return $this->quote_string($b);
    }
}

abstract class GDBResult implements Iterator, Countable
{
    private $map;
    
    private $paginating         = false;
    private $page               = null;
    private $rpp                = null;

    private $key                = null;

    private $mode               = 'array'; // array | object | value
    
    /**
     * No effect in 'array' mode.
     * In 'object' mode, this is the name of the class to instantiate.
     * In 'value' mode, this is the array key to use as the value.
     */
    private $mode_ident     = null; 
    private $mode_options   = array();
    
    /**
     * Returns a single field from the first row of this result set. Behaviour
     * is undefined if both <tt>value()</tt> and result-set iteration are
     * used.
     *
     * @param $offset offset of field to retrieve. Some adapters may also allow
     *        column names to be supplied.
     */
    public abstract function value($offset = 0);
    
    /**
     * Seeks this result to a specified offset. Must throw an exception on
     * failure.
     */
    public abstract function seek($offset);
    
    /**
     * Frees the resources associated with this result set. Successive calls
     * to this instance's methods are undefined.
     */
    public abstract function free();
    
    /**
     * Returns the total number of rows in this result set, irrespective of any
     * pagination constraints.
     */
    public abstract function row_count();
    
    /**
     * Fetch the next row from the result and return it as an associative
     * array. If there are no rows remaining, the boolean false must be
     * returned.
     */
    protected abstract function perform_next();
    
    /**
     * Raw result arrays are passed to filter_row() for processing both before
     * being returned to the client and before object instantiation (in 'object' mode).
     * Subclasses can use this method, for example, to perform type conversion.
     *
     * @param $row reference to raw result row. This should be modified in place.
     */
    protected function filter_row(&$row) {}
    
    /**
     * Returns 0 if this result set is empty.
     * Otherwise, returns the total number of pages in this result set (which
     * will be 1 if this result set has not been paginated explicitly).
     */
    public function page_count() {
        if ($this->paginating) {
            return ceil($this->row_count() / $this->rpp);
        } else {
            return $this->row_count() ? 1 : 0;
        }
    }
        
    /**
     * Returns the current page if this result is paginated, 1 otherwise.
     */
    public function page() { return $this->paginating ? $this->page : 1; }
    
    /**
     * Returns the number of results per page is this result is paginated, null
     * otherwise.
     */
    public function rpp() { return $this->rpp; }
    
    /**
     * Configure the mechanism used to transform result arrays into output.
     * Valid modes are array, object and value.
     */
    public function mode($mode, $ident, $options = array()) {
        $this->mode         = $mode;
        $this->mode_ident   = $ident;
        $this->mode_options = $options;
        return $this;
    }
    
    /**
     * Stack this result set as an array and return it.
     */
    public function stack() {
        $out = array();
        foreach ($this as $k => $v) $out[$k] = $v;
        return $out;
    }
    
    /**
     * Paginates this result object. This will limit objects returned by
     * the iteration to those enclosed by the page window.
     *
     * Usage example:
     * $gdb->q("SELECT * FROM user")->mode('object', 'User')->paginate(20, 2)->stack();
     */
    public function paginate($rpp, $page = 1) {
        $this->paginating   = true;
        $this->rpp          = (int) $rpp;
        $this->page         = (int) $page;
        return $this;
    }
    
    //
    // Iterator implementation
    
    private $index              = -1;
    private $limit              = null;
    private $current_row;
    private $current_row_memo;
    
    public function rewind() {
        
        if ($this->paginating) {
            $start_index = ($this->page - 1) * $this->rpp;
            $this->limit = $this->rpp;
        } else {
            $start_index = 0;
            $this->limit = null;
        }
        
        if ($this->index == -1) {
            if ($start_index != 0) {
                $this->seek($start_index);
                $this->index = $start_index - 1;
            }
            $this->next();
        } elseif ($this->index == $start_index) {
            // Do nothing; iterator is in rewound state
        } else {
            $this->seek($start_index);
            $this->index = $start_index - 1;
            $this->next();
        }
        
    }
    
    public function current() {
        if ($this->current_row_memo === null) {
            $row = $this->current_row;
            if ($row === false) {
                $this->current_row_memo = false;
            } else {
                $this->filter_row($row);
                if ($this->mode == 'array') {
                    $this->current_row_memo = $row;
                } elseif ($this->mode == 'value') {
                    $this->current_row_memo = $row[$this->mode_options];
                } else {
                    if ($this->mode_ident[0] == ':') {
                        $class_key = substr($this->mode_ident, 1);
                        $class = empty($row[$class_key]) ?
                                 $this->mode_options['default_class'] :
                                 $row[$class_key];
                    } else {
                        $class = $this->mode_ident;
                    }
                    if (isset($this->mode_options['factory'])) {
                        $this->current_row_memo = call_user_func(array($class, $this->mode_options['factory']), $row);
                    } elseif (isset($this->mode_options['method'])) {
                        $instance = new $class;
                        call_user_func(array($instance, $this->mode_options['method']), $row);
                        $this->current_row_memo = $instance;
                    } else {
                        $this->current_row_memo = new $class($row);
                    }
                }
            }
            
        }
        return $this->current_row_memo;
    }
    
    public function key($new_key = null) {
        if ($new_key) {
            $this->key = $new_key;
            return $this;
        } else {
            return $this->key ? $this->current_row[$this->key] : $this->index;
        }
    }
    
    public function next() {
        if ($this->limit === 0) {
            $this->current_row = false;
        } else {
            $this->current_row = $this->perform_next();
            if ($this->limit !== null) $this->limit--;
        }
        $this->current_row_memo = null;
        $this->index++;
    }
    
    public function valid() {
        return $this->current_row !== false;
    }
    
    public function count() {
        return $this->row_count();
    }
}

class GDBResultMySQL extends GDBResult
{
    private $native;
    private $map        = array();
    
    public function __construct($native_result) {
        
        $this->native = $native_result;
        
        $c = 0;
        while ($field = mysql_fetch_field($native_result)) {
            $len = mysql_field_len($native_result, $c);
            $name = $field->name;
            if ($field->type == 'date') {
                $this->map[$name] = 'date';
            } elseif ($field->type == 'datetime') {
                $this->map[$name] = 'datetime';
            } elseif ($field->type == 'int' && $len == 1) {
                $this->map[$name] = 'boolean';
            }
            $c++;
        }
        
    }
    
    protected function filter_row(&$row) {
        foreach ($this->map as $field => $type) {
            $v = $row[$field];
            if ($v === null) continue;
            if ($type == 'date') {
                $row[$field] = new Date($v);
            } elseif ($type == 'datetime') {
                $row[$field] = new Date_Time($v);
            } elseif ($type == 'boolean') {
                $row[$field] = (bool) $v;
            }
        }
    }
    
    public function row_count() {
        return mysql_num_rows($this->native);
    }
    
    public function value($offset = 0) {
        return mysql_result($this->native, 0, $offset);
    }
    
    public function seek($offset) {
        if (!mysql_data_seek($this->native, $offset)) {
            throw new GDB_Exception("Couldn't seek to offset $offset");
        }
    }
    
    public function free() {
        mysql_free_result($this->native);
    }
    
    protected function perform_next() {
        return mysql_fetch_assoc($this->native);
    }
}
?>