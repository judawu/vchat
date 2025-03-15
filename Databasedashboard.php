<?php
// db.php - 引入数据库连接类



// db.php - 引入数据库连接类
session_start(); // 启动会话

// 检查用户是否已登录
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    // 如果没有登录，重定向到登录页面
    header('Location: DatabaseLogin.php');
    exit();
}
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/logger.php';

// 设置内容类型为 HTML，并指定字符集为 UTF-8
header('Content-Type: text/html; charset=utf-8');

// 获取数据库实例
$logger = new Logger();
$db = DB::getInstance($logger);

// 获取所有数据库表名
function getTableNames() {
    global $db;
    $pdo = $db->getPdo();
    $stmt = $pdo->query("SHOW TABLES"); // 获取数据库中的所有表
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// 获取表格数据并分页
function fetchData($tableName, $limit, $offset) {
    global $db;
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM {$tableName} ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 获取表格数据的总行数
function getTotalRows($tableName) {
    global $db;
    $pdo = $db->getPdo();
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tableName}");
    return $stmt->fetchColumn();
}


// 删除所有数据
function deleteAllData($tableName) {
    global $db;
    $pdo = $db->getPdo();
    $stmt = $pdo->query("DELETE FROM {$tableName}");
    return $stmt->fetchColumn();
}

// 处理分页逻辑
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = isset($_GET['rows_per_page']) ? (int)$_GET['rows_per_page'] : 10;
$offset = ($page - 1) * $rowsPerPage;
$totalPages=0;
// 获取所有表名
$tableNames = getTableNames();

// 处理查询的表格数据
$tableName = isset($_GET['table_name']) ? $_GET['table_name'] : '';
$data = [];
$totalRows = 0;

if ($tableName && isValidTableName($tableName)) {
    $data = fetchData($tableName, $rowsPerPage, $offset);
    $totalRows = getTotalRows($tableName);
    $totalPages = ceil($totalRows / $rowsPerPage);
} else {
    echo "无效的表名";
}

// 验证表名是否合法
function isValidTableName($tableName) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $tableName);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>微信开发者服务器数据库表格数据展示</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; color: #333; line-height: 1.6; }
       header {
          background-color:  #003BC6; 
          color: white;
          padding: 20px 0;
        }
    
        .container {
          width: 90%;
          max-width: 1200px;
          margin: 0 auto;
        }
        h1, h2 { color: #007BFF; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #007BFF; color: #fff; }
        input[type="checkbox"] { margin: 0; }
        .pagination a { margin: 0 5px; color: #007BFF; text-decoration: none; }
        .pagination a:hover { text-decoration: underline; }
        .btn { padding: 5px 10px; margin: 5px; cursor: pointer; }
        .btn-edit, .btn-delete { display: none; }
        .popup { background-color: #fff; border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
        .popup-error { color: red; }
        .input-edit { width: 150px; }
         nav ul {
          list-style: none;
          display: flex;
          justify-content: flex-end;
        }
    
        nav ul li {
          margin-left: 20px;
        }
    
        nav ul li a {
          color: white;
          text-decoration: none;
          font-size: 1.1rem;
          transition: color 0.3s ease;
        }

    nav ul li a:hover {
      color: #ddd;
    }
    </style>
</head>
<body>
  <header>
    <div class="container">
     
      <nav>
        <ul>
          <li><a href="https://vchat.juda.monster/">主站</a></li>
       
          <li><a href="https://107.148.54.116:33936/55b9de4a" target="_blank">aaPannel(vpsadmin）</a></li>
          <li><a href="https://vboard.juda.monster/e21a4fea" target="_blank">机场管理</a></li>
          <li><a href="https://xui.juda.monster/" target="_blank">XUI管理</a></li>
        
         <li><a href="https://mp.weixin.qq.com/" target="_blank">微信公众服务</a></li>
          <li><a href="https://developers.weixin.qq.com/apiExplorer?type=messagePush" target="_blank">微信消息测试平台</a></li>
          <li><a href="https://mp.weixin.qq.com/debug?token=1834246867&lang=zh_CN" target="_blank">微信公众平台接口调试工具</a></li>
          <!-- 新增链接 -->
         <li><a href="WeixinFunction.php" target="_blank">微信功能测试</a></li>
          <li><a href="WeixinDraftOperation.php" target="_blank">微信公众号草稿发布功能</a></li>
         <li><a href="log_viewer.php" target="_blank">服务日志</a></li>
        </ul>
      </nav>
    </div>
  </header>
<h1>微信开发者服务器数据库表格数据展示</h1>

<!-- 表格选择和查询按钮 -->
<div>
    <label for="table_name">选择表格：</label>
    <select id="table_name" name="table_name" onchange="loadTableData()">
        <option value="">请选择表格</option>
        <?php if (!empty($tableNames)): ?>
            <?php foreach ($tableNames as $name): ?>
                <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tableName == $name ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">没有找到表格</option>
        <?php endif; ?>
    </select>
    <button onclick="loadTableData()">查询</button>
</div>

<?php if ($tableName): ?>
    <h2><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?> 表格数据</h2>
<?php endif; ?>
<!-- 显示数据表格 -->
<table>
    <thead>
        <tr>
            <th><input type="checkbox" id="select_all" onclick="toggleAll()"></th>
            <?php if (!empty($data)): ?>
                <?php foreach (array_keys($data[0]) as $column): ?>
                    <th><?php echo htmlspecialchars($column, ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
                <th>操作</th>
            <?php endif; ?>
        </tr>
    </thead>
<tbody>
    <?php if (!empty($data)): ?>
        <?php foreach ($data as $index => $row): ?>
        <tr id="row_<?php echo $row['id']; ?>">
            <td><input type="checkbox" class="row_select" data-id="<?php echo $row['id']; ?>" onclick="toggleButtons()"></td>
            <?php foreach ($row as $key => $value): ?>
                <td class="data-<?php echo $key; ?>"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></td>
            <?php endforeach; ?>
            <td>
                <button class="btn btn-edit" onclick="editRow(<?php echo $row['id']; ?>)">修改</button>
                <button class="btn btn-delete" onclick="deleteRow(<?php echo $row['id']; ?>)">删除</button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="100%">没有数据</td>
        </tr>
    <?php endif; ?>
</tbody>



</table>

<!-- 显示行数输入框和分页 -->
<div>
    <label for="rows_per_page">显示行数：</label>
    <input type="number" id="rows_per_page" name="rows_per_page" value="<?php echo $rowsPerPage; ?>" min="1" onchange="changeRowsPerPage()">
    <div class="pagination">
        <span>第 <?php echo $page; ?> 页</span>
        <?php if ($page > 1): ?>
        <a href="?table_name=<?php echo $tableName; ?>&page=<?php echo $page - 1; ?>&rows_per_page=<?php echo $rowsPerPage; ?>">上一页</a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?table_name=<?php echo $tableName; ?>&page=<?php echo $page + 1; ?>&rows_per_page=<?php echo $rowsPerPage; ?>">下一页</a>
        <?php endif; ?>
    </div>
</div>

<!-- 编辑和提交修改 -->
<div id="edit-popup" class="popup" style="display:none;">
    <h3>修改行数据</h3>
    <form id="edit-form">
        <div id="edit-form-content"></div>
        <button type="button" onclick="submitEdit()">确认修改</button>
    </form>
    <div id="error-message" class="popup-error"></div>
</div>
<!-- 添加删除所有和删除多行按钮 -->
<div>
    <button id="delete-all-btn" class="btn btn-delete-all" onclick="deleteAll()" style="display: none;">删除所有</button>
    <button id="delete-selected-btn" class="btn btn-delete-selected" onclick="deleteSelected()" style="display: none;">删除选中行</button>
</div>
<script>
// 控制全选/反选
// 控制全选/反选
function toggleAll() {
    const isChecked = document.getElementById("select_all").checked;
    document.querySelectorAll(".row_select").forEach(checkbox => checkbox.checked = isChecked);
    toggleButtons(); // 调用 toggleButtons 来显示或隐藏按钮
}


// 控制修改和删除按钮显示
// 控制修改和删除按钮显示
function toggleButtons() {
    const selectedRows = document.querySelectorAll(".row_select:checked");
    const deleteAllBtn = document.getElementById("delete-all-btn");
    const deleteSelectedBtn = document.getElementById("delete-selected-btn");

    // 如果有选中行，显示删除按钮
    if (selectedRows.length > 0) {
        deleteAllBtn.style.display = "inline-block";
        deleteSelectedBtn.style.display = "inline-block";
    } else {
        deleteAllBtn.style.display = "none";
        deleteSelectedBtn.style.display = "none";
    }

    // 显示与选中行相关的按钮（删除、修改）
    document.querySelectorAll(".btn-edit, .btn-delete").forEach(button => {
        button.style.display = "none";  // 默认隐藏所有按钮
    });

    selectedRows.forEach(row => {
        const rowId = row.getAttribute("data-id");
        const editBtn = document.querySelector(`#row_${rowId} .btn-edit`);
        const deleteBtn = document.querySelector(`#row_${rowId} .btn-delete`);
        
        // 只显示选中行的按钮
        if (editBtn) editBtn.style.display = "inline-block";
        if (deleteBtn) deleteBtn.style.display = "inline-block";
    });
}



// 编辑某一行数据
function editRow(id) {
    const row = document.getElementById("row_" + id);
    const cells = row.querySelectorAll("td");
    let formContent = '';
    let columnNames = [];

    // 获取列名
    cells.forEach((cell, index) => {
        if (index === 0 || index === cells.length - 1) return; // 跳过选择框和操作列
        const columnName = cell.className.replace('data-', '');
        columnNames.push(columnName);  // 保存列名
        // 排除 ID 字段
        if (columnName === 'id') return;
        formContent += `<label for="${columnName}">${columnName}</label><input type="text" name="${columnName}" class="input-edit" value="${cell.textContent}"><br>`;
    });

    // 将生成的表单内容插入
    document.getElementById("edit-form-content").innerHTML = formContent;
    document.getElementById("edit-popup").style.display = 'block';

    // 调用后台查询字段类型
    getColumnTypes(columnNames).then(columnTypes => {
        // 在前端对每个输入框进行验证
        document.querySelectorAll(".input-edit").forEach((input, index) => {
            const columnName = columnNames[index];
            const columnType = columnTypes[columnName];

            if (columnType === 'DATE') {
                input.setAttribute('pattern', '\\d{4}-\\d{2}-\\d{2}');
                input.setAttribute('placeholder', 'YYYY-MM-DD');
            } else if (columnType === 'DATETIME') {
                input.setAttribute('pattern', '\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}');
                input.setAttribute('placeholder', 'YYYY-MM-DD HH:MM:SS');
            } else if (columnType === 'INT') {
                input.setAttribute('type', 'number');
                input.setAttribute('placeholder', '请输入整数');
            }
        });
    });

    // 滚动到修改表单部分
    document.getElementById("edit-popup").scrollIntoView({ behavior: 'smooth' });

    // 清空错误信息
    document.getElementById("error-message").innerHTML = '';
}

// 获取字段的数据类型
function getColumnTypes(columnNames) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
       // xhr.open("POST",  "src/db_get_column_types.php.php", true);
         xhr.open("POST", "DatabaseAction.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        const tableName = document.getElementById("table_name").value;
        //let params = `table_name=${tableName}&columns=${JSON.stringify(columnNames)}`;
          let params = `action=get_column_types&table_name=${tableName}&columns=${JSON.stringify(columnNames)}`;
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    resolve(response.columnTypes);
                } else {
                    reject('获取字段类型失败');
                }
            } else {
                reject('请求失败');
            }
        };
        xhr.send(params);
    });
}


