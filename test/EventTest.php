<?php
class Event_Test extends Test_Unit
{
    public function test_source_name_and_args_accessible() {
        $e = new Event('a', 'b', array('c' => 'd'));
        assert_equal('a', $e->source);
        assert_equal('b', $e->name);
        assert_equal(array('c' => 'd'), $e->args);
    }
    
    public function test_get_delegates_to_args() {
        $e = new Event('a', 'b', array('d' => 100));
        assert_equal(100, $e->d);
        assert_null($e->e);
    }
    
    public function test_isset_delegates_to_args() {
        $e = new Event('a', 'b', array('c' => 1));
        _assert(isset($e->c));
        _assert(!isset($e->d));
    }
}
?>