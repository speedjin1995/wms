<?php
require_once 'db_connect.php';
session_start();

if(isset($_POST['purchaseNo'], $_POST['itemProduct'], $_POST['itemWeight'], $_POST['itemPrice'], $_POST['itemTotal'], $_POST['grandTotal'])) {
  $grandTotal = filter_input(INPUT_POST, 'grandTotal', FILTER_SANITIZE_STRING);
  $userID  = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $now = date('Y-m-d H:i:s');
  $today = date('Y-m-d 00:00:00');
  $success = true;

  // Build Purchase No
  if(!isset($_POST['purchaseNo']) || $_POST['purchaseNo'] == null || $_POST['purchaseNo'] == ''){
		$prefix = "P";
    $count = 1;
		$purchaseNo = $prefix.date("Ymd");;

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM purchases WHERE created_datetime >= ?")) {
      $select_stmt->bind_param('s', $today);
      
      // Execute the prepared query.
      if (! $select_stmt->execute()) {
          echo json_encode(
              array(
                  "status" => "failed",
                  "message" => "Failed to get latest count"
              )); 
      }
      else{
        $result = $select_stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
          $count = (int)$row['COUNT(*)'] + 1;
        }

        $charSize = strlen(strval($count));

        for($i=0; $i<(4-(int)$charSize); $i++){
          $purchaseNo.='0';  // S0000
        }

        $purchaseNo .= strval($count);  //S00009
			}
		}
		
		$select_stmt->close();
	}
	else{
    $purchaseNo = $_POST['purchaseNo'];
	}

  if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
    if ($update_stmt = $db->prepare("UPDATE purchases SET purchase_no=?, total_price=?, modified_by=?, modified_datetime=? WHERE id=?")) {
      $update_stmt->bind_param('sssss', $purchaseNo, $grandTotal, $userID, $now, $_POST['id']);

      // Execute the prepared query.
      if (! $update_stmt->execute()) {
        echo json_encode(
            array(
                "status"=> "failed", 
                "message"=> $update_stmt->error
            )
        );
      }
      else{
        $update_stmt->close();

        # purchases_cart 
        if (isset($_POST['itemProduct'])){
          $itemProduct = $_POST['itemProduct'];
          $itemWeight = $_POST['itemWeight'];
          $itemPrice = $_POST['itemPrice'];
          $itemTotal = $_POST['itemTotal'];
          $deleteStatus = 1; // mark as deleted
          if(isset($itemProduct) && $itemProduct != null && count($itemProduct) > 0){
            # Delete all existing product rawmat records tied to the product id then reinsert
            if ($delete_stmt = $db->prepare("UPDATE purchases_cart SET status=? WHERE purchase_id=?")){
                $delete_stmt->bind_param('ss', $deleteStatus, $_POST['id']);
                $delete_stmt->execute();
                $delete_stmt->close();

                foreach ($itemProduct as $key => $itemId) {
                  if ($purchase_stmt = $db->prepare("INSERT INTO purchases_cart (purchase_id, product_id, weight, price, total_price) VALUES (?, ?, ?, ?, ?)")){
                    $purchase_stmt->bind_param('sssss', $_POST['id'], $itemId, $itemWeight[$key], $itemPrice[$key], $itemTotal[$key]);
                    $purchase_stmt->execute();
                    $purchase_stmt->close();
                  }
                }
            } 
          }
        }

        $db->close();

        echo json_encode(
            array(
                "status"=> "success", 
                "message"=> "Updated Successfully!!"
            )
        );
      }
  }
  }else{
    if ($insert_stmt = $db->prepare("INSERT INTO purchases (purchase_no, total_price, created_by, created_datetime, company) VALUES (?, ?, ?, ?, ?)")) {
      $insert_stmt->bind_param('sssss', $purchaseNo, $grandTotal, $userID, $now, $company);

      // Execute the prepared query.
      if (! $insert_stmt->execute()) {
        echo json_encode(
            array(
                "status"=> "failed", 
                "message"=> "Failed to created purchases records due to ".$insert_stmt->error
            )
        );
        exit();
      }
      else{
          $id = $insert_stmt->insert_id;;
          $insert_stmt->close();

          if (isset($_POST['itemProduct'])){
            $itemProduct = $_POST['itemProduct'];
            $itemWeight = $_POST['itemWeight'];
            $itemPrice = $_POST['itemPrice'];
            $itemTotal = $_POST['itemTotal'];
            $deleteStatus = 1;
            if(isset($itemProduct) && $itemProduct != null && count($itemProduct) > 0){
              # Delete all existing product rawmat records tied to the product id then reinsert
              if ($delete_stmt = $db->prepare("UPDATE purchases_cart SET status=? WHERE purchase_id=?")){
                  $delete_stmt->bind_param('ss', $deleteStatus, $id);
                  $delete_stmt->execute();
                  $delete_stmt->close();

                  foreach ($itemProduct as $key => $itemId) {
                    if ($purchase_stmt = $db->prepare("INSERT INTO purchases_cart (purchase_id, product_id, weight, price, total_price) VALUES (?, ?, ?, ?, ?)")){
                      $purchase_stmt->bind_param('sssss', $id, $itemId, $itemWeight[$key], $itemPrice[$key], $itemTotal[$key]);
                      $purchase_stmt->execute();
                      $purchase_stmt->close();
                    }
                  }
              } 
            }
          }

          $db->close();

          echo json_encode(
              array(
                "status"=> "success", 
                "message"=> "Added Successfully!!"
              )
          );
      }
  }
  }
}
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );
    
}

?>