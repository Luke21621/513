<?php
// 启动会话以管理购物车数据
session_start();

// 加载产品数据
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);

// 将产品数组转换为以ID为键的关联数组，便于查找
$productsById = [];
foreach ($products as $product) {
    $productsById[$product['id']] = $product;
}

// 初始化购物车（如果不存在）
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 处理购物车操作
if (isset($_POST['action'])) {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    switch ($_POST['action']) {
        case 'increase':
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]++;
            }
            break;
        case 'decrease':
            if (isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId] > 1) {
                $_SESSION['cart'][$productId]--;
            }
            break;
        case 'remove':
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
            break;
        case 'add':
            if (isset($productsById[$productId])) {
                $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
                $quantity = max(1, min(99, $quantity)); // 限制在1-99之间
                $_SESSION['cart'][$productId] = (isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId] : 0) + $quantity;
            }
            break;
    }
    
    // 重定向以避免表单重复提交
    header('Location: cart.php');
    exit;
}

// 计算购物车统计信息
$cartItems = [];
$totalItems = 0;
$subtotal = 0;
$shipping = 12.00; // 固定运费

foreach ($_SESSION['cart'] as $productId => $quantity) {
    if (isset($productsById[$productId])) {
        $product = $productsById[$productId];
        $itemTotal = $product['price'] * $quantity;
        $cartItems[] = [
            'id' => $productId,
            'name' => $product['name'],
            'description' => $product['tagline'],
            'image' => $product['image'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $itemTotal
        ];
        $totalItems += $quantity;
        $subtotal += $itemTotal;
    }
}

$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub | Shopping Cart</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        /* 购物车特定样式 */
        .cart-section {
            padding: 3rem 0;
        }
        
        .cart-header {
            margin-bottom: 2rem;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background-color: #1d1d1f;
            color: #fff;
            border-radius: 12px;
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .cart-item-details h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        
        .cart-item-details p {
            margin: 0 0 0.5rem 0;
            color: #bbb;
            font-size: 0.9rem;
        }
        
        .cart-item-details .product-id {
            font-size: 0.8rem;
            color: #888;
        }
        
        .cart-item-price {
            font-weight: 600;
            color: #5d8c2c;
            font-size: 1.1rem;
        }

        
        .cart-item-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #444;
            background: transparent;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s;
        }
        
        .quantity-btn:hover {
            background: #333;
            border-color: #5d8c2c;
        }
        
        .quantity-value {
            min-width: 30px;
            text-align: center;
        }
        
        .remove-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        
        .remove-btn:hover {
            background: #c0392b;
        }
        
        .cart-summary {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #1d1d1f;
            color: #fff;
            border-radius: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.2rem;
            padding-top: 1rem;
            border-top: 1px solid #333;
            margin-bottom: 1.5rem;
        }
        
        .checkout-btn {
            display: block;
            width: 100%;
            background: #5d8c2c;
            color: #fff;
            border: none;
            padding: 0.9rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        
        .checkout-btn:hover {
            background: #4a6e22;
            transform: translateY(-2px);
        }
        
        .cart-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .login-checkout-btn {
            flex: 1;
            background: linear-gradient(90deg, #88efd2, #8bd3ff);
            color: #072023;
            border: none;
            padding: 0.9rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        
        .continue-shopping-btn {
            flex: 1;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.9rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        
        .login-checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(136, 239, 210, 0.25);
        }
        
        .continue-shopping-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem 0;
            color: var(--muted);
        }
        
        .empty-cart a {
            color: var(--primary);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cart-item-controls {
                align-items: flex-start;
                flex-direction: row;
                justify-content: space-between;
            }
            
            .cart-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container cart-section">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <p>Total in cart: <?php echo $totalItems; ?></p>
        </div>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <p><a href="shop.php">Continue Shopping</a></p>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" class="cart-item-image">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?></h3>
                            <p><?php echo htmlspecialchars($item['description'], ENT_QUOTES); ?></p>
                            <div class="product-id">ID: <?php echo $item['id']; ?></div>
                        </div>
                        <div class="cart-item-price">¥<?php echo number_format($item['price'], 2); ?></div>
                        <div class="cart-item-controls">
                            <div class="quantity-control">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="decrease">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="quantity-btn">-</button>
                                </form>
                                <span class="quantity-value"><?php echo $item['quantity']; ?></span>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="increase">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="quantity-btn">+</button>
                                </form>
                            </div>
                            <div class="cart-item-price">¥<?php echo number_format($item['total'], 2); ?></div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Items</span>
                    <span><?php echo $totalItems; ?></span>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>¥<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>¥<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>¥<?php echo number_format($total, 2); ?></span>
                </div>
                
                <!-- 根据图片所示位置添加按钮 -->
                <div class="cart-buttons">
                    <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
                        <a href="checkout.php" class="login-checkout-btn">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="customer_login.php?redirect=checkout.php" class="login-checkout-btn">Login to Checkout</a>
                    <?php endif; ?>
                    <a href="shop.php" class="continue-shopping-btn">Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>