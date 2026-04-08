<?php include "../includes/header.php"; include "../includes/db.php"; ?>
<link rel="stylesheet" href="style.css">
<div class="hero">
  <h1>Euodia Scents</h1>
  <p>Luxury Decant Perfumes — Authentic & Affordable</p>
</div>

<form class="search-bar" action="search.php" method="GET">
  <input name="q" placeholder="Search perfumes...">
  <select name="cat">
    <option value="">All Categories</option>
    <?php
    $cats=$conn->query("SELECT * FROM categories");
    while($c=$cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
    ?>
  </select>
  <input name="min" type="number" placeholder="Min XAF">
  <input name="max" type="number" placeholder="Max XAF">
  <button>Search</button>
</form>

<h2 class="section-head">Our Collection</h2>
<div class="products">
<?php
$q="SELECT * FROM products LIMIT 12";
$r=$conn->query($q);
while($p=$r->fetch_assoc()){
  echo "
  <div class='product' data-id='{$p['id']}' data-name='{$p['name']}' data-price='{$p['price']}'>
     <img src='{$p['image']}' alt=''>
     <h3>{$p['name']}</h3>
     <p class='price'>{$p['price']} XAF</p>
     <a class='btn' href='products.php?id={$p['id']}'>View Details</a>
  </div>";
}
?>
</div>
<script src="script.js"></script>
<?php include "../includes/footer.php"; ?>
