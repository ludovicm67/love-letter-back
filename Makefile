.PHONY: setup
setup:
	npm install

.PHONY: format
format:
	node_modules/prettier/bin/prettier.js --write "app/**/*.php"
	node_modules/prettier/bin/prettier.js --write "bootstrap/**/*.php"
	node_modules/prettier/bin/prettier.js --write "config/**/*.php"
	node_modules/prettier/bin/prettier.js --write "database/**/*.php"
	node_modules/prettier/bin/prettier.js --write "routes/**/*.php"
	node_modules/prettier/bin/prettier.js --write "tests/**/*.php"

.PHONY: test
test:
	vendor/bin/phpunit

.PHONY: tests
tests:
	vendor/bin/phpunit
