# 启动

```bash
docker-compose up -d
```

# 执行数据迁移

```bash
docker-compose run --rm artisan migrate 
```

# elasticsearch 创建索引

```bash
docker-compose run --rm artisan es:init
```

# 创建表 `articles` 并填充数据导入 `es`

```bash
docker-compose run --rm artisan migrate
docker-compose run --rm artisan db:seed --class=ArticleSeeder
docker-compose run --rm artisan es:import elasticsearch_index_1000
```
