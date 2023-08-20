init: docker-down docker-pull docker-build docker-up
up: docker-up
down: docker-down
restart: down up

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

test:
	docker-compose run --rm php-cli ./vendor/bin/simple-phpunit

test-coverage:
	docker-compose run --rm -e XDEBUG_MODE=coverage php-cli ./vendor/bin/simple-phpunit --coverage-clover tests/var/clover.xml --coverage-html tests/var/coverage