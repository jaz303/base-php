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

class GDB
{
    //
    // Statics
    
    public static $driver_class_map = array(
        'mysql'     => 'GDBMySQL'
    );
    
    public static $quote_methods = array(
        's'         => 'quote_string',
        'str'       => 'quote_string',
        'string'    => 'quote_string',
        'b'         => 'quote_boolean',
        'bool'      => 'quote_boolean',
        'boolean'   => 'quote_boolean',
        'f'         => 'quote_float',
        'float'     => 'quote_float',
        'i'         => 'quote_integer',
        'int'       => 'quote_integer',
        'integer'   => 'quote_integer',
        'x'         => 'quote_binary',
        'binary'    => 'quote_binary',
        'd'         => 'quote_date',
        'date'      => 'quote_date',
        'dt'        => 'quote_datetime',
        'datetime'  => 'quote_datetime'
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
            
            global $_GDB;
            
            if (!isset($_GDB[$name])) {
                throw new GDBException("Can't find configuration for GDB connection '$name'");
            }
            
            $config = $_GDB[$name];
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
        
        return preg_replace_callback('/{(!?=)?(i|f|b|s|x|dt?)(:(\w+))?}/',
            function($m) use ($replace_args, &$replace_pos, $self) {
            
                $index = isset($m[4]) ? $m[4] : $replace_pos++;

                if (!array_key_exists($index, $replace_args)) {
                    throw new GDBException("Can't replace index '{$index}'");
                }
            
                $value = $replace_args[$index];
                $quote_method = GDB::$quote_methods[$m[2]];

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
                $out[$k] = $raw_value;
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
        return $this->quote_string(Date::parse($value)->to_sql());
    }
    
    public function quote_datetime($value) {
        if ($value === NULL) return 'NULL';
        return $this->quote_string(Date_Time::parse($value)->to_sql());
    }
    
    //
    // Query Helpers
    
    /**
     * 
     *
     */
    public function select_value() {
        
    }
    
    /**
     * Inserts a row into a table
     *
     * @param $table table name to insert into
     * @param $values associative array of field => value. array will be passed to
     *        $this->auto_quote_array() so keys may contain type info, for example
     *        's:username'.
     * @return ID of inserted row, if available
     * @throws GDBException on failure
     */
    public function insert($table, $values) {
        $values = $this->auto_quote_array($values);
        $sql  = "INSERT INTO " . $this->quote_ident($table);
        $sql .= ' (' . implode(',', array_keys($values)) . ')';
        $sql .= ' VALUES (' . implode(',', array_values($values)) . ')';
        $this->x($sql);
        return $this->last_insert_id();
    }
    
    public function update($table, $values) {
        
    }
    
    //
    //
    
    public function new_schema_builder() {
        return new gdb\SchemaBuilder($this);
    }
    
    //
    // Transaction support
    
    private $tx_active  = false;
    
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
}

class GDBMySQL extends GDB
{
    private $link;
    
    protected function connect() {
        if (!$this->link = mysql_connect($this->config['host'],
                                         $this->config['username'],
                                         $this->config['password'])) {
            throw new GDBException("Error connecting to MySQL");
        }
        
        if (!mysql_select_db($this->config['database'], $this->link)) {
            throw new GDBException("Error selecting database '{$this->database}'");
        }
    }
    
    public function quote_ident($ident) {
        return "`$ident`";
    }
    
    public function quote_string($s) {
        return $s === null ? 'NULL' : "'" . mysql_real_escape_string($s, $this->link) . "'";
    }
    
    public function last_insert_id($sequence = null) {
        return mysql_insert_id($this->link);
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
    
    protected function handle_error($sql = null) {
        
        $error  = mysql_error($this->link);
        $code   = mysql_errno($this->link);
        
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
     * Valid modes: array (default), object and value.
     *
     */
    public function mode($mode, $ident, $options = array()) {
        $this->mode         = $mode;
        $this->mode_ident   = $ident;
        $this->mode_options = $options;
        return $this;
    }
    
    /**
     * Returns the first (filtered) row from this result
     */
    public function row() {
        foreach ($this as $v) return $v;
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
                $row[$field] = Date::parse($v);
            } elseif ($type == 'datetime') {
                $row[$field] = Date_Time::parse($v);
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
}
?>