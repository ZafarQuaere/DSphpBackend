# DilliStyleBackend API Reference

This document lists all the main APIs available in this project, including endpoints, request parameters, and example responses. Update this file whenever you make changes to any API.

---

## 1. User Authentication APIs

### Register User
- **Endpoint:** `/api/auth/register.php`
- **Method:** POST
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "username": "testuser",
    "email": "testuser@example.com",
    "password": "password123"
  }
  ```
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Registration successful.",
    "data": { "user_id": 1 }
  }
  ```

### Login User
- **Endpoint:** `/api/auth/login.php`
- **Method:** POST
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "email": "testuser@example.com",
    "password": "password123"
  }
  ```
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Login successful",
    "data": {
      "token": "<jwt_token>",
      "user": {
        "id": 1,
        "username": "testuser",
        "email": "testuser@example.com",
        "role": "ADMIN"
      }
    }
  }
  ```

---

## 2. Product APIs

### Create Product (with Image Upload)
- **Endpoint:** `/api/products/create.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (form-data):**
  - `name` (text)
  - `description` (text)
  - `price` (number)
  - `category_id` (number)
  - `stock_quantity` (number)
  - `featured` (number, optional)
  - `image` (file)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Product was created.",
    "data": {
      "id": 1,
      "image_url": "uploads/images/img_xxxxx.jpg"
    }
  }
  ```

### Get All Products
- **Endpoint:** `/api/products/read.php`
- **Method:** GET
- **Headers:** *(none required unless protected)*
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Products retrieved successfully.",
    "data": [
      {
        "id": 1,
        "name": "Test Product",
        "description": "This is a test product",
        "price": "99.99",
        "category_id": 1,
        "stock_quantity": 10,
        "featured": 0,
        "image_url": "uploads/images/img_xxxxx.jpg",
        "created_at": "2025-06-15 12:34:56"
      }
    ]
  }
  ```

### Update Product
- **Endpoint:** `/api/products/update.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (form-data or JSON):**
  - `id` (number)
  - Other fields to update (e.g., `name`, `description`, etc.)
  - `image` (file, optional)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Product updated successfully.",
    "data": null
  }
  ```

### Delete Product
- **Endpoint:** `/api/products/delete.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (JSON or form-data):**
  - `id` (number)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Product deleted successfully.",
    "data": null
  }
  ```

---

## 3. Category APIs

### Create Category
- **Endpoint:** `/api/categories/create.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (form-data):**
  - `name` (text)
  - `description` (text, optional)
  - `category_image` (file, optional)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Category created successfully.",
    "data": { "id": 1 }
  }
  ```

### Update Category
- **Endpoint:** `/api/categories/update.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (form-data or JSON):**
  - `id` (number)
  - `name` (text, optional)
  - `description` (text, optional)
  - `category_image` (file, optional)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Category updated successfully.",
    "data": null
  }
  ```

### Delete Category
- **Endpoint:** `/api/categories/delete.php`
- **Method:** POST
- **Headers:**
  - `Authorization: Bearer <token>`
- **Request Body (JSON or form-data):**
  - `id` (number)
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Category deleted successfully.",
    "data": null
  }
  ```

---

## 4. Other Product APIs

### Get Single Product
- **Endpoint:** `/api/products/read_one.php`
- **Method:** GET or POST
- **Request Body (if POST):**
  ```json
  { "id": 1 }
  ```
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Product retrieved successfully.",
    "data": { /* product fields */ }
  }
  ```

### Search Products
- **Endpoint:** `/api/products/search.php`
- **Method:** GET or POST
- **Request Body (if POST):**
  ```json
  { "search": "keyword" }
  ```
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Products found.",
    "data": [ /* array of products */ ]
  }
  ```

### Get Featured Products
- **Endpoint:** `/api/products/featured.php`
- **Method:** GET
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Featured products retrieved successfully.",
    "data": [ /* array of products */ ]
  }
  ```

### Get Products by Category
- **Endpoint:** `/api/products/category.php`
- **Method:** GET or POST
- **Request Body (if POST):**
  ```json
  { "category_id": 1 }
  ```
- **Response:**
  ```json
  {
    "status": 1,
    "message": "Products by category retrieved successfully.",
    "data": [ /* array of products */ ]
  }
  ```

---

**Update this file whenever you add, remove, or change any API!** 