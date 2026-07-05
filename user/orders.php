<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Get user orders
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
          CASE 
            WHEN o.status = 'delivered' THEN 'Delivered'
            WHEN o.status = 'shipped' THEN CONCAT('Shipped - ', o.estimated_delivery_days, ' days remaining')
            WHEN o.status = 'processing' THEN 'Processing'
            WHEN o.status = 'pending' THEN 'Pending'
            ELSE 'Cancelled'
          END as status_text
          FROM orders o 
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ShopEasy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>ShopEasy</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="dashboard.php">Products</a>
                <a href="cart.php">Cart</a>
                <a href="chat.php">Support</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Products</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
                <li><a href="orders.php" class="active">My Orders</a></li>
                <li><a href="chat.php">Customer Support</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>My Orders</h2>
            
            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 50px;">
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here</p>
                    <a href="dashboard.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['item_count']; ?> items</td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo $order['status_text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>c
    <?php if (isset($_GET['status'])): ?>
    <script>
        window.onload = function () {
            <?php if ($_GET['status'] === 'success'): ?>
                alert("✅ Payment Successful! Your order has been placed.");
            <?php elseif ($_GET['status'] === 'failed'): ?>
                alert("❌ Payment Failed. Please try again.");
            <?php endif; ?>
        };
    </script>
    <?php endif; ?>
</body>
</html>
