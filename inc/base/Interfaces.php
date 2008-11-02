<?php
interface Validatable
{
    public function is_valid();
    public function get_errors();
}
?>