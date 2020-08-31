#
# Makefile
#

.PHONY: help
.DEFAULT_GOAL := help


help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# ----------------------------------------------------------------------------------------------------------------

install: ## Installs all dependencies
	composer install

release: ## Creates a new ZIP package
	make prod -B
	cd .. && zip -r MollieShopware.zip MollieShopware/

test: ## Starts all Tests
	php vendor/bin/phpunit --configuration=phpunit.xml
