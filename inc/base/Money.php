<?php
if (!defined('MONEY_DEFAULT_CURRENCY')) {
    define('MONEY_DEFAULT_CURRENCY', 'GBP');
}

if (!defined('MONEY_DEFAULT_BANK_CLASS')) {
    define('MONEY_DEFAULT_BANK_CLASS', 'Money_Bank');
}

class Error_MoneyConversion extends Exception {}

class Money
{
    
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
    
    public function units() {
        return $this->units;
    }
    
    public function currency() {
        return $this->currency;
    }
    
    public function convert_to($currency) {
        if (self::$bank === null) {
            throw new Error_MoneyConversion("Bank not available");
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
?>