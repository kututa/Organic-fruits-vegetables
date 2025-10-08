<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

// helper: get columns of products table to avoid schema errors
function get_table_columns_admin($pdo, $table){
  $cols = [];
  try{
    $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`','``',$table) . "`");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cols[] = $r['Field'];
  }catch(Exception $e){
    return [];
  }
  return $cols;
}
$productColumns = get_table_columns_admin($pdo, 'products');

// detect primary key (or a unique key) for products table
function get_table_primary_key($pdo, $table){
  try{
    $stmt = $pdo->query("SHOW KEYS FROM `".str_replace('`','``',$table)."` WHERE Key_name = 'PRIMARY'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['Column_name'])) return $row['Column_name'];
    // fallback: find any unique index
    $stmt2 = $pdo->query("SHOW INDEX FROM `".str_replace('`','``',$table)."` WHERE Non_unique = 0");
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($row2 && !empty($row2['Column_name'])) return $row2['Column_name'];
  }catch(Exception $e){ }
  return null;
}
$productPk = get_table_primary_key($pdo, 'products');

// detect sensible DB column names for fields (map form names to DB columns)
$dbColFor = function(array $cands) use ($productColumns){
  foreach($cands as $cand) if (in_array($cand, $productColumns)) return $cand;
  return null;
};

$dbImageCol = $dbColFor(['image','image_path','imagepath','photo','img','filepath','image_url','imagefile']);
$dbCategoryCol = $dbColFor(['category','category_id','cat_id']);
$dbStockCol = $dbColFor(['stock','quantity','qty']);
$dbNameCol = $dbColFor(['name','title']);
$dbDescCol = $dbColFor(['description','short_description','desc']);
$dbPriceCol = $dbColFor(['price','cost']);
$dbStatusCol = $dbColFor(['status','active']);

// build mapping from form input name => db column
$fieldMap = [];
if ($dbNameCol) $fieldMap['name'] = $dbNameCol;
if ($dbDescCol) $fieldMap['description'] = $dbDescCol;
if ($dbCategoryCol) $fieldMap['category'] = $dbCategoryCol;
if ($dbPriceCol) $fieldMap['price'] = $dbPriceCol;
// Use correct stock column
$dbStockCol = $dbColFor(['stock_quantity','stock','quantity','qty']);
if ($dbStockCol) $fieldMap['stock'] = $dbStockCol;
if ($dbStatusCol) $fieldMap['status'] = $dbStatusCol;
// Use correct image column
$dbImageCol = $dbColFor(['image_path','image','imagepath','photo','img','filepath','image_url','imagefile']);
if ($dbImageCol) $fieldMap['image'] = $dbImageCol;

// start session for flash messages
if (session_status() === PHP_SESSION_NONE) session_start();

// flash helper
function set_flash($msg, $type = 'success'){
  $_SESSION['flash_message'] = $msg;
  $_SESSION['flash_type'] = $type;
}
function get_flash(){
  if (!isset($_SESSION['flash_message'])) return null;
  $m = ['msg'=>$_SESSION['flash_message'],'type'=>$_SESSION['flash_type']??'success'];
  if (isset($_SESSION['force_delete_url'])){ $m['force_delete_url'] = $_SESSION['force_delete_url']; unset($_SESSION['force_delete_url']); }
  unset($_SESSION['flash_message'], $_SESSION['flash_type']);
  return $m;
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_action'])) {
  // dynamic POST handling with validation
  $id = $_POST['id'] ?? null;

  // image validation and unique filename generation
  $imagePath = null;
  $uploadedNewImage = false;
  if (!empty($_FILES['image']['name'])) {
    $allowedExt = ['jpg','jpeg','png','webp'];
    $tmp = $_FILES['image']['tmp_name'];
    $origName = $_FILES['image']['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : null;
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg','image/png','image/webp'];
    if (!in_array($ext, $allowedExt) || ($mime && !in_array($mime, $allowedMimes))) {
      set_flash('Invalid image type. Allowed: jpg,jpeg,png,webp', 'danger');
      header('Location: products.php'); exit;
    }
    $uploadsDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
    $unique = time() . '_' . bin2hex(random_bytes(6));
    $fname = $unique . '.' . $ext;
    if (move_uploaded_file($tmp, $uploadsDir . $fname)){
      // store web-accessible relative path
      $imagePath = 'uploads/products/' . $fname;
      $uploadedNewImage = true;
    } else {
      set_flash('Failed to move uploaded file.', 'danger');
      header('Location: products.php'); exit;
    }
  }

  // map form fields to actual DB columns using $fieldMap
  $fields = [];
  $values = [];
  foreach(['name','description','category','price','stock','status'] as $formField){
    if (!isset($fieldMap[$formField])) continue;
    $dbCol = $fieldMap[$formField];

    // Special handling when DB expects a category_id (foreign key)
    if ($formField === 'category' && $dbCol && (stripos($dbCol, '_id') !== false || strtolower($dbCol) === 'category_id')){
      $catInput = trim((string)($_POST['category'] ?? ''));
      $catId = null;
      if ($catInput !== ''){
        try{
          // if numeric, verify exists in categories table
          if (is_numeric($catInput)){
            $chk = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ? LIMIT 1');
            $chk->execute([(int)$catInput]);
            $found = $chk->fetch(PDO::FETCH_ASSOC);
            if ($found) $catId = (int)$found['category_id'];
          }
          // if not numeric or not found, try to find by category_name (case-insensitive)
          if ($catId === null){
            $chk2 = $pdo->prepare('SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1');
            $chk2->execute([$catInput]);
            $found2 = $chk2->fetch(PDO::FETCH_ASSOC);
            if ($found2) {
              $catId = (int)$found2['category_id'];
            } else {
              // create new category (best-effort)
              $ins = $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)');
              $ins->execute([$catInput]);
              $catId = (int)$pdo->lastInsertId();
            }
          }
        }catch(Exception $e){
          $catId = null;
        }
      }
      $fields[] = $dbCol;
      $values[] = $catId === null ? null : $catId;
      continue;
    }

    if (isset($_POST[$formField])){
      $fields[] = $dbCol;
      $values[] = $_POST[$formField];
    }
  }
  // image
  if ($dbImageCol && $uploadedNewImage && $imagePath !== null){
    $fields[] = $dbImageCol;
    $values[] = $imagePath;
  }

  // update: if editing and a new image replaced the old one, delete old image file
  if ($id && $productPk){
    if (!empty($fields)){
      // if image being updated, find previous image to delete
      if ($uploadedNewImage && in_array('image', $productColumns)){
        $get = $pdo->prepare('SELECT `image` FROM `products` WHERE `'.str_replace('`','',$productPk).'` = ?');
        $get->execute([$id]);
        $prev = $get->fetch(PDO::FETCH_ASSOC);
        if ($prev && !empty($prev['image'])){
          $prevPath = __DIR__ . '/../' . ltrim($prev['image'], '/');
          if (is_file($prevPath)) @unlink($prevPath);
        }
      }
  $set = implode(', ', array_map(function($c){ return "`$c` = ?"; }, $fields));
      $sql = "UPDATE `products` SET $set WHERE `".str_replace('`','',$productPk)."` = ?";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array_merge($values, [$id]));
      set_flash('Product updated successfully', 'success');
    }
  } else {
    if (!empty($fields)){
      $colsSql = implode(', ', array_map(function($c){ return "`$c`"; }, $fields));
      $placeholders = rtrim(str_repeat('?,', count($fields)), ',');
      $sql = "INSERT INTO `products` ($colsSql) VALUES ($placeholders)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($values);
      set_flash('Product added successfully', 'success');
    } else {
      set_flash('No product fields matched database columns. Nothing was saved.', 'warning');
    }
  }
  // cleanup orphaned images after changes
  if (is_dir(__DIR__ . '/../uploads/products/')){
    // collect referenced images
    if (in_array('image', $productColumns)){
      $rows = $pdo->query('SELECT `image` FROM `products`')->fetchAll(PDO::FETCH_COLUMN);
      $referenced = array_filter(array_map(function($p){ return $p ? basename($p) : null; }, $rows));
      $dir = __DIR__ . '/../uploads/products/';
      foreach(scandir($dir) as $f){
        if ($f === '.' || $f === '..') continue;
        if (!in_array($f, $referenced)){
          @unlink($dir . $f);
        }
      }
    }
  }
  header('Location: products.php'); exit;
}

