#!/bin/bash

# Dilli Style Backend Production Deployment Script
# This script automates the deployment process with safety checks

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="Dilli Style Backend"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/backup_$TIMESTAMP"

echo -e "${GREEN}Starting deployment of $PROJECT_NAME${NC}"

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Pre-deployment checks
echo -e "${YELLOW}Running pre-deployment checks...${NC}"

# Check for required files
required_files=(".env" "database.sql" ".htaccess")
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}Error: Required file $file not found${NC}"
        exit 1
    fi
done

# Check .env file has production values
if grep -q "ENVIRONMENT=development" .env; then
    echo -e "${RED}Error: Environment is set to development in .env file${NC}"
    echo "Please update ENVIRONMENT=production"
    exit 1
fi

# Check JWT secret strength
jwt_secret=$(grep "JWT_SECRET=" .env | cut -d'=' -f2)
if [ ${#jwt_secret} -lt 32 ]; then
    echo -e "${RED}Error: JWT_SECRET is too short (minimum 32 characters)${NC}"
    exit 1
fi

# Create necessary directories
echo -e "${YELLOW}Creating necessary directories...${NC}"
directories=("logs" "cache" "cache/rate_limit" "errors" "uploads" "helpers" "scripts")
for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "Created directory: $dir"
    fi
done

# Set proper permissions
echo -e "${YELLOW}Setting file permissions...${NC}"

# Directories: 755
find . -type d -exec chmod 755 {} \;

# Files: 644
find . -type f -exec chmod 644 {} \;

# Scripts: 755
chmod 755 scripts/*.sh 2>/dev/null || true

# Sensitive files: 600
chmod 600 .env
chmod 600 config/*.php

# Protect sensitive directories
echo "Deny from all" > logs/.htaccess
echo "Deny from all" > cache/.htaccess
echo "Deny from all" > config/.htaccess

# Create error response files if they don't exist
echo -e "${YELLOW}Creating error response files...${NC}"
if [ ! -f "errors/.htaccess" ]; then
    echo "Options -Indexes" > errors/.htaccess
fi

# Remove development files
echo -e "${YELLOW}Removing development files...${NC}"
dev_files=("test.php" "phpinfo.php" "debug.php" ".env.local" ".env.development")
for file in "${dev_files[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        echo "Removed: $file"
    fi
done

# Clear cache
echo -e "${YELLOW}Clearing cache...${NC}"
if [ -d "cache" ]; then
    find cache -type f -name "*.json" -delete
    find cache -type f -name "*.cache" -delete
    echo "Cache cleared"
fi

# Optimize composer autoload (if composer is used)
if [ -f "composer.json" ] && command_exists composer; then
    echo -e "${YELLOW}Optimizing composer autoload...${NC}"
    composer install --no-dev --optimize-autoloader
fi

# Generate deployment info
echo -e "${YELLOW}Generating deployment info...${NC}"
cat > deployment_info.json <<EOF
{
    "project": "$PROJECT_NAME",
    "deployed_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "version": "$(grep 'API_VERSION' env.example | cut -d'=' -f2)",
    "environment": "production",
    "php_version": "$(php -v | head -n 1 | cut -d' ' -f2)"
}
EOF

# Create cron job file for maintenance
echo -e "${YELLOW}Creating cron job file...${NC}"
cat > cron_jobs.txt <<EOF
# Dilli Style Backend Cron Jobs

# Clean up rate limit cache every hour
0 * * * * /usr/bin/php /path/to/your/backend/index.php?cleanup=rate-limit-cache&key=your_cleanup_key

# Backup database daily at 2 AM
0 2 * * * /path/to/your/backend/scripts/backup.sh

# Clean up old logs weekly
0 0 * * 0 find /path/to/your/backend/logs -name "*.log" -mtime +30 -delete

# Monitor disk space and alert if low
0 */4 * * * df -h | grep -E '^/dev/' | awk '{ if(int($5) > 80) print $0 }'
EOF

# Security checks
echo -e "${YELLOW}Running security checks...${NC}"

# Check for exposed sensitive files
sensitive_patterns=("password" "secret" "key" "token")
for pattern in "${sensitive_patterns[@]}"; do
    if grep -r "$pattern" --include="*.txt" --include="*.md" . 2>/dev/null | grep -v "example" | grep -v "documentation"; then
        echo -e "${RED}Warning: Possible sensitive data found in files${NC}"
    fi
done

# Create deployment summary
echo -e "${GREEN}Deployment preparation complete!${NC}"
echo ""
echo "Deployment Summary:"
echo "==================="
echo "Project: $PROJECT_NAME"
echo "Timestamp: $TIMESTAMP"
echo "Environment: Production"
echo ""
echo "Next steps:"
echo "1. Upload all files to your server"
echo "2. Import database.sql to your production database"
echo "3. Update .env with production database credentials"
echo "4. Test all API endpoints"
echo "5. Set up cron jobs from cron_jobs.txt"
echo "6. Configure SSL certificate"
echo "7. Enable HSTS in .htaccess after SSL is working"
echo "8. Monitor logs for any issues"

# Create deployment verification script
cat > verify_deployment.php <<'EOF'
<?php
// Deployment verification script
// Run this after deployment to check everything is working

$checks = [];

// Check PHP version
$checks['php_version'] = [
    'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'message' => 'PHP Version: ' . PHP_VERSION,
    'required' => '>= 7.4.0'
];

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring'];
foreach ($required_extensions as $ext) {
    $checks["extension_$ext"] = [
        'status' => extension_loaded($ext),
        'message' => "Extension $ext",
        'required' => 'Loaded'
    ];
}

// Check .env file
$checks['env_file'] = [
    'status' => file_exists('.env'),
    'message' => '.env file',
    'required' => 'Exists'
];

// Check directories are writable
$writable_dirs = ['logs', 'cache', 'uploads'];
foreach ($writable_dirs as $dir) {
    $checks["writable_$dir"] = [
        'status' => is_writable($dir),
        'message' => "Directory $dir",
        'required' => 'Writable'
    ];
}

// Display results
echo "Deployment Verification Results\n";
echo "==============================\n\n";

$all_passed = true;
foreach ($checks as $check_name => $check) {
    $status = $check['status'] ? '✓' : '✗';
    $color = $check['status'] ? "\033[32m" : "\033[31m";
    echo $color . $status . "\033[0m " . $check['message'] . " (Required: " . $check['required'] . ")\n";
    if (!$check['status']) {
        $all_passed = false;
    }
}

echo "\n";
if ($all_passed) {
    echo "\033[32mAll checks passed! Deployment verified.\033[0m\n";
} else {
    echo "\033[31mSome checks failed. Please fix the issues above.\033[0m\n";
}
EOF

echo ""
echo -e "${GREEN}Deployment script completed successfully!${NC}" 