<?php
// 引入数据库连接
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/logger.php';

$logger = new Logger();
$db = DB::getInstance($logger);

// 获取数据库实例
$pdo = $db->getPdo();

// 获取请求参数
$tableName = $_POST['table_name'] ?? '';
$id = $_POST['id'] ?? '';
$data = $_POST;

// 处理更新逻辑
if ($tableName && $id) {
    // 获取表格的字段信息，假设有函数获取字段类型信息
    $columnTypes = getColumnTypes($tableName, $pdo);  // 从数据库获取字段类型
    
    // 构造更新 SQL 语句
    $setClause = '';
    $errorMessages = [];
    
    // 遍历数据并过滤掉不需要更新的字段
    foreach ($data as $key => $value) {
        if ($key !== 'table_name' && $key !== 'id' && $key !== 'action') {  // 排除 action 字段
            if (isset($columnTypes[$key])) {
                // 根据字段类型转换值
                $convertedValue = convertValueByType($value, $columnTypes[$key]);
                
                // 如果转换失败，添加错误信息
                if ($convertedValue === false) {
                    $errorMessages[] = "字段 '$key' 的数据类型不匹配，请检查输入值。";
                } else {
                    $data[$key] = $convertedValue;  // 更新数据为转换后的值
                }
            }
            $setClause .= "`$key` = :$key, ";
        }
    }

    // 如果存在错误，返回错误信息
    if (!empty($errorMessages)) {
        echo json_encode([
            'success' => false,
            'errorMessages' => $errorMessages
        ]);
        exit;  // 中止后续操作
    }

    // 去掉末尾的逗号和空格
    $setClause = rtrim($setClause, ', ');

    // 准备 SQL 更新语句
    $sql = "UPDATE `$tableName` SET $setClause WHERE `id` = :id";
    $stmt = $pdo->prepare($sql);

    // 绑定参数
    foreach ($data as $key => $value) {
        if ($key !== 'table_name' && $key !== 'id' && $key !== 'action') {  // 确保不绑定 action 字段
            $stmt->bindValue(":$key", $value);
        }
    }
    $stmt->bindValue(":id", $id);

    // 执行更新
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'errorMessages' => ['更新失败，请重试！']
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'errorMessages' => ['缺少表名或 ID 参数。']
    ]);
}

// 数据转换函数：根据数据库字段类型转换输入数据
function convertValueByType($value, $type) {
    switch ($type) {
        case 'timestamp':
        case 'date':
            // 检查日期格式（YYYY-MM-DD 或 YYYY-MM-DD HH:MM:SS）
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                return $value;  // 转换成功
            }
            return false;  // 转换失败
        case 'int':
            // 尝试将字符串转换为整数
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                return (int)$value;  // 转换成功
            }
            return false;  // 转换失败
        case 'float':
            // 尝试将字符串转换为浮动数字
            if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                return (float)$value;  // 转换成功
            }
            return false;  // 转换失败
        default:
            return $value;  // 对于其他类型，直接返回值（假设无特殊转换需求）
    }
}

// 获取字段类型（假设你有类似的函数从数据库中获取表的字段类型）
function getColumnTypes($tableName, $pdo) {
    $stmt = $pdo->prepare("DESCRIBE `$tableName`");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnTypes = [];
    foreach ($columns as $column) {
        $columnTypes[$column['Field']] = $column['Type'];
    }
    return $columnTypes;
}
?>
