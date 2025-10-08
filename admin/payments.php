<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page-1)*$perPage;
$totalPayments = $pdo->query('SELECT IFNULL(SUM(amount),0) FROM payments')->fetchColumn();
$stmt = $pdo->prepare('SELECT * FROM payments ORDER BY paid_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$perPage,$offset]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <h4>Payments</h4>
<div class="mb-3">Total Received: <strong>$<?= number_format($totalPayments,2) ?></strong></div>
<table class="table"><thead><tr><th>ID</th><th>Order Ref</th><th>Amount</th><th>Date</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
  <tr><td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['order_ref']) ?></td><td>$<?= number_format($r['amount'],2) ?></td><td><?= $r['created_at'] ?></td></tr>
<?php endforeach; ?></tbody></table>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>

<?php include __DIR__ . '/includes/footer.php'; ?>
