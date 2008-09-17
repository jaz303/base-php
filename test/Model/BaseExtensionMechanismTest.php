<?php
/**
 * :extensions[] = ["MBEMT_Extension_1"]
 * :extensions[] = ["MBEMT_Extension_2", 1, 2, [true, false], {"foo": "bar"}]
 */
class MBEMT_Model extends Model_Base
{
}

class MBEMT_Extension_1 extends Model_Extension
{
    public function get_exported_methods() {
        return array(
            "foo"           => "foo",
            "internal_bar"  => "bar",
            "self"          => "self"
        );
    }
    
    public function foo() { return "foo"; }
    public function internal_bar() { return "bar"; }
    public function self() { return $this; }
}

class MBEMT_Extension_2 extends Model_Extension
{
    public function get_exported_methods() {
        return array(
            "bleem" => "bleem",
            "self"  => "self"
        );
    }
    
    public function bleem() { return "bleem"; }
    public function self() { return $this; }
}

class Model_BaseExtensionMechanismTest extends Test_Unit
{
    public function setup() {
        $this->model = new MBEMT_Model;
    }
    
    public function test_extension_methods_can_be_called() {
        assert_equal('foo', $this->model->foo());
        assert_equal('bar', $this->model->bar());
        assert_equal('bleem', $this->model->bleem());
    }
    
    public function test_later_extension_takes_precedence_when_methods_collide() {
        _assert($this->model->self() instanceof MBEMT_Extension_2);
    }
    
    public function test_get_source_returns_correct_model() {
        assert_equal($this->model, $this->model->self()->get_source());
    }
    
    public function test_reflection_is_setup_correctly() {
        $property = new ReflectionProperty($this->model->self(), 'reflection');
        $property->setAccessible(true);
        $reflection = $property->getValue($this->model->self());
        _assert($reflection instanceof ReflectionClass);
        assert_equal('MBEMT_Model', $reflection->getName());
    }
    
    public function test_options_setup_correctly() {
        $property = new ReflectionProperty($this->model->self(), 'options');
        $property->setAccessible(true);
        $options = $property->getValue($this->model->self());
        assert_equal(
            $options,
            array(1, 2, array(true, false), array('foo' => 'bar'))
        );
    }
    
    public function test_calling_non_existant_method_throws_method_missing() {
        try {
            $this->model->baz();
            fail();
        } catch (Error_MethodMissing $mm) {
            pass();
        } catch (Exception $e) {
            fail();
        }
    }
}
?>