// 提交修改
function submitEdit() {
    const form = document.getElementById("edit-form");
    const data = new FormData(form);
    const errorMessage = document.getElementById("error-message");
    errorMessage.innerHTML = '';

    let hasError = false;
    data.forEach((value, key) => {
        if (!value.trim()) {
            errorMessage.innerHTML = `字段 "${key}" 不能为空。`;
            hasError = true;
        }
    });

    if (hasError) return;
    
    // 获取 table_name 和 id 进行数据库更新
    const tableName = document.getElementById("table_name").value;
    const rowId = document.querySelector('.row_select:checked').getAttribute('data-id'); // 获取当前选中的行 id
    
    // 发起 AJAX 请求更新数据
    const xhr = new XMLHttpRequest();
   // xhr.open("POST", "src/db_update_data.php", true);  // 调用处理更新的 PHP 文件
    xhr.open("POST", "DatabaseAction.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  //let params = `table_name=${tableName}&id=${rowId}`;
  let params = `action=update_data&table_name=${tableName}&id=${rowId}`;
    
    data.forEach((value, key) => {
         if (key !== "id" && key !== "action"){  // 避免将 id 字段传递过去
            params += `&${key}=${encodeURIComponent(value)}`;
        }
    });

    xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert("修改成功！");
                        location.reload();
                    } else {
                        let errorText = '';
                        if (response.errorMessages && response.errorMessages.length > 0) {
                            errorText = response.errorMessages.join('<br>');
                        } else {
                            errorText = '发生未知错误，请重试！';
                        }
                        errorMessage.innerHTML = errorText;
                    }
                } catch (e) {
                    console.error("解析 JSON 错误:", e);
                    console.log("返回的数据：", xhr.responseText);  // 打印返回的数据，帮助调试
                    errorMessage.innerHTML = "返回的数据格式错误，无法解析！";
                }
            } else {
                alert("修改失败，请重试！");
            }
        };


    xhr.send(params);
}



