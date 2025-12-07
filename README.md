# BookMory - Сервис планирования и учета времени чтения книг

Полнофункциональный веб-сервис для планирования, учета и анализа времени, потраченного на чтение книг.

## Запуск проекта

```bash
docker compose up -d --build
```

## Остановка проекта

```bash
docker compose down
```

Остановка с удалением всех данных (volumes):
```bash
docker compose down -v
```

## Доступ к сервисам

- **Приложение**: http://localhost:8080
- **Grafana**: http://localhost:3000
- **Prometheus**: http://localhost:9090

## Подключение к MySQL

Интерактивное подключение к контейнеру MySQL:

```bash
docker exec -it bookmory_mysql mysql -u matvey -p 1311 bookmory
```

После подключения можно выполнять SQL команды:
```sql
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM books;
```

Для выхода из MySQL:
```sql
EXIT;
```
