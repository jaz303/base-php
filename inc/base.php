<?php
/**
 * base.php
 */

//
// Common Exceptions

/**
 * @deprecated use InvalidArgumentException from SPL
 */
class IllegalArgumentException extends Exception {}

/**
 * Thrown when an object is requested to take some action that its
 * current state does not support.
 */
class IllegalStateException extends Exception {}

class UnsupportedOperationException extends Exception {}

/**
 * Represents any sort of IO error
 */
class IOException extends Exception {}

/**
 * Thrown when some operation involving finding something fails.
 *
 * It's explicitly *not* for range/bounds exceptions (e.g. access beyond an array's bounds).
 * In these circumstances use an OutOfRangeException or an OutOfBoundsException from the SPL.
 */
class NotFoundException extends Exception {}

class NoSuchMethodException extends Exception {}

class SecurityException extends Exception {}
class SyntaxException extends Exception {}



/**
 * Callbacks are invokable objects providing a common interface to the numerous
 * PHP callback types.
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
     * $object, "method"                => InstanceCallback
     *
     * @param $arg1 see above
     * @param $arg2 see above
     * @return invokable object
     * @throws IllegalArgumentException if supplied arguments do not resemble
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
        throw new IllegalArgumentException("Couldn't understand the supplied callback description");
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

/**
 * A rather naive inflector
 *
 * I have no interest in making this into some thing complex with tons of rule-based
 * processing.
 */
class Inflector
{
    public static function pluralize($count, $singular, $plural = null) {
        if ($count == 1) {
            return $singular;
        } else {
            return $plural === null ? ($singular . 's') : $plural;
        }
    }
    
    public static function humanize($string) {
        return ucfirst(strtolower(str_replace(array('_', '-'), ' ', $string)));
    }
    
    /**
     * CamelCase -> camel_case
     * already_underscored -> already_underscored
     */
    public static function underscore($string) {
        return strtolower(preg_replace('/([^^])([A-Z])/e', '\1_\2', $string));
    }
    
    /**
     * foo_bar -> FooBar
     * FooBar -> FooBar
     */
    public static function camelize($string) {
        return preg_replace('/(^|_)([a-z])/ie', 'strtoupper("$2")', $string);
    }
}
?>