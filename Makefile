command=list

images:
	@docker images

ps:
	@docker ps -a

run:
	@docker-compose up -d

down:
	@docker-compose down

retry: down run
	@# 重新执行容器

.PHONY: artisan
artisan:
	@docker-compose run --rm artisan ${command}

migrate:
	@docker-compose run --rm artisan migrate

es-init:
	@docker-compose run --run artisan es:init

seed:
	@docker-compose run --rm artisan db:seed --class=ArticleSeeder

import: migrate seed
	@docker-compose run --rm artisan es:import elasticsearch_index_1000
