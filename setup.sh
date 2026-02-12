#!/bin/bash

echo "========================================="
echo "Fund Transfer API - Setup Script"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running. Please start Docker and try again.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker is running${NC}"

# Check if containers are running
if ! docker ps | grep -q "symfony_app"; then
    echo -e "${YELLOW}Starting Docker containers...${NC}"
    docker-compose up -d
    sleep 5
fi

echo -e "${GREEN}✓ Containers are running${NC}"

# Install dependencies
echo ""
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
docker exec symfony_app composer install --no-interaction --optimize-autoloader

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependencies installed${NC}"
else
    echo -e "${RED}✗ Failed to install dependencies${NC}"
    exit 1
fi

# Create database
echo ""
echo -e "${YELLOW}Creating database...${NC}"
docker exec symfony_app php bin/console doctrine:database:create --if-not-exists

# Run migrations
echo ""
echo -e "${YELLOW}Running database migrations...${NC}"
docker exec symfony_app php bin/console doctrine:migrations:migrate --no-interaction

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database setup complete${NC}"
else
    echo -e "${RED}✗ Database setup failed${NC}"
    exit 1
fi

# Set permissions
echo ""
echo -e "${YELLOW}Setting permissions...${NC}"
docker exec symfony_app chown -R www-data:www-data var/
docker exec symfony_app chmod -R 775 var/

echo -e "${GREEN}✓ Permissions set${NC}"

# Run tests
echo ""
echo -e "${YELLOW}Running tests...${NC}"
docker exec symfony_app php bin/phpunit

# Show summary
echo ""
echo "========================================="
echo -e "${GREEN}Setup Complete!${NC}"
echo "========================================="
echo ""
echo "API is now available at: http://localhost:8001/api"
echo ""
echo "Test Accounts:"
echo "  - ACC1000000001 (Aakash Patel) - Balance: ₹10,000.00"
echo "  - ACC1000000002 (Ashish Bhatt ) - Balance: ₹5,000.00"
echo "  - ACC1000000003 (Bhavin Garg) - Balance: ₹15,000.50"
echo "  - ACC1000000004 (Divyesh Mehta) - Balance: ₹7,500.00"
echo "  - ACC1000000005 (Gaurav Shah) - Balance: ₹0.00"
echo ""
echo "Quick Test:"
echo "  curl http://localhost:8001/api/health"
echo ""
echo "Create a transfer:"
echo "  curl -X POST http://localhost:8001/api/transfers \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"fromAccountNumber\":\"ACC1000000001\",\"toAccountNumber\":\"ACC1000000002\",\"amount\":\"100.50\",\"description\":\"Test transfer\"}'"
echo ""
echo "For more information, see README.md"
echo ""
