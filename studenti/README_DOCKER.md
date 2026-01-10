Quick start (Docker)

1. Build and run containers:

```powershell
docker compose up --build -d
```

2. Check logs:

```powershell
docker compose logs -f apache
docker compose logs -f mysql
```

3. Open in browser:

http://localhost:8080/login.php

Notes:
- The MySQL init SQL is at `mysql/init.sql` and will be executed on the first container start.
- DB credentials (used by default in `docker-compose.yml`):
  - host: mysql (service name)
  - database: service_flow_db
  - user: user
  - password: password
  - root password: root

If you already have containers running and want to reinitialize DB, remove the `db_data` volume:

```powershell
docker compose down
docker volume rm ${PWD##*/}_db_data
# then bring up again
docker compose up --build -d
```
