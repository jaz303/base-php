<?php
require 'configure.php';

set_time_limit(0);

$tests = new Test_Suite('BasePHP Unit Tests');
$tests->require_all('test');
$tests->auto_fill();

chdir('tmp');

$tests->run(new Test_ConsoleReporter);
?>
