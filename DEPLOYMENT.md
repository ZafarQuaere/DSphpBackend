# Deployment Guide for Dilli Style PHP Backend

This guide will help you deploy the Dilli Style PHP backend to backend.dillistyle.shop on GoDaddy shared hosting and connect it with your Next.js frontend on Vercel.

## Prerequisites

- GoDaddy hosting account with dillistyle.shop domain
- Access to GoDaddy cPanel
- Database credentials for your GoDaddy hosting
- FTP client (like FileZilla) or cPanel File Manager
- Vercel account for hosting your Next.js frontend

## 1. Setting Up the Subdomain on GoDaddy

1. Log in to your GoDaddy account and go to your hosting dashboard
2. Navigate to the "Domains" section
3. Find and click on "Subdomains"
4. Create a new subdomain with:
   - Name: `backend`
   - Domain: `dillistyle.shop`
   - Document Root: `/backend` (This will create a folder for your API)
5. Click "Create" to set up the subdomain

## 2. Database Setup on GoDaddy

1. Log in to cPanel
2. Navigate to "MySQL Databases"
3. Create a new database (e.g., `DB_DilliStyle`)
4. Create a new database user with a strong password
5. Add the user to the database with all privileges
6. Note down the database name, username, and password

## 3. Upload Files to GoDaddy

### Using ZIP File (Recommended)

1. Create a zip file of your entire project:
   - On Windows: Right-click the project folder > Send to > Compressed (zipped) folder
   - On Mac: Right-click the project folder > Compress
   - Or use command line: `zip -r project.zip your-project-folder`

2. Log in to cPanel
3. Open File Manager
4. Navigate to the subdomain's document root (usually `/public_html/backend`)
5. Click "Upload" button in the top menu
6. Upload your zip file
7. Once uploaded, right-click the zip file and select "Extract"
8. In the extraction dialog, make sure the files will extract to the correct directory
9. Click "Extract File(s)" to unzip the project
10. After extraction is complete, you can delete the zip file to save space

### Using File Manager in cPanel:

1. Log in to cPanel
2. Open File Manager
3. Navigate to the subdomain's document root (usually `/public_html/backend`)
4. Upload all the PHP files and folders from your local project

### Using FTP:

1. Connect to your hosting using an FTP client with your GoDaddy credentials
2. Navigate to the subdomain's document root (usually `/public_html/backend`)
3. Upload all the PHP files and folders from your local project

## 4. Configure Environment

1. Create a `.env` file in your project root:
   ```bash
   cp env.example .env
   ```

2. Edit the `.env` file with your GoDaddy database credentials:
   ```
   # Database Configuration
   DB_HOST=localhost
   DB_NAME=your_database_name
   DB_USER=your_db_username
   DB_PASS=your_db_password
   
   # JWT Secret
   JWT_SECRET=your_random_secret_key
   
   # API Information
   API_VERSION=1.0.0
   API_NAME="Dilli Style API"
   
   # Base URL
   BASE_URL=https://backend.dillistyle.shop
   ```

