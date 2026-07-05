<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Check if reference is present
if (!isset($_GET['reference'])) {
    die("No transaction reference provided.");
}

$reference = $_GET['reference'];
$secretKey = 'sk_test_9b90f3039ef068b79e0c38ba71c75b964c4ae1dd'; // Replace with your real Paystack Secret Key

// Step 1: Verify transaction with Paystack
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretKey",
        "Cache-Control: no-cache",
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("Curl error: " . $err);
}

$result = json_decode($response, true);

// Step 2: Process the result
if ($result['status'] && $result['data']['status'] === 'success') {
    $order_id = $result['data']['metadata']['order_id'];
    $amount = $result['data']['amount'] / 100; // Convert from kobo
    $user_id = $_SESSION['user_id'];

    // Ensure the order exists and belongs to the logged-in user
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Invalid order.");
    }

    if ($order['payment_status'] === 'success') {
        header("Location: payment.php?order_id=$order_id");
        exit();
    }

    // Mark order as paid and record transaction
    try {
        $db->beginTransaction();

        // Update order
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'success', status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);

        // Insert into transactions
        $transaction_id = $result['data']['id'];
        $status = ['pending', 'success', 'failed'];
        $payment_gateway = ['Paystack', 'Flutter', 'Quick Teller'];
        $stmt = $db->prepare("INSERT INTO transactions (order_id, transaction_id, amount, status, payment_gateway) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $transaction_id, $amount, $status, $payment_gateway]);

        $db->commit();

        header("Location: payment.php?order_id=$order_id");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    die("Transaction failed or invalid reference.");
}
?>
