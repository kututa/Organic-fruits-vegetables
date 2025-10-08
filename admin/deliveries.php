<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $pdo->prepare('UPDATE deliveries SET status = ? WHERE id = ?')->execute([$status,$id]);
    header('Location: deliveries.php'); exit;
}

if (isset($_GET['delete'])) { $pdo->prepare('DELETE FROM deliveries WHERE id = ?')->execute([(int)$_GET['delete']]); header('Location: deliveries.php'); exit; }

$q = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$params = [];
$where = [];
if ($q) { $where[] = "(order_ref LIKE ? OR address LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = $pdo->prepare("SELECT COUNT(*) FROM deliveries $whereSql"); $total->execute($params); $totalRows = $total->fetchColumn();
$offset = ($page-1)*$perPage;
$sql = "SELECT * FROM deliveries $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    
<div class="d-flex justify-content-between mb-3"><h4>Deliveries</h4></div>

<form class="row g-2 mb-3"><div class="col-auto"><input name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search deliveries"></div><div class="col-auto"><button class="btn btn-primary">Search</button></div></form>

<table class="table table-striped"><thead><tr><th>ID</th><th>Order Ref</th><th>Address</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['order_ref']) ?></td>
    <td><?= htmlspecialchars($r['address']) ?></td>
    <td>$<?= number_format($r['amount'],2) ?></td>
    <td><?= htmlspecialchars($r['status']) ?></td>
    <td>
      <form method="post" style="display:inline-block">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <select name="status" class="form-select form-select-sm" style="display:inline-block;width:auto">
          <option <?= $r['status']=='Pending'?'selected':''?>>Pending</option>
          <option <?= $r['status']=='In Transit'?'selected':''?>>In Transit</option>
          <option <?= $r['status']=='Delivered'?'selected':''?>>Delivered</option>
        </select>
        <button class="btn btn-sm btn-secondary" name="update_status">Update</button>
      </form>
      <a class="btn btn-sm btn-danger" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete delivery?')">Delete</a>
    </td>
  </tr>
<?php endforeach; ?>
</tbody></table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


<?php $pages = ceil($totalRows / $perPage); ?><nav><ul class="pagination"><?php for($i=1;$i<=$pages;$i++): ?><li class="page-item <?= $i==$page? 'active':'' ?>"><a class="page-link" href="?page=<?=$i?>&q=<?=urlencode($q)?>"><?=$i?></a></li><?php endfor; ?></ul></nav>

<?php include __DIR__ . '/includes/footer.php'; ?>