3. Generate a secure JWT secret key using a password generator (you can use something like https://passwordsgenerator.net/ if you don't have command line access)

## 5. Import Database Schema

1. Go to cPanel > phpMyAdmin
2. Select your database
3. Go to the "Import" tab
4. Select the `database.sql` file from your project and import it

## 6. Configure .htaccess for GoDaddy

Make sure your `.htaccess` file has the necessary configurations. The project already includes a basic `.htaccess` file, but you may need to add error logging:

```
# Add this to .htaccess to enable error logging
php_value error_log /home/username/public_html/backend/errors.log
```

Replace `username` with your actual GoDaddy username.

## 7. Set Proper File Permissions

Through cPanel File Manager or FTP, set the following permissions:
- Directories: 755
- Files: 644

In cPanel File Manager:
1. Select all directories
2. Click "Change Permissions"
3. Set to 755
4. Select "Apply to directories only"
5. Click "Change Permissions"

Then for files:
1. Select all files
2. Click "Change Permissions"
3. Set to 644
4. Select "Apply to files only"
5. Click "Change Permissions"

## 8. Enable SSL for your API Subdomain

1. In cPanel, look for "SSL/TLS" or "Security" section
2. Choose "SSL/TLS Status" or similar option
3. Make sure SSL is enabled for backend.dillistyle.shop
4. If not available automatically, you may need to request an SSL certificate installation from GoDaddy support

## 9. Test Your API

Visit `https://backend.dillistyle.shop/` - You should see a JSON response:

```json
{
  "message": "Welcome to Dilli Style API",
  "version": "1.0.0",
  "status": "Active",
  "base_url": "https://backend.dillistyle.shop"
}
```

## 10. Connecting Your Next.js Frontend on Vercel

### Update API Base URL in Your Frontend

In your Next.js project, update the API base URL to point to your new backend:

1. Create or edit your environment variables in your Next.js project:

```javascript
// .env.local or similar
NEXT_PUBLIC_API_URL=https://backend.dillistyle.shop
```

2. Update your API fetching logic to use this variable:

```javascript
// Example API fetching function
async function fetchProducts() {
  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/products`);
  return response.json();
}
```

### Configure CORS for Your Vercel Frontend

If you're experiencing CORS issues, update your `.htaccess` file to allow requests from your Vercel domain:

```
# Replace the existing Access-Control-Allow-Origin with these lines
SetEnvIf Origin "^https://(www\.|)dillistyle\.shop$" ALLOWED_ORIGIN=$0
Header set Access-Control-Allow-Origin "%{ALLOWED_ORIGIN}e" env=ALLOWED_ORIGIN
Header set Access-Control-Allow-Credentials "true"
```

### Configure Vercel Environment

1. Log in to your Vercel dashboard
2. Select your Next.js project
3. Go to "Settings" > "Environment Variables"
4. Add your environment variables:
   - `NEXT_PUBLIC_API_URL` = `https://backend.dillistyle.shop`

### Deploy Your Frontend

1. Push your code changes to your GitHub repository
2. Vercel will automatically deploy your updated frontend
3. Once deployed, your Next.js frontend will be connected to your PHP backend on GoDaddy

## Troubleshooting

### CORS Issues

If you're experiencing CORS issues even after updating the `.htaccess` file:

1. Make sure your API requests include the right headers
2. Try setting more permissive CORS headers temporarily for debugging:
   ```
   Header always set Access-Control-Allow-Origin "*"
   Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
   Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
   ```
3. Check if GoDaddy has additional CORS restrictions (contact support if needed)

### Database Connection Issues

1. Check your database credentials in the `.env` file
2. Verify that your database user has the correct permissions
3. Check if GoDaddy has specific connection requirements for their MySQL service

### PHP Version Issues

1. GoDaddy shared hosting might use a different PHP version than your local environment
2. Check your PHP version in cPanel > "PHP Version" or "PHP Configuration"
3. Update your code if needed to be compatible with the available PHP version

### Debugging PHP Errors

1. Create an error log file:
   ```
   touch /home/username/public_html/backend/errors.log
   chmod 666 /home/username/public_html/backend/errors.log
   ```

2. Configure PHP to log errors by adding to `.htaccess`:
   ```
   php_value error_log /home/username/public_html/backend/errors.log
   php_value display_errors 0
   php_value log_errors 1
   ```

3. For temporary debugging, add this to the top of your PHP files:
   ```php
   ini_set('display_errors', 1);
   ini_set('display_startup_errors', 1);
   error_reporting(E_ALL);
   ```

### Path Issues

GoDaddy's file structure might differ from standard setups. If you encounter include/require path issues:

1. Use absolute paths in includes:
   ```php
   require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/config.php';
   ```

2. Or define a base path constant:
   ```php
   define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/backend');
   require_once BASE_PATH . '/config/config.php';
   ```

## Security Considerations

1. Use strong, unique passwords for all accounts
2. Generate a secure JWT secret key
3. Keep your code updated with security patches
4. Remove any setup/installation files after use
5. Implement rate limiting if needed
6. Consider restricting CORS to only your frontend domain
7. Set up proper file permissions to prevent unauthorized access
8. Configure proper HTTP security headers

## Post-Deployment Checklist

- [ ] API base URL is correctly set in your Next.js frontend
- [ ] API endpoints return expected responses
- [ ] User authentication works correctly
- [ ] Database operations function properly
- [ ] File uploads work (if applicable)
- [ ] SSL is working correctly for both frontend and API
- [ ] Error logging is configured and working
- [ ] CORS is properly configured to allow frontend requests
- [ ] All sensitive files and directories are secured 