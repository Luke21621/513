<?php
session_start();

// 清除客户登录会话
unset($_SESSION['customer_logged_in']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);
unset($_SESSION['customer_id']);
unset($_SESSION['customer_first_name']);
unset($_SESSION['customer_last_name']);
unset($_SESSION['customer_name']);

// 销毁会话（可选，如果只想清除客户相关会话，可以保留其他会话）
// session_destroy();

// 重定向到指定页面或首页
$redirect_url = $_GET['redirect'] ?? 'index.php';
header('Location: ' . $redirect_url);
exit;
?>

