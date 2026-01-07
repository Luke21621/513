<?php
session_start();
// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';
$TABLE = 'job_applications';

// 读取后台可编辑的职位列表（admin.php 写入 data/recruitment_positions.json）
$positions_file = __DIR__ . '/data/recruitment_positions.json';
$dynamic_positions = [];
if (file_exists($positions_file)) {
    $dynamic_positions = json_decode(file_get_contents($positions_file), true) ?: [];
}

// 初始化变量
$errors = [];
$success = false;
$values = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'position' => '',
    'experience' => '',
    'cover_letter' => ''
];

// 创建数据库表的函数
function createApplicationsTable($mysqli) {
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS `job_applications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `position` VARCHAR(100) NOT NULL,
            `experience` TEXT NOT NULL,
            `cover_letter` TEXT,
            `resume_path` VARCHAR(255),
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_position` (`position`),
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
    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['position'] = trim($_POST['position'] ?? '');
    $values['experience'] = trim($_POST['experience'] ?? '');
    $values['cover_letter'] = trim($_POST['cover_letter'] ?? '');
    
    // 验证必填字段
    if (empty($values['name'])) $errors[] = 'Full name is required.';
    if (empty($values['email']) || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (empty($values['phone'])) $errors[] = 'Phone number is required.';
    if (empty($values['position'])) $errors[] = 'Position applied for is required.';
    if (empty($values['experience'])) $errors[] = 'Work experience is required.';
    
    // 处理文件上传
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = mime_content_type($_FILES['resume']['tmp_name']);
        $fileSize = $_FILES['resume']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Please upload a PDF, DOC, DOCX, or TXT file.';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'File size exceeds 5MB limit.';
        } else {
            // 创建上传目录
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // 生成唯一文件名
            $fileExtension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['resume']['name']));
            $resumePath = $uploadDir . $newFileName;
            
            // 移动上传文件
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath)) {
                $errors[] = 'Failed to save uploaded file.';
            }
            
            // 保存相对路径到数据库
            $resumePath = 'uploads/' . $newFileName;
        }
    } elseif (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Resume/CV is required.';
    }
    
    // 如果没有错误，则保存到数据库
    if (empty($errors)) {
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_errno) {
            $errors[] = 'Database connection failed.';
        } else {
            $mysqli->set_charset('utf8mb4');
            
            // 创建表（如果不存在）
            if (!createApplicationsTable($mysqli)) {
                $errors[] = 'Failed to create applications table.';
            } else {
                // 插入数据
                $stmt = $mysqli->prepare("INSERT INTO `job_applications` (name, email, phone, position, experience, cover_letter, resume_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssssss", 
                        $values['name'],
                        $values['email'],
                        $values['phone'],
                        $values['position'],
                        $values['experience'],
                        $values['cover_letter'],
                        $resumePath
                    );
                    
                    if ($stmt->execute()) {
                        $success = true;
                        // 清空表单值
                        $values = array_map(function() { return ''; }, $values);
                    } else {
                        $errors[] = 'Failed to save application.';
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
    <title>Careers | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        /* Careers page styles with light background */
        .careers-section {
            padding: 3.5rem 0 6rem;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .careers-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid #e3e6eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            color: #1d1d1f;
        }

        .careers-card h2 {
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
            font-family: inherit;
            outline: none;
            transition: box-shadow 0.15s ease, border-color 0.15s ease;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #6b6b6b;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            box-shadow: 0 4px 12px rgba(93, 140, 44, 0.15);
            border-color: #5d8c2c;
        }
        
        .form-file {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            border: 1px dashed #e3e6eb;
            background: #ffffff;
            color: #1d1d1f;
            font-family: inherit;
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
        
        .file-note {
            font-size: 0.85rem;
            color: #6b6b6b;
            margin-top: 0.3rem;
        }
        
        /* Positions table styles */
        .positions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .positions-table th {
            background: #5d8c2c;
            color: #ffffff;
            text-align: left;
            padding: 1rem 1.2rem;
            font-weight: 600;
        }
        
        .positions-table td {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid #e3e6eb;
        }
        
        .positions-table tr:last-child td {
            border-bottom: none;
        }
        
        .positions-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .positions-table .department {
            color: #5d8c2c;
            font-weight: 600;
        }
        
        /* Position intro styles */
        .position-intro {
            text-align: center;
            color: #6b6b6b;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #5d8c2c;
        }
        
        .position-intro h3 {
            color: #1d1d1f;
            margin: 0 0 0.5rem 0;
        }
        
        @media (max-width: 640px) {
            .careers-card {
                padding: 1.5rem;
            }
            
            .positions-table {
                display: block;
                overflow-x: auto;
            }
            
            .positions-table th,
            .positions-table td {
                padding: 0.8rem 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="container">
        <div class="position-intro">
            <h3>Current Openings</h3>
            <p>We are actively seeking talented professionals to join our dynamic team. Below are the positions we are currently looking to fill:</p>
        </div>

        <table class="positions-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dynamic_positions)): ?>
                    <?php foreach ($dynamic_positions as $pos): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pos['position'] ?? '', ENT_QUOTES); ?></td>
                            <td class="department"><?php echo htmlspecialchars($pos['department'] ?? '', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($pos['location'] ?? '', ENT_QUOTES); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No openings currently.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <main>
        <section class="careers-section">
            <div class="container">
                <div class="careers-card">
                    <h2>Join Our Team</h2>
                    <p style="text-align: center; color: #6b6b6b; margin-bottom: 2rem;">
                        We're looking for passionate individuals to join our growing team
                    </p>
                    
                    <!-- Positions table -->
                    
                    
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
                            Thank you for your application! We have received your submission and will review it shortly.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data" action="recruitment.php">
                        <div class="form-row">
                            <label class="form-label" for="name">Full Name *</label>
                            <input class="form-input" type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($values['name'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="email">Email Address *</label>
                            <input class="form-input" type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="phone">Phone Number *</label>
                            <input class="form-input" type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($values['phone'], ENT_QUOTES); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="position">Position Applied For *</label>
                            <select class="form-select" id="position" name="position" required>
                                <option value="">Select a position</option>
                                <option value="Game Developer" <?php echo ($values['position'] === 'Game Developer') ? 'selected' : ''; ?>>Game Developer</option>
                                <option value="UI/UX Designer" <?php echo ($values['position'] === 'UI/UX Designer') ? 'selected' : ''; ?>>UI/UX Designer</option>
                                <option value="Marketing Specialist" <?php echo ($values['position'] === 'Marketing Specialist') ? 'selected' : ''; ?>>Marketing Specialist</option>
                                <option value="Customer Support" <?php echo ($values['position'] === 'Customer Support') ? 'selected' : ''; ?>>Customer Support</option>
                                <option value="QA Tester" <?php echo ($values['position'] === 'QA Tester') ? 'selected' : ''; ?>>QA Tester</option>
                                <option value="Other" <?php echo ($values['position'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="experience">Work Experience *</label>
                            <textarea class="form-textarea" id="experience" name="experience" placeholder="Describe your relevant work experience" required><?php echo htmlspecialchars($values['experience'], ENT_QUOTES); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="cover_letter">Cover Letter</label>
                            <textarea class="form-textarea" id="cover_letter" name="cover_letter" placeholder="Write a brief cover letter (optional)"><?php echo htmlspecialchars($values['cover_letter'], ENT_QUOTES); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <label class="form-label" for="resume">Resume/CV *</label>
                            <input class="form-file" type="file" id="resume" name="resume" accept=".pdf,.doc,.docx,.txt" required>
                            <div class="file-note">Accepted formats: PDF, DOC, DOCX, TXT (Max size: 5MB)</div>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <button class="btn btn--primary-gradient" type="submit">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>