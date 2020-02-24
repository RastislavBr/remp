#! /usr/bin/make

SUB_BEAM="Beam"
SUB_CAMPAIGN="Campaign"
SUB_MAILER="Mailer"
SUB_SSO="Sso"

export TARGET_GOOS TARGET_GOARCH TARGET_SUFFIX

sniff:
	cd $(SUB_BEAM) && make sniff
	cd $(SUB_CAMPAIGN) && make sniff
	cd $(SUB_MAILER) && make sniff
	cd $(SUB_SSO) && make sniff

sniff-fix:
	cd $(SUB_BEAM) && make sniff-fix
	cd $(SUB_CAMPAIGN) && make sniff-fix
	cd $(SUB_MAILER) && make sniff-fix
	cd $(SUB_SSO) && make sniff-fix

composer-install:
	composer install -d $(SUB_BEAM) --no-progress
	composer install -d $(SUB_CAMPAIGN) --no-progress
	composer install -d $(SUB_MAILER) --no-progress
	composer install -d $(SUB_SSO) --no-progress

migrate-db:
	# Beam and Campaign DBs are migrate automatically when running tests
	cd $(SUB_MAILER) && php bin/command.php migrate:migrate
	
phpunit:
	cd $(SUB_BEAM) && vendor/bin/phpunit
	cd $(SUB_CAMPAIGN) && vendor/bin/phpunit

copy-env:
	cd $(SUB_BEAM) && cp .env.example .env
	cd $(SUB_CAMPAIGN) && cp .env.example .env
	cd $(SUB_MAILER) && cp .env.example .env

phpstan:
	cd $(SUB_MAILER) && make phpstan

lint:
	cd $(SUB_BEAM) && make lint

vet:
	cd $(SUB_BEAM) && make vet

fixcs:
	cd $(SUB_BEAM) && make fixcs
	cd $(SUB_CAMPAIGN) && make fixcs
	cd $(SUB_MAILER) && make fixcs
	cd $(SUB_SSO) && make fixcs

syntax:
	cd $(SUB_BEAM) && make syntax
	cd $(SUB_CAMPAIGN) && make syntax
	cd $(SUB_MAILER) && make syntax
	cd $(SUB_SSO) && make syntax

docker-build:
	cd $(SUB_BEAM) && make docker-build