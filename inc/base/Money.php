<?php
if (!defined('MONEY_DEFAULT_CURRENCY')) {
    define('MONEY_DEFAULT_CURRENCY', 'GBP');
}

if (!defined('MONEY_DEFAULT_BANK_CLASS')) {
    define('MONEY_DEFAULT_BANK_CLASS', 'Money_Bank');
}

class Money_ConversionError extends Exception {}

/**
 * Money class
 *
 * @package BasePHP
 * @author Jason Frame
 */
class Money
{
    //
    // Bank
    
    private static $bank = null;
    
    public static function set_bank($mb) {
        self::$bank = $mb;
    }
    
    public static function get_bank() {
		if (self::$bank === null) {
			$class = MONEY_DEFAULT_BANK_CLASS;
			self::$bank = new $class;
		}
        return self::$bank;
    }
    
    //
    // Rounding
    
    public static function round($float, $direction) {
        if ($direction == 'up') {
            return ceil($float);
        } elseif ($direction == 'down') {
            return floor($float);
        } else {
            throw new Error_IllegalArgument;
        }
    }
    
    //
    // Parsing
    
    private static function real_currency($currency) {
        return strtoupper(($currency === null) ? MONEY_DEFAULT_CURRENCY : $currency);
    }
    
    //
    // Instance
    
    private $units;
    private $currency;
    
    /**
     * Create a new Money instance
     *
     * @param $units integer number of cents/pences/units
     * @param $currency current, default currency used if null
     */
    public function __construct($units, $currency = null) {
        $this->units    = (int) $units;
        $this->currency = self::real_currency($currency);
    }
    
    /**
     * Returns the number of units for this money value.
     *
     * @return units for this money value
     */
    public function units() {
        return $this->units;
    }
    
    /**
     * Returns the currency of this money value.
     *
     * @return currency for this money value.
     */
    public function currency() {
        return $this->currency;
    }
    
    public function convert_to($currency) {
        if (self::$bank === null) {
            throw new Money_ConversionError("Bank not available");
        } else {
            return self::$bank->convert($this, $currency);
        }
    }
    
    //
    // Formatting
    
    /**
     * Format this currency according to a given format.
     *
     * The following substitutions are understood:
     * %c - currency string
     * %s - currency symbol
     * %h - currency symbol (HTML)
     * %u - units
     * %f - decimal, example: 5.99
     *
     * Any other % formats which do not match exactly options above will be
     * passed to sprintf, which will receive a floating point version for
     * formatting.
     */
    public function format($string) {
        
        $pow = log(self::$CURRENCIES[$this->currency][0], 10);
        
        $subs = array(
            '%c'    => $this->currency,
            '%s'    => self::$CURRENCIES[$this->currency][1],
            '%h'    => self::$CURRENCIES[$this->currency][2],
            '%u'    => $this->units,
            '%f'    => "%01.{$pow}f"
        );
        
        $str = str_replace(array_keys($subs), array_values($subs), $string);
        $str = sprintf($str, $this->units / self::$CURRENCIES[$this->currency][0]);
        
        return $str;
        
    }
    
    //
    //
    
    public function is_zero() {
        return $this->units == 0;
    }
    
    public function negate() {
        return new Money(-$this->units, $this->currency);
    }
    
    public function add(Money $m) {
        if ($this->currency != $m->currency) {
            $m = $m->convert_to($this->currency);
        }
        return new Money($this->units + $m->units, $this->currency);
    }
    
    public function sub(Money $m) {
        if ($this->currency != $m->currency) {
            $m = $m->convert_to($this->currency);
        }
        return new Money($this->units - $m->units, $this->currency);
    }
    
    public function mul($v, $round = 'up') {
        return new Money((int) self::round($this->units * $v, $round), $this->currency);
    }
    
    public function div($v, $round = 'up') {
        return new Money((int) self::round($this->units / $v, $round), $this->currency);
    }
    
    // 
    private static $CURRENCIES = array
    (
        'GBP' => array(100, '£', '&pound;'),
        'USD' => array(100, '$', '$'),
        'EUR' => array(100, '€', '&euro;')
    );
}

/**
 * Converts money from one currency to another.
 *
 * @package BasePHP
 * @author Jason Frame
 */
class Money_Bank
{
    private $factors = array();
    
    public function set_factor($from, $to, $factor, $reverse = false) {
        $this->factors[$from][$to] = $factor;
        if ($reverse) {
            $this->factors[$to][$from] = 1 / $factor;
        }
    }
    
    public function get_factor($from, $to) {
        return isset($this->factors[$from][$to]) ? $this->factors[$from][$to] : null;
    }
    
    public function convert(Money $m, $to_currency, $round = 'down') {
        $factor = $this->get_factor($m->currency(), $to_currency);
        if ($factor === null) {
            throw new Money_ConversionError;
        }
        $units = Money::round($m->units() * $factor, $round);
        return new Money($units, $to_currency);
    }
    
    public function rob() {
        return new Money(100000000);
    }
}
?>