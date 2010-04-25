<?php
require '../configure.php';

$db = GDB::instance();

$db->insert('user', array(
    's:username' => 'jaz303',
    'b:active' => false
));


?>