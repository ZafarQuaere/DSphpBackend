<?php
class Order {
    // Database connection and table name
    private $conn;
    private $table_name = "orders";
    private $order_items_table = "order_items";
    
    // Object properties
    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $shipping_address;
    public $tracking_number;
    public $created_at;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create order from user's cart
    public function createFromCart($user_id, $shipping_address) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Get user's cart
            require_once 'Cart.php';
            $cart = new Cart($this->conn);
            $cart_id = $cart->getUserCart($user_id);
            
            // Get cart items and totals
            $cart_items = $cart->getCartItems();
            $cart_totals = $cart->getCartTotals();
            
            // Check if cart is empty
            if($cart_totals['total_items'] == 0) {
                return false;
            }
            
            // Create new order
            $query = "INSERT INTO " . $this->table_name . " 
                      SET user_id = :user_id, 
                          total_amount = :total_amount, 
                          status = :status, 
                          shipping_address = :shipping_address, 
                          created_at = :created_at";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind order values
            $this->user_id = htmlspecialchars(strip_tags($user_id));
            $this->total_amount = $cart_totals['total_price'];
            $this->status = "PENDING";
            $this->shipping_address = htmlspecialchars(strip_tags($shipping_address));
            $this->created_at = date('Y-m-d H:i:s');
            
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":total_amount", $this->total_amount);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":shipping_address", $this->shipping_address);
            $stmt->bindParam(":created_at", $this->created_at);
            
            // Execute order creation
            $stmt->execute();
            
            // Get newly created order ID
            $this->id = $this->conn->lastInsertId();
            
            // Create order items from cart items
            while($item = $cart_items->fetch(PDO::FETCH_ASSOC)) {
                $query = "INSERT INTO " . $this->order_items_table . " 
                          SET order_id = :order_id, 
                              product_id = :product_id, 
                              quantity = :quantity, 
                              price = :price";
                
                $stmt = $this->conn->prepare($query);
                
                // Bind values
                $stmt->bindParam(":order_id", $this->id);
                $stmt->bindParam(":product_id", $item['product_id']);
                $stmt->bindParam(":quantity", $item['quantity']);
                $stmt->bindParam(":price", $item['price']);
                
                // Execute order item creation
                $stmt->execute();
            }
            
            // Clear the user's cart
            $cart->clearCart();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch(Exception $e) {
            // Roll back transaction on error
            $this->conn->rollBack();
            return false;
        }
    }
    
    // Get user's orders
    public function getUserOrders($user_id) {
        // Query to get all user orders
        $query = "SELECT id, total_amount, status, shipping_address, tracking_number, created_at
                FROM " . $this->table_name . "
                WHERE user_id = ?
                ORDER BY created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind user id
        $user_id = htmlspecialchars(strip_tags($user_id));
        $stmt->bindParam(1, $user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get order by ID for a specific user
    public function getOrderById($user_id, $order_id) {
        // Query to get order
        $query = "SELECT id, total_amount, status, shipping_address, tracking_number, created_at
                FROM " . $this->table_name . "
                WHERE id = ? AND user_id = ?
                LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $order_id = htmlspecialchars(strip_tags($order_id));
        $user_id = htmlspecialchars(strip_tags($user_id));
        $stmt->bindParam(1, $order_id);
        $stmt->bindParam(2, $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record details
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set properties if record exists
        if($row) {
            $this->id = $row['id'];
            $this->total_amount = $row['total_amount'];
            $this->status = $row['status'];
            $this->shipping_address = $row['shipping_address'];
            $this->tracking_number = $row['tracking_number'];
            $this->created_at = $row['created_at'];
            return true;
        }
        
        return false;
    }
    
    // Get order items
    public function getOrderItems() {
        // Query to get order items with product details
        $query = "SELECT oi.id, oi.product_id, p.name, oi.price, p.image_url, oi.quantity
                FROM " . $this->order_items_table . " oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind order id
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update order status
    public function updateStatus($order_id, $status) {
        // Update query
        $query = "UPDATE " . $this->table_name . "
                SET status = :status
                WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $status = htmlspecialchars(strip_tags($status));
        $order_id = htmlspecialchars(strip_tags($order_id));
        
        // Validate status
        $valid_statuses = array("PENDING", "PROCESSING", "SHIPPED", "DELIVERED", "CANCELLED");
        if(!in_array($status, $valid_statuses)) {
            return false;
        }
        
        // Bind parameters
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $order_id);
        
        // Execute query
        if($stmt->execute()) {
            // Load order data
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $order_id);
            $stmt->execute();
            
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['id'];
                $this->user_id = $row['user_id'];
                $this->total_amount = $row['total_amount'];
                $this->status = $row['status'];
                $this->shipping_address = $row['shipping_address'];
                $this->tracking_number = $row['tracking_number'];
                $this->created_at = $row['created_at'];
                return true;
            }
        }
        
        return false;
    }
    
    // Admin: get all orders
    public function getAllOrders() {
        // Query to get all orders
        $query = "SELECT o.id, o.user_id, u.username, o.total_amount, o.status, 
                         o.shipping_address, o.tracking_number, o.created_at
                FROM " . $this->table_name . " o
                JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
}
?> 