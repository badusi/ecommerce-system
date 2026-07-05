<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();


$order_id = $_GET['order_id'] ?? 0;
$paid = isset($_GET['paid']);
$failed = isset($_GET['failed']);

$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

$payment_success = $paid;
$payment_error = $failed ? "Payment verification failed. Please try again." : "";

// Safely escape PHP for use in JavaScript
$email = htmlspecialchars($_SESSION['email'], ENT_QUOTES);
$full_name = htmlspecialchars($_SESSION['full_name'], ENT_QUOTES);
$amountKobo = intval($order['total_amount']) * 100;
$orderAmountFormatted = number_format($order['total_amount'], 2);
$paystackPublicKey = 'pk_test_8564a9d819cfc70e40596340ce0c884d3483b390';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment - ShopEasy</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="logo"><h1>ShopEasy</h1></div>
      <nav class="nav">
        <span>Welcome, <?= $full_name; ?></span>
        <a href="dashboard.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="../auth/logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <div class="dashboard">
    <main class="content" style="max-width: 600px; margin: 0 auto;">

      <?php if ($payment_success): ?>
        <div style="text-align:center; padding:40px; background:white; border-radius:10px;">
          <h2 style="color:green;">✓ Payment Successful</h2>
          <p>Your order has been placed.</p>
          <p><strong>Order ID:</strong> #<?= $order_id; ?></p>
          <p><strong>Amount:</strong> ₦<?= $orderAmountFormatted; ?></p>
          <a href="orders.php" class="btn btn-primary">View Orders</a>
        </div>

      <?php elseif ($payment_error): ?>
        <div style="text-align:center; padding:40px; background:white; border-radius:10px;">
          <h2 style="color:red;">✗ Payment Failed</h2>
          <p><?= $payment_error; ?></p>
          <a href="payment.php?order_id=<?= $order_id; ?>" class="btn btn-primary">Try Again</a>
        </div>

      <?php else: ?>
        <h2>Pay with Paystack</h2>
        <div style="padding:20px; background:white; border-radius:10px;">
          <p><strong>Order ID:</strong> #<?= $order_id; ?></p>
          <p><strong>Amount:</strong> ₦<?= $orderAmountFormatted; ?></p>
          <p><strong>Method:</strong> Paystack</p>

          <!-- Pay Button -->
          <button id="payBtn" class="btn btn-primary" style="width:100%; padding:15px; font-size:16px;">
            Pay ₦<?= $orderAmountFormatted; ?> with Paystack
          </button>
        </div>
      <?php endif; ?>

    </main>
  </div>

  <!-- Only one script & function -->
  <?php if (!$payment_success && !$payment_error): ?>
  <script src="https://js.paystack.co/v1/inline.js"></script>
  <script>
    document.getElementById('payBtn').addEventListener('click', function () {
      var handler = PaystackPop.setup({
        key: '<?= $paystackPublicKey ?>',
        email: '<?= $email ?>',
        amount: <?= $amountKobo ?>,
        currency: 'NGN',
        ref: "ORD_" + Date.now() + "_<?= $order_id ?>",
        callback: function(response) {
          window.location.href = "verify.php?reference=" + response.reference + "&order_id=<?= $order_id ?>";
        },
        onClose: function() {
          alert('Payment window closed.');
        }
      });
      handler.openIframe();
    });
  </script>
  <?php endif; ?>
</body>
</html>
