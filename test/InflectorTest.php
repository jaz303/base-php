<?php
class InflectorTest extends Test_Unit
{
    public function test_humanize() {
        assert_equal('Foo bar', Inflector::humanize('Foo bar'));
        assert_equal('Foo bar', Inflector::humanize('foo_bar'));
        assert_equal('Bar foo', Inflector::humanize('bar-foo'));
        assert_equal('Def con 1', Inflector::humanize('DEF-CON-1'));
    }
    
    public function test_underscore() {
        assert_equal('foo_bar', Inflector::underscore('foo_bar'));
        assert_equal('foo_bar', Inflector::underscore('FooBar'));
    }
    
    public function test_camelize() {
        assert_equal('FooBar', Inflector::camelize('foo_bar'));
        assert_equal('FooBar', Inflector::camelize('FooBar'));
    }
}
?>