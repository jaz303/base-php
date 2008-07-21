<?php
/**
 * Base unit test class
 * 
 * @package BasePHP
 * @author Jason Frame
 */
class Test_Unit extends Test_Base
{
	/**
	 * Called before every test case
	 */
	protected function setup() {}
	
	/**
	 * Called after every test case
	 */
	protected function teardown() {}
	
	protected function do_run_one(Test_Invoker $tmi) {
		$this->setup();
		$tmi->invoke($this);
		$this->teardown();
	}
}

?>
