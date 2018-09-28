test-container:
	@docker-compose run --rm tests sh
	@docker-compose down

test: vendor
	@vendor/bin/phpunit -d memory_limit=-1

vendor:
	@composer install
