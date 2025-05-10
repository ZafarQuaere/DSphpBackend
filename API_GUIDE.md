# Dilli Style API Guide

This document provides information about all available API endpoints in the Dilli Style backend.

## Base URL

Local development: `http://localhost/DSphpBackend`

## Authentication

Most endpoints require authentication using JWT token. To authenticate, include the token in the Authorization header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

## Available Endpoints

### Authentication

#### Register User
- URL: `/api/auth/register.php`
- Method: `POST`
- Authentication: None
- Request Body:
  ```json
  {
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123"
  }
  ```
- Success Response: `201 Created`
  ```json
  {
    "message": "User was successfully registered."
  }
  ```

#### Login
- URL: `/api/auth/login.php`
- Method: `POST`
- Authentication: None
- Request Body:
  ```json
  {
    "username": "johndoe",
    "password": "password123"
  }
  ```
- Success Response: `200 OK`
  ```json
  {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "type": "Bearer",
    "id": 1,
    "username": "johndoe",
    "email": "john@example.com",
    "role": "USER"
  }
  ```

### Products

#### Get All Products
- URL: `/api/products/read.php`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "products": [
      {
        "id": 1,
        "name": "Men's T-Shirt",
        "description": "Comfortable cotton t-shirt for men",
        "price": "24.99",
        "category": "Men",
        "image_url": "https://example.com/images/tshirt.jpg",
        "stock_quantity": 100,
        "featured": true,
        "created_at": "2023-05-01 12:00:00"
      },
      ...
    ]
  }
  ```

#### Get Product by ID
- URL: `/api/products/read_one.php?id=1`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "id": 1,
    "name": "Men's T-Shirt",
    "description": "Comfortable cotton t-shirt for men",
    "price": "24.99",
    "category": "Men",
    "image_url": "https://example.com/images/tshirt.jpg",
    "stock_quantity": 100,
    "featured": true,
    "created_at": "2023-05-01 12:00:00"
  }
  ```

#### Get Featured Products
- URL: `/api/products/featured.php`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "products": [
      {
        "id": 1,
        "name": "Men's T-Shirt",
        "description": "Comfortable cotton t-shirt for men",
        "price": "24.99",
        "category": "Men",
        "image_url": "https://example.com/images/tshirt.jpg",
        "stock_quantity": 100,
        "featured": true,
        "created_at": "2023-05-01 12:00:00"
      },
      ...
    ]
  }
  ```

#### Search Products
- URL: `/api/products/search.php?query=shirt`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "products": [
      {
        "id": 1,
        "name": "Men's T-Shirt",
        "description": "Comfortable cotton t-shirt for men",
        "price": "24.99",
        "category": "Men",
        "image_url": "https://example.com/images/tshirt.jpg",
        "stock_quantity": 100,
        "featured": true,
        "created_at": "2023-05-01 12:00:00"
      },
      ...
    ]
  }
  ```

#### Get Products by Category
- URL: `/api/products/category.php?name=Men`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "products": [
      {
        "id": 1,
        "name": "Men's T-Shirt",
        "description": "Comfortable cotton t-shirt for men",
        "price": "24.99",
        "category": "Men",
        "image_url": "https://example.com/images/tshirt.jpg",
        "stock_quantity": 100,
        "featured": true,
        "created_at": "2023-05-01 12:00:00"
      },
      ...
    ]
  }
  ```

### Categories

#### Get All Categories
- URL: `/api/categories/read.php`
- Method: `GET`
- Authentication: None
- Success Response: `200 OK`
  ```json
  {
    "categories": [
      {
        "id": 1,
        "name": "Men",
        "description": "Men's Clothing",
        "image_url": "https://example.com/images/men.jpg",
        "created_at": "2023-05-01 12:00:00"
      },
      ...
    ]
  }
  ```

### Cart

#### View Cart
- URL: `/api/cart/view.php`
- Method: `GET`
- Authentication: Required
- Success Response: `200 OK`
  ```json
  {
    "id": 1,
    "user_id": 1,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Men's T-Shirt",
        "image_url": "https://example.com/images/tshirt.jpg",
        "price": "24.99",
        "quantity": 2,
        "subtotal": "49.98"
      },
      ...
    ],
    "total_price": "49.98",
    "total_items": 2
  }
  ```

#### Add Item to Cart
- URL: `/api/cart/add_item.php`
- Method: `POST`
- Authentication: Required
- Request Body:
  ```json
  {
    "product_id": 1,
    "quantity": 2
  }
  ```
- Success Response: `200 OK`
  ```json
  {
    "id": 1,
    "user_id": 1,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Men's T-Shirt",
        "image_url": "https://example.com/images/tshirt.jpg",
        "price": "24.99",
        "quantity": 2,
        "subtotal": "49.98"
      },
      ...
    ],
    "total_price": "49.98",
    "total_items": 2
  }
  ```

## Testing with Postman

1. Import the provided Postman collection
2. Set up environment variables:
   - `base_url`: The base URL of your API (e.g., `http://localhost/DSphpBackend`)
   - `token`: This will be automatically set after login

3. Register a user with the Register User request
4. Login with the Login User request (this will automatically set the token)
5. Access the authenticated endpoints using the token 