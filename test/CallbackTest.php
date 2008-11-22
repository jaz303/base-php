<?php
class CT_Object
{
    public static function foo($input) {
        return "foo called - " . $input;
    }
    
    public function bar($input) {
        return "bar called - " . $input;
    }
}


class CallbackTest extends Test_Unit
{
    public function test_callback_factory() {
        
        $closure = function() { echo "word!"; };
        
        $expect = array(
            array('CT_Object',                  'foo',  'StaticCallback'),
            array(new CT_Object,                'bar',  'InstanceCallback'),
            array('htmlentities',               null,   'FunctionCallback'),
            array('CT_Object::foo',             null,   'StaticCallback'),
            array(array('CT_Object', 'foo'),    null,   'StaticCallback'),
            array(array(new CT_Object, 'foo'),  null,   'InstanceCallback'),
            array($closure,                     null,   'Closure')
        );
        
        foreach ($expect as $e) {
            
            try {
                $cb = Callback::create($e[0], $e[1]);
                if ($e[2] === false) fail();
                assert_equal($e[2], get_class($cb));
            } catch (Error_IllegalArgument $eia) {
                if ($e[2] === false) {
                    pass();
                } else {
                    fail();
                }
            }
            
        }
        
    }
 
 
    public function test_function_callback() {
        $cb = new FunctionCallback('htmlentities');
        assert_equal(htmlentities('<>'), $cb('<>'));
    }
    
    public function test_instance_callback() {
        $cb = new InstanceCallback(new CT_Object, 'bar');
        assert_equal('bar called - woot', $cb('woot'));
    }
    
    public function test_static_callback() {
        $cb = new StaticCallback('CT_Object', 'foo');
        assert_equal('foo called - hello', $cb('hello'));
    }
}
?>