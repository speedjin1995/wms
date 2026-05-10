<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['id'])){
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);

    if ($stmt = $db->prepare("SELECT sales.*, users.name as created_by_name FROM sales LEFT JOIN users ON sales.created_by = users.id WHERE sales.id=?")) {
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
                    'receipt_no' => $row['receipt_no'],
                    'subtotal' => $row['subtotal'],
                    'tax' => $row['tax'],
                    'tax_amount' => $row['tax_amount'],
                    'discount' => $row['discount'],
                    'total_price' => $row['total_price'],
                    'payments' => json_decode($row['payments'], true),
                    'created_by_name' => $row['created_by_name'],
                    'created_datetime' => $row['created_datetime'],
                );

                // Get Sales Cart
                $cart_stmt = $db->prepare("SELECT sales_cart.*, products.product_name as product_name FROM sales_cart LEFT JOIN products ON sales_cart.product_id = products.id WHERE sales_cart.sales_id=? AND sales_cart.status = 0");
                $cart_stmt->bind_param('s', $id);
                $cart_stmt->execute();
                $cart_result = $cart_stmt->get_result();
                $cartItems = array();
                    
                while ($cart_row = $cart_result->fetch_assoc()) {
                    $cartItems[] = array(
                        'id' => $cart_row['id'],
                        'sales_id' => $cart_row['sales_id'],
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
