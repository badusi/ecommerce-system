<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get comprehensive statistics
$stats = [];

// User statistics
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Staff statistics
$query = "SELECT COUNT(*) as count FROM users WHERE role IN ('staff_goods', 'staff_cs')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Product statistics
$query = "SELECT COUNT(*) as count FROM products WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Order statistics
$query = "SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders WHERE payment_status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['completed_orders'] = $order_stats['count'];
$stats['total_revenue'] = $order_stats['revenue'] ?? 0;

// Monthly revenue (last 6 months)
$monthly_revenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $query = "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND payment_status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute([$month]);
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    $monthly_revenue[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'revenue' => $revenue
    ];
}

// Top selling products
$query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          JOIN orders o ON oi.order_id = o.id 
          WHERE o.payment_status = 'completed'
          GROUP BY p.id, p.name 
          ORDER BY total_sold DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent registrations
$query = "SELECT full_name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>ShopEasy - Admin Panel</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Administrator)</span>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="staff.php">Staff</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php" class="active">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>System Reports & Analytics</h2>
            
            <!-- Overview Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_users']; ?></h3>
                    <p style="color: #666;">Total Users</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_staff']; ?></h3>
                    <p style="color: #666;">Staff Members</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['active_products']; ?></h3>
                    <p style="color: #666;">Active Products</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['completed_orders']; ?></h3>
                    <p style="color: #666;">Completed Orders</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; grid-column: span 2;">
                    <h3 style="color: #9b59b6; font-size: 2rem; margin-bottom: 10px;">₦<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p style="color: #666;">Total Revenue</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Monthly Revenue -->
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Monthly Revenue (Last 6 Months)</h3>
                    <div style="margin-top: 20px;">
                        <?php foreach ($monthly_revenue as $month): ?>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                                <span><?php echo $month['month']; ?></span>
                                <span style="font-weight: bold; color: #27ae60;">₦<?php echo number_format($month['revenue'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top Products -->
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Top Selling Products</h3>
                    <div style="margin-top: 20px;">
                        <?php foreach ($top_products as $index => $product): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                                <div>
                                    <span style="background: #3498db; color: white; padding: 2px 8px; border-radius: 50%; font-size: 0.8rem; margin-right: 10px;"><?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: bold;"><?php echo $product['total_sold']; ?> sold</div>
                                    <div style="color: #27ae60; font-size: 0.9rem;">₦<?php echo number_format($product['revenue'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent User Registrations -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Recent User Registrations</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
