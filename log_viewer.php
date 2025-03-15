<?php
// db.php - 引入数据库连接类
session_start(); // 启动会话

// 检查用户是否已登录
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    // 如果没有登录，重定向到登录页面
    header('Location: DatabaseLogin.php');
    exit();
}

$log_file = 'logs/wechat.log';

// 每页显示的日志条数
$logs_per_page = 50;

// 获取总行数
$log_content = '';
$total_lines = 0;
if (file_exists($log_file)) {
    // 获取日志文件的所有行
    $lines = file($log_file, FILE_IGNORE_NEW_LINES);
    $total_lines = count($lines); // 总行数
}

// 计算总页数
$total_pages = ceil($total_lines / $logs_per_page);

// 获取当前页码（默认为 1）
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // 确保页码大于 0
if ($page > $total_pages) $page = $total_pages; // 确保页码不超过总页数

// 计算起始行数
$start_line = ($page - 1) * $logs_per_page;
$end_line = min($start_line + $logs_per_page, $total_lines);

// 获取当前页的日志内容
$log_content = implode("\n", array_slice($lines, $start_line, $logs_per_page));

// 获取用户选择的保留时间
$retention_time = isset($_POST['retention_time']) ? $_POST['retention_time'] : '10秒';
$auto_delete = isset($_POST['auto_delete']) ? $_POST['auto_delete'] : false;

// 自动删除日志功能
function delete_old_logs($log_file, $retention_time) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES);
    $new_lines = [];
    $current_time = time();

    // 计算保留时间的阈值
    $time_threshold = 0;
    switch ($retention_time) {
        case '10秒':
            $time_threshold = 10; // 10秒
            break;
        case '30秒':
            $time_threshold = 30; // 30秒
            break;
        case '1分钟':
            $time_threshold = 60; // 1分钟
            break;
        case '1小时':
            $time_threshold = 60 * 60; // 1小时
            break;
        case '1天':
            $time_threshold = 24 * 60 * 60; // 1天
            break;
        case '1年':
            $time_threshold = 365 * 24 * 60 * 60; // 1年
            break;
        case '1周':
            $time_threshold = 7 * 24 * 60 * 60; // 7天的秒数
            break;
        case '1月':
            $time_threshold = 30 * 24 * 60 * 60; // 30天的秒数
            break;
        case '半年':
            $time_threshold = 180 * 24 * 60 * 60; // 180天的秒数
            break;
    }

    // 遍历日志并删除过期的日志
    foreach ($lines as $line) {
        // 假设日志的时间戳位于每行的开头（比如格式：2023-02-22 12:00:00）
        preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches);
        if (isset($matches[1])) {
            $log_time = strtotime($matches[1]);
            if ($current_time - $log_time <= $time_threshold) {
                $new_lines[] = $line; // 保留不超过阈值的日志
            }
        }
    }

    // 重写日志文件
    file_put_contents($log_file, implode("\n", $new_lines));
}

// 如果勾选了自动删除，执行删除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auto_delete) {
    delete_old_logs($log_file, $retention_time);
    header("Location: log_viewer.php"); // 刷新页面
    exit();
}

// 清空日志文件的功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    file_put_contents($log_file, ''); // 清空日志文件内容
    header("Location: log_viewer.php"); // 刷新页面
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看日志</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        #log {
            width: 80%;
            margin: 20px auto;
            padding: 10px;
            border: 1px solid #ccc;
            overflow-y: scroll;
            white-space: pre-wrap;
            background-color: #f4f4f4;
        }
        #clear-button {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: #ff4d4d;
            color: white;
            text-align: center;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        #clear-button:hover {
            background-color: #e60000;
        }
        #pagination {
            text-align: center;
            margin-top: 20px;
        }
        #pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
        }
        #pagination a:hover {
            background-color: #0056b3;
        }
        #settings {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<h1 style="text-align:center;">微信日志查看器</h1>

<!-- 设置日志保留时间和自动删除 -->
<form method="POST" action="" style="position: absolute; top: 20px; left: 120px;">
    <label for="retention_time">日志保留时间:</label>
    <select name="retention_time" id="retention_time">
        <option value="10秒" <?php echo ($retention_time === '10秒') ? 'selected' : ''; ?>>10秒</option>
        <option value="30秒" <?php echo ($retention_time === '30秒') ? 'selected' : ''; ?>>30秒</option>
        <option value="1分钟" <?php echo ($retention_time === '1分钟') ? 'selected' : ''; ?>>1分钟</option>
        <option value="1小时" <?php echo ($retention_time === '1小时') ? 'selected' : ''; ?>>1小时</option>
        <option value="1天" <?php echo ($retention_time === '1天') ? 'selected' : ''; ?>>1天</option>
        <option value="1年" <?php echo ($retention_time === '1年') ? 'selected' : ''; ?>>1年</option>
        <option value="1周" <?php echo ($retention_time === '1周') ? 'selected' : ''; ?>>1周</option>
        <option value="1月" <?php echo ($retention_time === '1月') ? 'selected' : ''; ?>>1月</option>
        <option value="半年" <?php echo ($retention_time === '半年') ? 'selected' : ''; ?>>半年</option>
    </select>
    <label for="auto_delete">自动删除日志</label>
    <input type="checkbox" name="auto_delete" id="auto_delete" <?php echo ($auto_delete) ? 'checked' : ''; ?>>
    <button type="submit" style="background-color: #28a745; color: white; padding: 5px 10px; margin-left: 10px;">应用设置</button>
</form>

<!-- 清空日志按钮 -->
<form method="POST" action="">
    <button type="submit" name="clear_log" id="clear-button">清空日志</button>
</form>

<div id="log">
    <?php echo nl2br(htmlspecialchars($log_content)); // 显示当前页的日志内容 ?>
</div>

<div id="pagination">
    <!-- 上一页按钮 -->
    <?php if ($page > 1): ?>
        <a href="log_viewer.php?page=<?php echo $page - 1; ?>">上一页</a>
    <?php endif; ?>

    <!-- 显示当前页码和总页数 -->
    <span>第 <?php echo $page; ?> 页 / 共 <?php echo $total_pages; ?> 页</span>

    <!-- 下一页按钮 -->
    <?php if ($page < $total_pages): ?>
        <a href="log_viewer.php?page=<?php echo $page + 1; ?>">下一页</a>
    <?php endif; ?>
</div>

</body>
</html>
