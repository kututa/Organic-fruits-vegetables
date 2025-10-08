<?php
session_start();
// Add to cart logic for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $image = isset($_POST['image']) ? $_POST['image'] : '';
    if ($id > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'quantity' => 1
            ];
        }
    }
    // Redirect to index.php to avoid form resubmission
    header('Location: index.php');
    exit;
}
// If not POST, redirect to index.php
header('Location: index.php');
exit;
