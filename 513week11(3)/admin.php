<?php
session_start();

// 检查管理员是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';

// 处理登出
if (isset($_GET['logout'])) {
    // 只清除管理员会话变量，保留其他会话（如顾客会话）
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    header('Location: admin_login.php');
    exit;
}


// 处理产品管理
$products_file = __DIR__ . '/data/products.json';
$products = [];
if (file_exists($products_file)) {
    $products = json_decode(file_get_contents($products_file), true) ?: [];
}

$product_message = '';
$product_error = '';

// 处理产品添加/编辑/删除（容错：products.json 为空或格式异常时回退为空数组）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_action'])) {
    $action = $_POST['product_action'];
    
        if ($action === 'add' || $action === 'edit') {
        // 计算安全的下一个ID，避免 products 为空时 max() 报错
        $existingIds = array_column(is_array($products) ? $products : [], 'id');
        $nextId = !empty($existingIds) ? (max($existingIds) + 1) : 1;
        $id = $action === 'edit' ? intval($_POST['product_id']) : $nextId;
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        
        // 验证
        if (empty($name) || empty($category) || $price <= 0 || empty($image)) {
            $product_error = 'All required fields must be filled and price must be greater than 0.';
        } else {
            $product = [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'image' => $image,
                'tagline' => $tagline
            ];
            
            if ($action === 'add') {
                $products[] = $product;
        $product_message = 'Product added successfully!';
            } else {
                $index = array_search($id, array_column($products, 'id'));
                if ($index !== false) {
                    $products[$index] = $product;
                    $product_message = 'Product updated successfully!';
                }
            }
            
            // 保存到JSON文件
            file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $products = json_decode(file_get_contents($products_file), true);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['product_id']);
        $products = array_filter($products, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        $products = array_values($products);
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $products = json_decode(file_get_contents($products_file), true);
        $product_message = 'Product deleted successfully!';
    }
}

// 处理订单管理
$order_message = '';
$order_error = '';

