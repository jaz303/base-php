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

class SecurityException extends Exception {}
class SyntaxException extends Exception {}


//
// Base class - rewire functionality

class Base
{
    /**
     * Performs some magic transformations on script input to make life a little
     * more bearable.
     */
    public static function rewire() {

        self::do_rewire($_POST);
        self::do_rewire($_GET);
        self::do_rewire($_REQUEST);
        self::do_rewire($_COOKIE);
        
        // For each uploaded file, create a corresponding file upload object in
        // $_POST. This allows us to deal with uploaded files in a more
        // elegant manner. It is not a security issue because there is no way
        // to inject object instances into $_POST.
        foreach ($_FILES as $key => $file) {
            if (is_string($file['name'])) {
                if ($file['error'] == UPLOAD_ERR_OK) {
                    $_POST[$key] = new UploadedFile($file);
                } else {
                    $_POST[$key] = new UploadedFileError($file['error']);
                }
            } elseif (is_array($file['name'])) {
                if (!is_array($_POST[$key])) {
                    $_POST[$key] = array();
                }
                self::recurse_files(
                    $file['name'],
                    $file['type'],
                    $file['tmp_name'],
                    $file['error'],
                    $file['size'],
                    $_POST[$key]
                );
            }
        }
        
    }
    
    private static function recurse_files($n, $ty, $tm, $e, $s, &$target) {
        foreach ($n as $k => $v) {
            if (is_string($v)) {
                if ($e[$k] == UPLOAD_ERR_OK) {
                    $target[$k] = new UploadedFile(array('name'      => $v,
                                                         'type'      => $ty[$k],
                                                         'tmp_name'  => $tm[$k],
                                                         'size'      => $s[$k]));
                } else {
                    $target[$k] = new UploadedFileError($e[$k]);
                }
            } else {
                if (!is_array($target[$k])) {
                    $target[$k] = array();                
                }
                self::recurse_files($n[$k], $ty[$k], $tm[$k], $e[$k], $s[$k], $target[$k]);
            }
        }
    }
    
    private static function do_rewire(&$array) {
        foreach (array_keys($array) as $k) {
            try {
                if ($k[0] == '@') {
                    $array[substr($k, 1)] = Date::for_param($array[$k]);
                    unset($array[$k]);
                } elseif ($k[0] == '$') {
                    $array[substr($k, 1)] = Money::for_param($array[$k]);
                    unset($array[$k]);
                } elseif (is_array($array[$k])) {
                    self::do_rewire($array[$k]);
                }
            } catch (Exception $e) {
                $array[$k] = null;
            }
        }
    }
}

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