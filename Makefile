dummy:

docs: dummy
	phpdoc -c php-documentor
	
test: dummy
	php run_tests.php
	
clean:
	rm -rf phpdoc/*
	rm -rf tmp/*
