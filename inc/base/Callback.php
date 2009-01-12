<?php
/**
 * Callbacks are invokable objects providing a common interface to the numerous
 * PHP callback types.
 *
 * @todo support currying
 * @todo support inline PHP code
 *
 * @author Jason Frame
 * @package BasePHP
 */
abstract class Callback
{
    /**
     * Turns any traditional 'callback' descriptor into an invokable object.
     *
     * Accepts one or two arguments; supported combinations are:
     *
     * Closure/other invokable object   => object
     * "Class::method"                  => StaticCallback
     * "function"                       => FunctionCallback
     * array("Class", "method")         => StaticCallback
     * array($object, "method")         => InstanceCallback
     * "Class", "method"                => StaticCallback
     * $object, "method"                => MethodCallback
     *
     * @param $arg1 see above
     * @param $arg2 see above
     * @return invokable object
     * @throws Error_IllegalArgument if supplied arguments do not resemble
     *         valid callback.
     */
    public static function create($arg1, $arg2 = null) {
        if ($arg2 === null) {
            if (is_object($arg1)) {
                return $arg1; // assume responds to __invoke()
            } elseif (is_string($arg1)) {
                if (strpos($arg1, '::') !== false) {
                    list($class, $method) = explode('::', $arg1);
                    return new StaticCallback($class, $method);
                } else {
                    return new FunctionCallback($arg1);
                }
            } elseif (is_array($arg1) && count($arg1) == 2 && is_string($arg1[1])) {
                if (is_string($arg1[0])) {
                    return new StaticCallback($arg1[0], $arg1[1]);
                } elseif (is_object($arg1[0])) {
                    return new InstanceCallback($arg1[0], $arg1[1]);
                }
            }
        } elseif (is_string($arg2)) {
            if (is_object($arg1)) {
                return new InstanceCallback($arg1, $arg2);
            } elseif (is_string($arg1)) {
                return new StaticCallback($arg1, $arg2);
            }
        }
        throw new Error_IllegalArguemnt("Couldn't understand the supplied callback description");
    }
}

class FunctionCallback extends Callback
{
    private $function;
    
    public function __construct($function) {
        $this->function = $function;
    }
    
    public function __invoke() {
        return call_user_func_array($this->function, func_get_args());
    }
}

class InstanceCallback extends Callback
{
    private $object;
    private $method;
    
    public function __construct($object, $method) {
        $this->object   = $object;
        $this->method   = $method;
    }
    
    public function __invoke() {
        return call_user_func_array(array($this->object, $this->method), func_get_args());
    }
}

class StaticCallback extends Callback
{
    private $class;
    private $method;
    
    public function __construct($class, $method) {
        $this->class    = $class;
        $this->method   = $method;
    }
    
    public function __invoke() {
        return call_user_func_array(array($this->class, $this->method), func_get_args());
    }
}
?>