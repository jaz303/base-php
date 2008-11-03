<?php
class Model_StateError extends Exception {}
class Model_NoSuchAttributeError extends Exception {}

class Model_Base extends Component implements ArrayAccess
{
    public function __construct($p = null) {
        if (is_array($p)) { // Explicit attributes
            
        } elseif ($p !== null) { // Load by ID
            
        } else { // Default attributes
            $this->attributes = static::defaults();
        }
    }
    
    //
    // Database connection
    
    public function __get($k) {
        if ($k == 'db') {
            $this->db = static::db();
            return $this->db;
        } else {
            return null;
        }
    }
    
    public static function db() {
        return GDB::instance();
    }
    
    //
    // Magic stuff for attributes
    
    public function __call($method, $args) {
        if (preg_match('/^(get_|set_|is_)(\w+)$/', $method, $matches)) {
            if ($method[0] == 'g') {
                return $this->read_attribute($matches[2]);
            } elseif ($method[0] == 's') {
                $this->write_attribute($matches[2], $args[0]);
                return $this;
            } elseif ($method[0] == 'i') {
                return (bool) $this->read_attribute($matches[2]);
            }
        }
    }
    
    //
    // Attribute Handling
    
    public static function defaults() {
        return array();
    }
    
    public static function attributes() {
        return array();
    }
    
    protected $attributes;
    
    public function get_attributes() {
        return $this->attributes;
    }
    
    public function get_quoted_attributes() {
        
        $db         = $this->db;
        $attributes = $this->get_attributes();
        $types      = static::attributes();
        $quoted     = array();
        
        foreach ($attributes as $k => $v) {
            $type = $types[$k];
            switch ($type) {
                case 'serialize':
                    $quoted[$k] = $db->quote_string(serialize($v));
                    break;
                case 'int':
                case 'float':
                case 'date':
                case 'datetime':
                case 'bool':
                case 'binary':
                case 'string':
                    $quoted[$k] = $db->{"quote_{$type}"}($v);
                    break;
            }
        }
        
        return $quoted;
        
    }
    
    public function assert_attribute($a) {
        if (!array_key_exists($a, $this->attributes)) {
            throw new Model_NoSuchAttributeError("No such attribute - $a");
        }
    }
    
    protected function read_attribute($a) {
        $this->assert_attribute($a);
        return $this->attributes[$a];
    }
    
    protected function write_attribute($a, $v) {
        $this->assert_attribute($a);
        $this->attributes[$a] = $v;
    }
    
    /**
     * Returns <var>true</var> if an attribute named <var>$o</var> exists.
     * Unlike <var>offsetGet</var> and <var>offsetSet</var>, this method looks only
     * at the attributes defined for this model and not at any non-attribute getters
     * and setters that may also be defined.
     *
     * @param $o attribute name for which to check existence of
     * @return true if attribute $o exists in this model
     */
    public function offsetExists($o) {
        return array_key_exists($o, $this->attributes);
    }
    
    /**
     * Array access is delegated to get_{offset}()
     * This allows for transparent mixing of attribute and non-attribute offsets.
     */
    public function offsetGet($o) {
        return $this->{"get_$o"}();
    }
    
    /**
     * Array setting is delegated to set_{offset}()
     * This allows for transparent mixing of attribute and non-attribute offsets.
     */
    public function offsetSet($o, $v) {
        $this->{"set_$o"}($v);
    }
    
    /**
     * Can't unset an offset.
     */
    public function offsetUnset($o) {
        throw new Error_UnsupportedOperation("Model attributes cannot be unset");
    }
    
    //
    // ID stuff
    
    protected $id = null;
    
    public function get_id() {
        return $this->id;
    }
    
    protected function set_id($id) {
        $this->id = $id === null ? null : (int) $id;
    }
    
    public function is_new() {
        return $this->id === null;
    }
    
    public function is_saved() {
        return $this->id !== null;
    }
    
    //
    // Persistence
    
    public static function table_name() {
        return null;
    }
    
    public function get_table_name() {
        return static::table_name();
    }
    
    public function assert_saved($msg = 'This operation requires that the model is saved') {
        if (!$this->is_saved()) {
            throw new Model_StateError($msg);
        }
    }
    
    public function save() {
        if ($this->is_saved()) {
            $this->db->update($table, $this->get_quoted_attributes(), 'id', $this->get_id());
        } else {
            $this->set_id($this->db->insert($table, $this->get_quoted_attributes()));
        }
    }
    
    public function delete() {
        $this->assert_saved();
        $this->db->delete($table, 'id', $this->get_id());
        $this->set_id(null);
    }
    
    //
    // Helpers for generating associations
    
    protected function has_one($name, $class_name, $options = array()) {
        return new Model_Association_HasOne($this, $name, $class_name, $options);
    }
    
    protected function has_many($name, $class_name, $options = array()) {
        return new Model_Association_HasMany($this, $name, $class_name, $options);
    }
    
    protected function belongs_to($name, $class_name, $options = array()) {
        return new Model_Association_BelongsTo($this, $name, $class_name, $options);
    }
    
    //
    // Events
    
}

abstract class Model_Association extends Component_Extension
{
    protected $association_name;
    protected $associated_class;
    
    public function __construct($source, $reflection, $name, $class, $options) {
        $this->association_name     = $name;
        $this->assocaited_class     = $class;
        parent::__construct($source, $reflection, $options);
    }
}

class Model_Association_HasOne extends Model_Association
{
    
}

class Model_Association_HasMany extends Model_Association
{
    
}

class Model_Association_BelongsTo extends Model_Association
{
    
}




// class HasMany implements IteratorAggregate
// {
//   protected $source;
//   protected $name;
//   protected $class_name;
//   
//   protected $collection   = null;
//   protected $dirty        = false;
//   
//   public function __construct($source, $name, $class_name) {
//     $this->source = $source;
//   }
//   
//   public function exported_methods() {
//     return array('self' => $this->name);
//   }
//   
//   public function self() {
//     return $this;
//   }
//   
//   public function build() {
//     // create instance
//     // set foreign key
//     // return
//   }
//   
//   public function add_id($item_id) {
//     
//   }
//   
//   public function add($item) {
//     
//   }
//   
//   public function clear() {
//     $this->collection = array();
//     $this->dirty = true;
//   }
//   
//   public function reload() {
//     $this->collection = null;
//     return $this;
//   }
//   
//   public function getIterator() {
//     $this->load();
//     return new ArrayIterator($this->collection);
//   }
//   
//   private function load() {
//     if ($this->collection === null) {
//       // $class_name::push_scope();
//       // $this->collection = $class_name::find_all();
//       // $class_name::pop_scope();
//     }
//   }
//   
//   //
//   // Callbacks
//   
//   public function prepare_for_save() {}
//   
//   public function before_save() {}
//   public function after_save() {}
//   
// }




?>