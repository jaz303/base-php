<?php
// Demonstration configuration file for BasePHP
// This is all there is to it. You might want a more complex autoloader; on
// real projects I tend to try a bunch of other stuff first then attempt to
// load from Base as a last resort...

// You need a database connection to run the tests...
// If you're using MySQL, the default 'test' database should suffice, so just
// fill in your username/password.
$_GDB['default'] = array(
    'driver'    => 'MySQL',
    'host'      => 'localhost',
    'username'  => 'root',
    'password'  => '',
    'database'  => 'test'
);

$root = dirname(__FILE__);
ini_set("include_path", ".:{$root}/inc");

function __autoload($class) {
    @include "base/" . str_replace("_", "/", $class) . ".php";
}
?>