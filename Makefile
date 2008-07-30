docs:
	phpdoc -c php-documentor
	
tests:
	php run_tests.php
	
clean:
	rm -rf doc/*
	rm -rf tmp/*
