<?php
require_once 'db_connect.php';
session_start();
$company = $_SESSION['customer'];

if(isset($_POST['userID'])){
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($select_stmt = $db->prepare("select counting.*, products.product_name, supplies.supplier_name from counting, products, supplies where counting.product = products.id AND counting.supplier = supplies.id AND counting.deleted = '0' AND counting.company = '$company' AND counting.id=?")) {
        $select_stmt->bind_param('s', $id);

        if (! $select_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Something went wrong went execute"
                )); 
        }
        else{
            $result = $select_stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $createdDateTime = new DateTime($row['created_datetime']);
                $createdDateTime->modify('+8 hours');
                $formattedDateTime = $createdDateTime->format('d/m/Y H:i:s');

                $message = '<html>
    <head>
        <style>
            @media print {
                @page {
                    size: 50mm 40mm; /* Set the page size to 50mm x 40mm */
                    margin: 0; /* Remove default margins */
                }
            } 

            table {
                width: 100%;
                border-collapse: collapse;
            } 
            
            .table th, .table td {
                padding: 1px;
                vertical-align: top;
            } 
            
            .table-bordered {
                border: 1px solid #000000;
            } 
            
            .table-bordered th, .table-bordered td {
                border: 1px solid #000000;
                font-family: sans-serif;
            } 
            
            .row {
                display: flex;
                flex-wrap: wrap;
                margin-top: 20px;
            } 
            
            .col-md-3{
                position: relative;
                width: 25%;
            }
            
            .col-md-9{
                position: relative;
                width: 75%;
            }
            
            .col-md-7{
                position: relative;
                width: 58.333333%;
            }
            
            .col-md-5{
                position: relative;
                width: 41.666667%;
            }
            
            .col-md-6{
                position: relative;
                width: 50%;
            }
            
            .col-md-4{
                position: relative;
                width: 33.333333%;
            }
            
            .col-md-8{
                position: relative;
                width: 66.666667%;
            }
        </style>
    </head>
    
    <body>';
        $message .= '<table class="table">
            <tbody>
                <tr>
                    <td style="width: 40%;border-top:0px;">
                        <img src="assets/logo_customer.png" width="100%" height="auto" />
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$formattedDateTime.'</span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="table">
            <tbody>
                <tr>
                    <td style="width: 30%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">Supplier</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">:</span>
                        </p>
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$row['supplier_name'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 30%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">Item</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">:</span>
                        </p>
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$row['product_name'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 30%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">IQC No.</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">:</span>
                        </p>
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$row['batch_no'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 30%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">Article No.</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">:</span>
                        </p>
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$row['article_code'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 30%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">Batch No.</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">:</span>
                        </p>
                    </td>
                    <td style="width: 60%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">'.$row['batch_no'].'</span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table><br>
        
        <table class="table">
            <tbody>
                <tr>
                    <td style="width: 40%;border-top:0px;">
                        <p>
                            <span style="font-size: 10px;font-family: sans-serif;">T.W.: '.$row['gross'].'</span><br>
                            <span style="font-size: 10px;font-family: sans-serif;">U.W.: '.$row['unit'].'</span>
                        </p>
                    </td>
                    <td style="width: 10%;border-top:0px;">&nbsp</td>
                    <td style="width: 50%;border-top:0px;">
                        <p>
                            <span style="font-size: 14px;font-family: sans-serif;">'.$row['count'].'</span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>';

    $message .= '</body>
</html>';

                

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $message
                    )
                );
            }
            else{
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Data Not Found"
                    )); 
            }
        }
    }
    else{
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "Something went wrong"
            )); 
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