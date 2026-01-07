<?php
session_start();
$errors = [];
$values = ['username'=>'','email'=>'','phone'=>'','address'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = trim($_POST['username'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['address'] = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($values['username'] === '') $errors[] = 'Username is required.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $success = true;
        // 实际项目中应把用户信息写入数据库并对密码进行哈希
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        /* 页面内少量补充：确保注册页样式生效（可移至 style.css） */
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main>
        <section class="auth-section">
            <div class="container">
                <div class="auth-card">
                    <h2>Register</h2>
                    <?php if (!empty($errors)) : ?>
                        <div class="form-errors">
                            <ul>
                                <?php foreach ($errors as $e) : ?>
                                    <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!empty($success)) : ?>
                        <div class="form-success">Account created (demo). In a real app you would be redirected or logged in.</div>
                    <?php endif; ?>

                    <form method="post" action="register.php" novalidate>
                        <div class="form-row">
                            <label class="form-label">Username</label>
                            <input class="form-input" type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($values['username'], ENT_QUOTES); ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-label">Email</label>
                            <input class="form-input" type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES); ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-label">Phone Number</label>
                            <input class="form-input" type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($values['phone'], ENT_QUOTES); ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-label">Address</label>
                            <input class="form-input" type="text" name="address" placeholder="Address" value="<?php echo htmlspecialchars($values['address'], ENT_QUOTES); ?>">
                        </div>

                        <div class="form-row">
                            <label class="form-label">Password</label>
                            <input class="form-input" type="password" name="password" placeholder="Password">
                        </div>

                        <div class="form-row">
                            <label class="form-label">Confirm Password</label>
                            <input class="form-input" type="password" name="confirm_password" placeholder="Confirm Password">
                        </div>

                        <div style="margin-top:1rem;">
                            <button class="btn btn--primary-gradient" type="submit">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>
