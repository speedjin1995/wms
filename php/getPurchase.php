<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['id'])){
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);

    if ($stmt = $db->prepare("SELECT purchases.*, users.name as created_by_name FROM purchases LEFT JOIN users ON purchases.created_by = users.id WHERE purchases.id=?")) {
        $stmt->bind_param('s', $id);
        
        if (!$stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed", 
                    "message" => "Something went wrong"
                )
            ); 
        } else {
            $result = $stmt->get_result();
            $message = array();
            
            if ($row = $result->fetch_assoc()) {
                $message = array(
                    'id' => $row['id'],
                    'purchase_no' => $row['purchase_no'],
                    'total_price' => $row['total_price'],
                    'created_by_name' => $row['created_by_name'],
                    'created_datetime' => $row['created_datetime'],
                );

                // Get Purchase Cart
                $cart_stmt = $db->prepare("SELECT purchases_cart.*, products.product_name as product_name FROM purchases_cart LEFT JOIN products ON purchases_cart.product_id = products.id WHERE purchases_cart.purchase_id=? AND purchases_cart.status = 0");
                $cart_stmt->bind_param('s', $id);
                $cart_stmt->execute();
                $cart_result = $cart_stmt->get_result();
                $cartItems = array();
                    
                while ($cart_row = $cart_result->fetch_assoc()) {
                    $cartItems[] = array(
                        'id' => $cart_row['id'],
                        'purchase_id' => $cart_row['purchase_id'],
                        'product_id' => $cart_row['product_id'],
                        'product_name' => $cart_row['product_name'],
                        'weight' => $cart_row['weight'],
                        'price' => $cart_row['price'],
                        'total_price' => $cart_row['total_price']
                    );
                }
                    
                $message['cart_items'] = $cartItems;
            }
            
            echo json_encode(
                array(
                    "status" => "success", 
                    "message" => $message
                )
            );   
        }
    }
} else {
    echo json_encode(
        array(
            "status" => "failed", 
            "message" => "Missing Attribute"
        )
    ); 
}
?>