<?php
// 强制关闭 php://input，彻底避免这个坑（许多生产环境都这么做）
@fclose(fopen('php://input', 'r'));
ob_start(); // 开启输出缓冲
session_start();  // 启动 session，存储用户登录状态
$config = require 'config/config.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取用户输入的用户名和密码
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 验证用户名和密码
    if ($username === $config['USERNAME'] && $password === $config['PASSWORD']) {
        // 登录成功，设置 session 标记
        $_SESSION['loggedin'] = true;
        ob_end_clean();        // ← 清空缓冲区（防止前面有 BOM/空格）
        header('Location: Databasedashboard.php'); // 重定向到成功后的页面（后续会创建）
        exit();
    } else {
        // 登录失败
        $error = 'Invalid username or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
    /* Reset some default browser styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f9;
      color: #333;
      line-height: 1.6;
    }

    /* Center the login form */
    .login-container {
      width: 100%;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #8A2BE2, #4CAF50);
    }

    .login-box {
      background-color: #fff;
      padding: 40px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .login-box:hover {
      transform: translateY(-5px);
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 20px;
      color: #333;
    }

    .input-group {
      margin-bottom: 20px;
      position: relative;
    }

    .input-group input {
      width: 100%;
      padding: 12px 15px;
      font-size: 1rem;
      border: 2px solid #ccc;
      border-radius: 5px;
      outline: none;
      transition: border-color 0.3s ease;
    }

    .input-group input:focus {
      border-color: #4CAF50;
    }

    .input-group label {
      position: absolute;
      top: -10px;
      left: 15px;
      font-size: 0.9rem;
      color: #666;
      background-color: white;
      padding: 0 5px;
      transition: all 0.3s ease;
    }

    .input-group input:focus + label {
      top: -25px;
      left: 15px;
      color: #4CAF50;
    }

    .submit-btn {
      width: 100%;
      padding: 12px 15px;
      background-color: #4CAF50;
      color: white;
      font-size: 1.2rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
      background-color: #45a049;
    }

    .error-message {
      color: red;
      margin-top: 20px;
      font-size: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .login-box {
        padding: 30px 20px;
      }

      h1 {
        font-size: 1.8rem;
      }

      .submit-btn {
        font-size: 1rem;
      }
    }
</style>

</head>
<body>
    <h1>Login</h1>
    <form method="POST" action="DatabaseLogin.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Login">
    </form>

    <?php if (isset($error)) { echo "<p style='color: red;'>$error</p>"; } ?>
</body>
</html>
