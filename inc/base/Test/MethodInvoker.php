<?php
class Test_MethodInvoker extends Test_Invoker
{
	private $method;
	
	public function __construct(ReflectionMethod $method) {
		$this->method = $method;	
	}
	
	public function invoke(Test_Base $instance) {
		$this->method->invoke($instance);
	}
}
