<?php
session_start();

// 检查顾客是否已登录
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    // 未登录，重定向到顾客登录页面
    header('Location: customer_login.php?redirect=checkout.php');
    exit;
}

// 数据库配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';

// 加载产品数据
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
$productsById = [];
foreach ($products as $product) {
    $productsById[$product['id']] = $product;
}

// 初始化变量
$errors = [];
$success = false;
$user_verified = false;
$customer_email = '';
$customer_phone = '';
$order_id = null;

// 购物车数据
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = [];
$subtotal = 0;
$shipping = 12.00;
$totalItems = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    if (isset($productsById[$productId])) {
        $product = $productsById[$productId];
        $itemTotal = $product['price'] * $quantity;
        $cartItems[] = [
            'id' => $productId,
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $itemTotal
        ];
        $subtotal += $itemTotal;
        $totalItems += $quantity;
    }
}

$total = $subtotal + $shipping;

// 创建订单表的函数
function createOrdersTable($mysqli) {
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

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 从session获取已登录的顾客信息
    $customer_email = $_SESSION['customer_email'] ?? '';
    $customer_phone = trim($_POST['phone'] ?? $_SESSION['customer_phone'] ?? '');
    
    // 验证必填字段
    if (empty($customer_email)) {
        $errors[] = 'Customer email not found. Please log in again.';
    }
    if (empty($customer_phone)) {
        $errors[] = 'Phone number is required.';
    }
    
    // 如果没有错误，则使用已登录的顾客信息
    if (empty($errors)) {
        
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_errno) {
            $errors[] = 'Database connection failed.';
        } else {
            $mysqli->set_charset('utf8mb4');
            
            // 验证用户信息（可选，因为已经登录）
            $stmt = $mysqli->prepare("SELECT * FROM `wpkt_fc_subscribers` WHERE `email` = ?");
            if ($stmt) {
                $stmt->bind_param("s", $customer_email);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        // 用户身份验证成功
                        $user_verified = true;
                        
                        // 创建订单表（如果不存在）
                        if (!createOrdersTable($mysqli)) {
                            $errors[] = 'Failed to create orders table: ' . $mysqli->error;
                        } else {
                            // 准备订单数据
                            $product_ids = [];
                            foreach ($cartItems as $item) {
                                $product_ids[] = $item['id'] . ':' . $item['quantity'];
                            }
                            $product_ids_str = implode(',', $product_ids);
                            
                            // 准备产品详情（JSON格式）
                            $product_details = json_encode($cartItems, JSON_UNESCAPED_UNICODE);
                            
                            // 获取客户姓名
                            $customer_name = '';
                            if (isset($row['first_name']) || isset($row['last_name'])) {
                                $customer_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                            } elseif (isset($row['name'])) {
                                $customer_name = $row['name'];
                            }
                            if (empty($customer_name)) {
                                $first = $_SESSION['customer_first_name'] ?? '';
                                $last = $_SESSION['customer_last_name'] ?? '';
                                $customer_name = trim($first . ' ' . $last);
                            }
                            
                            // 插入订单数据到gamehub_orders表
                            $stmt2 = $mysqli->prepare("INSERT INTO `gamehub_orders` 
                                (customer_email, customer_phone, customer_name, product_details, product_ids, subtotal, shipping, total_amount, total_items, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt2) {
                                $status = 'confirmed';
                                $stmt2->bind_param("sssssdddis", 
                                    $customer_email,
                                    $customer_phone,
                                    $customer_name,
                                    $product_details,
                                    $product_ids_str,
                                    $subtotal,
                                    $shipping,
                                    $total,
                                    $totalItems,
                                    $status
                                );
                                
                                if ($stmt2->execute()) {
                                    $success = true;
                                    $order_id = $stmt2->insert_id;
                                    
                                    // 清空购物车
                                    $_SESSION['cart'] = [];
                                    
                                    // 保存订单信息到session用于确认页面显示
                                    $_SESSION['last_order'] = [
                                        'order_id' => $order_id,
                                        'customer_email' => $customer_email,
                                        'customer_phone' => $customer_phone,
                                        'customer_name' => $customer_name,
                                        'items' => $cartItems,
                                        'subtotal' => $subtotal,
                                        'shipping' => $shipping,
                                        'total' => $total,
                                        'total_items' => $totalItems,
                                        'placed_at' => date('Y-m-d H:i:s')
                                    ];
                                } else {
                                    $errors[] = 'Failed to place order: ' . $stmt2->error;
                                }
                                $stmt2->close();
                            } else {
                                $errors[] = 'Failed to prepare order statement: ' . $mysqli->error;
                            }
                        }
                    } else {
                        $errors[] = 'Customer information not found. Please contact support.';
                    }
                } else {
                    $errors[] = 'Failed to verify customer information.';
                }
                $stmt->close();
            } else {
                $errors[] = 'Failed to prepare verification statement.';
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
    <title>Checkout | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        .checkout-page { padding: 2.5rem 0; }
        .checkout-header { margin-bottom: 1rem; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; }
        .checkout-form { background: #0f1720; color: #fff; padding: 1.5rem; border-radius: 12px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #333; background: #1a1a1a; color: #fff; }
        .order-items { display: grid; gap: 1rem; }
        .order-item { background: #0f1720; color: #fff; padding: 1rem; border-radius: 12px; display: flex; gap: 1rem; align-items: center; }
        .order-item img { width: 84px; height: 64px; object-fit: cover; border-radius: 8px; }
        .order-item .meta { flex: 1; }
        .order-item .meta h4 { margin: 0 0 0.25rem 0; font-size: 1rem; }
        .order-item .meta .qty { color: #9aa3a6; font-size: 0.9rem; }
        .summary-card { background: linear-gradient(180deg, rgba(6,10,12,0.92), rgba(2,6,8,0.95)); color: #c7d7d9; padding: 1.25rem; border-radius: 12px; }
        .summary-row { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px dashed rgba(255,255,255,0.03); }
        .summary-row.total { font-weight:700; font-size:1.15rem; border-top:1px solid rgba(255,255,255,0.03); padding-top:1rem; }
        .btn-row { display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1rem; }
        .secondary { background: transparent; border: 1px solid rgba(255,255,255,0.08); color: #c7d7d9; padding:0.6rem 1rem; border-radius:8px; }
        .primary { background: linear-gradient(90deg,#88efd2,#8bd3ff); border:none; color:#072023; padding:0.6rem 1rem; border-radius:8px; font-weight:600; }
        .error-message { background: #e74c3c; color: white; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; }
        .success-message { background: #2ecc71; color: white; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; }
        
        /* 添加 Continue Shopping 按钮样式 */
        .continue-shopping-btn {
            display: inline-block;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.6rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
            transition: all 0.2s;
        }
        
        .continue-shopping-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 900px) { 
            .checkout-grid { grid-template-columns: 1fr; } 
            .btn-row { justify-content: center; } 
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container checkout-page">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <h3>Thank you for your order!</h3>
                <p>Your order has been placed successfully.</p>
                <p>Order ID: #<?php echo htmlspecialchars($order_id, ENT_QUOTES); ?></p>
                <p><a href="payment.php?order_id=<?php echo urlencode($order_id); ?>" class="btn" style="margin-top:0.5rem;display:inline-block;">Proceed to Payment</a></p>
            </div>
        <?php else: ?>
            <?php if (empty($cartItems)): ?>
                <div class="quote-card">Your cart is empty. <a href="shop.php">Go shopping</a></div>
            <?php else: ?>
                <div class="checkout-grid">
                    <div>
                        <div class="checkout-form">
                            <h2>Customer Information</h2>
                            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['customer_email'] ?? 'N/A', ENT_QUOTES); ?></p>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['customer_email'] ?? $customer_email, ENT_QUOTES); ?>" required readonly>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_SESSION['customer_phone'] ?? $customer_phone, ENT_QUOTES); ?>" required>
                                </div>
                                <button type="submit" class="primary">Place Order</button>
                            </form>
                        </div>
                    </div>

                    <aside>
                        <div class="summary-card">
                            <div style="margin-bottom:0.5rem;font-weight:600;font-size:1.05rem;">Order Summary</div>
                            <div class="order-items">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES); ?>" alt="">
                                        <div class="meta">
                                            <h4><?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?></h4>
                                            <div class="qty">× <?php echo $item['quantity']; ?> &nbsp; • &nbsp; ¥<?php echo number_format($item['total'],2); ?></div>
                                        </div>
                                        <div style="font-weight:600;color:#88efd2;">¥<?php echo number_format($item['price'],2); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="summary-row"><div>Subtotal</div><div>¥<?php echo number_format($subtotal,2); ?></div></div>
                            <div class="summary-row"><div>Shipping</div><div>¥<?php echo number_format($shipping,2); ?></div></div>
                            <div class="summary-row total"><div>Total</div><div>¥<?php echo number_format($total,2); ?></div></div>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>