<?php
session_start();
// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';
$TABLE = 'support_feedback';

// 读取后台可编辑的支持信息（admin.php 写入 data/support_info.json）
$support_info = [
    'email' => '2162106274@qq.com',
    'hours' => 'Monday to Friday, 9:00 AM - 5:00 PM'
];
$support_info_file = __DIR__ . '/data/support_info.json';
if (file_exists($support_info_file)) {
    $loaded = json_decode(file_get_contents($support_info_file), true);
    if (is_array($loaded)) {
        $support_info = array_merge($support_info, $loaded);
    }
}

// 初始化变量
$errors = [];
$success = false;
$values = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

// 创建数据库表的函数
function createSupportTable($mysqli) {
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS `support_feedback` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `subject` VARCHAR(200) NOT NULL,
            `message` TEXT NOT NULL,
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_email` (`email`),
            KEY `idx_submitted_at` (`submitted_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    return $mysqli->query($createTableQuery);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取并清理输入数据
    $values['name'] = trim($_POST['name'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['subject'] = trim($_POST['subject'] ?? '');
    $values['message'] = trim($_POST['message'] ?? '');
    
    // 验证必填字段
    if (empty($values['name'])) $errors[] = 'Full name is required.';
    if (empty($values['email']) || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

    if (empty($values['subject'])) $errors[] = 'Subject is required.';
    if (empty($values['message'])) $errors[] = 'Message is required.';
    
    // 如果没有错误，则保存到数据库并显示成功消息（不管邮件是否发送成功）
    if (empty($errors)) {
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_errno) {
            $errors[] = 'Database connection failed.';
        } else {
            $mysqli->set_charset('utf8mb4');
            
            // 创建表（如果不存在）
            if (!createSupportTable($mysqli)) {
                $errors[] = 'Failed to create support table.';
            } else {
                // 插入数据
                $stmt = $mysqli->prepare("INSERT INTO `support_feedback` (name, email, subject, message) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssss", 
                        $values['name'],
                        $values['email'],
                        $values['subject'],
                        $values['message']
                    );
                    
                    if ($stmt->execute()) {
                        // 不管邮件是否发送成功，都设置为成功
                        $success = true;
                        
                        // 尝试发送邮件给供应商（但不依赖于结果）
                        $to = "2162106274@qq.com"; // 供应商邮箱
                        $from = $values['email'];
                        $subject = "Support Request: " . $values['subject'];
                        $body = "Name: " . $values['name'] . "\n";
                        $body .= "Email: " . $values['email'] . "\n";
                        $body .= "Subject: " . $values['subject'] . "\n";
                        $body .= "Message: " . $values['message'] . "\n";
                        
                        // 设置邮件头
                        $headers = "From: " . $from . "\r\n";
                        $headers .= "Reply-To: " . $from . "\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        
                        // 发送邮件（忽略结果）
                        mail($to, $subject, $body, $headers);
                        
                        // 清空表单值
                        $values = array_map(function() { return ''; }, $values);
                    } else {
                        $errors[] = 'Failed to save feedback.';
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customer Support | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        /* Support page styles with light background */
        .support-section {
            padding: 3.5rem 0 6rem;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .support-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid #e3e6eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            color: #1d1d1f;
        }

        .support-card h2 {
            margin: 0 0 1.25rem 0;
            color: #1d1d1f;
            font-size: 1.6rem;
            text-align: center;
        }
        
        .form-row {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.45rem;
            color: #1d1d1f;
            font-weight: 500;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            border: 1px solid #e3e6eb;
            background: #ffffff;
            color: #1d1d1f;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #0aa08c;
            box-shadow: 0 0 0 3px rgba(10, 160, 140, 0.1);
        }
        
        .form-textarea {
            min-height: 140px;
            resize: vertical;
        }
        
        .support-info {
            background: #f0f8f7;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .support-info h3 {
            margin-top: 0;
            color: #0aa08c;
        }
        
        .support-info p {
            margin: 0.5rem 0;
        }
        
        .form-errors {
            background: #fff0f0;
            border: 1px solid #ffc7c7;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            color: #d32f2f;
        }
        
        .form-errors ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        
        .form-errors li {
            margin-bottom: 0.25rem;
        }
        
        .form-success {
            background: #f0f8f7;
            border: 1px solid #c0e8e0;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            color: #0aa08c;
        }
        
        @media (max-width: 640px) {
            .support-card {
                padding: 1.5rem;
                margin: 0 1rem;
            }
            
            .support-section {
                padding: 2rem 0 4rem;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main>
        <section class="support-section">
            <div class="container">
                <div class="support-card">
                    <h2>Customer Support</h2>
                    <p style="text-align: center; color: #6b6b6b; margin-bottom: 2rem;">
                        Have questions or need assistance? Send us a message and we'll get back to you as soon as possible.
                    </p>
                    
                    <div class="support-info">
                        <h3>Contact Information</h3>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($support_info['email'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($support_info['email'], ENT_QUOTES); ?></a></p>
                        <p><strong>Business Hours:</strong> <?php echo htmlspecialchars($support_info['hours'], ENT_QUOTES); ?></p>
                        <p>We aim to respond to all inquiries within 24 hours during business days.</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="form-errors">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="form-success">
                            Your message has been sent successfully! We have received your email and will respond shortly.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="support.php">
                        <div class="form-row">
                            <label class="form-label" for="name">Full Name *</label>
                            <input class="form-input" type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($values['name'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="email">Email Address *</label>
                            <input class="form-input" type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="subject">Subject *</label>
                            <input class="form-input" type="text" id="subject" name="subject" placeholder="Enter subject" value="<?php echo htmlspecialchars($values['subject'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="message">Message *</label>
                            <textarea class="form-textarea" id="message" name="message" placeholder="Enter your message" required><?php echo htmlspecialchars($values['message'], ENT_QUOTES); ?></textarea>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <button class="btn btn--primary-gradient" type="submit">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>