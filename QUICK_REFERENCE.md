# Quick Reference Guide

## One-Line Commands

### Setup
```bash
# Complete setup in one command
./setup.sh

# Or manually:
docker-compose up -d && docker exec symfony_app composer install && docker exec symfony_app php bin/console doctrine:database:create --if-not-exists && docker exec symfony_app php bin/console doctrine:migrations:migrate --no-interaction
```

### Testing
```bash
# Run all tests
docker exec symfony_app php bin/phpunit

# Quick API test
curl http://localhost:8001/api/health | jq
```

### Create Transfer
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000002","amount":"100.00","description":"Quick test"}' | jq
```

## Test Accounts

| Account | Holder | Balance |
|---------|--------|---------|
| ACC1000000001 | Aakash Patel | $10,000 |
| ACC1000000002 | Ashish Bhatt  | $5,000 |
| ACC1000000003 | Bhavin Garg | $15,000.50 |
| ACC1000000004 | Divyesh Mehta | $7,500 |
| ACC1000000005 | Gaurav Shah | $0 |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/health | Health check |
| POST | /api/transfers | Create transfer |
| GET | /api/transfers/{id} | Get transfer details |
| GET | /api/accounts/{number}/transfers | Get account history |

## Common Commands

### Container Management
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart Symfony services
docker-compose restart symfony_app symfony_nginx

# View logs
docker logs -f symfony_app
```

### Database
```bash
# Access MySQL
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel

# Run migration
docker exec symfony_app php bin/console doctrine:migrations:migrate --no-interaction

# Check balances
docker exec laravel_mysql mysql -ularavel -plaravel laravel -e "SELECT account_number, balance FROM accounts;"
```

### Cache & Logs
```bash
# Clear cache
docker exec symfony_app php bin/console cache:clear

# View logs
docker exec symfony_app tail -f var/log/dev.log

# Clear logs
docker exec symfony_app truncate -s 0 var/log/dev.log
```

## HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | GET requests succeed |
| 201 | Created | Transfer created successfully |
| 400 | Bad Request | Validation error / Invalid input |
| 404 | Not Found | Account or transaction not found |
| 422 | Unprocessable | Insufficient funds / Business rule violation |
| 500 | Server Error | Unexpected error |

## Troubleshooting

### Issue: API not responding
```bash
docker-compose restart symfony_app symfony_nginx
docker logs symfony_nginx
```

### Issue: Database errors
```bash
docker exec symfony_app php bin/console doctrine:database:create --if-not-exists
docker exec symfony_app php bin/console doctrine:migrations:migrate --no-interaction
```

### Issue: Permission errors
```bash
docker exec symfony_app chown -R www-data:www-data var/
docker exec symfony_app chmod -R 775 var/
```

### Issue: Composer issues
```bash
docker exec symfony_app composer install --no-interaction
docker exec symfony_app composer dump-autoload
```

## Quick Tests

### 1. Successful Transfer
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000002","amount":"50.00"}' | jq
```

### 2. Insufficient Funds
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000005","toAccountNumber":"ACC1000000001","amount":"100.00"}' | jq
```

### 3. Get Account History
```bash
curl http://localhost:8001/api/accounts/ACC1000000001/transfers | jq
```

## URLs

- **API Base**: http://localhost:8001/api
- **Health Check**: http://localhost:8001/api/health
- **Transfers**: http://localhost:8001/api/transfers

## Files

- **Main Config**: config/services.yaml
- **Database Config**: config/packages/doctrine.yaml
- **Routes**: src/Controller/FundTransferController.php
- **Service**: src/Service/FundTransferService.php
- **Tests**: tests/Integration/FundTransferControllerTest.php
- **Migrations**: migrations/
- **Logs**: var/log/

## Environment Variables

```bash
APP_ENV=dev
DATABASE_URL=mysql://laravel:laravel@mysql:3306/laravel
LOCK_DSN=redis://redis:6379
CACHE_DSN=redis://redis:6379/1
```

---

**Need Help?**
- See [README.md](README.md) for detailed documentation
- See [TESTING_GUIDE.md](TESTING_GUIDE.md) for comprehensive testing instructions
