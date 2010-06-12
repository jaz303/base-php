<?php
if (!defined('MONEY_DEFAULT_CURRENCY')) {
    define('MONEY_DEFAULT_CURRENCY', 'GBP');
}

if (!defined('MONEY_DEFAULT_BANK_CLASS')) {
    define('MONEY_DEFAULT_BANK_CLASS', 'MoneyBank');
}

class MoneyConversionException extends Exception {}

/**
 * Money class
 *
 * @package BasePHP
 * @author Jason Frame
 */
class Money
{
    //
    // Currencies
    
    /**
     * Defines a map of known currencies. Entry format is:
     *
     * currency_code => array(units_per_major, text_symbol, html_symbol)
     */
    public static $CURRENCIES = array
    (
        'GBP' => array(100, '£', '&pound;'),
        'USD' => array(100, '$', '$'),
        'EUR' => array(100, '€', '&euro;')
    );
    
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
    // Parsing
    
    /**
     * Parses a money value from request parameters
     *
     * valid input forms are:
     * array('units' => 100)
     * array('units' => 100, 'currency' => 'USD')
     * "100"
     * "100.00"
     * "100USD"
     * "100.00USD"
     *
     * For strings, it is assumed the value will expressed in a currency's "major"
     * unit (dollars, pounds, euros etc) and so will be multiplied by the currency's
     * "units_per_major" setting.
     *
     * @param $value value to parse
     * @param $with_currency whether to allow currency specification
     *        if $with_currency is false and a currency is specified,
     *        this method will return null.
     * @return Money instance or null if an error was encountered
     */
    public static function from_request($value, $with_currency = false) {
        
        $units = null;
        $currency = MONEY_DEFAULT_CURRENCY;
        
        if (is_array($value)) {
            if (isset($value['units'])) {
                $units = (int) $value['units'];
                if (isset($value['currency'])) {
                    $currency = $value['currency'];
                }
            }
        } elseif (preg_match('/^(\d+)(\.\d+)?\s*([a-z]+)?$/i', trim($value), $matches)) {
            $major = $matches[1];
            if (!empty($matches[3])) {
                $currency = strtoupper($matches[3]);
            }
            if (!empty($matches[2])) {
                // TODO: possibly check that number of decimal places is correct
                // for currency we're dealing with. That assumes, however, that
                // we'll always be dealing with powers of 10. Not sure how that
                // holds.
                $major .= $matches[2];
            }
            if (isset(self::$CURRENCIES[$currency])) {
                $units = floor($major * self::$CURRENCIES[$currency][0]);
            }
        }
        
        if ($units === null) {
            return null;
        }
        
        if ($currency != MONEY_DEFAULT_CURRENCY && !$with_currency) {
            return null;
        }
        
        if (!isset(self::$CURRENCIES[$currency])) {
            return null;
        }
        
        return new Money($units, $currency);
        
    }
    
    //
    // Rounding
    
    public static function round($float, $direction) {
        if ($direction == 'up') {
            return ceil($float);
        } elseif ($direction == 'down') {
            return floor($float);
        } else {
            throw new InvalidArgumentException;
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
     * @param $units integer number of cents/pence/units
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
    
    /**
     * Converts this value to some other currency
     *
     * @param $currency target currency code
     * @param $bank bank used to perform conversion, if null default bank will be used
     * @return converted currency
     * @throws MoneyConversionException if conversion is impossible
     */
    public function convert_to($currency, $bank = null) {
        if ($bank === null) $bank = self::get_bank();
        return $bank->convert($this, $currency);
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
     *
     * @param $string format string
     * @return formatted currency string
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
    
    /**
     * Returns true if this value is zero
     *
     * @return true if this value is zero, false otherwise
     */
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
}

/**
 * Converts money from one currency to another.
 *
 * @package BasePHP
 * @author Jason Frame
 */
class MoneyBank
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
            throw new MoneyConversionException("no conversion for {$m->currency()} -> $to_currency");
        }
        $units = Money::round($m->units() * $factor, $round);
        return new Money($units, $to_currency);
    }
    
    public function rob() {
        return new Money(100000000);
    }
}
?>