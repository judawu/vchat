<?php
// db_get_column_types.php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/logger.php';

// 允许跨域访问（如果是跨域请求）
$logger = new Logger();
$db = DB::getInstance($logger);

// 获取数据库实例
$pdo = $db->getPdo();

// 获取客户端传来的表名和字段名
$tableName = isset($_POST['table_name']) ? $_POST['table_name'] : '';
$columns = isset($_POST['columns']) ? json_decode($_POST['columns']) : [];

if (empty($tableName) || empty($columns)) {
    echo json_encode(['success' => false, 'message' => '表名或字段名为空']);
    exit;
}

try {
  
  
    // 构建查询语句，获取字段类型
    $columnTypes = [];
    foreach ($columns as $column) {
        $stmt = $pdo->prepare("DESCRIBE $tableName $column");
        $stmt->execute();
        $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($columnInfo) {
            $columnTypes[$column] = $columnInfo['Type'];
        } else {
            $columnTypes[$column] = '未知';
        }
    }

    // 返回字段类型信息
    echo json_encode(['success' => true, 'columnTypes' => $columnTypes]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]);
}
?>
