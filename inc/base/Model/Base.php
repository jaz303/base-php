<?php
class Model_Base extends Component
{
    public function __construct($p = null) {
        if (is_array($p)) {
            // set up params
        } elseif ($p !== null) {
            // load by ID
        }
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