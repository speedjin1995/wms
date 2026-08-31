<?php
require_once '../../db_connect.php';
session_start();

$company = $_SESSION['customer'];

if (isset($_POST['userID'])) {
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_NUMBER_INT);
    
    // Get user's module_access
    $stmt = $db->prepare("SELECT module_access FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    $moduleAccess = $user['module_access'] ? json_decode($user['module_access'], true) : ['modules' => [], 'categories' => []];
    
    // Get company's available modules (products column)
    $stmt2 = $db->prepare("SELECT products FROM companies WHERE id = ?");
    $stmt2->bind_param('i', $company);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $companyData = $result2->fetch_assoc();
    $stmt2->close();
    
    $companyModules = $companyData['products'] ? json_decode($companyData['products'], true) : [];
    
    // Filter to only main modules (exclude feature flags like 'fruits', 'second_remarks')
    $mainModules = ['wholesale', 'weighing', 'industrial', 'processing'];
    $availableModules = array_values(array_intersect($companyModules, $mainModules));
    
    // Get categories grouped by module for this company
    $categories = [];
    $catQuery = $db->prepare("SELECT id, category_name, module FROM categories WHERE customer = ? AND deleted = '0' ORDER BY module, category_name");
    $catQuery->bind_param('i', $company);
    $catQuery->execute();
    $catResult = $catQuery->get_result();
    while ($row = $catResult->fetch_assoc()) {
        $module = $row['module'];
        if (!isset($categories[$module])) {
            $categories[$module] = [];
        }
        $categories[$module][] = [
            'id' => $row['id'],
            'name' => $row['category_name']
        ];
    }
    $catQuery->close();
    
    echo json_encode([
        'status' => 'success',
        'message' => [
            'moduleAccess' => $moduleAccess,
            'availableModules' => $availableModules,
            'categories' => $categories
        ]
    ]);
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Missing Attribute']);
}
?>
