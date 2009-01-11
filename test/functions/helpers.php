<?php
class functions_helpersTest extends Test_Unit
{
    public function test_int_or_null() {
        assert_null(int_or_null(null));
        assert_equal(5, int_or_null(5));
        assert_equal(1, int_or_null(true));
    }
    
    public function test_trim_to() {
        assert_equal('foo', trim_to('  foobar  ', 3));
        assert_equal('moose', trim_to('     moose     ', 10));
    }
    
    public function test_trim_or_null() {
        assert_null(trim_or_null(null));
        assert_null(trim_or_null(null, 5));
        assert_equal('', trim_or_null('    ', 5));
        assert_equal('foof', trim_or_null('  foof  '));
        assert_equal('foo', trim_or_null('  foof  ', 3));
    }
    
    public function test_trim_to_null() {
        assert_null(trim_to_null(null));
        assert_null(trim_to_null(null, 10));
        assert_null(trim_to_null('   '));
        assert_null(trim_to_null('   ', 5));
        assert_equal('0', trim_to_null(0, 5));
        assert_equal('foof', trim_to_null('  foof  '));
        assert_equal('foo', trim_to_null('  foof  ', 3));
    }
}
?>