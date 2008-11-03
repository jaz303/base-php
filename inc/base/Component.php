<?php
/**
 * @package BasePHP
 * @author Jason Frame
 */
abstract class Component
{
    //
    // Extensions
    
    protected $self_reflection      = null;
    private $extension_methods      = array();
    private $named_extensions       = array();
    private $anonymous_extensions   = array();
    
    /**
     * Override this method to return an array of initialised extensions for this instance.
     *
     * @return array of <var>Component_Extension</var> instances.
     */
    protected function extensions() {
        return array();
    }
    
    /**
     * Convenience method for instantiating an extension.
     *
     * @param $class_name name of extension class to be instantiated
     * @param $options optional hash of options to pass to extension's constructor
     * @return extension instance
     */
    protected function create_extension() {
        $args = func_get_args();
        $class = array_shift($args);
        return new $class($this, $this->self_reflection, $args);
    }
    
    protected function initialise_extensions() {
        if ($this->self_reflection === null) {
            $this->self_reflection = new ReflectionClass($this);
            foreach ($this->extensions() as $extension) {
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
    }
    
    public function __call($method, $args) {
        $this->initialise_extensions();
        if (isset($this->extension_methods[$method])) {
            return call_user_func_array($this->extension_methods[$method], $args);
        } else {
            throw new Error_MethodMissing;
        }
    }
    
    //
    // Events
    
    protected function fire_event() {
        
        $args = func_get_args();
        $name = array_shift($args);
        
        if (method_exists($this, $name)) {
            call_user_func_array(array($this, $name), $args);
        }
        
        foreach ($this->named_extensions as $ext) {
            $ext->fire_event($name, $args);
        }
        
        foreach ($this->anonymous_extensions as $ext) {
            $ext->fire_event($name, $args);
        }
        
    }
}

/**
 * @package BasePHP
 * @author Jason Frame
 */
class Event
{
    public $source;
    public $name;
    public $args;
    
    public function __construct($source, $name, $args = array()) {
        $this->source   = $source;
        $this->name     = $name;
        $this->args     = $args;
    }
    
    public function __isset($k) {
        return array_key_exists($k, $this->args);
    }
    
    public function __get($k) {
        return isset($this->args[$k]) ? $this->args[$k] : null;
    }
}

/**
 * @package BasePHP
 * @author Jason Frame
 */
abstract class Component_Extension
{
    protected $source;
    protected $reflection;
    protected $options;
    
    /**
     * @param $source <var>Component</var> to which this extension is attached.
     * @param $reflection <var>ReflectionClass</var> derived from the class of the
     *        source <var>Component</var>
     * @param $options optional hash of options parameters
     */
    public function __construct($source, $reflection, $options = array()) {
        $this->source       = $source;
        $this->reflection   = $reflection;
        $this->options      = $options;
    }
    
    /**
     * Returns the source <var>Component</var> for this extension to which this extension
     * is attached.
     *
     * @return <var>Component</var> to which this extension is attached.
     */
    public function get_source() {
        return $this->source;
    }
    
    /**
     * Returns a hash of methods which should decorate the source class.
     * Array should be of the form internal_name => external_name, that is, calls to
     * external_name on the source instance will the delegated to internal_name
     * on the extension instance. This functionality can be used, for example, to
     * create named associations for model objects which can be manipulated with
     * explicit methods:
     *
     * $model->users()->find_all()
     * $model->build_user()
     *
     * @return map of exported method names in the form internal_name => external_name
     */
    public function get_exported_methods() {
        return array();
    }
    
    /**
     * Returns the name of this extension, allowing it to be looked up via the source
     * <var>Component</var>. Return <var>null</var> to denote an anonymous extension.
     *
     * @return the name of this extension, or <var>null</var> for anonymous extensions.
     */
    public function get_name() {
        return null;
    }
    
    /**
     * Returns <var>$this</var>. Used when an exported method which returns the extension
     * itself is desired.
     *
     * @return <var>$this</var> instance.
     */
    public function self() {
        return $this;
    }
    
    public function fire_event($event) {
        
    }
}
?>