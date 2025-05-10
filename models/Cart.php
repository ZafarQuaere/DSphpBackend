<?php
class Cart {
    // Database connection and table name
    private $conn;
    private $table_name = "carts";
    private $cart_items_table = "cart_items";
    
    // Object properties
    public $id;
    public $user_id;
    public $created_at;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get user cart or create if doesn't exist
    public function getUserCart($user_id) {
        // First, check if the user already has a cart
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind user_id
        $user_id = htmlspecialchars(strip_tags($user_id));
        $stmt->bindParam(1, $user_id);
        
        // Execute query
        $stmt->execute();
        
        // If user has no cart, create one
        if($stmt->rowCount() == 0) {
            $query = "INSERT INTO " . $this->table_name . " SET user_id = :user_id, created_at = :created_at";
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(":user_id", $user_id);
            $created_at = date('Y-m-d H:i:s');
            $stmt->bindParam(":created_at", $created_at);
            
            // Execute query
            $stmt->execute();
            
            // Set ID to newly created cart
            $this->id = $this->conn->lastInsertId();
        } else {
            // Get existing cart ID
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
        }
        
        $this->user_id = $user_id;
        return $this->id;
    }
    
    // Get cart items
    public function getCartItems() {
        // Query to get cart items with product details
        $query = "SELECT ci.id, ci.product_id, p.name, p.price, p.image_url, ci.quantity
                FROM " . $this->cart_items_table . " ci
                LEFT JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind cart id
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Add item to cart
    public function addItem($product_id, $quantity) {
        // First check if item already exists in cart
        $query = "SELECT id, quantity FROM " . $this->cart_items_table . " 
                  WHERE cart_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $stmt->bindParam(1, $this->id);
        $product_id = htmlspecialchars(strip_tags($product_id));
        $stmt->bindParam(2, $product_id);
        
        // Execute query
        $stmt->execute();
        
        // If item exists, update quantity
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            
            $query = "UPDATE " . $this->cart_items_table . " 
                      SET quantity = :quantity
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(":quantity", $new_quantity);
            $stmt->bindParam(":id", $row['id']);
            
            // Execute query
            return $stmt->execute();
        } else {
            // Insert new item
            $query = "INSERT INTO " . $this->cart_items_table . " 
                      SET cart_id = :cart_id, 
                          product_id = :product_id, 
                          quantity = :quantity";
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind values
            $quantity = htmlspecialchars(strip_tags($quantity));
            
            $stmt->bindParam(":cart_id", $this->id);
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":quantity", $quantity);
            
            // Execute query
            return $stmt->execute();
        }
    }
    
    // Update cart item
    public function updateItem($item_id, $quantity) {
        // Check if item belongs to this cart
        $query = "SELECT id FROM " . $this->cart_items_table . " 
                  WHERE id = ? AND cart_id = ?";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $item_id = htmlspecialchars(strip_tags($item_id));
        $stmt->bindParam(1, $item_id);
        $stmt->bindParam(2, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // If item exists and belongs to cart, update
        if($stmt->rowCount() > 0) {
            $query = "UPDATE " . $this->cart_items_table . " 
                      SET quantity = :quantity
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind values
            $quantity = htmlspecialchars(strip_tags($quantity));
            
            $stmt->bindParam(":quantity", $quantity);
            $stmt->bindParam(":id", $item_id);
            
            // Execute query
            return $stmt->execute();
        }
        
        return false;
    }
    
    // Remove item from cart
    public function removeItem($item_id) {
        // Check if item belongs to this cart
        $query = "SELECT id FROM " . $this->cart_items_table . " 
                  WHERE id = ? AND cart_id = ?";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $item_id = htmlspecialchars(strip_tags($item_id));
        $stmt->bindParam(1, $item_id);
        $stmt->bindParam(2, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // If item exists and belongs to cart, delete it
        if($stmt->rowCount() > 0) {
            $query = "DELETE FROM " . $this->cart_items_table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            // Bind item id
            $stmt->bindParam(1, $item_id);
            
            // Execute query
            return $stmt->execute();
        }
        
        return false;
    }
    
    // Clear cart (remove all items)
    public function clearCart() {
        // Delete all cart items
        $query = "DELETE FROM " . $this->cart_items_table . " WHERE cart_id = ?";
        $stmt = $this->conn->prepare($query);
        
        // Bind cart id
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Calculate cart totals
    public function getCartTotals() {
        // Query to calculate cart totals
        $query = "SELECT SUM(p.price * ci.quantity) as total_price, 
                         SUM(ci.quantity) as total_items
                  FROM " . $this->cart_items_table . " ci
                  LEFT JOIN products p ON ci.product_id = p.id
                  WHERE ci.cart_id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind cart id
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get totals
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array(
            "total_price" => $row['total_price'] ? $row['total_price'] : 0,
            "total_items" => $row['total_items'] ? $row['total_items'] : 0
        );
    }
}
?> 