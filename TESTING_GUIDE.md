# Testing Guide - Fund Transfer API

This guide provides comprehensive instructions for testing the Fund Transfer API.

## Table of Contents
- [Setup for Testing](#setup-for-testing)
- [Running Automated Tests](#running-automated-tests)
- [Manual API Testing](#manual-api-testing)
- [Load Testing](#load-testing)
- [Test Scenarios](#test-scenarios)

## Setup for Testing

### 1. Ensure All Services Are Running

```bash
# Check running containers
docker ps

# You should see:
# - symfony_app
# - symfony_nginx
# - laravel_mysql
# - laravel_redis
```

### 2. Verify Database Setup

```bash
# Check if tables exist
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel -e "SHOW TABLES;"

# You should see:
# - accounts
# - transactions
# - doctrine_migration_versions
```

### 3. Check Test Accounts

```bash
# View test accounts
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel -e "SELECT account_number, account_holder, balance FROM accounts;"
```

## Running Automated Tests

### Full Test Suite

```bash
# Run all tests
docker exec -it symfony_app php bin/phpunit

# Run with verbose output
docker exec -it symfony_app php bin/phpunit --verbose

# Run with detailed test results
docker exec -it symfony_app php bin/phpunit --testdox
```

### Specific Test Classes

```bash
# Run only integration tests
docker exec -it symfony_app php bin/phpunit tests/Integration/

# Run specific test class
docker exec -it symfony_app php bin/phpunit tests/Integration/FundTransferControllerTest.php

# Run specific test method
docker exec -it symfony_app php bin/phpunit --filter testSuccessfulTransfer
```

### Code Coverage

```bash
# Generate coverage report (requires xdebug)
docker exec -it symfony_app php bin/phpunit --coverage-text

# Generate HTML coverage report
docker exec -it symfony_app php bin/phpunit --coverage-html coverage/
```

## Manual API Testing

### Using cURL

#### 1. Health Check
```bash
curl -X GET http://localhost:8001/api/health | jq
```

Expected Response:
```json
{
  "status": "healthy",
  "timestamp": "2025-02-11 12:00:00"
}
```

#### 2. Successful Transfer
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC1000000001",
    "toAccountNumber": "ACC1000000002",
    "amount": "100.50",
    "description": "Test payment"
  }' | jq
```

Expected Response (201):
```json
{
  "success": true,
  "data": {
    "transaction_id": "uuid-here",
    "from_account": "ACC1000000001",
    "to_account": "ACC1000000002",
    "amount": "100.50",
    "currency": "INR",
    "status": "completed",
    "description": "Test payment",
    "created_at": "2025-02-11 12:00:00",
    "completed_at": "2025-02-11 12:00:00"
  }
}
```

#### 3. Get Transaction Details
```bash
# Replace TRANSACTION_ID with actual ID from previous response
curl -X GET http://localhost:8001/api/transfers/TRANSACTION_ID | jq
```

#### 4. Get Account History
```bash
curl -X GET "http://localhost:8001/api/accounts/ACC1000000001/transfers?limit=10" | jq
```

#### 5. Test Insufficient Funds
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC1000000005",
    "toAccountNumber": "ACC1000000001",
    "amount": "100.00",
    "description": "This should fail"
  }' | jq
```

Expected Response (422):
```json
{
  "success": false,
  "error": "Insufficient funds in account ACC1000000005. Required: 100.00, Available: 0.00"
}
```

#### 6. Test Invalid Account
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "INVALID_ACCOUNT",
    "toAccountNumber": "ACC1000000001",
    "amount": "50.00",
    "description": "This should fail"
  }' | jq
```

Expected Response (404):
```json
{
  "success": false,
  "error": "Account not found: INVALID_ACCOUNT"
}
```

#### 7. Test Validation Error
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC1000000001",
    "toAccountNumber": "ACC1000000002",
    "amount": "-50.00",
    "description": "Negative amount"
  }' | jq
```

Expected Response (400):
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "amount": "Amount must be positive"
  }
}
```

### Using Postman

1. **Import Collection**
   - Open Postman
   - Click Import
   - Select `postman_collection.json` from the project root
   - All endpoints will be available in the collection

2. **Test Endpoints**
   - Health Check
   - Create Transfer - Success
   - Create Transfer - Insufficient Funds
   - Create Transfer - Account Not Found
   - Get Transaction Details
   - Get Account Transfers

3. **Variables**
   - Base URL: `http://localhost:8001`
   - You can create an environment variable for this

### Using HTTPie (Alternative to cURL)

```bash
# Install HTTPie
pip install httpie

# Health check
http GET http://localhost:8001/api/health

# Create transfer
http POST http://localhost:8001/api/transfers \
  fromAccountNumber=ACC1000000001 \
  toAccountNumber=ACC1000000002 \
  amount=100.50 \
  description="Test payment"

# Get transaction
http GET http://localhost:8001/api/transfers/TRANSACTION_ID

# Get account history
http GET "http://localhost:8001/api/accounts/ACC1000000001/transfers?limit=10"
```

## Load Testing

### Using Apache Bench (ab)

#### 1. Install Apache Bench
```bash
# Ubuntu/Debian
sudo apt-get install apache2-utils

# macOS
brew install apache2
```

#### 2. Test Health Endpoint
```bash
# 1000 requests, 50 concurrent
ab -n 1000 -c 50 http://localhost:8001/api/health
```

#### 3. Test Transfer Endpoint
```bash
# Create a JSON file for the request body
cat > transfer.json << 'EOF'
{
  "fromAccountNumber": "ACC1000000001",
  "toAccountNumber": "ACC1000000002",
  "amount": "1.00",
  "description": "Load test"
}
EOF

# Run load test (100 requests, 10 concurrent)
ab -n 100 -c 10 -p transfer.json -T application/json http://localhost:8001/api/transfers
```

### Using wrk (Advanced Load Testing)

```bash
# Install wrk
sudo apt-get install wrk

# Test health endpoint
wrk -t10 -c100 -d30s http://localhost:8001/api/health

# Test transfer endpoint with POST data
wrk -t10 -c50 -d30s -s post.lua http://localhost:8001/api/transfers
```

Create `post.lua`:
```lua
wrk.method = "POST"
wrk.body = '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000002","amount":"1.00","description":"Load test"}'
wrk.headers["Content-Type"] = "application/json"
```

## Test Scenarios

### Scenario 1: Basic Transfer Flow

**Objective**: Verify complete transfer workflow

**Steps**:
1. Check initial balances
2. Create a transfer of $100 from ACC1000000001 to ACC1000000002
3. Verify transaction was created and completed
4. Check updated balances
5. Retrieve transaction history for both accounts

**Commands**:
```bash
# Step 1: Check balances
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT account_number, balance FROM accounts WHERE account_number IN ('ACC1000000001', 'ACC1000000002');"

# Step 2: Create transfer
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000002","amount":"100.00","description":"Test"}' | jq

# Step 3: Get transaction (use ID from previous response)
curl http://localhost:8001/api/transfers/TRANSACTION_ID | jq

# Step 4: Check balances again
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT account_number, balance FROM accounts WHERE account_number IN ('ACC1000000001', 'ACC1000000002');"

# Step 5: Get transaction history
curl "http://localhost:8001/api/accounts/ACC1000000001/transfers?limit=5" | jq
curl "http://localhost:8001/api/accounts/ACC1000000002/transfers?limit=5" | jq
```

### Scenario 2: Concurrent Transfers

**Objective**: Test system under concurrent load

**Steps**:
1. Create multiple concurrent transfers
2. Verify all transactions are processed correctly
3. Verify no race conditions or data inconsistencies

**Commands**:
```bash
# Create 10 concurrent transfers
for i in {1..10}; do
  curl -X POST http://localhost:8001/api/transfers \
    -H "Content-Type: application/json" \
    -d "{\"fromAccountNumber\":\"ACC1000000001\",\"toAccountNumber\":\"ACC1000000002\",\"amount\":\"10.00\",\"description\":\"Concurrent test $i\"}" &
done
wait

# Check final balances
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT account_number, balance FROM accounts WHERE account_number IN ('ACC1000000001', 'ACC1000000002');"

# Count transactions
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT COUNT(*) as total, status FROM transactions GROUP BY status;"
```

### Scenario 3: Error Handling

**Objective**: Verify proper error responses

**Test Cases**:
1. Insufficient funds
2. Invalid account number
3. Same account transfer
4. Negative amount
5. Invalid JSON
6. Missing required fields

**Commands**:
```bash
# Test 1: Insufficient funds
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000005","toAccountNumber":"ACC1000000001","amount":"100.00"}' | jq

# Test 2: Invalid account
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"INVALID","toAccountNumber":"ACC1000000001","amount":"50.00"}' | jq

# Test 3: Same account
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000001","amount":"50.00"}' | jq

# Test 4: Negative amount
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001","toAccountNumber":"ACC1000000002","amount":"-50.00"}' | jq

# Test 5: Invalid JSON
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{invalid json}' | jq

# Test 6: Missing fields
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{"fromAccountNumber":"ACC1000000001"}' | jq
```

### Scenario 4: Data Integrity

**Objective**: Verify data consistency and integrity

**Steps**:
1. Record initial total balance of all accounts
2. Perform multiple transfers
3. Verify total balance remains the same (conservation of money)
4. Check transaction records match account balance changes

**Commands**:
```bash
# Step 1: Get initial total balance
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT SUM(balance) as total_balance FROM accounts;"

# Step 2: Perform transfers (see Scenario 2)

# Step 3: Verify total balance
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT SUM(balance) as total_balance FROM accounts;"

# Step 4: Compare transaction amounts with balance changes
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT SUM(amount) as total_transferred FROM transactions WHERE status='completed';"
```

## Monitoring and Debugging

### View Application Logs
```bash
# Follow logs in real-time
docker exec -it symfony_app tail -f var/log/dev.log

# View last 50 lines
docker exec -it symfony_app tail -n 50 var/log/dev.log

# Search for errors
docker exec -it symfony_app grep ERROR var/log/dev.log
```

### Database Queries
```bash
# View all accounts
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT * FROM accounts;"

# View all transactions
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10;"

# View failed transactions
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel \
  -e "SELECT * FROM transactions WHERE status='failed';"
```

### Redis Monitoring
```bash
# Connect to Redis
docker exec -it laravel_redis redis-cli

# Monitor Redis commands in real-time
docker exec -it laravel_redis redis-cli MONITOR

# Check Redis info
docker exec -it laravel_redis redis-cli INFO

# View all keys
docker exec -it laravel_redis redis-cli KEYS '*'
```

## Performance Benchmarks

Expected performance metrics (on standard development machine):

- **Health Check**: ~1000 req/sec
- **Transfer API**: ~100-200 req/sec (with database writes)
- **Get Transaction**: ~500 req/sec (read-only)
- **Response Time**: < 100ms for most requests

## Troubleshooting

### Tests Failing

1. **Check database connection**
   ```bash
   docker exec -it symfony_app php bin/console doctrine:query:sql "SELECT 1"
   ```

2. **Clear cache**
   ```bash
   docker exec -it symfony_app php bin/console cache:clear
   ```

3. **Reset database**
   ```bash
   docker exec -it symfony_app php bin/console doctrine:database:drop --force
   docker exec -it symfony_app php bin/console doctrine:database:create
   docker exec -it symfony_app php bin/console doctrine:migrations:migrate --no-interaction
   ```

### API Not Responding

1. **Check containers**
   ```bash
   docker ps
   docker logs symfony_nginx
   docker logs symfony_app
   ```

2. **Restart services**
   ```bash
   docker-compose restart symfony_app symfony_nginx
   ```

## Success Criteria

All tests should pass with:
- ✅ No database integrity violations
- ✅ Proper HTTP status codes
- ✅ Accurate balance calculations
- ✅ Correct transaction states
- ✅ No race conditions under concurrent load
- ✅ Proper error messages
- ✅ All validations working

---

For more information, see the main [README.md](README.md)
