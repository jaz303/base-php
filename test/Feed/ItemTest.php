<?php
class Feed_ItemTest extends Test_Unit
{
    public function setup() {
        $this->item = new Feed_Item;
    }
    
    public function test_title() {
        $this->test_csg('title');
    }
    
    public function test_link() {
        $this->test_csg('title');
    }
    
    public function test_description() {
        $this->test_csg('title');
    }
    
    public function test_guid() {
        $this->test_csg('guid');
    }
    
    public function test_guid_permalink() {
        $this->item->guid('foo', false);
        _assert(!$this->item->permalink());
    }
    
    public function test_permalink() {
        _assert($this->item->permalink());
        assert_equal($this->item, $this->item->permalink(false));
        _assert(!$this->item->permalink());
    }
    
    public function test_date() {
        assert_null($this->item->date());
        assert_equal($this->item, $this->item->date('2008-11-27T00:10'));
        $test = new Date_Time(2008, 11, 27, 0, 10, 0);
        _assert($test->equals($this->item->date()));
    }
    
    public function test_new_items_have_no_categories() {
        assert_equal(0, count($this->item->categories()));
    }
    
    public function test_adding_category() {
        $this->item->add_category('c1')
                   ->add_category('c2');
        assert_equal(array('c1', 'c2'), $this->item->categories());
    }
    
    public function test_setting_categories() {
        
        assert_equal($this->item, $this->item->categories(array('foo')));
        assert_equal(array('foo'), $this->item->categories());
    
        $this->item->categories('a', 'b', 'c');
        assert_equal(array('a', 'b', 'c'), $this->item->categories());
    
    }
    
    //
    //
    
    private function test_csg($field, $value = 'test') {
        assert_null($this->item->$field());
        assert_equal($this->item, $this->item->$field($value));
        assert_equal($value, $this->item->$field());
    }
}
?>