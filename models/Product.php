<?php
class Product {
    // Database connection and table name
    private $conn;
    private $table_name = "products";
    
    // Object properties
    public $id;
    public $name;
    public $description;
    public $price;
    public $category_id;
    public $image_url;
    public $stock_quantity;
    public $featured;
    public $created_at;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create product
    public function create() {
        // Insert query
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name, 
                      description = :description, 
                      price = :price, 
                      category_id = :category_id, 
                      image_url = :image_url, 
                      stock_quantity = :stock_quantity, 
                      featured = :featured, 
                      created_at = :created_at";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->featured = htmlspecialchars(strip_tags($this->featured));
        $this->created_at = date('Y-m-d H:i:s');
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":created_at", $this->created_at);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Read all products
    public function read() {
        // Select all query
        $query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, 
                    p.image_url, p.stock_quantity, p.featured, p.created_at
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read single product
    public function readOne() {
        // Query to read single record
        $query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, 
                    p.image_url, p.stock_quantity, p.featured, p.created_at
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
                LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind id
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get record details
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set properties if record exists
        if($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->category_name = $row['category_name'];
            $this->image_url = $row['image_url'];
            $this->stock_quantity = $row['stock_quantity'];
            $this->featured = $row['featured'];
            $this->created_at = $row['created_at'];
            return true;
        }
        
        return false;
    }
    
    // Update product
    public function update() {
        // Update query
        $query = "UPDATE " . $this->table_name . "
                SET name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    image_url = :image_url, 
                    stock_quantity = :stock_quantity, 
                    featured = :featured
                WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->featured = htmlspecialchars(strip_tags($this->featured));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':stock_quantity', $this->stock_quantity);
        $stmt->bindParam(':featured', $this->featured);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete product
    public function delete() {
        // Delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind id
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Search products
    public function search($keywords) {
        // Search query
        $query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, 
                    p.image_url, p.stock_quantity, p.featured, p.created_at
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.name LIKE ? OR p.description LIKE ?
                ORDER BY p.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind search terms
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read products by category
    public function readByCategory($category_name) {
        // Query
        $query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, 
                    p.image_url, p.stock_quantity, p.featured, p.created_at
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE c.name = ?
                ORDER BY p.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind category name
        $category_name = htmlspecialchars(strip_tags($category_name));
        $stmt->bindParam(1, $category_name);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read featured products
    public function readFeatured() {
        // Query
        $query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, 
                    p.image_url, p.stock_quantity, p.featured, p.created_at
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.featured = 1
                ORDER BY p.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
}
?> 