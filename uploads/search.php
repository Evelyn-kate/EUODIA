<?php include "../includes/header.php"; include "../includes/db.php";

$q = trim($_GET['q']??'');
$cat = trim($_GET['cat']??'');
$min = (int)($_GET['min']??0);
$max = (int)($_GET['max']??0);

$cond=[];
if($q)   $cond[]="name LIKE '%$q%'";
if($cat) $cond[]="category_id=$cat";
if($min) $cond[]="price >= $min";
if($max) $cond[]="price <= $max";

$sql="SELECT * FROM products";
if($cond) $sql.=" WHERE ".implode(" AND ",$cond);

$res=$conn->query($sql);
?>
<link rel="stylesheet" href="style.css">
<h2>Search Results</h2>
<div class="products">
<?php
if(!$res->num_rows) echo "<p>No products found.</p>";
while($p=$res->fetch_assoc()){
 echo "<div class='product'>
        <img src='{$p['image']}'>
        <h3>{$p['name']}</h3>
        <p class='price'>{$p['price']} XAF</p>
        <a class='btn' href='product.php?id={$p['id']}'>View</a>
      </div>";
}
?>
</div>


<?php include "../includes/footer.php"; ?>
