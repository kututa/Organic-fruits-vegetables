<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

// totals
$totUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totProducts = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totDeliveries = $pdo->query('SELECT COUNT(*) FROM deliveries')->fetchColumn();
$pendingDeliveries = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE delivery_status = 'Pending'")->fetchColumn();
$totalPayments = $pdo->query('SELECT IFNULL(SUM(amount),0) FROM payments')->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-white bg-primary mb-3"><div class="card-body"><h5>Total Users</h5><h3><?= $totUsers ?></h3></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-success mb-3"><div class="card-body"><h5>Total Products</h5><h3><?= $totProducts ?></h3></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-warning mb-3"><div class="card-body"><h5>Pending Deliveries</h5><h3><?= $pendingDeliveries ?></h3></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-info mb-3"><div class="card-body"><h5>Total Payments</h5><h3>$<?= number_format($totalPayments,2) ?></h3></div></div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5>Quick Actions</h5>
    <a class="btn btn-primary" href="products.php">Manage Products</a>
    <a class="btn btn-secondary" href="users.php">Manage Users</a>
    <a class="btn btn-warning" href="deliveries.php">Manage Deliveries</a>
  </div>
</div>


    
</body>
</html>


<?php include __DIR__ . '/includes/footer.php'; ?>


