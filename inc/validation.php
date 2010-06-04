<?php
/**
 * V is Validation
 *
 * An assorted collection of simple validation methods.
 *
 * @package base-php
 */
class V
{
    /**
     * Checks a value for emptiness.
     *
     * Our definition of empty:
     * array - empty if no elements
     * string -> empty if zero-length
     * null -> empty
     * integer, float, boolean, object, resource -> not empty
     *
     * @param $value value to check for emptiness
     * @return true if $value is empty, false otherwise
     */
    public static function is_empty($value) {
        if (is_array($value)) {
            return empty($value);
        } elseif (is_string($value)) {
            return strlen($value) == 0;
        } else {
            return $value === null;
        }
    }
    
    /**
     * Checks a value for blankness.
     *
     * A blank value is one which is empty, a string containing only whitespace, or
     * an object whose is_blank() method returns true.
     *
     * @param $value value to check for blankness
     * @return true if $value is blank, false otherwise
     */
    public static function is_blank($value) {
        if (is_object($value) && method_exists($value, 'is_blank')) return $value->is_blank();
        if (is_string($value)) $value = trim($value);
        return self::is_empty($value);
    }
    
    public static function is_integer($value) {
        if (is_integer($value)) return true;
        return preg_match('/^\s*-?\d+\s*$/', $value);
    }
    
    public static function is_numeric($value) {
        if (is_integer($value) || is_float($value)) return true;
        return preg_match('/^\s*-?\d+(\.\d+)?\s*$/', $value);
    }
    
    public static function is_email($email, $use_dns = true) {
        if (!preg_match('/^[^@]+@([a-z0-9-]+(\.[a-z0-9-]+))*$/i', $value, $matches)) {
			return false;
		} elseif ($use_dns) {
		    $tmp = array();
		    return getmxrr($matches[1], $tmp) || dns_get_record($matches[1], DNS_A);
		} else {
		    return true;
		}
    }
}
?>