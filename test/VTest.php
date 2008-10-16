<?php
class VTest extends Test_Unit
{
    public function test_empty() {
        foreach (array(
            array('',               true),
            array(false,            false),
            array(true,             false),
            array(array(),          true),
            array(array(1,1,1),     false),
            array('1.0',            false),
            array('0',              false),
            array(null,             true)
        ) as $test) {
            assert_equal($test[1], V::is_empty($test[0]));
        }
    }

    public function test_int() {
        foreach (array(
            array('', false),
            array('-', false),
            array(1, true),
            array("50", true),
            array(1.1, false),
            array(1.21, false),
            array("0.15", false),
            array("-10.0", false),
            array("-5", true),
            array("10ada", false),
            array("flooble", false),
            array(true, false)
        ) as $test) {
            assert_equal($test[1], V::is_int($test[0]));
        }
    }
    
    public function test_float() {
        foreach (array(
            array('', false),
            array('-', false),
            array(1, true),
            array("50", true),
            array(1.1, true),
            array(1.21, true),
            array("0.15", true),
            array("-10.0", true),
            array("10ada", false),
            array("flooble", false),
            array(true, false)
        ) as $test) {
            assert_equal($test[1], V::is_float($test[0]));
        }
    }
    
    public function test_cc() {
        $this->run_simple_tests('is_credit_card_number', array(
            'abc'					=> false,
			'4408041234567890'		=> false,
            '4408041234567893'      => true,
            '4417123456789112'      => false,
            '4417123456789113'      => true
        ));
    }
    
    public function test_card_date() {
		foreach (array(
		    array(1, 1, true),
			array(1, 99, true),
			array(0, 0, false),
			array(0, 1, false),
			array(1, 0, true),
			array(12, 0, true),
			array(12, 100, false),
			array(13, 0, false),
			array(13, 1, false)
		) as $test) {
		    assert_equal($test[2], V::is_card_date($test[0], $test[1]));
		}
		
	}
	
	public function test_cv2() {
        $this->run_simple_tests('is_cv2', array(
             '12'        => false,
             '123'       => true,
             '1234'      => true,
             '12345'     => true,
             '123456'    => false,
             ' 123'      => false,
             '123 '      => false,
             ' 123 '     => false,
             '123a'      => false,
             'a123'      => false,
             '12-2'      => false
         ));
	}
    
    public function test_uk_postcode() {
        $this->run_simple_tests('is_uk_postcode', array(
            'G12 1TY'                   => true,
            ' WH13 8HH'                 => false,
            'WH13 8HH'                  => false,
            'WH13 8HH'                  => true,
            'WH13'                      => true
        ));
    }
    
    public function test_short_uk_postcode() {
        $this->run_simple_tests('is_short_uk_postcode', array(
            'G1'                        => true,
            'G16'                       => true,
            'G'                         => false,
            'G17NA'                     => false,
            'G1 7NB'                    => false,
            ' G23'                      => false,
            'G32 '                      => false
        ));
    }
    
    public function test_full_uk_postcode() {
        $this->run_simple_tests('is_full_uk_postcode', array(
            'G1'                        => false,
            'G'                         => false,
            'G17NA'                     => true,
            'G1 7NB'                    => true,
            'G17 NA'                    => false,
            ' G1 7NB'                   => false,
            'G1 7NB '                   => false
        ));
    }
    
    public function test_email() {
        $this->run_simple_tests('is_email', array(
            'jason@magiclamp.co.uk'     => true,
            'foo.bar@yahoo.com'         => true,
            ' a@b.com'                  => false,
            'a@b.com '                  => false,
            'alfred'                    => false
        ));
    }
    
    private function run_simple_tests($method, $array) {
        foreach ($array as $value => $expect)
            self::run_simple_test($method, $value, $expect);
    }
    
    private function run_simple_test($method, $value, $expect) {
        assert_equal($expect, call_user_func(array('V', $method), $value));
    }
}
?>