<?php
require_once __DIR__ . '/auth.php';
// don't require admin on login page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/css/bootstrap.min.css" rel="stylesheet">
  <title>Admin Dashboard</title>
  <style>
    body {padding-top:56px}
    .sidebar {min-height:100vh}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarsMain">
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user'])): ?>
          <li class="nav-item"><a class="nav-link" href="#">Hello, <?=htmlspecialchars($_SESSION['user']['username'])?></a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <div class="col-2 bg-light sidebar py-3">
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="deliveries.php">Deliveries</a></li>
        <li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
      </ul>
    </div>
    <div class="col-10 py-3">
