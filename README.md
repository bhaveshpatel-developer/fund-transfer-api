# Fund Transfer API

A secure and scalable REST API for transferring funds between accounts built with Symfony 7.2, MySQL, and Redis.

## 🎯 Features

- **Secure Fund Transfers**: Transfer money between accounts with ACID compliance
- **Distributed Locking**: Redis-based distributed locks prevent race conditions
- **Optimistic Locking**: Entity versioning prevents concurrent modification issues
- **Transaction Safety**: Database transactions ensure data consistency
- **Comprehensive Validation**: Input validation and business rule enforcement
- **Error Handling**: Proper HTTP status codes and error messages
- **Logging**: Detailed logging for debugging and monitoring
- **API Documentation**: Clean RESTful API design
- **High Performance**: Redis caching for optimal performance under load
- **Test Coverage**: Integration and unit tests

## 🛠️ Technology Stack

- **PHP 8.2+**
- **Symfony 7.2** - Modern PHP framework
- **MySQL 8.0** - Relational database
- **Redis** - Caching and distributed locking
- **Doctrine ORM** - Database abstraction
- **PHPUnit** - Testing framework
- **Docker** - Containerization

## 📋 Prerequisites

- Docker and Docker Compose
- Git

## 🚀 Installation & Setup

### 1. Clone or Extract the Project

```bash
# If using existing docker setup, copy the fund_transfer_api folder to your project root
cd ~/Documents/Projects/fund_transfer_api
# The fund_transfer_api folder should be at the same level as 'src' folder
```

### 2. Update Docker Compose Configuration

Your `docker-compose.yml` should already include the Symfony services. The configuration includes:

- `symfony_app` - PHP-FPM application container
- `symfony_nginx` - Nginx web server (port 8001)
- `mysql` - MySQL database (shared with Laravel)
- `redis` - Redis cache (shared with Laravel)

### 3. Start Docker Containers

```bash
# From your project root (~/Documents/Projects/fund_transfer_api)
docker-compose up -d
```

### 4. Install Dependencies

```bash
# Access the Symfony container
docker exec -it symfony_app bash

# Install Composer dependencies
composer install

# Exit container
exit
```

### 5. Setup Database

```bash
# Create database (if not exists)
docker exec -it symfony_app php bin/console doctrine:database:create --if-not-exists

# Run migrations
docker exec -it symfony_app php bin/console doctrine:migrations:migrate --no-interaction

# Verify tables are created
docker exec -it laravel_mysql mysql -ularavel -plaravel laravel -e "SHOW TABLES;"
```

### 6. Set Permissions

```bash
# Ensure proper permissions for cache and logs
docker exec -it symfony_app chown -R www-data:www-data var/
```

## 🧪 Running Tests

```bash
# Run all tests
docker exec -it symfony_app php bin/phpunit

# Run with coverage (requires xdebug)
docker exec -it symfony_app php bin/phpunit --coverage-text

# Run specific test
docker exec -it symfony_app php bin/phpunit tests/Integration/FundTransferControllerTest.php
```

## 📡 API Endpoints

### Base URL
```
http://localhost:8001/api
```

### 1. Health Check
```http
GET /api/health
```

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2025-02-11 12:00:00"
}
```

### 2. Create Transfer

Transfer funds from one account to another.

```http
POST /api/transfers
Content-Type: application/json

{
    "fromAccountNumber": "ACC1000000001",
    "toAccountNumber": "ACC1000000002",
    "amount": "100.50",
    "description": "Payment for services"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "data": {
        "transaction_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "from_account": "ACC1000000001",
        "to_account": "ACC1000000002",
        "amount": "100.50",
        "currency": "INR",
        "status": "completed",
        "description": "Payment for services",
        "created_at": "2025-02-11 12:00:00",
        "completed_at": "2025-02-11 12:00:00"
    }
}
```

**Error Response - Insufficient Funds (422):**
```json
{
    "success": false,
    "error": "Insufficient funds in account ACC1000000001. Required: 100.50, Available: 50.00"
}
```

**Error Response - Account Not Found (404):**
```json
{
    "success": false,
    "error": "Account not found: ACC1000000001"
}
```

**Error Response - Validation Error (400):**
```json
{
    "success": false,
    "error": "Validation failed",
    "errors": {
        "amount": "Amount must be positive",
        "fromAccountNumber": "From account number is required"
    }
}
```

### 3. Get Transfer Details

Retrieve details of a specific transaction.

```http
GET /api/transfers/{transactionId}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "transaction_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "from_account": "ACC1000000001",
        "to_account": "ACC1000000002",
        "amount": "100.50",
        "currency": "INR",
        "status": "completed",
        "description": "Payment for services",
        "failure_reason": null,
        "created_at": "2025-02-11 12:00:00",
        "completed_at": "2025-02-11 12:00:00"
    }
}
```

### 4. Get Account Transaction History

Retrieve transaction history for a specific account.

```http
GET /api/accounts/{accountNumber}/transfers?limit=50
```

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "transaction_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
            "type": "debit",
            "from_account": "ACC1000000001",
            "to_account": "ACC1000000002",
            "amount": "100.50",
            "currency": "INR",
            "status": "completed",
            "description": "Payment for services",
            "created_at": "2025-02-11 12:00:00"
        }
    ],
    "count": 1
}
```

