<?php
/**
 * GDB parent exception
 */
class GDBException extends Exception {}

/**
 * Exception thrown when a query results in an error
 */
class GDBQueryException extends GDBException {}

/**
 * General exception for an integrity constraint violation
 */
class GDBIntegrityConstraintViolation extends GDBQueryException {}

/**
 * Thrown when a foreign key violation takes place
 */
class GDBForeignKeyViolation extends GDBIntegrityConstraintViolation {}

/** 
 * Thrown when a unique index violation occurs
 */
class GDBUniqueViolation extends GDBIntegrityConstraintViolation {}

/**
 * Thrown when a CHECK constraint is violated
 */
class GDBCheckViolation extends GDBIntegrityConstraintViolation {}

abstract class GDB
{
    //
    // Statics
    
    public static $driver_class_map = array(
        'mysql'     => 'GDBMySQL'
    );
    
    public static $quote_methods = array(
        's'             => 'quote_string',
        'str'           => 'quote_string',
        'string'        => 'quote_string',
        'b'             => 'quote_boolean',
        'bool'          => 'quote_boolean',
        'boolean'       => 'quote_boolean',
        'f'             => 'quote_float',
        'float'         => 'quote_float',
        'i'             => 'quote_integer',
        'int'           => 'quote_integer',
        'integer'       => 'quote_integer',
        'x'             => 'quote_binary',
        'binary'        => 'quote_binary',
        'd'             => 'quote_date',
        'date'          => 'quote_date',
        'dt'            => 'quote_date_time',
        'date_time'     => 'quote_date_time',
        'ud'            => 'quote_utc_date',
        'utc_date'      => 'quote_utc_date',
        'udt'           => 'quote_utc_date_time',
        'utc_date_time' => 'quote_utc_date_time'
    );
    
    public static function resolve_field_type_and_name($string) {
        if (($p = strpos($string, ':')) !== false) {
            return array(substr($string, 0, $p), substr($string, $p + 1));
        } else {
            return array(null, $string);
        }
    }
    
    private static $instances   = array();
    private static $query_count = 0;

    public static function instance($name = 'default') {
        
        if (!isset(self::$instances[$name])) {
            
            if (!isset($GLOBALS['_GDB'][$name])) {
                throw new GDBException("Can't find configuration for GDB connection '$name'");
            }
            
            $config = $GLOBALS['_GDB'][$name];
            $class  = self::$driver_class_map[$config['driver']];
            
            self::$instances[$name] = new $class($config);
            
        }
        
        return self::$instances[$name];
        
    }
    
    //
    //
    //
    
    protected $config;
    
    public function __construct($config = array()) {
        $this->config = $config;
        $this->connect();
    }
    
    //
    // Query methods
    
