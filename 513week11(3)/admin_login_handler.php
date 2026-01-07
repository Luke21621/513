<?php
session_start();

// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';

header('Content-Type: application/json');

$errors = [];
$login_error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // 从POST获取重定向URL，如果没有则使用默认值
    $redirect_url = $_POST['redirect'] ?? 'admin.php';
    
    // 验证必填字段
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    // 如果没有错误，则验证管理员凭据
    if (empty($errors)) {
        // 默认管理员凭据
        $default_username = 'admin';
        $default_password = 'admin123';
        
        // 验证凭据
        if ($username === $default_username && $password === $default_password) {
            // 登录成功，设置会话
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            
            // 登录成功跳转到指定页面
            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => $redirect_url
            ]);
            exit;
        } else {
            // 也可以从数据库验证（如果需要）
            $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
            if (!$mysqli->connect_errno) {
                $mysqli->set_charset('utf8mb4');
                
                // 创建管理员表（如果不存在）
                $createTableQuery = "
                    CREATE TABLE IF NOT EXISTS `admin_users` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $mysqli->query($createTableQuery);
                
                // 查询管理员
                $stmt = $mysqli->prepare("SELECT * FROM `admin_users` WHERE `username` = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            // 验证密码（使用password_verify如果密码是哈希的）
                            if (password_verify($password, $row['password']) || $password === $row['password']) {
                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['admin_username'] = $username;
                                // 登录成功跳转到指定页面
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Login successful!',
                                    'redirect' => $redirect_url
                                ]);
                                exit;
                            }
                        }
                    }
                    $stmt->close();
                }
            }
            $mysqli->close();
            
            $login_error = 'Invalid username or password.';
        }
    }
    
    // 返回错误
    $error_messages = !empty($errors) ? $errors : [];
    if (!empty($login_error)) {
        $error_messages[] = $login_error;
    }
    
    echo json_encode([
        'success' => false,
        'errors' => $error_messages
    ]);
    exit;
}

// 如果不是POST请求，返回错误
echo json_encode([
    'success' => false,
    'errors' => ['Invalid request method.']
]);
?>

