<?php
// Get the current script path to determine correct navigation links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - ShopEasy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><a href="../index.html" style="color: #3498db; text-decoration: none;">ShopEasy</a></h1>
            </div>
            <nav class="nav">
                <a href="login.php">Login</a>
                <a href="../index.html">Home</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div style="text-align: center; padding: 100px 20px; max-width: 600px; margin: 0 auto;">
            <div style="font-size: 6rem; color: #e74c3c; margin-bottom: 20px;">🚫</div>
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Access Denied</h2>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
                You don't have permission to access this page. Please contact your administrator if you believe this is an error.
            </p>
            <div>
                <a href="login.php" class="btn btn-primary" style="margin-right: 10px;">Login</a>
                <a href="../index.html" class="btn btn-secondary">Go Home</a>
            </div>
        </div>
    </main>
</body>
</html>
