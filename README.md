# Dilli Style PHP Backend

A PHP backend for the Dilli Style E-commerce website with JWT authentication.

## Features

- User registration and login with JWT authentication
- Product management
- Category management
- Shopping cart functionality
- Order processing
- Authentication middleware for protected routes
- Input validation
- MySQL database integration

## Project Structure

```
DSphpBackend/
├── api/                    # API endpoints
│   ├── auth/               # Authentication endpoints
│   │   ├── login.php       # User login
│   │   └── register.php    # User registration
│   ├── products/           # Product endpoints
│   │   ├── read.php        # Get all products
│   │   ├── read_one.php    # Get product by ID
│   │   ├── featured.php    # Get featured products
│   │   ├── search.php      # Search products
│   │   └── category.php    # Get products by category
│   ├── categories/         # Category endpoints
│   │   └── read.php        # Get all categories
│   └── cart/               # Cart endpoints
│       ├── view.php        # View cart
│       └── add_item.php    # Add item to cart
├── config/                 # Configuration files
│   ├── database.php        # Database connection
│   └── jwt.php             # JWT utilities
├── models/                 # Data models
│   ├── User.php            # User model
│   ├── Product.php         # Product model
│   ├── Category.php        # Category model
│   ├── Cart.php            # Cart model
│   └── Order.php           # Order model
├── middlewares/            # Middleware functions
│   └── AuthMiddleware.php  # Authentication middleware
├── database.sql            # Database SQL schema
├── index.php               # Welcome message
├── API_GUIDE.md            # API documentation
└── README.md               # This file
```

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:
```
git clone <repository-url>
cd DSphpBackend
```

2. Create the database:
```
mysql -u root -p < database.sql
```

3. Configure your web server to point to the DSphpBackend directory.

4. Update the database configuration in `config/database.php` if needed.

## Database Setup

The `database.sql` file contains all the SQL statements needed to create the database, tables, and some demo data.

It includes:
- Users table with admin and regular user accounts
- Categories table with sample categories
- Products table with sample products
- Tables for carts, cart items, orders, and order items

## API Documentation

See the [API Guide](API_GUIDE.md) for detailed information about all available endpoints.

## Testing with Postman

1. Import the `DilliStyle-PHP-API.postman_collection.json` file into Postman.
2. Create an environment with a variable `base_url` set to your local server URL (e.g., `http://localhost/DSphpBackend`).
3. Register a user or use the demo user (username: `user`, password: `user123`).
4. Login to get a JWT token, which will be automatically set for authenticated requests.
5. Test the various API endpoints.

## Demo Accounts

- Admin: 
  - Username: `admin`
  - Password: `admin123`

- Regular User:
  - Username: `user`
  - Password: `user123`

## Security Notes

- This implementation uses a custom JWT implementation for demonstration purposes.
- In a production environment, consider using the `firebase/php-jwt` library for better security.
- Ensure proper HTTPS configuration in a production environment.
- Consider implementing rate limiting for login attempts.

## License

MIT 