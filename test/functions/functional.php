<?php
class functions_functionalTest extends Test_Unit
{
    public function test_all() {
        _assert(all(array(1,2,3), function($v) { return $v > 0; }));
        _assert(!all(array(1,2,3), function($v) { return $v > 2; }));
    }
    
    public function test_any() {
        _assert(any(array(1,2,3), function($v) { return $v > 2; }));
        _assert(!any(array(1,2,3), function($v) { return $v > 4; }));
    }

    public function test_every() {
        $c = 0;
        every(array(1,2,3), function($v, $i) use(&$c) { $c += ($v + $i); });
        assert_equal(9, $c);
    }
    
    public function test_kevery() {
        $c = 0;
        kevery(array(1 => 1, 2 => 2, 3 => 3), function($k, $v, $i) use(&$c) { $c += ($k + $v + $i); });
        assert_equal(15, $c);
    }
    
    public function test_map() {
        assert_equal(array(2,4,6), map(array(1,2,3), function($v) { return $v * 2; }));
    }
    
    public function test_kmap() {
        assert_equal(array('a' => 2, 'b' => 4, 'c' => 6),
                        kmap(array('a' => 1, 'b' => 2, 'c' => 3), function($v) {
                            return $v * 2;
                        })
                    );
    }
    
    public function test_inject() {
        assert_equal(15, inject(array(1,2,3,4,5), 0, function($m, $v) { return $m + $v; }));
    }
    
    public function test_kinject() {
        assert_equal(25, kinject(array(1,2,3,4,5), 0, function($m, $k, $v) { return $m + $k + $v; }));
    }
    
    public function test_filter() {
        assert_equal(array(2, 4), filter(array(1,2,3,4), function($v) { return $v % 2 == 0; }));
    }
    
    public function test_kfilter() {
        assert_equal(array('b' => 2, 'd' => 4),
                        kfilter(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4), function($v) {
                            return $v % 2 == 0;
                        })
                    );
    }
    
    public function test_reject() {
        assert_equal(array(1, 3), reject(array(1,2,3,4), function($v) { return $v % 2 == 0; }));
    }
    
    public function test_kreject() {
        assert_equal(array('a' => 1, 'c' => 3),
                        kreject(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4), function($v) {
                            return $v % 2 == 0;
                        })
                    );
    }
}
?>