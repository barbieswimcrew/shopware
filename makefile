#
# Makefile
#

.PHONY: help build
.DEFAULT_GOAL := help


help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# ----------------------------------------------------------------------------------------------------------------

release: ## Creates a new ZIP package
	make prod -B
	cd .. && zip -r MollieShopware.zip MollieShopware/

prod: ## Installs all production dependencies
	composer install --no-dev

dev: ## Installs all dev dependencies
	composer install

test: ## Starts all Tests
	php vendor/bin/phpunit --configuration=phpunit.xml
