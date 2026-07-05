<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['reference']) || !isset($_GET['order_id'])) {
    header("Location: orders.php?status=failed");
    exit();
}

$reference = $_GET['reference'];
$order_id = intval($_GET['order_id']);

// Verify payment with Paystack
$paystack_secret = "sk_test_9b90f3039ef068b79e0c38ba71c75b964c4ae1dd"; // replace with your secret key
$url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $paystack_secret"
]);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $result = json_decode($response, true);

    if ($result['status'] && $result['data']['status'] === 'success') {
        // ✅ Payment confirmed - update DB
        $query = "UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id, $_SESSION['user_id']]);

        header("Location: orders.php?status=success");
        exit();
    }
}

// ❌ If we reach here → failed
$query = "UPDATE orders SET payment_status = 'failed' WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);

// After curl_exec
$response = curl_exec($ch);
curl_close($ch);

file_put_contents("paystack_debug.log", $response); // log response for debugging


header("Location: orders.php?status=failed");
exit();
