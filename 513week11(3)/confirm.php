<?php
session_start();

// 加载产品数据
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
$productsById = [];
foreach ($products as $product) {
    $productsById[$product['id']] = $product;
}

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

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Luke';

$orderPlaced = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // 简单的下单处理：保存订单到 session（示例）并清空购物车
    $_SESSION['last_order'] = [
        'items' => $cartItems,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
        'placed_at' => date('Y-m-d H:i:s')
    ];
    $_SESSION['cart'] = [];
    $orderPlaced = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        .order-page { padding: 2.5rem 0; }
        .order-header { margin-bottom: 1rem; }
        .order-grid { display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; }
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
        @media (max-width: 900px) { .order-grid { grid-template-columns: 1fr; } .btn-row { justify-content: center; } }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container order-page">
        <div class="order-header">
            <h1>Order Confirmation</h1>
            <p>Logged in user: <?php echo htmlspecialchars($username, ENT_QUOTES); ?></p>
        </div>

        <?php if ($orderPlaced): ?>
            <div class="quote-card" style="background:#0f1720;color:#cfe9e6;padding:1.5rem;">
                <h3>Thank you — your order has been placed!</h3>
                <p>Order time: <?php echo htmlspecialchars($_SESSION['last_order']['placed_at'], ENT_QUOTES); ?></p>
                <p>Total: ¥<?php echo number_format($_SESSION['last_order']['total'], 2); ?></p>
                <p><a href="shop.php" class="btn" style="margin-top:0.5rem;display:inline-block;">Continue Shopping</a></p>
            </div>
        <?php else: ?>
            <?php if (empty($cartItems)): ?>
                <div class="quote-card">Your cart is empty. <a href="shop.php">Go shopping</a></div>
            <?php else: ?>
                <div class="order-grid">
                    <div>
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
                    </div>

                    <aside>
                        <div class="summary-card">
                            <div style="margin-bottom:0.5rem;font-weight:600;font-size:1.05rem;">Summary</div>
                            <div class="summary-row"><div>Subtotal</div><div>¥<?php echo number_format($subtotal,2); ?></div></div>
                            <div class="summary-row"><div>Shipping</div><div>¥<?php echo number_format($shipping,2); ?></div></div>
                            <div class="summary-row total"><div>Total</div><div>¥<?php echo number_format($total,2); ?></div></div>

                            <div class="btn-row">
                                <a href="cart.php" class="secondary">Back to Cart Edit</a>
                                <form method="POST" style="display:inline;margin:0;">
                                    <input type="hidden" name="place_order" value="1">
                                    <button type="submit" class="primary">Confirm Order</button>
                                </form>
                            </div>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>
