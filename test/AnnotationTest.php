<?php
/**
 * :foo = true
 * :bar = [1,2,3]
 */
class AnnotationTestStub
{
	/**
	 * :is_attribute
	 * :foo = "hello"
	 * :bar = {"a": 1, "b": 2}
	 */
	public function bleem() {}
	
	/**
	 * :foo = 12345
	 * :bar = [true, true, false]
	 */
	public static function baz() {}
	
	/**
	 * :ext[] = 1
	 * :ext[] = [1,2,3]
	 * :ext[] = {"foo": "bar", "baz": "bleem"}
	 */
	public function zip() {}
}

class AnnotationTest extends Test_Unit
{
    public function test_class_annotations() {
		assert_equal(
			array('foo' => true, 'bar' => array(1,2,3)),
			Annotation::for_class('AnnotationTestStub')
		);
	}
	
	public function test_instance_method_annotations() {
		assert_equal(
			array('is_attribute' => true, 'foo' => 'hello', 'bar' => array('a' => 1, 'b' => 2)),
			Annotation::for_method('AnnotationTestStub', 'bleem')
		);
	}
	
	public static function test_static_method_annotations() {
		assert_equal(
			array('foo' => 12345, 'bar' => array(true, true, false)),
			Annotation::for_method('AnnotationTestStub', 'baz')
		);
	}
	
	public static function test_array_annotations() {
	    assert_equal(
	        array(
	            'ext' => array(
	                1,
	                array(1, 2, 3),
	                array('foo' => 'bar', 'baz' => 'bleem')
	            )
	        ),
	        Annotation::for_method('AnnotationTestStub', 'zip')
	    );
	}
}
?>