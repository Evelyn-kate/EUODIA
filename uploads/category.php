<?php include "../includes/header.php"; include "../includes/db.php"; ?>
<link rel="stylesheet" href="style.css">

<?php
$cat_id = $_GET['id'] ?? 0;
$cat_id = intval($cat_id);

// Determine if viewing all categories or specific category
$show_all = empty($cat_id);

if ($show_all) {
    // Show all products
    $category_name = "All Categories";
    $products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");
} else {
    // Get specific category details
    $cat_result = $conn->query("SELECT * FROM categories WHERE id = $cat_id");
    $category = $cat_result->fetch_assoc();

    if (!$category) {
        echo "<div style='text-align:center;padding:40px;color:#d4af37;'><h2>Category not found</h2><a href='index.php' style='color:#d4af37;'>← Back to Home</a></div>";
        include "../includes/footer.php";
        exit;
    }

    $category_name = htmlspecialchars($category['name']);
    // Get products in this category
    $products_result = $conn->query("SELECT * FROM products WHERE category_id = $cat_id ORDER BY id DESC");
}
?>

<div class="hero" style="padding:30px 0;text-align:center;">
    <h1><?php echo $category_name; ?></h1>
    <p style="color:#999;margin:10px 0;">Browse our <?php echo strtolower($category_name); ?></p>
    <a href="index.php" style="color:#d4af37;text-decoration:none;">← Back to Home</a>
</div>

<div class="products">
<?php
if ($products_result->num_rows > 0) {
    while($p = $products_result->fetch_assoc()) {
        echo "
        <div class='product' data-id='{$p['id']}' data-name='{$p['name']}' data-price='{$p['price']}'>
            <img src='{$p['image']}' alt='{$p['name']}'>
            <h3>{$p['name']}</h3>
            <p class='price'>{$p['price']} XAF</p>
            <a class='btn' href='products.php?id={$p['id']}'>View Details</a>
        </div>";
    }
} else {
    echo "<div style='grid-column:1/-1;text-align:center;padding:40px;color:#999;'>No products in this category yet.</div>";
}
?>
</div>

<script src="script.js"></script>
<?php include "../includes/footer.php"; ?>