// 确保订单表存在（与 checkout.php 保持一致）
function ensureOrdersTable($mysqli) {
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS `gamehub_orders` (
            `order_id` INT(11) NOT NULL AUTO_INCREMENT,
            `customer_email` VARCHAR(100) NOT NULL,
            `customer_phone` VARCHAR(20) NOT NULL,
            `customer_name` VARCHAR(100) DEFAULT NULL,
            `product_details` TEXT NOT NULL COMMENT 'JSON格式存储订单项详情',
            `product_ids` TEXT NOT NULL COMMENT '产品ID和数量，格式: id:quantity,id:quantity',
            `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `shipping` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_amount` DECIMAL(10,2) NOT NULL,
            `total_items` INT(11) NOT NULL DEFAULT 0,
            `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `status` VARCHAR(20) DEFAULT 'pending' COMMENT '订单状态: pending, confirmed, completed, cancelled',
            PRIMARY KEY (`order_id`),
            KEY `idx_customer_email` (`customer_email`),
            KEY `idx_order_date` (`order_date`),
            KEY `idx_status` (`status`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    return $mysqli->query($createTableQuery);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $action = $_POST['order_action'];
    
    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = trim($_POST['new_status'] ?? '');
        
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if (!$mysqli->connect_errno) {
            $mysqli->set_charset('utf8mb4');
            // 若表不存在则创建
            ensureOrdersTable($mysqli);
            $stmt = $mysqli->prepare("UPDATE `gamehub_orders` SET `status` = ? WHERE `order_id` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_status, $order_id);
                if ($stmt->execute()) {
                    $order_message = '订单状态更新成功！';
                } else {
                    $order_error = '更新订单状态失败。';
                }
                $stmt->close();
            }
            $mysqli->close();
        }
    }
}

// 获取订单
$orders = [];
$order_search = $_GET['order_search'] ?? '';
$order_status_filter = $_GET['order_status'] ?? '';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$mysqli->connect_errno) {
    $mysqli->set_charset('utf8mb4');

    // 确保订单表存在，避免首次访问 500
    ensureOrdersTable($mysqli);
    
    $sql = "SELECT * FROM `gamehub_orders` WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($order_search)) {
        $sql .= " AND (customer_email LIKE ? OR customer_phone LIKE ? OR order_id = ?)";
        $search_param = "%{$order_search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = intval($order_search);
        $types .= 'ssi';
    }
    
    if (!empty($order_status_filter)) {
        $sql .= " AND status = ?";
        $params[] = $order_status_filter;
        $types .= 's';
    }
    
    $sql .= " ORDER BY order_date DESC LIMIT 100";
    
    $stmt = $mysqli->prepare($sql);
    if ($stmt && (!empty($params) || empty($params))) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        $stmt->close();
    }
    $mysqli->close();
}

// 处理论坛管理
$forum_message = '';
$forum_error = '';

// 确保论坛表存在
function ensureDiscussionTable($mysqli) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
    $action = $_POST['forum_action'];
    
    if ($action === 'delete_post') {
        $post_id = intval($_POST['post_id']);
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if (!$mysqli->connect_errno) {
            $mysqli->set_charset('utf8mb4');
            ensureDiscussionTable($mysqli);
            $stmt = $mysqli->prepare("DELETE FROM `discussion_posts` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $post_id);
                if ($stmt->execute()) {
                    $forum_message = 'Post deleted successfully!';
                } else {
                    $forum_error = 'Failed to delete post.';
                }
                $stmt->close();
            }
            $mysqli->close();
        }
    }
}

// 获取论坛帖子
$forum_posts = [];
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$mysqli->connect_errno) {
    $mysqli->set_charset('utf8mb4');
    ensureDiscussionTable($mysqli);
    $result = $mysqli->query("SELECT * FROM `discussion_posts` ORDER BY `created_at` DESC LIMIT 100");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $forum_posts[] = $row;
        }
    }
    $mysqli->close();
}

// 处理网站内容管理
$content_message = '';
$content_error = '';

// 确保招聘表存在
function ensureJobApplicationsTable($mysqli) {
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

// 确保支持表存在
function ensureSupportFeedbackTable($mysqli) {
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

// 招聘信息管理
$recruitment_positions_file = __DIR__ . '/data/recruitment_positions.json';
$recruitment_positions = [];
if (file_exists($recruitment_positions_file)) {
    $recruitment_positions = json_decode(file_get_contents($recruitment_positions_file), true) ?: [];
} else {
    // 默认职位
    $recruitment_positions = [
        ['position' => 'Game Developer', 'department' => 'Engineering', 'location' => 'Shanghai'],
        ['position' => 'UI/UX Designer', 'department' => 'Design', 'location' => 'Shanghai'],
        ['position' => 'Marketing Specialist', 'department' => 'Marketing', 'location' => 'Beijing'],
        ['position' => 'Customer Support', 'department' => 'Customer Service', 'location' => 'Guangzhou'],
        ['position' => 'QA Tester', 'department' => 'Engineering', 'location' => 'Shenzhen']
    ];
    file_put_contents($recruitment_positions_file, json_encode($recruitment_positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content_action'])) {
    $action = $_POST['content_action'];
    
        if ($action === 'add_position' || $action === 'edit_position') {
        $position = trim($_POST['position'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $location = trim($_POST['location'] ?? '');
        
        if (empty($position) || empty($department) || empty($location)) {
            $content_error = 'All fields are required.';
        } else {
            $new_position = ['position' => $position, 'department' => $department, 'location' => $location];
            
            if ($action === 'add_position') {
                $recruitment_positions[] = $new_position;
            } else {
                $index = intval($_POST['position_index']);
                if (isset($recruitment_positions[$index])) {
                    $recruitment_positions[$index] = $new_position;
                }
            }
            
            file_put_contents($recruitment_positions_file, json_encode($recruitment_positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $content_message = 'Position information updated successfully!';
        }
    } elseif ($action === 'delete_position') {
        $index = intval($_POST['position_index']);
        if (isset($recruitment_positions[$index])) {
            unset($recruitment_positions[$index]);
            $recruitment_positions = array_values($recruitment_positions);
            file_put_contents($recruitment_positions_file, json_encode($recruitment_positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $content_message = 'Position deleted successfully!';
        }
    } elseif ($action === 'update_support') {
        $support_email = trim($_POST['support_email'] ?? '');
        $support_hours = trim($_POST['support_hours'] ?? '');
        
        $support_info = [
            'email' => $support_email,
            'hours' => $support_hours
        ];
        
        file_put_contents(__DIR__ . '/data/support_info.json', json_encode($support_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $content_message = 'Support information updated successfully!';
    }
}

// 读取支持信息
$support_info = ['email' => '2162106274@qq.com', 'hours' => 'Monday to Friday, 9:00 AM - 5:00 PM'];
$support_info_file = __DIR__ . '/data/support_info.json';
if (file_exists($support_info_file)) {
    $support_info = json_decode(file_get_contents($support_info_file), true) ?: $support_info;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        
        .admin-tabs {
            background: #ffffff;
            border-bottom: 1px solid #e3e6eb;
            padding: 0 2rem;
        }
        
        .admin-tabs-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
        }
        
        .admin-tab {
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            color: #6b6b6b;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .admin-tab:hover {
            color: #2d2d2d;
            background: #f8f9fa;
        }
        
        .admin-tab.active {
            color: #4a90e2;
            border-bottom-color: #4a90e2;
        }
        
        .admin-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-section {
            display: none;
        }
        
        .admin-section.active {
            display: block;
        }
        
        .admin-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .admin-card h2 {
            margin: 0 0 1.5rem 0;
            color: #2d2d2d;
            font-size: 1.5rem;
        }
        
        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
        }
        
        .message.error {
            background: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d2d2d;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #e3e6eb;
            background: #ffffff;
            color: #2d2d2d;
            font-size: 0.95rem;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #4a90e2;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background: #357abd;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: #ffffff;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e3e6eb;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2d2d2d;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff5e6;
            color: #d69e2e;
        }
        
        .status-confirmed {
            background: #f0fff4;
            color: #38a169;
        }
        
        .status-cancelled {
            background: #fff5f5;
            color: #e53e3e;
        }
        
        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .search-filter input,
        .search-filter select {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #e3e6eb;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    
    <div class="admin-content">
        <!-- Admin Welcome Section -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">Welcome to Admin Dashboard</h2>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: #4a90e2; font-weight: 600; font-size: 1.1rem;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES); ?></span>
                    <a href="admin.php?logout=1" style="padding: 0.75rem 1.5rem; background: #e53e3e; color: #ffffff; border-radius: 8px; text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#c53030'" onmouseout="this.style.background='#e53e3e'">Logout</a>
                </div>
            </div>
        </div>

        <!-- Product Management -->
        <div id="products" class="admin-section active">
            <div class="admin-card">
                <h2>Product Management</h2>
                
                <?php if ($product_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($product_message, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if ($product_error): ?>
                    <div class="message error"><?php echo htmlspecialchars($product_error, ENT_QUOTES); ?></div>
                <?php endif; ?>
                
                <h3>Add New Product</h3>
                <form method="POST">
                    <input type="hidden" name="product_action" value="add">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <input type="text" name="category" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price *</label>
                            <input type="number" step="0.01" name="price" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Image URL *</label>
                            <input type="text" name="image" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tagline</label>
                        <textarea name="tagline" class="form-textarea"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>
            
            <div class="admin-card">
                <h2>All Products</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($product['category'], ENT_QUOTES); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?></td>
                                <td>
                                    <div class="action-buttons">
                                    <button class="btn btn-primary btn-small" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="product_action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Order Management -->
        <div id="orders" class="admin-section active">
            <div class="admin-card">
                <h2>Order Management</h2>
                
                <?php if ($order_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($order_message, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if ($order_error): ?>
                    <div class="message error"><?php echo htmlspecialchars($order_error, ENT_QUOTES); ?></div>
                <?php endif; ?>
                
                <div class="search-filter">
                    <input type="text" id="order_search_input" placeholder="Search orders (email, phone, order ID)" value="<?php echo htmlspecialchars($order_search, ENT_QUOTES); ?>" onkeypress="if(event.key==='Enter') searchOrders()">
                    <select id="order_status_filter" onchange="searchOrders()">
                        <option value="">All statuses</option>
                        <option value="pending" <?php echo $order_status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $order_status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $order_status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button class="btn btn-primary" onclick="searchOrders()">Search</button>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Email</th>
                            <th>Customer Phone</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['order_id'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_phone'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($order['product_ids'], ENT_QUOTES); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES); ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'Pending',
                                                'confirmed' => 'Confirmed',
                                                'cancelled' => 'Cancelled'
                                            ];
                                            echo $status_text[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 6px;">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Forum Management -->
        <div id="forum" class="admin-section active">
            <div class="admin-card">
                <h2>Forum Management</h2>
                
                <?php if ($forum_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($forum_message, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if ($forum_error): ?>
                    <div class="message error"><?php echo htmlspecialchars($forum_error, ENT_QUOTES); ?></div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                <tr>
                    <th>ID</th>
                    <th>User Email</th>
                    <th>Username</th>
                    <th>Content</th>
                    <th>Posted At</th>
                    <th>Actions</th>
                </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forum_posts)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">没有帖子</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($forum_posts as $post): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['id'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($post['user_email'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($post['user_name'], ENT_QUOTES); ?></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($post['content'], 0, 100), ENT_QUOTES); ?>...</td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                                            <input type="hidden" name="forum_action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recruitment / Content Management -->
        <div id="content" class="admin-section active">
            <div class="admin-card">
                <h2>Recruitment Management</h2>
                
                <?php if ($content_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($content_message, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if ($content_error): ?>
                    <div class="message error"><?php echo htmlspecialchars($content_error, ENT_QUOTES); ?></div>
                <?php endif; ?>
                
                <h3>Add New Position</h3>
                <form method="POST">
                    <input type="hidden" name="content_action" value="add_position">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Position *</label>
                            <input type="text" name="position" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department *</label>
                            <input type="text" name="department" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-input" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Position</button>
                </form>
                
                <h3 style="margin-top: 2rem;">Current Positions</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recruitment_positions as $index => $position): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($position['position'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($position['department'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($position['location'], ENT_QUOTES); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-small" onclick="editPosition(<?php echo $index; ?>, '<?php echo htmlspecialchars($position['position'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($position['department'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($position['location'], ENT_QUOTES); ?>')">Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this position?');">
                                            <input type="hidden" name="content_action" value="delete_position">
                                            <input type="hidden" name="position_index" value="<?php echo $index; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card">
                <h2>Support / Contact Information Management</h2>
                
                <form method="POST">
                    <input type="hidden" name="content_action" value="update_support">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="support_email" class="form-input" value="<?php echo htmlspecialchars($support_info['email'], ENT_QUOTES); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Business Hours</label>
                            <input type="text" name="support_hours" class="form-input" value="<?php echo htmlspecialchars($support_info['hours'], ENT_QUOTES); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Support Info</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 编辑产品模态框 -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <h2>Edit Product</h2>
            <form method="POST" id="editProductForm">
                <input type="hidden" name="product_action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" id="edit_product_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <input type="text" name="category" id="edit_product_category" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Price *</label>
                    <input type="number" step="0.01" name="price" id="edit_product_price" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Image URL *</label>
                    <input type="text" name="image" id="edit_product_image" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <textarea name="tagline" id="edit_product_tagline" class="form-textarea"></textarea>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditProductModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 编辑职位模态框 -->
    <div id="editPositionModal" class="modal">
        <div class="modal-content">
            <h2>Edit Position</h2>
            <form method="POST" id="editPositionForm">
                <input type="hidden" name="content_action" value="edit_position">
                <input type="hidden" name="position_index" id="edit_position_index">
                <div class="form-group">
                    <label class="form-label">Position Name *</label>
                    <input type="text" name="position" id="edit_position_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <input type="text" name="department" id="edit_position_department" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" id="edit_position_location" class="form-input" required>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditPositionModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        
        function searchOrders() {
            const search = document.getElementById('order_search_input').value;
            const status = document.getElementById('order_status_filter').value;
            let url = 'admin.php?tab=orders';
            if (search) url += '&order_search=' + encodeURIComponent(search);
            if (status) url += '&order_status=' + encodeURIComponent(status);
            window.location.href = url;
        }
        
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_product_name').value = product.name;
            document.getElementById('edit_product_category').value = product.category;
            document.getElementById('edit_product_price').value = product.price;
            document.getElementById('edit_product_image').value = product.image;
            document.getElementById('edit_product_tagline').value = product.tagline || '';
            document.getElementById('editProductModal').classList.add('active');
        }
        
        function closeEditProductModal() {
            document.getElementById('editProductModal').classList.remove('active');
        }
        
        function editPosition(index, position, department, location) {
            document.getElementById('edit_position_index').value = index;
            document.getElementById('edit_position_name').value = position;
            document.getElementById('edit_position_department').value = department;
            document.getElementById('edit_position_location').value = location;
            document.getElementById('editPositionModal').classList.add('active');
        }
        
        function closeEditPositionModal() {
            document.getElementById('editPositionModal').classList.remove('active');
        }
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            const editProductModal = document.getElementById('editProductModal');
            const editPositionModal = document.getElementById('editPositionModal');
            if (event.target === editProductModal) {
                closeEditProductModal();
            }
            if (event.target === editPositionModal) {
                closeEditPositionModal();
            }
        }
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>