    public function q($sql) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $sql = call_user_func_array(array($this, 'auto_quote_query'), $args);
        }
        self::$query_count++;
        return $this->perform_query($sql);
    }
    
    public function x($sql) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $sql = call_user_func_array(array($this, 'auto_quote_query'), $args);
        }
        self::$query_count++;
        return $this->perform_exec($sql);
    }
    
    //
    // Methods to override in adapters
    
    public function last_insert_id($sequence = null) { return null; }
    
    protected function connect() { }
    protected function perform_query($sql) { }
    protected function perform_exec($sql) { }
    
    //
    // Quoting
    
    public function auto_quote_query($sql) {
        
        $args   = func_get_args();
        $sql    = array_shift($args);
         
        $c = count($args);
        if ($c == 0) {
            return $sql;
        }
        
        $replace_args = ($c == 1 && is_array($args[0])) ? $args[0] : $args;
        $replace_pos  = 0;
        $self = $this;
        
        return preg_replace_callback('/{(!?=)?(\w+)(:(\w+))?}/',
            function($m) use ($replace_args, &$replace_pos, $self) {
            
                $index = isset($m[4]) ? $m[4] : $replace_pos++;

                if (!array_key_exists($index, $replace_args)) {
                    throw new GDBException("Can't replace index '{$index}'");
                }
            
                $value = $replace_args[$index];
                $quote_method = GDB::$quote_methods[$m[2]];
                if (!$quote_method) {
                    throw new InvalidArgumentException("'$m[2]' is not a valid auto-quote type specifier");
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
                    $out .= '(' . implode(',', array_map(array($self, $quote_method), $value)) . ')';
                } else {
                    $out .= $self->$quote_method($value);
                }

                return $out;
                
            },
        $sql);
        
    }
    
    public function auto_quote_array($array) {
        $out = array();
        foreach ($array as $k => $raw_value) {
            if (($p = strpos($k, ':')) !== false) {
                $method = self::$quote_methods[substr($k, 0, $p)];
                $out[substr($k, $p + 1)] = $this->$method($raw_value);
            } else {
                $out[$k] = $raw_value === null ? 'NULL' : $raw_value;
            }
        }
        return $out;
    }
    
    public function quote($type, $value) {
        $method = self::$quote_methods[$type];
        return $this->$method($value);
    }
    
    public function quote_string($value) {
        return $value === NULL ? 'NULL' : ('\'' . addslashes($value) . '\'');
    }
    
    public function quote_boolean($value) {
        return $value === NULL ? 'NULL' : ($value ? '1' : '0');
    }
    
    public function quote_float($value) {
        if ($value === null) return 'NULL';
        if (is_string($value) && (preg_match('/^-?\d+(\.\d+)?$/', $value))) return $value;
        return (float) $value;
    }
    
    public function quote_ident($ident) {
        return $ident;
    }
    
    public function quote_integer($value) {
        return $value === NULL ? 'NULL' : (int) $value;
    }
    
    public function quote_binary($value) {
        return $this->quote_string($value);
    }
    
    public function quote_date($value) {
        if ($value === NULL) return 'NULL';
        if (!is_object($value)) $value = new Date($value);
        return $this->quote_string($value->iso_date());
    }
    
    public function quote_date_time($value) {
        if ($value === NULL) return 'NULL';
        if (!is_object($value)) $value = new Date_Time($value);
        return $this->quote_string($value->iso_date_time());
    }
    
    public function quote_utc_date($value) {
        if ($value === NULL) return 'NULL';
        if (!is_object($value)) $value = new Date($value);
        return $this->quote_string($value->to_utc()->iso_date());
    }
    
    public function quote_utc_date_time($value) {
        if ($value === NULL) return 'NULL';
        if (!is_object($value)) $value = new Date_Time($value);
        return $this->quote_string($value->to_utc()->iso_date_time());
    }
    
    //
    // Query Helpers
    
    public function select($table, $project = null) {
        $query = new GDBQuery($this, $table, $project);
        return $query;
    }
    
    /**
     * Inserts a row into a table
     *
     * @param $table table name to insert into
     * @param $values associative array of field => value. array will be passed to
     *        $this->auto_quote_array() so keys may contain type info, for example
     *        's:username'.
     * @return ID of inserted row, if available
     */
    public function insert($table, $values) {
        $values = $this->auto_quote_array($values);
        $sql  = "INSERT INTO " . $this->quote_ident($table);
        $sql .= ' (' . implode(',', array_keys($values)) . ')';
        $sql .= ' VALUES (' . implode(',', array_values($values)) . ')';
        $this->x($sql);
        return $this->last_insert_id();
    }
    
    /**
     * Updates values in a table
     *
     * @param (string) $table table to update
     * @param (array) $values associative array of field => value. array will be passed to
     *        $this->auto_quote_array() so keys may contain type info, for example
     *        's:username'.
     * @param ... remaining parameters are passed to conditions_for()
     * @return number of affected rows
     */
    public function update($table, $values) {
        $sql = "UPDATE {$this->quote_ident($table)} SET";
        $sep = ' ';
        foreach ($this->auto_quote_array($values) as $k => $v) {
            $sql .= $sep . $k . ' = ' . $v;
            $sep = ', ';
        }
        $conditions = call_user_func_array(
            array($this, 'conditions_for'),
            array_slice(func_get_args(), 2)
        );
        if ($conditions) $sql .= " WHERE $conditions";
        return $this->x($sql);
    }
    
    public function delete($table) {
        $sql = "DELETE FROM {$this->quote_ident($table)}";
        $conditions = call_user_func_array(
            array($this, 'conditions_for'),
            array_slice(func_get_args(), 2)
        );
        if ($conditions) $sql .= " WHERE $conditions";
        return $this->x($sql);
    }
    
    /**
     * Converts various representations of conditions into SQL suitable for
     * WHERE clause
     *
     * @params
     *   Returns empty string
     * @params
     *   Array of field => value becomes field1 = 'foo' AND field2 = 'bar'
     *   Supports array key type-hinting e.g. array('i:id' => '12')
     *   @param (array) $arg1
     * @params
     *   
     *   @param (string) $arg1 fieldname (may contain type hint)
     *   @param (string) $arg2 value
     * @params
     *   Interpolates SQL fragment with values from array
     *   @param (string) $arg1 SQL string
     *   @param (array) $arg2 array of values with which to interpolate $arg1
     *
     * @return valid SQL fragment suitable for use in WHERE clause
     */
    public function conditions_for($arg1 = null, $arg2 = null) {
        switch (func_num_args()) {
            case 0:
                return '';
            case 1:
                if (is_array($arg1)) {
                    $values = array();
                    foreach ($this->auto_quote_array($arg1) as $k => $v) $values[] = "$k = $v";
                    return implode(' AND ', $values);
                } else {
                    return $arg1;
                }
            case 2:
                if (is_array($arg2)) {
                    return $this->auto_quote_query($arg1, $arg2);
                } else {
                    list($type, $name) = $this->resolve_field_type_and_name($arg1);
                    if ($type !== null) {
                        $quoter = self::$quote_methods[$type];
                        $arg2 = $this->$quoter($arg2);
                    }
                    return "$name = $arg2";
                }
            default:
                throw new GDBException;
        }
    }
    
    //
    //
    
    public abstract function new_schema_builder();
    
    //
    // Transaction support
    
    private $tx_active      = false;
    private $savepoints     = 0;
    
    
    public function in_transaction() { return $this->tx_active; }
    
    /**
     * Begins a new transaction and executes the given anonymous function.
     * If the anonymous function throws an exception, transaction will be rolled
     * back and exception is re-thrown. Otherwise, transaction is committed.
     *
     * @see with_transaction() if you wish to "nest" transactions
     *
     * @param $lambda anonymous function to run within transaction
     */
    public function transaction($lambda) {
        $this->begin();
        try {
            $lambda();
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Runs a given anonymous function, starting a new transaction if none is already
     * active.
     *
     * If you're using this method to create nested transactions be sure to always throw
     * an exception to indicate failure so that the outermost handler knows to rollback
     * the physical transaction.
     *
     * @param $lambda anonymous function to run
     */
    public function with_transaction($lambda) {
        if ($this->in_transaction()) {
            $lambda();
        } else {
            $this->transaction($lambda);
        }
    }
    
    public function with_savepoint($lambda) {
        if (!$this->in_transaction()) {
            $this->transaction($lambda);
        } else {
            $savepoint = "gdb_" . (++$this->savepoints);
            $this->set_savepoint($savepoint);
            try {
                $lambda();
                $this->release_savepoint($savepoint);
            } catch (\Exception $e) {
                $this->rollback_to_savepoint($savepoint);
                throw $e;
            }
        }
    }
    
    /**
     * Begin a new transaction.
     *
     * @throws GDBException if a transaction is already active
     */
    public function begin() {
        if ($this->tx_active) {
            throw new GDBException("can't begin new transaction");
        } else {
            $this->x('BEGIN');
            $this->tx_active = true;
        }
    }
    
    /**
     * Commit the active transaction.
     *
     * @throws GDBException if no transaction is active
     */
    public function commit() {
        if ($this->tx_active) {
            $this->x('COMMIT');
            $this->tx_active = false;
        } else {
            throw new GDBException("can't commit, no active transaction");
        }
    }
    
    /**
     * Rollback the active transaction.
     *
     * @throws GDBException if no transaction is active
     */
    public function rollback() {
        if ($this->tx_active) {
            $this->x('ROLLBACK');
            $this->tx_active = false;
        } else {
            throw new GDBException("can't commit, no active transaction");
        }
    }
    
    public function set_savepoint($id) {
        $this->x("SAVEPOINT $id");
    }
    
    public function rollback_to_savepoint($id) {
        $this->x("ROLLBACK TO SAVEPOINT $id");
    }
    
    public function release_savepoint($id) {
        $this->x("RELEASE SAVEPOINT $id");
    }
}

class GDBMySQL extends GDB
{
    private $write_link = null;
    private $read_link  = null;
    
    public function get_mysql_link() {
        return $this->get_mysql_write_link();
    }
    
    public function get_mysql_read_link() {
        if ($this->read_link === null) {
            if (isset($this->config['read'])) {
                $rh = $this->config['read'];
                $this->read_link = $this->mysql_connect($rh[array_rand($rh)]);
            } else {
                throw new GDBException("Error connecting to MySQL - no read config available");
            }
        }
        return $this->read_link;
    }
    
    public function get_mysql_write_link() {
        if ($this->write_link === null) {
            if (isset($this->config['write'])) {
                $wh = $this->config['write'];
                $this->write_link = $this->mysql_connect($wh[array_rand($wh)]);
            } else {
                throw new GDBException("Error connecting to MySQL - no write config available");
            }            
        }
        return $this->write_link;
    }
    
    protected function any_link() {
        if ($this->read_link) return $this->read_link;
        if ($this->write_link) return $this->write_link;
        if (isset($this->config['read'])) return $this->get_mysql_read_link();
        if (isset($this->config['write'])) return $this->get_mysql_write_link();
        return null;
    }
    
    protected function get_mysql_link_for_query($sql) {
        if (preg_match('/^\s*select/i', $sql)) {
            return $this->get_mysql_read_link();
        } else {
            return $this->get_mysql_write_link();
        }
    }
    
    protected function mysql_connect($config) {
        if (!($link = mysql_connect($config['host'], $config['username'], $config['password']))) {
            throw new GDBException("Error connecting to MySQL ({$config['username']}@{$config['host']})");
        }
        
        if (!mysql_select_db($config['database'], $link)) {
            throw new GDBException("Error selecting database '{$config['database']}'");
        }
        
        return $link;
    }
    
    protected function connect() {
        if (isset($this->config['read']) || isset($this->config['write'])) {
            // do nothing - delay connection until we know what we need
        } else {
            $this->read_link = $this->mysql_connect($this->config);
            $this->write_link = $this->read_link;
        }
    }
    
    public function quote_ident($ident) {
        return "`$ident`";
    }
    
    public function quote_string($s) {
        return $s === null ? 'NULL' : "'" . mysql_real_escape_string($s, $this->any_link()) . "'";
    }
    
    public function last_insert_id($sequence = null) {
        return mysql_insert_id($this->write_link);
    }
    
    protected function perform_query($sql) {
        $link = $this->get_mysql_link_for_query($sql);
        if (!($res = mysql_query($sql, $link))) {
            $this->handle_error($sql, $link); // will throw
        } else {
            return new GDBResultMySQL($res);
        }
    }
    
    protected function perform_exec($sql) {
        $link = $this->get_mysql_link_for_query($sql);
        if (!($res = mysql_query($sql, $link))) {
            $this->handle_error($sql, $link); // will throw
        } else {
            return mysql_affected_rows($link);
        }
    }
    
    protected function handle_error($sql, $link) {
        
        $error  = mysql_error($link);
        $code   = mysql_errno($link);
        
        switch ($code) {
            
            case 1022:
            case 1062:
            case 1169: throw new GDBUniqueViolation($error);
            
            case 1216:
            case 1217: throw new GDBForeignKeyViolation($error);
            
            default:
            
                $msg  = "Query Error\n";
                $msg .= "-----------\n";
                $msg .= "Database said: $error (code $code)";
                
                if ($sql) {
                    $msg .= "\n\nOffending Query\n";
                    $msg .= "---------------\n\n";
                    $msg .= $sql;
                }
                
                throw new GDBQueryException($msg);
            
        }
    
    }
    
    /**
     * Returns an informal array describing the connected database.
     */
    public function get_usage() {
        
        $out = array();
        
        foreach ($this->q('SHOW TABLES') as $table) {
            $table_name = array_shift($table);
            
            $r_status = mysql_query("SHOW TABLE STATUS LIKE '$table_name'");
            $status = mysql_fetch_assoc($r_status);
            mysql_free_result($r_status);
            
            $out['tables'][$table_name] = array(
                'rows'          => $status['Rows'],
                'engine'        => $status['Engine'],
                'data_size'     => $status['Data_length'],
                'index_size'    => $status['Index_length']
            );
        }
        
        return $out;
    
    }
    
    public function new_schema_builder() {
        return new gdb\MySQLSchemaBuilder($this);
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
    
    private $first_row_memo = null;
    
    /**
     * Returns the number of fields in each row of this result set.
     *
     * @return the number of fields in each row of this result set.
     */
    public abstract function field_count();
    
    /**
     * Returns the name of the `$offset`'th field in this result set (zero-indexed).
     * This is the same as the key used used when accessing result values in associative arrays.
     *
     * @return the name of specified field offset.
     */
    public abstract function field_name($offset);
    
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
     * Returns the first (filtered) row from this result. Behaviour
     * is undefined if both <tt>first_row()</tt> and result-set iteration
     * are used.
     */
    public function first_row() {
        if ($this->first_row_memo === null) {
            $this->next();
            $this->first_row_memo = $this->current();
        }
        return $this->first_row_memo;
    }
    
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
     * Valid modes: array (default), object and value.
     *
     */
    public function mode($mode, $ident, $options = array()) {
        if ($mode == 'value' && is_integer($ident)) {
            $ident = $this->field_name($ident);
        }
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
    
    /**
     * @ignore
     */
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
    
    /**
     * @ignore
     */
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
                    $this->current_row_memo = $row[$this->mode_ident];
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
            $this->key = is_integer($new_key) ? $this->field_name($ney_key) : $new_key;
            return $this;
        } else {
            return $this->key ? $this->current_row[$this->key] : $this->index;
        }
    }
    
    /**
     * @ignore
     */
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
    
    /**
     * @ignore
     */
    public function valid() {
        return $this->current_row !== false;
    }
    
    /**
     * Returns the total number of rows in this result set, regardless of pagination
     * settings.
     *
     * @return total number of rows in this result set
     */
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
            $name = $field->name;
            if ($field->type == 'date') {
                $this->map[$name] = 'date';
            } elseif ($field->type == 'datetime') {
                $this->map[$name] = 'datetime';
            } elseif ($field->type == 'int' && mysql_field_len($native_result, $c) == 1) {
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
            throw new GDBException("Couldn't seek to offset $offset");
        }
    }
    
    public function free() {
        mysql_free_result($this->native);
    }
    
    protected function perform_next() {
        return mysql_fetch_assoc($this->native);
    }
    
    public function field_count() {
        return mysql_num_fields($this->native);
    }
    
    public function field_name($offset) {
        return mysql_field_name($this->native, $offset);
    }
}

