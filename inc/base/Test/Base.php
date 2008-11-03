<?php
class Error_AssertionFailed extends Exception {}

abstract class Test_Base
{
	public static $reporter	= null;
	
	public function get_name() {
		return get_class($this);	
	}
		
	public function run(Test_Reporter $reporter) {
		self::$reporter = $reporter;
		foreach ($this->get_test_invokers() as $ti) {
			$this->run_one($ti);	
		}
		self::$reporter = null;
	}
	
	protected function run_one(Test_Invoker $ti) {
		self::$reporter->test_enter($ti);
		try {
			$this->do_run_one($ti);
			self::$reporter->test_pass();
		} catch (Error_AssertionFailed $eaf) {
			self::$reporter->test_fail($eaf);
		} catch (Exception $e) {
			self::$reporter->test_error($e);
		}
		self::$reporter->test_exit($ti);
	}
	
	/**
	 * Returns an array of Test_Invoker instances which, combined, will run all
	 * tests declared by this test class. The default behaviour is to return
	 * a collection of Test_MethodInvoker for all public methods prefixed by
	 * 'test_'
	 */
	protected function get_test_invokers() {
		$reflector = new ReflectionClass($this);
		$all = array();
		foreach ($reflector->getMethods() as $method) {
			if ($method->isPublic() && preg_match('/^test_/', $method->getName())) {
				$all[] = new Test_MethodInvoker($method);
			}
		}
		return $all;
	}
	
	protected abstract function do_run_one(Test_Invoker $ti);
}

//
// Assertions are written as normal functions and we use a static property on
// Test_Base to keep track of state. The reason: it's less to type. No one likes
// writing $this->assert() over and over.

function pass() {
	_assert(true);
}

function fail($msg = "") {
	_assert(false, $msg);
}

/**
 * Assert
 * 
 * @param $v value to be checked for truthiness
 * @param $msg message to report on failure
 */
function _assert($v, $msg = "") {
	if (!$v) {
		Test_Base::$reporter->assert_fail();
		throw new Error_AssertionFailed($msg);
	} else {
		Test_Base::$reporter->assert_pass();
	}
}

function assert_object($v, $msg = "") {
	_assert(is_object($v), $msg);	
}

function assert_array($v, $msg = "") {
	_assert(is_array($v), $msg);	
}

function assert_not_equal($l, $r, $msg = "") {
    _assert($l != $r, $msg);
}

function assert_equal($l, $r, $msg = "") {
    _assert($l == $r, $msg);
}

function assert_identical($l, $r, $msg = "") {
	_assert($l === $r, $msg);	
}

function assert_equal_strings($l, $r, $msg = "") {
	_assert(strcmp($l, $r) === 0);	
}

function assert_match($regex, $r, $msg = "") {
	_assert(preg_match($regex, $r), $msg);	
}

function assert_null($v, $msg = "") {
	_assert($v === null, $msg);
}

function assert_not_null($v, $msg = "") {
	_assert($v !== null, $msg);	
}
?>
