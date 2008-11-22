<?php
// Technique borrowed from Yii Framework.

$class_map = `util/generate_autoload_map inc`;
$code = 'static $map = ' . $class_map . ';';
$code = explode("\n", $code);

for ($i = 0; $i < count($code); $i++) {
    $code[$i] = '    ' . $code[$i];
}

$code   = implode("\n", $code);
$src    = file_get_contents('configure.php');
$out    = preg_replace('|//\s+START-MAP(.*?)//\s+END-MAP|s', "// START-MAP\n{$code}\n    // END-MAP", $src);

file_put_contents('configure.php', $out);
?>