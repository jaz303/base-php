<?php
class Errors implements IteratorAggregate
{
    private $errors = array();
    private $base   = array();
    
    public function ok() {
        return count($this->errors) == 0 && count($this->base) == 0;
    }
    
    public function add($key, $error) {
        $this->errors[$key][] = $error;
    }
    
    public function add_to_base($message) {
        $this->base[] = $message;
    }
    
    public function on($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : false;
    }
    
    public function first_on($field) {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : false;
    }
    
    public function full_messages() {
        $messages = array();
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $message) {
                if ($message[0] == '^') {
                    $messages[] = substr($message, 1);
                } else {
                    $field = Inflector::humanize($field);
                    $messages[] = "$field $message";
                }
            }
        }
        foreach ($this->base as $b) $messages[] = $b;
        return $messages;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->full_messages());
    }
}
?>