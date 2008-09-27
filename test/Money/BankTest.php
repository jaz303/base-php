<?php
class Money_BankTest extends Test_Unit
{
    public function setup() {
        $this->bank = new Money_Bank;
    }
    
    public function test_set_and_get_factor() {
        
        $this->bank->set_factor('GBP', 'USD', 2);
        assert_equal(2, $this->bank->get_factor('GBP', 'USD'));
        
        $this->bank->set_factor('AAA', 'BBB', 4, true);
        assert_equal(4, $this->bank->get_factor('AAA', 'BBB'));
        assert_equal(0.25, $this->bank->get_factor('BBB', 'AAA'));
        
    }
    
    public function test_conversion() {
        
        $this->bank->set_factor('GBP', 'USD', 2);
        
        $m = new Money(100, 'GBP');
        
        $r = $this->bank->convert($m, 'USD');
        assert_equal(200, $r->units());
        assert_equal('USD', $r->currency());
        
    }
}
?>