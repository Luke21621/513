<?php
/**
 * subscribers.php
 *
 * 仅管理员登录后可见；读取订阅者列表。
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 重定向到登录页面，并传递重定向参数
    $redirect_url = 'subscribers.php';
    header('Location: admin_login.php?redirect=' . urlencode($redirect_url));
    exit;
}

// MySQL 配置
$DB_HOST = 'sql207.infinityfree.com';
$DB_NAME = 'if0_39945182_wp995';
$DB_USER = 'if0_39945182';
$DB_PASS = 'NIX0x5WK67c';

// 默认表名
$TABLE = 'wpkt_fc_subscribers';

// 连接数据库
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "<p>无法连接数据库: (" . $mysqli->connect_errno . ") " . htmlspecialchars($mysqli->connect_error) . "</p>";
    exit;
}

// 设置字符集
$mysqli->set_charset('utf8mb4');

// 读取表中所有数据（可根据需要添加分页/限制）
$sql = "SELECT * FROM `" . $mysqli->real_escape_string($TABLE) . "`";
$result = $mysqli->query($sql);
if (!$result) {
    http_response_code(500);
    echo "<p>查询失败: " . htmlspecialchars($mysqli->error) . "</p>";
    exit;
}

// 通用字段候选名（按优先级）
$first_candidates = ['first_name','firstname','fname','given_name','givenname','forename'];
$last_candidates  = ['last_name','lastname','lname','surname','family_name','familyname'];
$email_candidates = ['email','user_email','e-mail','mail','email_address'];
$phone_candidates = ['phone','phone_number','mobile','mobile_number','telephone','tel','contact','contact_number'];
$fullname_candidates = ['name','fullname','full_name','display_name','subscriber_name'];

function find_value($row, $candidates) {
    foreach ($candidates as $c) {
        if (array_key_exists($c, $row)) return $row[$c];
    }
    // 尝试不区分大小写匹配
    $lowerKeys = array_change_key_case($row, CASE_LOWER);
    foreach ($candidates as $c) {
        $lc = strtolower($c);
        if (array_key_exists($lc, $lowerKeys)) return $lowerKeys[$lc];
    }
    return null;
}

// HTML 输出头部
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List | GameHub</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        .customer-list-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .customers-table th {
            background: #f8f9fa;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid var(--border);
        }
        
        .customers-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .customers-table tr:last-child td {
            border-bottom: none;
        }
        
        .customers-table tr:hover td {
            background-color: #f8f9fa;
        }
        
        .table-footer {
            margin-top: 20px;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main>
        <div class="customer-list-container">
            <div class="page-header">
                <h1>Customer List</h1>
                <p>Data from table: <code><?php echo htmlspecialchars($TABLE); ?></code></p>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rownum = 0;
                        while ($row = $result->fetch_assoc()) {
                            $rownum++;
                            // 直接尝试找到 email 和 phone
                            $email = find_value($row, $email_candidates);
                            $phone = find_value($row, $phone_candidates);
                            // 尝试找到 first 和 last
                            $first = find_value($row, $first_candidates);
                            $last  = find_value($row, $last_candidates);
                            // 如果没有单独的 first/last，尝试 fullname 并拆分
                            if ((empty($first) || empty($last)) && ($fullname = find_value($row, $fullname_candidates))) {
                                $fullname = trim($fullname);
                                // 简单拆分：以最后一个空格分割为名与姓（适用于"名 姓"或"名中间 姓"）
                                if (strpos($fullname, ' ') !== false) {
                                    $parts = preg_split('/\s+/', $fullname);
                                    $last = array_pop($parts);
                                    $first = implode(' ', $parts);
                                } else {
                                    // 无空格就放到 first
                                    $first = $fullname;
                                }
                            }

                            // 作为最后手段，尝试查找任何可能的键名中含有 first/last/email/phone 的值
                            if (empty($email)) {
                                foreach ($row as $k => $v) {
                                    if (stripos($k, 'mail') !== false) { $email = $v; break; }
                                }
                            }
                            if (empty($phone)) {
                                foreach ($row as $k => $v) {
                                    if (stripos($k, 'phone') !== false || stripos($k, 'mobile') !== false || stripos($k, 'tel') !== false) { $phone = $v; break; }
                                }
                            }
                            if (empty($first) || empty($last)) {
                                foreach ($row as $k => $v) {
                                    if (empty($first) && (stripos($k,'first')!==false || stripos($k,'given')!==false || stripos($k,'forename')!==false)) $first = $v;
                                    if (empty($last) && (stripos($k,'last')!==false || stripos($k,'surname')!==false || stripos($k,'family')!==false)) $last = $v;
                                }
                            }

                            // 输出表格行
                            echo "<tr>";
                            echo "<td>" . $rownum . "</td>";
                            echo "<td>" . htmlspecialchars($last ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($first ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($email ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($phone ?? '') . "</td>";
                            echo "</tr>\n";
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="table-footer">
                    <p>Total customers: <?php echo $result->num_rows; ?></p>
                    <p class="muted">Data source: <?php echo htmlspecialchars($DB_HOST); ?>, database <?php echo htmlspecialchars($DB_NAME); ?></p>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>No customer data found in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>

<?php
if (isset($result) && $result) {
    $result->free();
}
if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}
?>