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
