<?php
class Model_Base
{
    
    
    
    //
    // Extensions
    
    private $extensions_initialised = false;
    private $extension_methods      = array();
    private $named_extensions       = array();
    private $anonymous_extensions   = array();
    
    protected function initialise_extensions() {
        if (!$this->extensions_initialised) {
            $annotations = Annotation::for_class(get_class($this));
            if (isset($annotations['extensions'])) {
                $class_reflection = new ReflectionClass($this);
                foreach ($annotations['extensions'] as $extension_args) {
                    $extension_class = array_shift($extension_args);
                    $extension = new $extension_class($this, $class_reflection, $extension_args);
                    foreach ($extension->get_exported_methods() as $internal => $external) {
                        $method = new ReflectionMethod($extension, $internal);
                        $this->extension_methods[$external] = $method->getClosure($extension);
                    }
                    if ($extension_name = $extension->get_name()) {
                        $this->named_extensions[$extension_name] = $extension;
                    } else {
                        $this->anonymous_extensions[] = $extension;
                    }
                }
            }
            $this->extensions_initialised = true;
        }
    }
    
    public function __call($method, $args) {
        $this->initialise_extensions();
        if (isset($this->extension_methods[$method])) {
            return call_user_func_array($this->extension_methods[$method], $args);
        } else {
            throw new Error_MethodMissing;
        }
    }
}

class Model_Extension
{
    protected $source;
    protected $reflection;
    protected $options;
    
    public function __construct($source, $reflection, $options) {
        $this->source       = $source;
        $this->reflection   = $reflection;
        $this->options      = $options;
    }
    
    public function get_source() {
        return $this->source;
    }
    
    public function get_exported_methods() {
        return array();
    }
    
    public function get_name() {
        return null;
    }
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