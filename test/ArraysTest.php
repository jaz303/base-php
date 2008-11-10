<?php
class ArraysTest extends Test_Unit
{
    public function setup() {
        $this->items = array(
            array('k' => 5, 'name' => 'Jason'),
            array('k' => 3, 'name' => 'Jimmy'),
            array('k' => 1, 'name' => 'Ronnie'),
            array('k' => 9, 'name' => 'Bob')
        );
    }
    
    public function test_sort() {
        
        Arrays::kvsort($this->items, 'k');
        
        assert_equal(array(
            array('k' => 1, 'name' => 'Ronnie'),
            array('k' => 3, 'name' => 'Jimmy'),
            array('k' => 5, 'name' => 'Jason'),
            array('k' => 9, 'name' => 'Bob')
        ), $this->items);
        
    }
    
    public function test_sort_reverse() {
        
        Arrays::kvrsort($this->items, 'k');
        
        assert_equal(array(
            array('k' => 9, 'name' => 'Bob'),
            array('k' => 5, 'name' => 'Jason'),
            array('k' => 3, 'name' => 'Jimmy'),
            array('k' => 1, 'name' => 'Ronnie')
        ), $this->items);
        
    }
}
?>