<?php
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
            throw new Error_MoneyConversion;
        }
        $units = Money::round($m->units() * $factor, $round);
        return new Money($units, $to_currency);
    }
    
    public function rob() {
        return new Money(100000000);
    }
}
?>