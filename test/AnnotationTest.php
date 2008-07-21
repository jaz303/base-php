<?php
/**
 * :foo = true
 * :bar = [1,2,3]
 */
class AnnotationTestStub
{
	/**
	 * :foo = "hello"
	 * :bar = {"a": 1, "b": 2}
	 */
	public function bleem() {}
	
	/**
	 * :foo = 12345
	 * :bar = [true, true, false]
	 */
	public static function baz() {}
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
			array('foo' => 'hello', 'bar' => array('a' => 1, 'b' => 2)),
			Annotation::for_method('AnnotationTestStub', 'bleem')
		);
	}
	
	public static function test_static_method_annotations() {
		assert_equal(
			array('foo' => 12345, 'bar' => array(true, true, false)),
			Annotation::for_method('AnnotationTestStub', 'baz')
		);
	}
}
?>