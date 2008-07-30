docs:
	phpdoc -c php-documentor
	
test:
	php run_tests.php
	
clean:
	rm -rf phpdoc/*
	rm -rf tmp/*
