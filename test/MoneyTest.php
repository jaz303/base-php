<?php
class MoneyTest extends Test_Unit
{
    public function test_default_bank_is_instance_of_correct_class() {
        assert_equal(get_class(Money::get_bank()), MONEY_DEFAULT_BANK_CLASS);
    }
    
    public function test_units() {
        
        $m = new Money(100);
        assert_equal(100, $m->units());
        
        $m = new Money(-50);
        assert_equal(-50, $m->units());
        
        $m = new Money(0);
        assert_equal(0, $m->units());
        
    }
    
    public function test_currency() {
        
        $m = new Money(100);
        assert_equal(MONEY_DEFAULT_CURRENCY, $m->currency());
        
        $m = new Money(100, 'USD');
        assert_equal('USD', $m->currency());
        
        $m = new Money(100, 'usd');
        assert_equal('USD', $m->currency());
        
    }
    
    public function test_zero() {
        
        $tests = array(
            100 => false,
            1 => false,
            0 => true,
            -1 => false,
            -100 => false
        );
        
        foreach ($tests as $s => $d) {
            $m = $this->factory($s);
            assert_equal($d, $m->is_zero());
        }
        
    }
    
    public function test_negate() {
        $m = new Money(100);
        $n = $m->negate();
        assert_equal(-100, $n->units());
    }
    
    public function test_add() {
        
        $tests = array(
            array(0, 0, 0),
            array(0, 100, 100),
            array(100, 0, 100),
            array(33, 66, 99),
            array(0, -100, -100),
            array(-50, -40, -90)
        );
        
        foreach ($tests as $t) {
            $l = new Money($t[0]);
            $r = new Money($t[1]);
            assert_equal($t[2], $l->add($r)->units());
        }
        
    }
    
    public function test_implied_conversion_for_add() {
        
        $b = Money::get_bank();
        $b->set_factor('USD', 'GBP', 0.5);
        
        $l = new Money(100, 'GBP');
        $r = new Money(50, 'USD');
        
        $n = $l->add($r);
        
        assert_equal(125, $n->units());
        assert_equal('GBP', $n->currency());
        
    }
    
    public function test_sub() {
        
        $tests = array(
            array(0, 0, 0),
            array(0, 100, -100),
            array(100, 0, 100),
            array(33, 66, -33),
            array(0, -100, 100),
            array(-50, -40, -10)
        );
        
        foreach ($tests as $t) {
            $l = new Money($t[0]);
            $r = new Money($t[1]);
            assert_equal($t[2], $l->sub($r)->units());
        }
        
    }
    
    public function test_implied_conversion_for_sub() {
        
        $b = Money::get_bank();
        $b->set_factor('USD', 'GBP', 0.5);
        
        $l = new Money(100, 'GBP');
        $r = new Money(50, 'USD');
        
        $n = $l->sub($r);
        
        assert_equal(75, $n->units());
        assert_equal('GBP', $n->currency());
        
    }
    
    public function test_mul() {
        
        $tests = array(
            array(1.5, 'up', 150),
            array(1.55, 'up', 155),
            array(1.555, 'up', 156),
            array(1.5, 'down', 150),
            array(1.55, 'down', 155),
            array(1.555, 'down', 155),
        );
        
        foreach ($tests as $d) {
            $m = $this->factory();
            $n = $m->mul($d[0], $d[1]);
            assert_equal($d[2], $n->units());
        }
        
    }
    
    public function test_div() {
        
        $tests = array(
            array(2, 'up', 50),
            array(3, 'up', 34),
            array(2, 'down', 50),
            array(3, 'down', 33),
        );
        
        foreach ($tests as $d) {
            $m = $this->factory();
            $n = $m->div($d[0], $d[1]);
            assert_equal($d[2], $n->units());
        }
        
    }
    
    public function test_format() {
        
        $m = new Money(1234, 'GBP');
        
        assert_equal('GBP', $m->format('%c'));
        assert_equal('£', $m->format('%s'));
        assert_equal('&pound;', $m->format('%h'));
        assert_equal('1234', $m->format('%u'));
        assert_equal('12.34', $m->format('%f'));

        assert_equal('£ 12.34 GBP', $m->format('%s %f %c'));
        
        $m = new Money(99, 'GBP');
        assert_equal('0.99', $m->format('%f'));
        
        $m = new Money(0, 'GBP');
        assert_equal('0.00', $m->format('%f'));
        
    }
    

    private function factory($val = 100, $currency = "GBP") {
        return new Money($val, $currency);
    }
    
}
?>