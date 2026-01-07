<?php
session_start();

// 如果已经登录，重定向到指定页面或管理页面
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $redirect = $_GET['redirect'] ?? 'admin.php';
    header('Location: ' . $redirect);
    exit;
}

// 获取重定向参数
$redirect_url = $_GET['redirect'] ?? 'admin.php';

// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';

$errors = [];
$login_error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // 从POST获取重定向URL，如果没有则使用GET参数或默认值
    $redirect_url = $_POST['redirect'] ?? $_GET['redirect'] ?? 'admin.php';
    
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
        $default_username = 'administrator';
        $default_password = 'GameHub2024@Admin';
        
        // 验证凭据
        if ($username === $default_username && $password === $default_password) {
            // 登录成功，清除顾客会话变量并设置管理员会话
            unset($_SESSION['customer_logged_in']);
            unset($_SESSION['customer_email']);
            unset($_SESSION['customer_phone']);
            unset($_SESSION['customer_id']);
            unset($_SESSION['customer_first_name']);
            unset($_SESSION['customer_last_name']);
            unset($_SESSION['customer_name']);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;

            // 重定向到指定页面或管理后台
            header('Location: ' . $redirect_url);
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
                                // 登录成功，清除顾客会话变量并设置管理员会话
                                unset($_SESSION['customer_logged_in']);
                                unset($_SESSION['customer_email']);
                                unset($_SESSION['customer_phone']);
                                unset($_SESSION['customer_id']);
                                unset($_SESSION['customer_first_name']);
                                unset($_SESSION['customer_last_name']);
                                unset($_SESSION['customer_name']);

                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['admin_username'] = $username;
                                // 重定向到指定页面或管理后台
                                header('Location: ' . $redirect_url);
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        /* Page layout - white background with centered card */
        body {
            margin: 0;
            background: #ffffff;
            font-family: 'Montserrat', Arial, sans-serif;
            color: #1d1d1f;
        }

        main {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6rem 1rem;
            min-height: calc(100vh - 220px);
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            padding: 0;
            z-index: 1;
        }

        /* Card */
        .login-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.2rem 2rem;
            box-shadow: 0 35px 60px rgba(11, 27, 57, 0.08);
            border: 1px solid #f0f2f5;
            position: relative;
            overflow: visible;
        }

        /* top gradient accent for admin */
        .login-card::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            height: 6px;
            background: linear-gradient(90deg, #ff6b6b, #ffa500);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .login-header { text-align: center; margin-bottom: 1rem; }

        .login-icon { font-size: 3rem; margin-bottom: 0.6rem; display:inline-block; filter: drop-shadow(0 6px 18px rgba(255,107,107,0.06)); }
        .login-icon svg { width: 3rem; height: 3rem; display:block; margin:0 auto 0.6rem; }

        .login-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 6px;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            font-size: 0.78rem;
            color: #9b9b9b;
            margin: 0 0 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group { margin-bottom: 1rem; }
        .form-label { display:block; margin-bottom:6px; color:#4a4a55; font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05rem; }
        .form-input {
            width:100%;
            padding:10px 12px;
            border-radius:8px;
            border:1px solid #e9edf2;
            background:#ffffff;
            font-size:0.95rem;
            font-weight:600; /* increased weight for better readability */
            color: #222222;
            outline:none;
            box-sizing:border-box;
            font-family: inherit;
        }
        .form-input::placeholder { color: rgba(34,34,34,0.25); font-weight:400; }
        .form-input:focus { box-shadow: 0 8px 24px rgba(255,107,107,0.06); border-color:#ff6b6b; transform: translateY(-1px); }

        .btn-login {
            width:100%;
            padding:11px;
            border-radius:8px;
            border:none;
            background:linear-gradient(90deg,#ff6b6b,#ffa500);
            color:#fff;
            font-weight:700;
            cursor:pointer;
            box-shadow: 0 8px 24px rgba(255,107,107,0.12);
            margin-top:8px;
            text-transform:uppercase;
            letter-spacing:0.6px;
        }
        .btn-login:hover { transform: translateY(-2px); }

        .btn-cancel {
            width:100%;
            padding:10px;
            border-radius:8px;
            border:1px solid #e6e9ee;
            background:#fbfbfc;
            color:#6c757d;
            margin-top:10px;
            cursor:pointer;
        }

        .default-credentials { text-align:center; font-size:0.85rem; color:#666; margin-top:1.2rem; padding-top:1rem; border-top:1px solid #f0f2f5; }
        .default-credentials strong { color:#ff6b6b; }

        .security-badge { position:absolute; top:14px; right:14px; background:#fff4f4; color:#ff6b6b; padding:6px 10px; border-radius:16px; border:1px solid rgba(255,107,107,0.12); font-weight:700; font-size:0.72rem; }

        @media (max-width: 480px) {
            main { padding: 3.5rem 1rem; min-height: calc(100vh - 200px); }
            .login-card { padding: 1.6rem; border-radius: 12px; }
            .login-title { font-size: 1.2rem; }
            .login-icon { font-size: 2.6rem; }
        }
    </style>
</head>
<body>
	<?php include __DIR__ . '/header.php'; ?>

	<main>
    <div class="login-container">
        <div class="login-card">
            <div class="security-badge">SECURE ACCESS</div>
            <div class="login-header">
                <div class="login-icon" aria-hidden="true">
                    <!-- Inline SVG lock icon (no external image) -->
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="lock icon">
                        <rect x="3" y="10" width="18" height="11" rx="2" fill="#ff6b6b"/>
                        <path d="M7 10V8a5 5 0 1 1 10 0v2" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <circle cx="12" cy="15" r="1.4" fill="#ffffff"/>
                    </svg>
                </div>
                <h1 class="login-title">ADMIN ACCESS</h1>
                <p class="login-subtitle">Management System</p>
            </div>
            
            <?php if (!empty($errors) || !empty($login_error)): ?>
                <div class="error-message">
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($login_error)): ?>
                        <div><?php echo htmlspecialchars($login_error, ENT_QUOTES); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_url, ENT_QUOTES); ?>">
                <div class="form-group">
                    <label class="form-label" for="username">Username *</label>
                    <input 
                        class="form-input" 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter username" 
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" name="admin_login" class="btn-login">Login</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
            </form>
            
            <div class="default-credentials">
                <strong>$ Default Credentials:</strong><br>
                Username: administrator<br>
                Password: GameHub2024@Admin<span class="terminal-cursor"></span>
            </div>
        </div>
    </div>

	</main>

	<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>

