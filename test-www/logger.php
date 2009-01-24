<?php
require '../configure.php';

define('LOGGER_DEFAULT_THRESHOLD', 2);

$logger = Logger::get();

$logger->debug("Test debug - shouldn't see me");
$logger->info("Test info");
$logger->warn("Test warn");
$logger->error("Test error");
?>