<?php
class Model_ErrorsTest extends Test_Unit
{
    public function setup() {
        $this->errors = new Model_Errors;
        _assert($this->errors->ok());
    }
    
    public function test_adding_field_error_makes_not_ok() {
        $this->errors->add('foo', "can't be blank");
        _assert(!$this->errors->ok());
    }
    
    public function test_adding_base_error_makes_not_ok() {
        $this->errors->add_to_base('foo');
        _assert(!$this->errors->ok());
    }
    
    public function test_error_is_returned_correctly() {
        $this->errors->add('foo', 'error 1');
        $this->errors->add('foo', 'error 2');
        assert_equal(2, count($this->errors->on('foo')));
    }
    
    public function test_first_error_is_returned_correctly() {
        $this->errors->add('foo', 'error 1');
        $this->errors->add('foo', 'error 2');
        _assert(is_string($this->errors->first_on('foo')));
    }
    
    public function test_errors_on_methods_return_false_when_no_errors() {
        _assert(false === $this->errors->on('foo'));
        _assert(false === $this->errors->first_on('foo'));
    }
    
    public function test_full_message_is_used_when_message_starts_with_hat() {
        $this->errors->add('foo', '^Error message');
        $full = $this->errors->full_messages();
        assert_equal('Error message', $full[0]);
    }
    
    public function test_humanized_field_name_is_prepended_to_message() {
        $this->errors->add('foo', 'error message');
        $full = $this->errors->full_messages();
        _assert(0 === strpos($full[0], 'Foo'));
    }
}
?>