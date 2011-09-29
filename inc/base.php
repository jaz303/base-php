<?php
/**
 * base.php
 */

//
// Common Exceptions

// BEFORE CREATING YOUR OWN, PLEASE REMEMBER THE SPL DEFINES THESE EXCEPTIONS:
//
// LogicException
//   BadFunctionCallException
//     BadMethodCallException
//   DomainException
//   InvalidArgumentException
//   LengthException
//   OutOfRangeException
// RuntimeException
//   OutOfBoundsException
//   OverflowException
//   RangeException
//   UnderflowException
//   UnexpectedValueException

/**
 * Thrown when an object is requested to take some action that its
 * current state does not support.
 */
class IllegalStateException extends DomainException {}

class UnsupportedOperationException extends RuntimeException {}

/**
 * Represents any sort of IO error
 */
class IOException extends RuntimeException {}

/**
 * Thrown when some operation involving finding something fails.
 *
 * It's explicitly *not* for range/bounds exceptions (e.g. access beyond an array's bounds).
 * In these circumstances use an OutOfRangeException or an OutOfBoundsException from the SPL.
 */
class NotFoundException extends LogicException {}

class NoSuchMethodException extends RuntimeException {}

class SecurityException extends Exception {}
class SyntaxException extends Exception {}

/**
 * General interface denoting a persistable object.
 *
 * Additionally, a persistable object should generally implement <tt>get_</tt>,
 * <tt>set_</tt> and, optionally, <tt>is_</tt> methods for each of its attributes.
 *
 * @package BasePHP
 */
interface Persistable
{
    /**
     * Save this object.
     *
     * @return true on success, false on failure
     */
	public function save();

	/**
	 * Returns true if this object has been persisted, false otherwise.
	 *
	 * @return true if this object has been persisted, false otherwise.
	 */
	public function is_saved();

	/**
	 * Returns a copy of this object's attributes as an associative array.
	 *
	 * @return a copy of this object's attributes as an associative array.
	 */
	public function attributes();

	/**
	 * Sets all of this object's attributes from an associative array.
	 * May throws an <tt>\InvalidArgumentException</tt> exception if any keys in
	 * <tt>$array</tt> are not valid properties of this object.
	 *
	 * @param $attributes array of attributes to set on this object.
	 * @throws \InvalidArgumentException if specified properties do not exist.
	 */
	public function set_attributes(array $attributes);

    /**
     * Returns true if this object is valid, false otherwise.
     *
     * @return true if this object is valid, false otherwise.
     */
	public function is_valid();

    /**
     * Returns an <tt>\Errors</tt> objects containing errors generated on the
     * last call to <tt>is_valid()</tt>
     *
     * @return <tt>\Errors</tt> instance detailing validation errors
     */
	public function errors();
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
     * Closure/other invokable object
     * "Class::method"
     * "function"
     * array("Class", "method")
     * array($object, "method")
     * "Class", "method"
     * $object, "method"
     *
     * @param $arg1 see above
     * @param $arg2 see above
     * @return invokable object
     * @throws InvalidArgumentException if supplied arguments do not resemble
     *         valid callback.
     */
    public static function create($arg1, $arg2 = null) {
        if ($arg2 === null) {
            if (is_object($arg1)) {
        		return $arg1; // assume implements __invoke()
        	} else {
        	    // handles $arg1 being either
        	    // string, array(string, string) or array(object, string)
        	    return function() use($arg1) {
                    return call_user_func_array($arg1, func_get_args());
                };
            }
        } elseif (is_string($arg2)) {
            return function() use ($arg1, $arg2) {
                return call_user_func_array(array($arg1, $arg2), func_get_args());
            };
        }
        throw new InvalidArgumentException("Couldn't understand the supplied callback description");
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

class StringUtils
{
    const ALPHA_LOWER   = 1;
    const ALPHA_UPPER   = 2;
    const ALPHA         = 3;
    const NUMERIC       = 4;
    const ALPHA_NUMERIC = 7;
  
    public static function random($length, $set = self::ALPHA_NUMERIC) {
        if (is_string($set)) {
            $chars = $set;
        } else {
            $chars = '';
            if ($set && self::ALPHA_LOWER)      $chars .= 'abcdefghijklmnopqrstuvwxyz';
            if ($set && self::ALPHA_UPPER)      $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if ($set && self::ALPHA_NUMERIC)    $chars .= '0123456789';
        }
        $cl = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[rand(0, $cl)];
        }
        return $out;
    }
}
?>