// 校验日期格式 YYYY-MM-DD
function isValidDate(date) {
    return /^\d{4}-\d{2}-\d{2}$/.test(date);
}

// 校验时间戳格式 YYYY-MM-DD HH:MM:SS
function isValidTimestamp(timestamp) {
    return /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(timestamp);
}

// 校验整数
function isValidInteger(value) {
    return /^[+-]?\d+$/.test(value);
}

// 删除选中的行
// 删除选中的行
// 删除选中的行
function deleteRow(id) {
    if (confirm("确定要删除该行吗？")) {
        const tableName = document.getElementById("table_name").value;
        
        // 发起 AJAX 请求删除数据
        const xhr = new XMLHttpRequest();
        //xhr.open("POST", "src/db_delete_data.php", true);  // 调用处理删除的 PHP 文件
           xhr.open("POST", "DatabaseAction.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

       // let params = `id=${id}&table_name=${tableName}`; // 传递 id 和 table_name
       let params = `action=delete_data&id=${id}&table_name=${tableName}`;
        xhr.onload = function() {
            if (xhr.status === 200) {
                // 删除成功后刷新页面
                alert("删除成功！");
                location.reload();
            } else {
                alert("删除失败，请重试！");
            }
        };
        xhr.send(params);
    }
}


// 加载表格数据
function loadTableData() {
    const tableName = document.getElementById("table_name").value;
    if (!tableName) {
        alert("请选择一个表格！");
        return;
    }
    location.href = `?table_name=${tableName}&page=1&rows_per_page=10`;
}

// 切换每页显示的行数
function changeRowsPerPage() {
    const rowsPerPage = document.getElementById("rows_per_page").value;
    const tableName = document.getElementById("table_name").value;
    location.href = `?table_name=${tableName}&page=1&rows_per_page=${rowsPerPage}`;
}

// 删除所有数据
function deleteAll() {
    const tableName = document.getElementById("table_name").value;

    if (confirm("确定要删除所有数据吗？")) {
        const xhr = new XMLHttpRequest();
        //xhr.open("POST", "src/db_delete_data.php", true);
        xhr.open("POST", "DatabaseAction.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      //  const params = `table_name=${tableName}&delete_all=true`;
         const params = `action=delete_all_data&table_name=${tableName}&delete_all=true`;
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert("所有数据删除成功！");
                location.reload(); // 刷新页面
            } else {
                alert("删除失败，请重试！");
            }
        };
        xhr.send(params);
    }
}
// 删除选中行
function deleteSelected() {
    const selectedIds = [];
    document.querySelectorAll(".row_select:checked").forEach(checkbox => {
        selectedIds.push(checkbox.getAttribute("data-id"));
    });

    if (selectedIds.length === 0) {
        alert("没有选择任何行");
        return;
    }

    if (confirm("确定要删除选中的行吗？")) {
        const tableName = document.getElementById("table_name").value;

        // 发起 AJAX 请求删除选中的数据
        const xhr = new XMLHttpRequest();
        //xhr.open("POST", "src/db_delete_data.php", true);
        xhr.open("POST", "DatabaseAction.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

       // const params = `table_name=${tableName}&ids=${JSON.stringify(selectedIds)}`;
        const params = `action=delete_selected_data&table_name=${tableName}&ids=${JSON.stringify(selectedIds)}`;
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert("删除成功！");
                location.reload(); // 刷新页面
            } else {
                alert("删除失败，请重试！");
            }
        };
        xhr.send(params);
    }
}


</script>

</body>
</html>