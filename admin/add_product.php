<?php
include "../includes/db.php";

if ($_POST) {
  $name = $_POST['name'];
  $price = $_POST['price'];
  $description = $_POST['description'];
  $image = $_POST['image'];
  $category = $_POST['category'];

  $conn->query("INSERT INTO products(name,description,price,image,category_id)
  VALUES('$name','$description',$price,'$image',$category)");

  header("Location: products.php");
}
?>

<form method="POST">
  <input name="name" placeholder="Perfume name">
  <textarea name="description" placeholder="Description"></textarea>
  <input name="price" placeholder="Price in XAF">
  <input name="image" placeholder="Image URL">
  <select name="category">
    <?php
    $cats = $conn->query("SELECT * FROM categories");
    while($c = $cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
    ?>
  </select>
  <button>Add Product</button>
</form>