// delete - only if id column exists
  if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($productPk){
    // delete associated image if exists
    if (in_array('image', $productColumns)){
      $g = $pdo->prepare('SELECT `image` FROM `products` WHERE `'.str_replace('`','',$productPk).'` = ?');
      $g->execute([$id]);
      $r = $g->fetch(PDO::FETCH_ASSOC);
      if ($r && !empty($r['image'])){
        $path = __DIR__ . '/../' . ltrim($r['image'], '/');
        if (is_file($path)) @unlink($path);
      }
    }
    try{
      $stmt = $pdo->prepare('DELETE FROM products WHERE `'.str_replace('`','',$productPk).'` = ?');
      $stmt->execute([$id]);
      set_flash('Product deleted successfully', 'success');
    }catch(PDOException $e){
      // foreign key constraint (e.g., referenced by order_items)
      if ($e->getCode() === '23000'){
        // provide a force-delete link (unsafe if you rely on order history)
        $_SESSION['force_delete_url'] = 'products.php?force_delete=' . urlencode($id);
        set_flash('Could not delete product because it is referenced by other records. You may force delete (this will remove related items).', 'danger');
      } else {
        set_flash('Error deleting product: ' . $e->getMessage(), 'danger');
      }
    }
  }
  // redirect back regardless (if id column missing, do nothing to avoid SQL error)
  // cleanup orphaned images
  if (in_array('image', $productColumns) && is_dir(__DIR__ . '/../uploads/products/')){
    $rows = [];
    try{ $rows = $pdo->query('SELECT `image` FROM `products`')->fetchAll(PDO::FETCH_COLUMN); }catch(Exception $e){}
    $referenced = array_filter(array_map(function($p){ return $p ? basename($p) : null; }, $rows));
    $dir = __DIR__ . '/../uploads/products/';
    foreach(scandir($dir) as $f){ if ($f === '.' || $f === '..') continue; if (!in_array($f, $referenced)) @unlink($dir . $f); }
  }
  header('Location: products.php'); exit;
}

