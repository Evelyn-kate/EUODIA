<?php include "../includes/header.php"; include "../includes/db.php"; ?>
<link rel="stylesheet" href="style.css">
<div class="hero">
  <h1>Euodia Scents</h1>
  <p>Luxury Decant Perfumes — Authentic & Affordable</p>
</div>

<form class="search-bar" action="search.php" method="GET">
  <input name="q" placeholder="Search perfumes...">
  <input name="min" type="number" placeholder="Min XAF">
  <input name="max" type="number" placeholder="Max XAF">
  <button>Search</button>
</form>
<h2 class="section-head">Browse Categories</h2>
<div class="categories-container">
<div class='category-card'>
  <a href='category.php'>
    <h3>All Categories</h3>
  </a>
</div>
<?php
$cats_all=$conn->query("SELECT id, name FROM categories");
while($cat=$cats_all->fetch_assoc()){
  echo "
  <div class='category-card'>
    <a href='category.php?id={$cat['id']}'>
      <h3>{$cat['name']}</h3>
    </a>
  </div>";
}
?>
</div>
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
