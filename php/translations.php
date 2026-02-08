<?php
session_start();
require_once "db_connect.php";
$db->set_charset("utf8mb4");

if(isset($_POST['company'], $_POST['keyCode'], $_POST['englishDecs'], $_POST['chineseDecs'], $_POST['malayDecs'], $_POST['tamilDecs'], $_POST['japaneseDecs'])){
	$keyCode = filter_input(INPUT_POST, 'keyCode', FILTER_SANITIZE_STRING);
	$englishDecs = filter_input(INPUT_POST, 'englishDecs', FILTER_SANITIZE_STRING);
	$chineseDecs = filter_input(INPUT_POST, 'chineseDecs', FILTER_SANITIZE_STRING);
    $malayDecs = filter_input(INPUT_POST, 'malayDecs', FILTER_SANITIZE_STRING);
	$tamilDecs = filter_input(INPUT_POST, 'tamilDecs', FILTER_SANITIZE_STRING);
    $japaneseDecs = filter_input(INPUT_POST, 'japaneseDecs', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);

    if($_POST['keyId'] != null && $_POST['keyId'] != ''){
        if ($update_stmt = $db->prepare("UPDATE message_resource SET message_key_code=?, en=?, zh=?, my=?, ne=?, ja=?, company=? WHERE id=?")) {
            $update_stmt->bind_param('ssssssss', $keyCode, $englishDecs, $chineseDecs, $malayDecs, $tamilDecs, $japaneseDecs, $company, $_POST['keyId']);
            
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
                $db->close();
                
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Updated Successfully!!" 
                    )
                );
            }
        }
    }
    else{
        if ($insert_stmt = $db->prepare("INSERT INTO message_resource (message_key_code, en, zh, my, ne, ja, company) VALUES (?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssss', $keyCode, $englishDecs, $chineseDecs, $malayDecs, $tamilDecs, $japaneseDecs, $company);
            
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
                echo json_encode(
                    array(
                        "status"=> "failed", 
                        "message"=> $insert_stmt->error
                    )
                );
            }
            else{
                $insert_stmt->close();
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