## 🧪 Testing the API

### Using cURL

#### 1. Health Check
```bash
curl http://localhost:8001/api/health
```

#### 2. Create a Transfer
```bash
curl -X POST http://localhost:8001/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC1000000001",
    "toAccountNumber": "ACC1000000002",
    "amount": "100.50",
    "description": "Test transfer"
  }'
```

#### 3. Get Transaction Details
```bash
# Replace {transaction_id} with actual transaction ID from previous response
curl http://localhost:8001/api/transfers/{transaction_id}
```

#### 4. Get Account History
```bash
curl http://localhost:8001/api/accounts/ACC1000000001/transfers
```

### Using Postman

1. Import the following collection or create requests manually:
   - **Health Check**: `GET http://localhost:8001/api/health`
   - **Create Transfer**: `POST http://localhost:8001/api/transfers`
   - **Get Transfer**: `GET http://localhost:8001/api/transfers/{id}`
   - **Account History**: `GET http://localhost:8001/api/accounts/{number}/transfers`

2. Set `Content-Type: application/json` header for POST requests

### Test Accounts

The following test accounts are seeded in the database:

| Account Number | Account Holder | Balance | Currency |
|---------------|---------------|---------|----------|
| ACC1000000001 | Aakash Patel | 10,000.00 | INR |
| ACC1000000002 | Ashish Bhatt  | 5,000.00 | INR |
| ACC1000000003 | Bhavin Garg | 15,000.50 | INR |
| ACC1000000004 | Divyesh Mehta | 7,500.00 | INR |
| ACC1000000005 | Gaurav Shah | 0.00 | INR |

## 🏗️ Architecture & Design Decisions

### 1. Distributed Locking Strategy

**Problem**: Concurrent transfers involving the same accounts could cause race conditions.

**Solution**: 
- Redis-based distributed locks using Symfony's Lock component
- Locks acquired in sorted order to prevent deadlocks
- 30-second TTL with automatic release
- Ensures only one transfer per account pair at a time

### 2. Database Transaction Safety

**Implementation**:
- Pessimistic locking (`LOCK FOR UPDATE`) on account records
- Optimistic locking with version field on accounts
- All operations wrapped in database transactions
- Automatic rollback on failures

### 3. High-Load Considerations

**Optimizations**:
- Redis caching for frequently accessed data
- Database indexes on query-heavy columns
- Connection pooling via Doctrine
- Efficient query design to minimize database round trips
- Proper use of database transactions

### 4. Error Handling

**Strategy**:
- Custom exceptions for business logic errors
- Proper HTTP status codes (404, 422, 400, 500)
- Detailed error messages for debugging
- Structured error responses
- Comprehensive logging

### 5. Data Validation

**Layers**:
- DTO-level validation using Symfony Validator
- Business logic validation in service layer
- Database constraints as final safeguard
- Amount precision validation (2 decimal places)

### 6. Scalability Design

**Features**:
- Stateless API design
- Horizontal scaling capability
- Redis for distributed state
- Efficient database queries with proper indexing
- Separated read and write operations

## 📊 Database Schema

### Accounts Table
```sql
- id (PK)
- account_number (UNIQUE, indexed)
- account_holder
- balance (DECIMAL 15,2)
- currency (VARCHAR 3)
- is_active (BOOLEAN)
- created_at (DATETIME)
- updated_at (DATETIME)
- version (INT) -- for optimistic locking
```

### Transactions Table
```sql
- id (PK)
- transaction_id (UNIQUE UUID)
- from_account_id (FK, indexed)
- to_account_id (FK, indexed)
- amount (DECIMAL 15,2)
- currency (VARCHAR 3)
- status (indexed)
- description (TEXT)
- failure_reason (TEXT)
- created_at (DATETIME, indexed)
- completed_at (DATETIME)
```

