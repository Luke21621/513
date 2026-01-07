<?php
session_start();

// If already logged in, redirect to specified page or homepage
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    $redirect_url = $_GET['redirect'] ?? 'index.php';
    header('Location: ' . $redirect_url);
    exit;
}

// 获取重定向参数
$redirect_url = $_GET['redirect'] ?? 'index.php';

// Database configuration
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';
$TABLE = 'wpkt_fc_subscribers';

$errors = [];
$login_error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_login'])) {
    $email_input = trim($_POST['email_input'] ?? '');
    $phone_input = trim($_POST['phone_input'] ?? '');

    // Validate required fields
    if (empty($email_input)) {
        $errors[] = 'Please enter your email address.';
    }
    if (empty($phone_input)) {
        $errors[] = 'Please enter your phone number.';
    }

    // If no errors, verify customer credentials
    if (empty($errors)) {
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($mysqli->connect_errno) {
            $login_error = 'Database connection failed.';
        } else {
            $mysqli->set_charset('utf8mb4');

            // Query customer information: login with both email AND phone
            $stmt = $mysqli->prepare("SELECT * FROM `" . $mysqli->real_escape_string($TABLE) . "` WHERE `email` = ? AND `phone` = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $email_input, $phone_input);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        // Login successful, clear admin session variables and set customer session
                        unset($_SESSION['admin_logged_in']);
                        unset($_SESSION['admin_username']);

                        $_SESSION['customer_logged_in'] = true;
                        $_SESSION['customer_email'] = $row['email'] ?? '';
                        $_SESSION['customer_phone'] = $row['phone'] ?? '';
                        $_SESSION['customer_id'] = $row['id'] ?? '';

                        // Save other possible fields
                        if (isset($row['first_name'])) $_SESSION['customer_first_name'] = $row['first_name'];
                        if (isset($row['last_name'])) $_SESSION['customer_last_name'] = $row['last_name'];
                        if (isset($row['name'])) $_SESSION['customer_name'] = $row['name'];

                        $stmt->close();
                        $mysqli->close();

                        // Redirect to specified page or homepage
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        $login_error = 'Invalid email address or phone number combination. Please check and try again.';
                    }
                } else {
                    $login_error = 'Query failed. Please try again later.';
                }
                if (isset($stmt)) {
                    $stmt->close();
                }
            } else {
                $login_error = 'Database query preparation failed.';
            }
            if (isset($mysqli)) {
                $mysqli->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customer Login | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* Page layout */
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
            min-height: calc(100vh - 220px); /* leave space for header/footer */
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
            box-shadow: 0 35px 60px rgba(11, 27, 57, 0.06);
            border: 1px solid #f0f2f5;
            position: relative;
            overflow: visible;
        }

        /* top gradient accent */
        .login-card::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .login-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 0.6rem;
            filter: drop-shadow(0 6px 18px rgba(102, 126, 234, 0.06));
        }

        .login-title {
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            text-align: center;
            font-size: 0.88rem;
            color: #7a7a87;
            margin: 0 0 1.1rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-label { display:block; margin-bottom:6px; color:#4a4a55; font-weight:600; font-size:0.78rem; }
        .form-input {
            width:100%;
            padding:10px 12px;
            border-radius:8px;
            border:1px solid #e9edf2;
            background:#ffffff;
            font-size:0.95rem;
            outline:none;
            box-sizing:border-box;
        }
        .form-input:focus { box-shadow: 0 8px 24px rgba(102,126,234,0.08); border-color:#667eea; transform: translateY(-1px); }

        .btn-login {
            width:100%;
            padding:11px;
            border-radius:8px;
            border:none;
            background:linear-gradient(90deg,#667eea,#764ba2);
            color:#fff;
            font-weight:700;
            cursor:pointer;
            box-shadow: 0 8px 24px rgba(102,126,234,0.12);
            margin-top:8px;
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

        .register-link { text-align:center; margin-top:14px; font-size:0.86rem; color:#777; }
        .register-link a { color:#667eea; font-weight:600; }

        .error-message { margin-bottom: 12px; }

        @media (max-width: 480px) {
            main { padding: 3.5rem 1rem; min-height: calc(100vh - 200px); }
            .login-card { padding: 1.6rem; border-radius: 12px; }
            .login-title { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

	<main>
    <div class="login-container">
        <div class="login-card">
            <div class="login-icon">👤</div>
            <h1 class="login-title">Customer Login</h1>
            <p class="login-subtitle">Customer Login</p>
            
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
            
            <form method="POST" action="customer_login.php">
                <div class="form-group">
                    <label class="form-label" for="email_input">Email Address *</label>
                    <input
                        class="form-input"
                        type="email"
                        id="email_input"
                        name="email_input"
                        placeholder="Enter your email address"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone_input">Phone Number *</label>
                    <input
                        class="form-input"
                        type="tel"
                        id="phone_input"
                        name="phone_input"
                        placeholder="Enter your phone number"
                        required
                        autocomplete="tel"
                    >
                </div>

                <button type="submit" name="customer_login" class="btn-login">Login</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="http://luke3.great-site.net/wordpress/register/">Register Now</a>
            </div>
        </div>
    </div>

	</main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>

