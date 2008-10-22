<?php
class ISO_CountryTest extends Test_Unit
{
    public function test_codes_returns_array_of_valid_code() {
        $codes = ISO_Country::codes();
        _assert(is_array($codes));
        foreach ($codes as $c) _assert(ISO_Country::exists($c));
    }
    
    public function test_exists_works() {
        _assert(ISO_Country::exists('GB'));
        _assert(!ISO_Country::exists('XYZ'));
    }
    
    public function test_names_returns_array_of_valid_items() {
        $names = ISO_Country::names();
        foreach ($names as $code => $name) {
            assert_equal($name, ISO_Country::name_for($code));
        }
    }
    
    public function test_preferred_countries_works() {
        
        $names = ISO_Country::names(array('GB', 'DK'), '---');
        
        $c == 0;
        foreach ($names as $code => $name) {
            if ($c == 0) {
                assert_equal('GB', $code);
            } elseif ($c == 1) {
                assert_equal('DK', $code);
            } elseif ($c == 2) {
                assert_equal('', $code);
                assert_equal('---', $name);
                break;
            }
            $c++;
        }
        
    }
    
    public function test_name_for_returns_value_when_valid() {
        assert_equal('United Kingdom', ISO_Country::name_for('GB'));
    }
    
    public function test_name_for_throws_when_invalid_and_no_default() {
        try {
            ISO_Country::name_for('XYZ');
            fail();
        } catch (Error_NoSuchElement $nse) {
            pass();
        }
    }
    
    public function test_name_for_returns_default_when_invalid_and_default() {
        assert_equal('Foobar', ISO_Country::name_for('XYZ', 'Foobar'));
    }
}
?>