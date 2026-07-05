<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_goods');

$database = new Database();
$db = $database->getConnection();

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $category_id = intval($_POST['category_id']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $image_url = trim($_POST['image_url']);
    $status = $_POST['status'];
    $availability = $_POST['availability'];
    $delivery_days_min = intval($_POST['delivery_days_min']);
    $delivery_days_max = intval($_POST['delivery_days_max']);

    if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
        $error = "Please fill all required fields with valid values";
    } elseif ($delivery_days_min <= 0 || $delivery_days_max <= 0 || $delivery_days_min > $delivery_days_max) {
        $error = "Please enter valid delivery days (min should be less than or equal to max)";
    } else {
        try {
            $query = "INSERT INTO products (name, description, price, discount_price, category_id, stock_quantity, image_url, status, availability, delivery_days_min, delivery_days_max, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $description, $price, $discount_price, $category_id, $stock_quantity, $image_url, $status, $availability, $delivery_days_min, $delivery_days_max, $_SESSION['user_id']]);
            
            $success = "Product added successfully!";
            
            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $error = "Failed to add product. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Staff Portal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>ShopEasy - Staff Portal</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Product Manager)</span>
                <a href="../../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Products</a></li>
                <li><a href="add-product.php" class="active">Add Product</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="inventory.php">Inventory</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Add New Product</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 600px;">
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="price">Regular Price (₦) *</label>
                            <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo $_POST['price'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_price">Discount Price (₦)</label>
                            <input type="number" name="discount_price" id="discount_price" step="0.01" min="0" value="<?php echo $_POST['discount_price'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" min="0" value="<?php echo $_POST['stock_quantity'] ?? '0'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="availability">Availability *</label>
                        <select name="availability" id="availability" required onchange="updateDeliveryDays()">
                            <option value="store" <?php echo (($_POST['availability'] ?? 'store') == 'store') ? 'selected' : ''; ?>>In Store</option>
                            <option value="shipped" <?php echo (($_POST['availability'] ?? '') == 'shipped') ? 'selected' : ''; ?>>Shipped from Warehouse</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="delivery_days_min">Min Delivery Days *</label>
                            <input type="number" name="delivery_days_min" id="delivery_days_min" min="1" value="<?php echo $_POST['delivery_days_min'] ?? '2'; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="delivery_days_max">Max Delivery Days *</label>
                            <input type="number" name="delivery_days_max" id="delivery_days_max" min="1" value="<?php echo $_POST['delivery_days_max'] ?? '5'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image_url">Image URL</label>
                        <input type="url" name="image_url" id="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function updateDeliveryDays() {
            const availability = document.getElementById('availability').value;
            const minDays = document.getElementById('delivery_days_min');
            const maxDays = document.getElementById('delivery_days_max');
            
            if (availability === 'store') {
                minDays.value = 2;
                maxDays.value = 5;
            } else {
                minDays.value = 5;
                maxDays.value = 10;
            }
        }
    </script>
</body>
</html>
