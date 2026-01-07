<?php
session_start();

// 检查是否有订单ID参数
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

// 获取订单信息
$order_info = isset($_SESSION['last_order']) ? $_SESSION['last_order'] : null;

// 如果没有订单信息，重定向到购物车
if (!$order_info) {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        .payment-page { padding: 2.5rem 0; }
        .payment-header { margin-bottom: 1rem; text-align: center; }
        .payment-card { background: #0f1720; color: #fff; padding: 2rem; border-radius: 12px; max-width: 600px; margin: 0 auto; text-align: center; }
        .success-icon { font-size: 3rem; color: #2ecc71; margin-bottom: 1rem; }
        .order-details { background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin: 1.5rem 0; text-align: left; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .detail-row:last-child { border-bottom: none; }
        .btn { display: inline-block; background: linear-gradient(90deg,#88efd2,#8bd3ff); border:none; color:#072023; padding:0.8rem 1.5rem; border-radius:8px; font-weight:600; text-decoration: none; margin-top: 1rem; }
        
        /* 添加 Continue Shopping 按钮样式 */
        .continue-shopping-btn {
            display: inline-block;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
            transition: all 0.2s;
            font-weight: 600;
        }
        
        .continue-shopping-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container payment-page">
        <div class="payment-header">
            <h1>Payment Processing</h1>
        </div>

        <div class="payment-card">
            <div class="success-icon">✓</div>
            <h2>Order Confirmed!</h2>
            <p>Thank you for your purchase. Your order has been successfully placed.</p>
            
            <div class="order-details">
                <div class="detail-row">
                    <span>Order ID:</span>
                    <span>#<?php echo htmlspecialchars($order_info['order_id'], ENT_QUOTES); ?></span>
                </div>
                <div class="detail-row">
                    <span>Date:</span>
                    <span><?php echo htmlspecialchars($order_info['placed_at'], ENT_QUOTES); ?></span>
                </div>
                <div class="detail-row">
                    <span>Email:</span>
                    <span><?php echo htmlspecialchars($order_info['customer_email'], ENT_QUOTES); ?></span>
                </div>
                <div class="detail-row">
                    <span>Total Amount:</span>
                    <span>¥<?php echo number_format($order_info['total'], 2); ?></span>
                </div>
            </div>
            
            <a href="shop.php" class="continue-shopping-btn">Continue Shopping</a>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>