<?php
// 引入数据库连接
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/logger.php';

$logger = new Logger();
$db = DB::getInstance($logger);

// 获取数据库实例
$pdo = $db->getPdo();

// 获取删除请求的 id 和 table_name
$tableName = $_POST['table_name'] ?? '';
$id = $_POST['id'] ?? '';
$deleteAll = $_POST['delete_all'] ?? false; // 判断是否删除所有数据
$ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : []; // 删除选中行的 ID

// 验证表名是否合法
if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    echo "无效的表名";
    exit;
}

try {
    
 
    
    
    // 如果是删除所有数据
    
    
    if ($deleteAll&& $tableName) {
        $stmt = $pdo->prepare("DELETE FROM `$tableName`");
        $stmt->execute();
        echo "所有数据已删除";
    }
    // 如果是删除选中行
    else if (!empty($ids) && $tableName) {
        // 生成适当的占位符
        $placeholders = rtrim(str_repeat("?,", count($ids)), ",");
        $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE `id` IN ($placeholders)");
        $stmt->execute($ids); // 执行删除
        echo "选中数据已删除";
    } 
    
     else if  ($id && $tableName){
        // 生成适当的占位符
        $stmt = $pdo->prepare("DELETE FROM $tableName WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute(); // 执行删除
        echo "选中数据已删除";
    } 
    
    
    
    else {
        echo "无效的操作";
    }
} catch (Exception $e) {
    echo "删除失败: " . $e->getMessage();
}
?>
