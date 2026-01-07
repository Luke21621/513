<?php
// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';
$DISCUSSION_TABLE = 'discussion_posts';

// 启动会话
session_start();

// 初始化变量
$errors = [];
$success = false;
$user = null;
$post_content = '';

// 检查用户是否已登录（使用顾客登录系统）
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    $user = [
        'email' => $_SESSION['customer_email'] ?? '',
        'first_name' => $_SESSION['customer_first_name'] ?? '',
        'last_name' => $_SESSION['customer_last_name'] ?? ''
    ];
}

// 创建讨论帖子表的函数
function createDiscussionTable($mysqli) {
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS `discussion_posts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_email` VARCHAR(100) NOT NULL,
            `user_name` VARCHAR(100) NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_email` (`user_email`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    return $mysqli->query($createTableQuery);
}

// 登录处理已移至customer_login.php，这里不再处理登录表单

// 处理登出（使用顾客登出系统）
if (isset($_GET['logout'])) {
    // 重定向到顾客登出页面
    header('Location: customer_logout.php?redirect=discussion.php');
    exit;
}

// 处理发布帖子
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post']) && $user) {
    $post_content = trim($_POST['content'] ?? '');
    
    // 验证必填字段
    if (empty($post_content)) {
        $errors[] = 'Post content is required.';
    }
    
    // 如果没有错误，则保存帖子到数据库
    if (empty($errors)) {
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_errno) {
            $errors[] = 'Database connection failed.';
        } else {
            $mysqli->set_charset('utf8mb4');
            
            // 创建表（如果不存在）
            if (!createDiscussionTable($mysqli)) {
                $errors[] = 'Failed to create discussion table.';
            } else {
                // 插入帖子数据
                $stmt = $mysqli->prepare("INSERT INTO `discussion_posts` (user_email, user_name, content) VALUES (?, ?, ?)");
                if ($stmt) {
                    $user_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    if (empty($user_name)) {
                        $user_name = $user['email'];
                    }
                    
                    $stmt->bind_param("sss", 
                        $user['email'],
                        $user_name,
                        $post_content
                    );
                    
                    if ($stmt->execute()) {
                        $success = 'Post published successfully!';
                        $post_content = ''; // 清空表单
                    } else {
                        $errors[] = 'Failed to publish post.';
                    }
                    $stmt->close();
                } else {
                    $errors[] = 'Failed to prepare statement.';
                }
            }
            $mysqli->close();
        }
    }
}

// 获取所有讨论帖子
$posts = [];
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$mysqli->connect_errno) {
    $mysqli->set_charset('utf8mb4');
    
    // 创建表（如果不存在）
    createDiscussionTable($mysqli);
    
    // 查询所有帖子，按时间倒序排列
    $sql = "SELECT * FROM `discussion_posts` ORDER BY `created_at` DESC LIMIT 50";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    }
    $mysqli->close();
}

// 如果帖子少于20个，添加示例帖子
if (count($posts) < 20) {
    $sample_posts = [
        ['user_email' => 'john.doe@example.com', 'user_name' => 'John Doe', 'content' => 'This is a great platform for gamers! I love the variety of games available.', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        ['user_email' => 'jane.smith@example.com', 'user_name' => 'Jane Smith', 'content' => 'The community here is very helpful. I got great advice on my gaming setup.', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['user_email' => 'mike.johnson@example.com', 'user_name' => 'Mike Johnson', 'content' => 'Just purchased a new game and it arrived quickly. Excellent service!', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['user_email' => 'sarah.williams@example.com', 'user_name' => 'Sarah Williams', 'content' => 'The forums are well organized and easy to navigate. Great job!', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
        ['user_email' => 'david.brown@example.com', 'user_name' => 'David Brown', 'content' => 'I appreciate the regular updates and new features being added.', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['user_email' => 'lisa.taylor@example.com', 'user_name' => 'Lisa Taylor', 'content' => 'Customer support was very responsive when I had a question about my order.', 'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))],
        ['user_email' => 'robert.miller@example.com', 'user_name' => 'Robert Miller', 'content' => 'The gaming recommendations are spot on. Found some amazing games I never would have discovered otherwise.', 'created_at' => date('Y-m-d H:i:s', strtotime('-7 days'))],
        ['user_email' => 'emily.davis@example.com', 'user_name' => 'Emily Davis', 'content' => 'Love the community events and tournaments. Keeps things exciting!', 'created_at' => date('Y-m-d H:i:s', strtotime('-8 days'))],
        ['user_email' => 'james.wilson@example.com', 'user_name' => 'James Wilson', 'content' => 'The mobile app makes it so easy to browse and purchase games on the go.', 'created_at' => date('Y-m-d H:i:s', strtotime('-9 days'))],
        ['user_email' => 'jennifer.moore@example.com', 'user_name' => 'Jennifer Moore', 'content' => 'Great selection of indie games. Supporting smaller developers is important.', 'created_at' => date('Y-m-d H:i:s', strtotime('-10 days'))],
        ['user_email' => 'michael.taylor@example.com', 'user_name' => 'Michael Taylor', 'content' => 'The reviews and ratings help me make informed decisions about purchases.', 'created_at' => date('Y-m-d H:i:s', strtotime('-11 days'))],
        ['user_email' => 'amanda.anderson@example.com', 'user_name' => 'Amanda Anderson', 'content' => 'The wishlist feature is fantastic for keeping track of games I want to buy.', 'created_at' => date('Y-m-d H:i:s', strtotime('-12 days'))],
        ['user_email' => 'chris.thomas@example.com', 'user_name' => 'Chris Thomas', 'content' => 'Fast shipping and careful packaging. My games always arrive in perfect condition.', 'created_at' => date('Y-m-d H:i:s', strtotime('-13 days'))],
        ['user_email' => 'ashley.jackson@example.com', 'user_name' => 'Ashley Jackson', 'content' => 'The price matching policy saved me a lot of money on recent purchases.', 'created_at' => date('Y-m-d H:i:s', strtotime('-14 days'))],
        ['user_email' => 'matthew.white@example.com', 'user_name' => 'Matthew White', 'content' => 'I enjoy the developer interviews and behind-the-scenes content.', 'created_at' => date('Y-m-d H:i:s', strtotime('-15 days'))],
        ['user_email' => 'jessica.harris@example.com', 'user_name' => 'Jessica Harris', 'content' => 'The rewards program gives great benefits for frequent shoppers.', 'created_at' => date('Y-m-d H:i:s', strtotime('-16 days'))],
        ['user_email' => 'daniel.martin@example.com', 'user_name' => 'Daniel Martin', 'content' => 'Easy returns policy gives me confidence when trying new games.', 'created_at' => date('Y-m-d H:i:s', strtotime('-17 days'))],
        ['user_email' => 'samantha.thompson@example.com', 'user_name' => 'Samantha Thompson', 'content' => 'The gift card option made it easy to give games as presents.', 'created_at' => date('Y-m-d H:i:s', strtotime('-18 days'))],
        ['user_email' => 'andrew.garcia@example.com', 'user_name' => 'Andrew Garcia', 'content' => 'Regular sales and promotions are a great way to expand my library.', 'created_at' => date('Y-m-d H:i:s', strtotime('-19 days'))],
        ['user_email' => 'olivia.martinez@example.com', 'user_name' => 'Olivia Martinez', 'content' => 'The pre-order system ensures I never miss the release of anticipated games.', 'created_at' => date('Y-m-d H:i:s', strtotime('-20 days'))]
    ];
    
    // 添加足够的示例帖子以达到至少20个
    $needed = 20 - count($posts);
    for ($i = 0; $i < $needed && $i < count($sample_posts); $i++) {
        $posts[] = $sample_posts[$i];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Discussion Forum | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        /* Discussion forum styles */
        .discussion-section {
            padding: 3.5rem 0 6rem;
            background: #f8f9fa;
            min-height: 70vh;
            transition: background-color 0.3s ease;
        }

        /* Dark mode styles */
        body.dark-mode .discussion-section {
            background: #1a1a1a;
        }

        .discussion-card {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid #e3e6eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            color: #1d1d1f;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        body.dark-mode .discussion-card {
            background: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }

        .discussion-card h2 {
            margin: 0 0 1.25rem 0;
            color: #1d1d1f;
            font-size: 1.6rem;
            text-align: center;
            transition: color 0.3s ease;
        }

        body.dark-mode .discussion-card h2 {
            color: #e0e0e0;
        }
        
        .form-row {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.45rem;
            color: #1d1d1f;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        body.dark-mode .form-label {
            color: #e0e0e0;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            border: 1px solid #e3e6eb;
            background: #ffffff;
            color: #1d1d1f;
            font-family: inherit;
            outline: none;
            transition: box-shadow 0.15s ease, border-color 0.15s ease, background-color 0.3s ease, color 0.3s ease;
        }

        body.dark-mode .form-input,
        body.dark-mode .form-textarea {
            background: #3a3a3a;
            border-color: #555;
            color: #e0e0e0;
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #6b6b6b;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            box-shadow: 0 4px 12px rgba(93, 140, 44, 0.15);
            border-color: #5d8c2c;
        }
        
        .form-errors {
            background: #fff5f5;
            color: #e53e3e;
            padding: 0.6rem 0.9rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fed7d7;
        }
        
        .form-errors ul {
            margin: 0;
            padding-left: 1.1rem;
        }
        
        .form-success {
            background: #f0fff4;
            color: #38a169;
            padding: 0.6rem 0.9rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #c6f6d5;
            text-align: center;
        }
        
        .btn--primary-gradient {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.02rem;
            border: none;
            cursor: pointer;
            background: #5d8c2c;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(93, 140, 44, 0.2);
            width: 100%;
            font-size: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .btn--primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(93, 140, 44, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .post-list {
            margin-top: 2rem;
        }
        
        .post-item {
            border-bottom: 1px solid #e3e6eb;
            padding: 1.5rem 0;
            transition: border-color 0.3s ease;
        }

        body.dark-mode .post-item {
            border-color: #404040;
        }
        
        .post-item:last-child {
            border-bottom: none;
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .post-author {
            font-weight: 600;
            color: #1d1d1f;
            transition: color 0.3s ease;
        }

        body.dark-mode .post-author {
            color: #e0e0e0;
        }
        
        .post-date {
            color: #6b6b6b;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        body.dark-mode .post-date {
            color: #999;
        }
        
        .post-content {
            color: #333;
            line-height: 1.6;
            transition: color 0.3s ease;
        }

        body.dark-mode .post-content {
            color: #d0d0d0;
        }
        
        .login-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0f8f4;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #d1e7dd;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        body.dark-mode .user-info {
            background: #2a3a2f;
            border-color: #3a4a3f;
        }
        
        .welcome-message {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        body.dark-mode .welcome-message {
            color: #e0e0e0;
        }

        /* Theme toggle button */
        .theme-toggle-btn {
            background: #5d8c2c;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-left: 1rem;
        }

        .theme-toggle-btn:hover {
            background: #4a6e22;
            transform: translateY(-1px);
        }

        body.dark-mode .theme-toggle-btn {
            background: #6b9c3c;
        }

        body.dark-mode .theme-toggle-btn:hover {
            background: #5d8c2c;
        }

        .theme-toggle-icon {
            font-size: 1.1rem;
        }
        
        @media (max-width: 640px) {
            .discussion-card {
                padding: 1.5rem;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .user-info > div:last-child {
                flex-direction: column;
                width: 100%;
            }

            .theme-toggle-btn {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }
        }

        /* Dark mode for header and footer */
        body.dark-mode header {
            background: #1a1a1a;
            border-bottom-color: #404040;
        }

        body.dark-mode footer {
            background: #1a1a1a;
            border-top-color: #404040;
        }

        body.dark-mode .nav-links a {
            color: #e0e0e0;
        }

        body.dark-mode footer .footer-content {
            color: #e0e0e0;
        }
        /* Ensure footer links are visible in dark mode */
        body.dark-mode footer .footer-links a {
            color: #e0e0e0;
        }
        body.dark-mode footer .footer-links a:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main>
        <section class="discussion-section">
            <div class="container">
                <div class="discussion-card">
                    <h2>Community Discussion Forum</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="form-errors">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!empty($success)): ?>
                        <div class="form-success">
                            <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$user): ?>
                        <!-- 登录提示 -->
                        <div class="login-form">
                            <p style="text-align: center; color: #6b6b6b; margin-bottom: 2rem;">
                                Please log in to participate in discussions. Use your customer account details.
                            </p>
                            
                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="customer_login.php?redirect=discussion.php" class="btn btn--primary-gradient" style="display: inline-block; text-decoration: none;">Log In</a>
                            </div>
                            
                            <p style="text-align: center; margin-top: 1.5rem; color: #6b6b6b;">
                                Don't have an account? <a href="http://luke3.great-site.net/wordpress/register/">Register here</a>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- 用户已登录，显示发帖表单和帖子列表 -->
                        <div class="user-info">
                            <div class="welcome-message">
                                Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?: htmlspecialchars($user['email'], ENT_QUOTES); ?>!
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                                    <span class="theme-toggle-icon" id="themeIcon">🌙</span>
                                    <span id="themeText">Dark Mode</span>
                                </button>
                                <a href="customer_logout.php?redirect=discussion.php" class="btn-secondary">Log Out</a>
                            </div>
                        </div>
                        
                        <!-- 发布新帖子表单 -->
                        <form method="post" action="discussion.php">
                            <div class="form-row">
                                <label class="form-label" for="content">Share your thoughts *</label>
                                <textarea class="form-textarea" id="content" name="content" placeholder="What would you like to discuss?" required><?php echo htmlspecialchars($post_content, ENT_QUOTES); ?></textarea>
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <button class="btn btn--primary-gradient" type="submit" name="post">Publish Post</button>
                            </div>
                        </form>
                        
                        <!-- 帖子列表 -->
                        <div class="post-list">
                            <h3 style="margin-top: 2.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e3e6eb;">
                                Recent Discussions
                            </h3>
                            
                            <?php if (empty($posts)): ?>
                                <p style="text-align: center; color: #6b6b6b; padding: 2rem;">
                                    No posts yet. Be the first to start a discussion!
                                </p>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <div class="post-item">
                                        <div class="post-header">
                                            <div class="post-author">
                                                <?php echo htmlspecialchars($post['user_name'], ENT_QUOTES); ?>
                                            </div>
                                            <div class="post-date">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="post-content">
                                            <?php echo nl2br(htmlspecialchars($post['content'], ENT_QUOTES)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            
            // Toggle dark mode class
            body.classList.toggle('dark-mode');
            
            // Update icon and text based on current theme
            const isDarkMode = body.classList.contains('dark-mode');
            if (isDarkMode) {
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Light Mode';
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.textContent = '🌙';
                themeText.textContent = 'Dark Mode';
                localStorage.setItem('theme', 'light');
            }
        }

        // Load saved theme preference on page load
        window.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            
            if (savedTheme === 'dark') {
                body.classList.add('dark-mode');
                if (themeIcon) themeIcon.textContent = '☀️';
                if (themeText) themeText.textContent = 'Light Mode';
            } else {
                body.classList.remove('dark-mode');
                if (themeIcon) themeIcon.textContent = '🌙';
                if (themeText) themeText.textContent = 'Dark Mode';
            }
        });
    </script>
</body>
</html>