<?php
class H_Test extends Test_Unit
{
    public function test_parse_selector_with_neither() {
        assert_equal(array('id' => null, 'class' => null), H::parse_selector(''));
        assert_equal(array('id' => null, 'class' => null), H::parse_selector('dsfdsf'));
    }
    
    public function test_parse_selector_with_id_only() {
        assert_equal(array('id' => 'foobar', 'class' => null), H::parse_selector('#foobar'));
    }
    
    public function test_parse_selector_with_class_only() {
        assert_equal(array('id' => null, 'class' => 'boo'), H::parse_selector('.boo'));
    }
    
    public function test_parse_selector_with_id_and_class() {
        assert_equal(array('id' => 'blip', 'class' => 'blop'), H::parse_selector('#blip.blop'));
    }
    
    public function test_parse_selector_with_id_and_multiple_classes() {
        assert_equal(array('id' => 'boost', 'class' => 'a b c'), H::parse_selector('#boost.a.b.c'));
    }
    
    public function test_tag_with_no_content_and_no_attributes() {
        assert_equal('<foo></foo>', H::tag('foo'));
    }
    
    public function test_tag_with_no_content_and_attributes() {
        assert_equal('<foo a1="moose" a2="bleem"></foo>', H::tag('foo', array('a1' => 'moose', 'a2' => 'bleem')));
    }
    
    public function test_tag_with_content_and_no_attributes() {
        assert_equal("<foo>bar</foo>", H::tag('foo', 'bar'));
    }
    
    public function test_tag_with_content_and_attributes() {
        assert_equal('<foo a1="moose" a2="bleem">bar</foo>', H::tag('foo', 'bar', array('a1' => 'moose', 'a2' => 'bleem')));
    }
    
    public function test_empty_tag_with_no_attributes() {
        assert_equal('<foo />', H::empty_tag('foo'));
    }
    
    public function test_empty_tag_with_attributes() {
        assert_equal('<foo a="z" b="y" />', H::empty_tag('foo', array('a' => 'z', 'b' => 'y')));
    }
}
?>