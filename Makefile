
.SILENT: ;
.ONESHELL: ;
.NOTPARALLEL: ;
help:
	@echo "                          ======================================================"
	@echo "                          Local Help"
	@echo "                          ======================================================"
	@echo "                    help: Display this help menu"
	@echo "                    pull: Pull the latest version of images from the registry"
	@echo "                   build: Build the test project and install dependencies"
	@echo "                      up: Bring up the containers"
	@echo "                 restart: Restart the containers"
	@echo "                    down: Take down the containers"
	@echo "                    test: Run the tests"
	@echo "                    auth: Authenticate the AWS client"
	@echo "                    root: Login to the 'test' container as 'root' user"

build:
	echo "Building Docker Images"

	echo "Starting Docker Containers"
	docker-compose --project-name $(PROJECT_NAME) up -d
	echo "Installing Composer Dependencies"
	docker exec $$(docker-compose --project-name $(PROJECT_NAME) ps -q tests) sh -c "composer install"
	echo "Done"

test:
	docker exec $$(docker-compose ps -q workspace) sh -c "vendor/bin/phpunit"

down:
	docker-compose down --volumes --remove-orphans

reload: down up migrate

up:
	echo "Starting Containers"
	docker-compose up -d
	sleep 5
#	docker-compose up -d

	echo "\nInstalling Composer Dependencies"
	docker exec $$(docker-compose ps -q workspace) sh -c "composer install"
	echo "Done"

pull:
	docker pull 190853051067....

auth:
	eval $$(aws ecr get-login --no-include-email)

restart:
	docker-compose restart

update: down pull build

root:
	docker exec -it $$(docker-compose ps -q workspace) bash

migrate:
	docker exec $$(docker-compose ps -q workspace) sh -c "php artisan migrate"

seed:
	docker exec $$(docker-compose ps -q workspace) sh -c "php artisan db:seed --force"

clear:
	docker exec $$(docker-compose ps -q workspace) sh -c "truncate -s 0 storage/logs/*.log"

env:
	echo "\n Copying env file"
	cp prod.env .env

prod-up:
	echo "\n Starting containers"
	docker-compose up -d nginx workspace
#	sleep 10
#	docker-compose up -d

	echo "\nInstalling Composer Dependencies"
	docker exec $$(docker-compose ps -q workspace) sh -c "composer install --no-dev"
	echo "Done"

deploy: env prod-up
	cp prod.env .env