## 🔒 Security Considerations

1. **Input Validation**: All inputs validated before processing
2. **SQL Injection**: Protected via Doctrine ORM parameterized queries
3. **Race Conditions**: Prevented via distributed locks
4. **Data Integrity**: Database transactions ensure ACID compliance
5. **Logging**: Sensitive data excluded from logs

## 🚀 Performance Under Load

The system is designed to handle high loads through:

1. **Redis Caching**: Reduces database queries
2. **Connection Pooling**: Efficient database connection reuse
3. **Distributed Locks**: Prevents resource contention
4. **Database Indexes**: Fast query execution
5. **Optimized Queries**: Minimal N+1 query problems

### Load Testing

You can test the system under load using tools like Apache Bench:

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test health endpoint (100 requests, 10 concurrent)
ab -n 100 -c 10 http://localhost:8001/api/health

# Test transfers (requires JSON payload)
ab -n 50 -c 5 -p transfer.json -T application/json http://localhost:8001/api/transfers
```

## 📝 Logging

Logs are stored in `var/log/` directory:

- **dev.log**: Development environment logs
- **prod.log**: Production environment logs
- **fund_transfer**: Custom channel for transfer operations

View logs:
```bash
docker exec -it symfony_app tail -f var/log/dev.log
```

## 🐛 Troubleshooting

### Database Connection Issues
```bash
# Check MySQL is running
docker ps | grep mysql

# Test connection
docker exec -it laravel_mysql mysql -ularavel -plaravel -e "SELECT 1"
```

### Permission Issues
```bash
# Fix permissions
docker exec -it symfony_app chown -R www-data:www-data var/
docker exec -it symfony_app chmod -R 775 var/
```

### Redis Connection Issues
```bash
# Check Redis is running
docker exec -it laravel_redis redis-cli ping
# Should return: PONG
```

### Clear Cache
```bash
docker exec -it symfony_app php bin/console cache:clear
```

## 📈 Future Improvements

If given more time, here are enhancements I would implement:

1. **Currency Conversion**: Support for multi-currency transfers with exchange rates
2. **Transfer Limits**: Daily/monthly transfer limits per account
3. **Idempotency**: Idempotency keys to prevent duplicate transfers
4. **Webhooks**: Real-time notifications for transfer events
5. **Audit Trail**: Complete audit log of all changes
6. **Rate Limiting**: API rate limiting per IP/user
7. **Authentication**: JWT-based authentication system
8. **Transaction Reversal**: Ability to reverse completed transactions
9. **Scheduled Transfers**: Support for future-dated transfers
10. **Bulk Transfers**: Process multiple transfers in batch
11. **Analytics API**: Transaction statistics and reporting
12. **GraphQL API**: Alternative to REST for complex queries
13. **Event Sourcing**: Complete event history for compliance
14. **Microservices**: Split into separate services (accounts, transactions, notifications)
15. **Message Queue**: Async processing with RabbitMQ/Kafka

## ⏱️ Time Spent

**Approximate time spent: ~3-4 hours**

Breakdown:
- Project setup and dependencies: 30 minutes
- Entity and repository design: 30 minutes
- Service layer with locking logic: 45 minutes
- Controller and API design: 30 minutes
- Database migrations and seeding: 20 minutes
- Test writing: 45 minutes
- Docker configuration: 20 minutes
- Documentation: 45 minutes

## 🤖 AI Tools Used

**Tools**: Claude (Anthropic) via claude.ai

**Prompts Used**:
1. "Help me create a Symfony-based fund transfer API with the following requirements: [task description]"
2. "Create entities for Account and Transaction with proper validation and relationships"
3. "Implement distributed locking using Redis for concurrent transfer safety"
4. "Write comprehensive integration tests for the fund transfer API"
5. "Create Docker configuration for Symfony with PHP 8.2, MySQL, and Redis"
6. "Generate migrations for the database schema"
7. "Create a detailed README with API documentation and testing instructions"

**AI Assistance**:
- Code generation: ~60% AI-generated, 40% manual refinement
- All AI-generated code was reviewed, understood, and tested
- Architecture decisions were made independently
- Business logic implementation reviewed and validated manually

## 👨‍💻 Author

This project demonstrates production-ready PHP/Symfony development practices including:
- Clean architecture
- SOLID principles
- Comprehensive testing
- Proper error handling
- Security best practices
- Performance optimization
- Clear documentation

## 📄 License

MIT License