/**
 * Fluent query interface
 *
 * $db->select('user', '*')
 */
class GDBQuery implements IteratorAggregate
{
    private $db;
    private $table;
    private $project;
    
    private $joins      = array();
    private $order      = array();
    
    public function __construct(GDB $db, $table, $project = '*') {
        
        $this->db       = $db;
        $this->table    = $table;
        
        if ($project === null) $project = '*';
        $this->project($project);
        
    }
    
    public function project($fields) {
        if (func_num_args() > 1) {
            $this->project = implode(', ', func_get_args());
        } elseif (is_array($fields)) {
            $this->project = implode(', ', $fields);
        } else {
            $this->project = $fields;
        }
    }
    
    //
    // Joins
    
    public function join() {
        
    }
    
    public function left_join() {
        
    }
    
    public function right_join() {
        
    }
    
    public function inner_join() {
        
    }
    
    //
    // Ordering
    
    public function order($field, $direction = 'asc') {
        $this->order[] = array($field, $direction);
        return $this;
    }
    
    public function asc($field) { return $this->order($field, 'asc'); }
    public function desc($field) { return $this->order($field, 'desc'); }
    
    //
    // Generate
    
    public function to_sql() {
        
        $sql = "SELECT {$this->project} FROM {$this->table}";
    
        
        return $sql;
        
    }
    
    //
    // Kick!
    
    public function result() {
        return $this->db->q($this->to_sql());
    }
    
    public function getIterator() {
        return $this->result();
    }
}

class GDBQueryJoin
{
    
}
?>