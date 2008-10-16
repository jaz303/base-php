<?php
/**
 * V for Validation
 *
 * Simple routines for validation.
 *
 * Before we begin, this is BasePHP's definition of empty:
 *
 * - a value is NOT empty if it holds a boolean value (true or false)
 * - a value is NOT empty if it is a resource or an object
 * - a value is empty if it is null
 * - an array is only empty if it contains no elements
 * - if none of the above apply, a value is empty if its string representation
 *   is zero-length
 *
 * @see <tt>S</tt> for sanitisation
 */
class V
{
    //
    // Primitives
    
    public static function is_empty($v) {
        if (is_bool($v)) return false;
	    if (is_null($v)) return true;
	    if (is_array($v)) return count($v) == 0;
	    if (is_object($v)) return false;
	    if (is_resource($v)) return false;
	    return strlen($v) == 0;
    }
    
    public static function is_int($i) {
        if (is_bool($i)) return false;
		return (bool) preg_match('/^-?\d+$/', $i);
    }
    
    public static function is_float($f) {
        if (strcmp($f, '-') == 0 || strcmp($f, '') == 0) return false;
        if (is_bool($f)) return false;
		return preg_match('/^-?(\d+)?(\.\d+)?$/', $f);
    }
    
    //
    // Credit Card
    
    /**
     * Validates that a credit card number passes the Mod-10 algorithm.
     * You should sanitise the credit card number beforehand by stripping all
     * spaces/dashes.
     *
     * @param $c credit card number
     * @return true if $c validates using the mod-10 algorithm, false otherwise.
     */
    public static function is_credit_card_number($c) {
        
        if (!preg_match('/^\d+$/', $c)) return false;

        $c = strrev($c);
        $l = strlen($c);
        $s = 0;

        for ($i = 0; $i < $l; $i++) {
            $x = $c[$i];
            if ($i % 2 == 1) $x *= 2;
            if ($x > 9) {
                $x = 1 + ($x % 10);
            }
            $s += $x;
        }

        return $s % 10 == 0;
        
    }
    
    /**
     * Validates that a date (expiry or start date) appearing on a card is valid.
     * We simply check that $m and $y are integral strings and that their values
     * fall within valid ranges.
     *
     * @param $m month
     * @param $y year
     * @return true if $m and $y form a valid card date, false otherwise.
     */
    public static function is_card_date($m, $y) {
        return preg_match('/^\d+$/', $m) &&
               preg_match('/^\d+$/', $y) &&
               $m >= 1 && $m <= 12 &&
               $y >= 0 && $y <= 99;
    }
    
    /**
     * Validates a card security (CV2) number.
     *
     * @param $c security code
     * @return true if $c is a valid security number, false otherwise.
     */
    public static function is_cv2($c) {
        return (bool) preg_match('/^\d{3,5}$/', $c);
    }
    
    //
    // Email
    
    public static function is_email($e) {
        return (bool) preg_match('/^[^\s@]+@[a-z0-9\-]+(\.[a-z0-9\-]+)*$/i', $e);
    }
    
    //
    // UK Postcodes
    
    public static function is_uk_postcode($p) {
        return self::is_short_uk_postcode($p) || self::is_full_uk_postcode($p);
    }
    
    public static function is_short_uk_postcode($p) {
        return (bool) preg_match('/^([A-Z]{1,2}[0-9]{1,2}|[A-Z]{1,2}[0-9][A-Z])$/', $p);
    }
    
    public static function is_full_uk_postcode($p) {
        if ($p == "G1R 0AA") return true;
        return (bool) preg_match('/^([A-Z]{1,2}[0-9]{1,2}|[A-Z]{1,2}[0-9][A-Z]) ?[0-9][ABDEFGHJLNPQRSTUWXYZ]{2}?$/', $p);
    }
}
?>