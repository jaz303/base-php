<?php
class Model_Errors
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
            $field = Inflector::humanize($field);
            foreach ($errors as $message) {
                if ($message[0] == '^') {
                    $messages[] = substr($message, 1);
                } else {
                    $messages[] = "$field $message";
                }
            }
        }
        foreach ($this->base as $b) $messages[] = $b;
        return $messages;
    }
}
?>