<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
   
    switch ($action) {
        case 'get_column_types':
            include_once 'src/db_get_column_types.php';
            break;
        case 'update_data':
            include_once 'src/db_update_data.php';
            break;
        case 'delete_data':
            include_once 'src/db_delete_data.php';
            break;
        case 'delete_all_data':
            include_once 'src/db_delete_data.php';
            break;
        case 'delete_selected_data':
            include_once 'src/db_delete_data.php';
            break;
        default:
            echo json_encode(["success" => false, "errorMessages" => ["无效的操作"]]);
            break;
}
}
?>
