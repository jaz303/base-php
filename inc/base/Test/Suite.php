<?php
class Test_Suite
{
	private $name;
	private $tests	= array();
	
	public function __construct($name) {
		$this->name = $name;	
	}
	
	public function require_all($dir = 'test') {
		$stack = array($dir);
		while (count($stack)) {
			$dir = array_pop($stack);
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if ($file[0] == '.') continue;
				$fqd = $dir . DIRECTORY_SEPARATOR . $file;
				if (is_dir($fqd)) {
					$stack[] = $fqd;
				} else {
					require $fqd;
				}
			}
			closedir($dh);
		}
	}
	
	public function auto_fill() {
		foreach (get_declared_classes() as $class_name) {
			if (is_subclass_of($class_name, "Test_Unit")) {
				$this->add_test(new $class_name);	
			}
		}
	}
	
	public function add_test(Test_Base $test) {
		$this->tests[] = $test;
	}
	
	public function run(Test_Reporter $reporter) {
		$reporter->start();
		foreach ($this->tests as $test) {
			$test->run($reporter);
		}
		$reporter->end();
		$reporter->summary();
	}
}
?>