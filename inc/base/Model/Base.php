<?php
/**
 * :protected = ["id", "created_at", "updated_at"]
 */
class Model_Base
{
    /**
     * :attribute = "int"
     */
    protected $id = null;
    
    public function get_id() { return $this->id; }
    protected function set_id($id) { $this->id = $id === null ? null : (int) $id; }
    
    //
    // Attribute handling
    
    public function get_attribute_reflections() {
        return Model_Base_AttributeReflection::for_object($this);
    }
    
    public function get_attribute_names() {
        $array = array();
        foreach ($this->get_attribute_reflections() as $reflection) {
            $array[] = $reflection->get_name();
        }
        return $array;
    }
    
    public function get_attributes() {
        $array = array();
        foreach ($this->get_attribute_reflections() as $reflection) {
            $array[$reflection->get_name()] = $reflection->get_value($this);
        }
        return $array;
    }
    
    public function get_quoted_attributes() {
        $array = array();
        foreach ($this->get_attribute_reflections() as $reflection) {
            $array[$reflection->get_name()] = $reflection->get_quoted_value($this);
        }
        return $array;
    }
}

class Model_Base_AttributeReflection
{
    private $cache = array();
    
    public static function for_object($object) {
        return self::for_class(get_class($object));
    }
    
    public static function for_class($class) {
        if (!isset(self::$cache[$class])) {
            $all = array();
            foreach (Annotation::select($class, null, 'attribute', true) as $annote) {
                $attrib = new Model_Base_AttributeReflection($annote[0], $annote[1]);
                $all[$attrib->get_name()] = $attrib;
            }
            self::$cache[$class] = $all;
        }
        return self::$cache[$class];
    }
    
    private $reflector;
    private $name;
    private $type;
    
    public function __construct($reflector, $annotes) {
        $this->reflector    = $reflector;
        $this->name         = isset($annotes['attribute_name']) ? $annotes['attribute_name'] : $reflector->getName();
        $this->type         = $annotes['attribute'];
    }
    
    public function get_name() { return $this->name; }
    public function get_type() { return $this->type; }
    
    public function get_value($instance) {
        if ($reflector instanceof ReflectionProperty) {
            return $reflector->getValue($instance);
        } else {
            return $reflector->invoke($instance);
        }
    }
    
    public function get_quoted_value($instance) {
        $val = $this->get_value($instance);
        $type = $this->get_type();
        switch ($type) {
            case 'serialize':
                return FF_Model::db()->quote_string(serialize($val));
            case 'int':
            case 'float':
            case 'date':
            case 'datetime':
            case 'bool':
            case 'binary':
            case 'string':
                return FF_Model::db()->{"quote_{$type}"}($val);
            default:
                throw new Exception("Unknown type: $type");
        }
    }
}
?>