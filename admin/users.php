<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

// change status or delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]);
  header('Location: users.php'); exit;
}
if (isset($_POST['change_status'])) {
  $id = (int)$_POST['id'];
  $status = $_POST['status'];
  $pdo->prepare('UPDATE users SET account_status = ? WHERE user_id = ?')->execute([$status,$id]);
  header('Location: users.php'); exit;
}

$q = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$params = [];
$where = [];
if ($q) { $where[] = "(name LIKE ? OR email LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql"); $total->execute($params); $totalRows = $total->fetchColumn();
$offset = ($page-1)*$perPage;

$sql = "SELECT * FROM users $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($users_raw as $row) {
  $users[] = [
    'id' => $row['user_id'],
    'username' => $row['name'],
    'email' => $row['email'],
    'role' => $row['role'],
    'status' => $row['account_status'],
  ];
}

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
  <div class="d-flex justify-content-between mb-3"><h4>Users</h4></div>

<form class="row g-2 mb-3"><div class="col-auto"><input name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search users"></div><div class="col-auto"><button class="btn btn-primary">Search</button></div></form>

<table class="table table-striped"><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($users as $u): ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['role']) ?></td>
    <td><?= htmlspecialchars($u['status']) ?></td>
    <td>
      <form method="post" style="display:inline-block">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <select name="status" class="form-select form-select-sm" style="display:inline-block;width:auto">
          <option <?= $u['status']=='active'?'selected':''?>>active</option>
          <option <?= $u['status']=='inactive'?'selected':''?>>inactive</option>
        </select>
        <button class="btn btn-sm btn-secondary" name="change_status">Update</button>
      </form>
      <a class="btn btn-sm btn-danger" href="?delete=<?= $u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
    </td>
  </tr>
<?php endforeach; ?>
</tbody></table>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>




<?php $pages = ceil($totalRows / $perPage); ?><nav><ul class="pagination"><?php for($i=1;$i<=$pages;$i++): ?><li class="page-item <?= $i==$page? 'active':'' ?>"><a class="page-link" href="?page=<?=$i?>&q=<?=urlencode($q)?>"><?=$i?></a></li><?php endfor; ?></ul></nav>

<?php include __DIR__ . '/includes/footer.php'; ?>

