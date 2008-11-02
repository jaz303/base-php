<?php
// Technique borrowed from Yii Framework.

$root   = dirname(__FILE__) . '/inc/';
$stack  = array($root . 'base');
$map    = array();

while (count($stack)) {
    $iterator = new RecursiveDirectoryIterator(array_pop($stack));
    foreach ($iterator as $f) {
        if ($f->isDir()) {
            $stack[] = $f->getRealPath();
        } elseif ($f->isFile() && preg_match('/\.php$/', $f->getFileName())) {
            $contents = file_get_contents($f->getRealPath());
            $classes = preg_match_all('/^\s*(abstract\s+)?(class|interface)\s+(\w+)/m', $contents, $matches, PREG_PATTERN_ORDER);
            foreach ($matches[3] as $class) {
                $map[$class] = str_replace($root, '', $f->getRealPath());
            }
        }
    }
}

$code   = 'static $map = ' . var_export($map, true) . ';';
$code   = explode("\n", $code);
for ($i = 0; $i < count($code); $i++) {
    $code[$i] = '    ' . $code[$i];
}
$code   = implode("\n", $code);

$src    = file_get_contents('configure.php');
$out    = preg_replace('|//\s+START-MAP(.*?)//\s+END-MAP|s', "// START-MAP\n{$code}\n    // END-MAP", $src);

file_put_contents('configure.php', $out);
?>