<?php
session_start();

// Ensure cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    if ($id !== null && isset($_SESSION['cart'][$id])) {
        switch ($action) {
            case 'increment':
                $_SESSION['cart'][$id]['quantity'] += 1;
                break;
            case 'decrement':
                $_SESSION['cart'][$id]['quantity'] -= 1;
                if ($_SESSION['cart'][$id]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$id]);
                }
                break;
            case 'remove':
                unset($_SESSION['cart'][$id]);
                break;
        }
    }
}
// Redirect back to cart
header('Location: cart.php');
exit;