// Force delete handler: remove dependent rows then product (use with caution)
if (isset($_GET['force_delete'])){
  $fid = $_GET['force_delete'];
  if ($productPk){
    try{
      $pdo->beginTransaction();
      // delete order_items referencing this product - adapt column names if needed
      // the SQL below assumes order_items.product_id references products.<productPk>
      $delItems = $pdo->prepare('DELETE FROM order_items WHERE product_id = ?');
      $delItems->execute([$fid]);
      $del = $pdo->prepare('DELETE FROM products WHERE `'.str_replace('`','',$productPk).'` = ?');
      $del->execute([$fid]);
      $pdo->commit();
      set_flash('Product and related order items deleted (force).', 'success');
    }catch(Exception $e){
      $pdo->rollBack();
      set_flash('Force delete failed: ' . $e->getMessage(), 'danger');
    }
  }
  header('Location: products.php'); exit;
}


// Fetch all categories for dropdown and filter
$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

// search, filter, pagination
$q = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$params = [];
$where = [];
if ($q) { $where[] = "(name LIKE ? OR description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($categoryFilter) { $where[] = "category_id = ?"; $params[] = $categoryFilter; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
$total->execute($params);
$totalRows = $total->fetchColumn();
$offset = ($page-1)*$perPage;

$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ";
// Disambiguate category_id in WHERE clause
if ($where) {
  $whereSql = preg_replace('/\bcategory_id\b/', 'p.category_id', $whereSql);
  $whereSql = preg_replace('/\bdescription\b/', 'p.description', $whereSql);
  $sql .= $whereSql;
}
$sql .= " ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?=htmlspecialchars($flash['type'])?> alert-dismissible fade show" role="alert">
      <?=htmlspecialchars($flash['msg'])?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <div class="d-flex justify-content-between mb-3">
  <h4>Products</h4>
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openProductModal()">Add Product</button>
</div>

<form class="row g-2 mb-3">
  <div class="col-auto"><input name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search"></div>
  <div class="col-auto">
    <select name="category" class="form-control">
      <option value="">All Categories</option>
      <?php foreach($categories as $cat): ?>
        <option value="<?=htmlspecialchars($cat['category_id'])?>" <?=($categoryFilter==$cat['category_id']?'selected':'')?>><?=htmlspecialchars($cat['category_name'])?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>

<table class="table table-striped">
  <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Image</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($products as $p): ?>
      <tr>
      <?php
        $pkVal = ($productPk && isset($p[$productPk])) ? $p[$productPk] : '';
        $nameVal = $fieldMap['name'] ?? null ? ($p[$fieldMap['name']] ?? ($p['name'] ?? '')) : ($p['name'] ?? '');
        // Show category name from join
        $catVal = $p['category_name'] ?? '';
        $priceVal = null;
        if (isset($fieldMap['price']) && isset($p[$fieldMap['price']])) $priceVal = $p[$fieldMap['price']];
        elseif (isset($p['price'])) $priceVal = $p['price'];
        $stockVal = $fieldMap['stock'] ?? null ? ($p[$fieldMap['stock']] ?? ($p['stock_quantity'] ?? $p['stock'] ?? '')) : ($p['stock_quantity'] ?? $p['stock'] ?? '');
        $statusVal = $fieldMap['status'] ?? null ? ($p[$fieldMap['status']] ?? ($p['status'] ?? '')) : ($p['status'] ?? '');
        $imgVal = $dbImageCol ? ($p[$dbImageCol] ?? ($p['image_path'] ?? $p['image'] ?? null)) : ($p['image_path'] ?? $p['image'] ?? null);
      ?>
        <td><?= htmlspecialchars($pkVal) ?></td>
        <td><?= htmlspecialchars($nameVal) ?></td>
        <td><?= htmlspecialchars($catVal) ?></td>
  <td><?= $priceVal !== null ? ('Ksh ' . number_format($priceVal,2)) : '' ?></td>
        <td><?= htmlspecialchars($stockVal) ?></td>
        <td><?= htmlspecialchars($statusVal) ?></td>
        <td><?php if(!empty($imgVal)): ?><img src="/<?=htmlspecialchars($imgVal)?>" style="height:50px" /><?php endif; ?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="#" onclick='editProduct(<?=json_encode($p)?>)'>Edit</a>
          <a class="btn btn-sm btn-danger" href="?delete=<?= htmlspecialchars($pkVal) ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
      <div class="modal-header"><h5 class="modal-title">Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="product_action" value="1">
        <input type="hidden" name="id" id="prod_id">
        <div class="mb-3"><label>Name</label><input id="prod_name" name="name" class="form-control"></div>
        <div class="mb-3"><label>Description</label><textarea id="prod_description" name="description" class="form-control"></textarea></div>
        <div class="mb-3"><label>Category</label>
          <select id="prod_category" name="category" class="form-control">
            <option value="">Select Category</option>
            <?php foreach($categories as $cat): ?>
              <option value="<?=htmlspecialchars($cat['category_id'])?>"><?=htmlspecialchars($cat['category_name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3 row"><div class="col"><label>Price</label><input id="prod_price" name="price" class="form-control" type="number" step="0.01"></div><div class="col"><label>Stock</label><input id="prod_stock" name="stock" class="form-control" type="number"></div></div>
        <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control"></div>
        <div class="mb-3"><label>Status</label><select id="prod_status" name="status" class="form-control"><option>active</option><option>inactive</option></select></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>





<?php // pagination ?>
<?php $pages = ceil($totalRows / $perPage); ?>
<nav><ul class="pagination">
  <?php for($i=1;$i<=$pages;$i++): ?>
    <li class="page-item <?= $i==$page? 'active':'' ?>"><a class="page-link" href="?page=<?=$i?>&q=<?=urlencode($q)?>&category=<?=urlencode($categoryFilter)?>"><?=$i?></a></li>
  <?php endfor; ?>
</ul></nav>

<!-- Modal -->


<script>
function openProductModal(){
  document.getElementById('prod_id').value='';
  document.getElementById('prod_name').value='';
  document.getElementById('prod_description').value='';
  document.getElementById('prod_category').value='';
  document.getElementById('prod_price').value='';
  document.getElementById('prod_stock').value='0';
  document.getElementById('prod_status').value='active';
}
function editProduct(p){
  var m = new bootstrap.Modal(document.getElementById('productModal'));
  try{
    var pk = productPkName || 'id';
    document.getElementById('prod_id').value = p[pk] ?? '';
  }catch(e){ document.getElementById('prod_id').value = p.id ?? ''; }
  document.getElementById('prod_name').value = p.name ?? '';
  document.getElementById('prod_description').value = p.description ?? '';
  // Set category dropdown to correct value
  if(p.category_id){
    document.getElementById('prod_category').value = p.category_id;
  } else {
    document.getElementById('prod_category').value = '';
  }
  document.getElementById('prod_price').value = p.price ?? '';
  document.getElementById('prod_stock').value = p.stock_quantity ?? p.stock ?? 0;
  document.getElementById('prod_status').value = p.status ?? 'active';
  m.show();
}
</script>

<script>
// expose server-detected primary key for JS
var productPkName = <?= json_encode($productPk) ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


