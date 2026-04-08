<?php
include "../includes/db.php";
$id=$_GET['id'];
$p=$conn->query("SELECT id,name,price FROM products WHERE id=$id")->fetch_assoc();
echo json_encode($p);
?>
