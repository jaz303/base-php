<?php
class Feed_Test extends Test_Unit
{
    public function setup() {
        $this->feed = new Feed;
    }
    
    public function test_title() {
        $this->test_csg('title');
    }
    
    public function test_link() {
        $this->test_csg('link');
    }
    
    public function test_copyright() {
        $this->test_csg('copyright');
    }
    
    public function test_description() {
        $this->test_csg('description');
    }
    
    public function test_language() {
        $this->test_csg('language');
    }
    
    public function test_date() {
        assert_null($this->feed->date());
        assert_equal($this->feed, $this->feed->date('2008-11-27T00:10'));
        $test = new Date_Time(2008, 11, 27, 0, 10, 0);
        _assert($test->equals($this->feed->date()));
    }
    
    public function test_new_feed_has_no_items() {
        $items = $this->feed->items();
        assert_equal(0, count($items));
    }
    
    public function test_new_feed_has_zero_count() {
        assert_equal(0, count(new Feed));
    }

    //
    //
    
    private function test_csg($field, $value = 'test') {
        assert_null($this->feed->$field());
        assert_equal($this->feed, $this->feed->$field($value));
        assert_equal($value, $this->feed->$field());
    }
}
?>