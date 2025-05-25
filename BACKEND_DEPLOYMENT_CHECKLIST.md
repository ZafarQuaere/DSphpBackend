# Deployment Checklist for backend.dillistyle.shop

Follow these steps to deploy this PHP backend to the new subdomain `backend.dillistyle.shop`.

## 1. Subdomain Setup

- [ ] Login to GoDaddy hosting account
- [ ] Navigate to Domains > Subdomains
- [ ] Create subdomain `backend.dillistyle.shop`
- [ ] Set document root to `/backend`

## 2. File Upload

- [ ] Create a zip file of the entire project
- [ ] Log in to cPanel
- [ ] Navigate to File Manager > public_html
- [ ] Create a directory named `backend` if it doesn't exist
- [ ] Upload the zip file to the `backend` directory
- [ ] Extract the zip file
- [ ] Remove the zip file

## 3. Environment Configuration

- [ ] Create `.env` file from `env.example`:
  ```
  # Database Configuration
  DB_HOST=localhost
  DB_NAME=DB_DilliStyle
  DB_USER=admin_zafar
  DB_PASS=Za*****20

  # JWT Secret
  JWT_SECRET=aJ5*kL9$pQ2#rT7!zX3@bN6^mV0&cD8%

  # API Information
  API_VERSION=1.0.0
  API_NAME="Dilli Style API"

  # Base URL
  BASE_URL=https://backend.dillistyle.shop

  # Application Environment
  APP_ENV=production

  
  ```

## 4. Database Setup

- [ ] Log in to cPanel
- [ ] Navigate to MySQL Databases
- [ ] Create a new database (or use existing one)
- [ ] Create a new user (or use existing one)
- [ ] Add the user to the database with all privileges
- [ ] Import the `database.sql` file through phpMyAdmin

## 5. Permissions Setup

- [ ] Set directory permissions to 755:
  ```
  find /home/username/public_html/backend -type d -exec chmod 755 {} \;
  ```
- [ ] Set file permissions to 644:
  ```
  find /home/username/public_html/backend -type f -exec chmod 644 {} \;
  ```

## 6. Error Logging Setup

- [ ] Create an error log file:
  ```
  touch /home/username/public_html/backend/php_errors.log
  chmod 666 /home/username/public_html/backend/php_errors.log
  ```

## 7. SSL Configuration

- [ ] Navigate to cPanel > SSL/TLS
- [ ] Make sure SSL is enabled for backend.dillistyle.shop
- [ ] Request SSL certificate if not automatically available

## 8. Testing

- [ ] Visit https://backend.dillistyle.shop/
- [ ] Verify you see the API welcome message:
  ```json
  {
    "message": "Welcome to Dilli Style API",
    "version": "1.0.0",
    "status": "Active", 
    "base_url": "https://backend.dillistyle.shop"
  }
  ```
- [ ] Test a few endpoints using Postman to ensure functionality

## 9. Frontend Configuration

- [ ] Update your frontend environment variables:
  ```
  NEXT_PUBLIC_API_URL=https://backend.dillistyle.shop
  ```
- [ ] Deploy the updated frontend to Vercel

## 10. Final Checks

- [ ] Verify CORS is properly configured
- [ ] Test authentication endpoints
- [ ] Test product endpoints
- [ ] Test cart endpoints
- [ ] Check error logs for any issues 