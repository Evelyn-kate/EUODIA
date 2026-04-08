<?php
include "../includes/db.php";
$id = $_GET['id'];
$p = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
?>

<h1><?php echo $p['name']; ?></h1>
<img src="<?php  #echo $p['image']; ?>">


<?php
// Folder containing images
$folder = "images/";

// Scan the folder
$images = array_diff(scandir($folder), array('.', '..'));

echo '<h1>Our Products</h1>';
echo '<div style="display:flex; flex-wrap: wrap;">';

foreach ($images as $img) {
    $path = $folder . $img;

    if (is_file($path)) {
        echo '<div style="margin:10px; text-align:center;">';
        echo '<img src="' . htmlspecialchars($path) . '" style="width:200px; height:auto;"><br>';
        echo '<span>' . htmlspecialchars(pathinfo($img, PATHINFO_FILENAME)) . '</span>'; // Product name from filename
        echo '</div>';
    }
}

echo '</div>';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $p['name']; ?> — Euodia Peace Scents</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #0d0d0d;
      color: #eee;
    }

    .product-page {
      max-width: 1100px;
      margin: 40px auto;
      padding: 20px;
      display: flex;
      gap: 40px;
      flex-wrap: wrap;
    }

    .product-image {
      flex: 1;
      min-width: 300px;
      max-width: 500px;
    }

    .product-image img {
      width: 100%;
      border-radius: 12px;
      border: 1px solid #333;
      box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }

    .product-details {
      flex: 1;
      min-width: 300px;
    }

    .breadcrumb {
      font-size: 0.85em;
      color: #888;
      margin-bottom: 15px;
    }

    .breadcrumb a {
      color: #d4af37;
      text-decoration: none;
    }

    .breadcrumb a:hover {
      text-decoration: underline;
    }

    .product-title {
      font-size: 2em;
      color: #d4af37;
      margin-bottom: 10px;
      letter-spacing: 1px;
    }

    .product-category {
      font-size: 0.95em;
      color: #888;
      margin-bottom: 20px;
    }

    .product-price {
      font-size: 1.8em;
      color: #fff;
      margin-bottom: 25px;
    }

    .product-price span {
      color: #d4af37;
      font-weight: bold;
    }

    .product-description {
      color: #bbb;
      line-height: 1.7;
      margin-bottom: 30px;
      font-size: 1em;
    }

    .quantity-selector {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
    }

    .quantity-selector label {
      color: #ccc;
      font-size: 0.95em;
    }

    .quantity-selector input {
      width: 70px;
      padding: 10px;
      border: 1px solid #333;
      border-radius: 6px;
      background: #1a1a1a;
      color: #eee;
      font-size: 1em;
      text-align: center;
    }

    .quantity-selector input:focus {
      outline: none;
      border-color: #d4af37;
    }

    .btn-add {
      display: inline-block;
      padding: 16px 50px;
      background: linear-gradient(135deg, #d4af37, #b8962e);
      border: 2px solid transparent;
      border-radius: 12px;
      color: #1a1a1a;
      font-size: 1.15em;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);
      letter-spacing: 0.5px;
      position: relative;
      overflow: hidden;
    }

    .btn-add::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.2);
      transition: left 0.3s ease;
    }

    .btn-add:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 35px rgba(212, 175, 55, 0.5);
      background: linear-gradient(135deg, #e8c547, #c4a536);
    }

    .btn-add:hover::before {
      left: 100%;
    }

    .btn-add:active {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
    }

    .product-meta {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #333;
    }

    .product-meta p {
      font-size: 0.9em;
      color: #777;
      margin-bottom: 8px;
    }

    .product-meta span {
      color: #aaa;
    }

    .back-link {
      display: inline-block;
      margin-top: 25px;
      color: #d4af37;
      text-decoration: none;
      font-size: 0.95em;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    .not-found {
      text-align: center;
      padding: 100px 20px;
      color: #888;
      font-size: 1.2em;
    }

    @media (max-width: 768px) {
      .product-page {
        flex-direction: column;
        align-items: center;
      }

      .product-image, .product-details {
        max-width: 100%;
      }

      .product-title {
        font-size: 1.6em;
      }

      .product-price {
        font-size: 1.5em;
      }
    }
  </style>
</head>
<body>

<div class="product-page">

  <div class="product-image">
    <img src="<?php echo $p['image']; ?>" alt="<?php echo $p['name']; ?>">
  </div>

  <div class="product-details">

    <p class="breadcrumb">
      <a href="index.php">Home</a> / 
      <a href="search.php?cat=<?php echo $p['category_id'] ?? ''; ?>"><?php echo $p['category'] ?? 'Uncategorized'; ?></a> / 
      <?php echo $p['name']; ?>
    </p>

    <h1 class="product-title"><?php echo $p['name']; ?></h1>

    <p class="product-category"><?php echo $p['category'] ?? 'Uncategorized'; ?></p>

    <p class="product-price"><span><?php echo number_format($p['price']); ?></span> XAF</p>

    <p class="product-description">
      <?php echo $p['description'] ?: "Experience the essence of luxury with this exquisite fragrance from Euodia Peace Scents. Crafted for those who appreciate elegance and sophistication."; ?>
    </p>

    <div class="quantity-selector">
      <label for="qty">Quantity:</label>
      <input type="number" id="qty" value="1" min="1" max="10">
    </div>

    <button class="btn-add add-to-cart-btn" onclick="addToCart(<?php echo $p['id']; ?>)">Add to Cart</button>

    <div class="product-meta">
      <p><strong>SKU:</strong> <span>EU-<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></span></p>
      <p><strong>Category:</strong> <span><?php echo $p['category'] ?? 'Uncategorized'; ?></span></p>
      <p><strong>Availability:</strong> <span style="color:#6bff6b;">In Stock</span></p>
    </div>

    <a href="index.php" class="back-link">← Continue Shopping</a>

  </div>

</div>

<script src="script.js"></script>

<?php include "../includes/footer.php"; ?>

</body>
</html>
