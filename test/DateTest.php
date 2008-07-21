<?php
class DateTest extends Test_Unit
{
	public function test_date_for_with_null() {
		assert_null(Date::date_for(null));
	}
	
	public function test_date_for_with_date() {
		$date = new Date(1980, 12, 12);
		_assert($date->equals(Date::date_for($date)));
	}
	
	public function test_date_for_with_string() {
		$date = new Date(1980, 12, 12);
		_assert($date->equals(Date::date_for('1980-12-12')));
	}
	
	public function test_is_leap_year() {
		_assert(Date::is_leap_year(1988));
		_assert(!Date::is_leap_year(1989));
		_assert(Date::is_leap_year(2008));
	}
	
	public function test_days_for_month() {
		assert_equal(31, Date::days_for_month(1, 2008));
		assert_equal(29, Date::days_for_month(2, 2008));
		assert_equal(28, Date::days_for_month(2, 2007));
	}
	
	
    public function test_construction_by_array() {
		
        $d = new Date(array(2006, 5, 12));
        $this->assert_instance($d, 2006, 5, 12);
        
        $d = new Date(array(2006, 5));
        $this->assert_instance($d, 2006, 5, 1);
        
        $d = new Date(array(2006));
        $this->assert_instance($d, 2006, 1, 1);
        
    }
    
    public function test_construction_by_args() {
        $d = new Date(2006, 11, 2);
        $this->assert_instance($d, 2006, 11, 2);
    }
    
    public function test_construction_by_timestamp() {
        
        $now = time();
        $d = new Date($now);
        $this->assert_instance($d, date('Y', $now), date('m', $now), date('d', $now));
        
    }
    
    public function test_construction_by_iso() {
        
        $d = new Date("2006-11-05");
        $this->assert_instance($d, 2006, 11, 5);
        
        $d = new Date("1980-12-12T23:12:19");
        $this->assert_instance($d, 1980, 12, 12);
        
    }
    
    public function test_construction_by_string() {
        
    }
    
    public function test_illegal_constructor_arg_throws() {
        
        $illegal = array(
            array(2006, 2, 29),
            array(2006, 13, 12),
            array(2006, 12, 32),
        );
        
        foreach ($illegal as $i) {
            try {
                $d = new Date($i);
                fail();
            } catch (Exception $e) {
                pass();
            }
        }
        
        try {
            $d = new Date("1980-12-12ASDASF");
            fail();
        } catch (Exception $e) {
            pass();
        }
        
    }
    
    public function test_conversion_to_iso() {
        
        $d = new Date(2006, 12, 12);
        assert_equal($d->to_iso_date(), "2006-12-12");
        assert_equal($d->to_iso_datetime(), "2006-12-12T00:00:00");
        assert_equal($d->to_iso_date(), $d->__toString());
        
        $d = new Date(50, 1, 1);
        assert_equal($d->to_iso_date(), "0050-01-01");
        assert_equal($d->to_iso_datetime(), "0050-01-01T00:00:00");
        
    }
    
    public function test_conversion_to_unix() {
        
    }
    
    public function test_conversion_to_unix_fails_if_pre_epoch() {
        try {
            $d = new Date(1969, 1, 1);
            $d->to_unix();
            fail();
        } catch (Error_UnsupportedOperation $e) {
            pass();
        }
    }
    
    public function test_formatting() {
        $now = time();
        $d = new Date($now);
        assert_equal($d->to_format('Y-m-d H:i:s'), date('Y-m-d H:i:s', $now));
    }
    
    public function test_comparison() {
        
        $dates = array(
            new Date(2006, 5, 15),
            new Date(2006, 6, 1),
            new Date(2006, 6, 30)
        );
        
        for ($j = 0; $j < count($dates); $j++) {
            for ($i = 0; $i < count($dates); $i++) {
                $result = $dates[$i]->compare_to($dates[$j]);
                $expect = $i == $j ? 0 : ($i > $j ? 1 : -1);
                assert_equal($result, $expect);
                if ($expect == 0) {
                    _assert($dates[$i]->equals($dates[$j]));
                } else {
                    _assert(!$dates[$i]->equals($dates[$j]));
                }
            }
        }

    }
    
    private function assert_instance($d, $year, $month = 1, $day = 1) {
        assert_equal($d->year(),      $year);
        assert_equal($d->month(),     $month);
        assert_equal($d->day(),       $day);
        assert_equal($d->hour(),      0);
        assert_equal($d->minute(),    0);
        assert_equal($d->second(),    0);
    }
}
?>
