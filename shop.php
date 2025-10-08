<?php
require_once __DIR__ . '/admin/includes/db.php';

function get_table_columns_shop($pdo, $table){
    $cols = [];
    try{
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`','``',$table) . "`");
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cols[] = $r['Field'];
    }catch(Exception $e){
        return [];
    }
    return $cols;
}

$existing = get_table_columns_shop($pdo, 'products');
$wanted = ['name','description','price','image','category'];
$selectCols = array_values(array_intersect($wanted, $existing));
if (empty($selectCols)){
    $allProducts = [];
} else {
    $selectSql = implode(',', array_map(function($c){ return "`$c`"; }, $selectCols));
    $where = in_array('status',$existing) ? "WHERE `status`='active'" : '';
    $order = in_array('created_at',$existing) ? "ORDER BY `created_at` DESC" : '';
    $sql = "SELECT $selectSql FROM `products` $where $order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $allProducts = $stmt->fetchAll();
    foreach($allProducts as &$p){ foreach($wanted as $k) if (!array_key_exists($k,$p)) $p[$k] = null; }
    unset($p);
}

function escape2($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function render_card_shop($p){
    $img = $p['image'] ? escape2($p['image']) : 'img/fruite-item-1.jpg';
    $name = escape2($p['name'] ?? 'Product');
    $desc = escape2($p['description'] ?? '');
    $short = mb_strlen($desc) > 120 ? mb_substr($desc,0,117) . '...' : $desc;
    $price = isset($p['price']) ? number_format((float)$p['price'],2) : '0.00';
    $categoryLabel = escape2($p['category'] ?? '');
    return "<div class=\"col-md-6 col-lg-6 col-xl-4\">\n".
        "<div class=\"rounded position-relative fruite-item\">\n".
        "<div class=\"fruite-img\"><img src=\"$img\" class=\"img-fluid w-100 rounded-top\" alt=\"\"></div>\n".
        "<div class=\"text-white bg-secondary px-3 py-1 rounded position-absolute\" style=\"top: 10px; left: 10px;\">$categoryLabel</div>\n".
        "<div class=\"p-4 border border-secondary border-top-0 rounded-bottom\">\n".
        "<h4>$name</h4>\n".
        "<p>$short</p>\n".
        "<div class=\"d-flex justify-content-between flex-lg-wrap\">\n".
        "<p class=\"text-dark fs-5 fw-bold mb-0\">\$$price</p>\n".
        "<a href=\"#\" class=\"btn border border-secondary rounded-pill px-3 text-primary\"><i class=\"fa fa-shopping-bag me-2 text-primary\"></i> Add to cart</a>\n".
        "</div></div></div></div>\n";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shop - Fruitables</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- keep navbar same as original but point links to php -->
    <nav class="navbar navbar-light bg-white navbar-expand-xl">
        <a href="index.php" class="navbar-brand"><h1 class="text-primary display-6">Fruitables</h1></a>
    </nav>

    <div class="container-fluid fruite py-5">
        <div class="container py-5">
            <h1 class="mb-4">Fresh fruits shop</h1>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="row g-4">
                        <div class="col-xl-3">
                            <div class="input-group w-100 mx-auto d-flex">
                                <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                                <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                            </div>
                        </div>
                        <div class="col-6"></div>
                        <div class="col-xl-3">
                            <div class="bg-light ps-3 py-3 rounded d-flex justify-content-between mb-4">
                                <label for="fruits">Default Sorting:</label>
                                <select id="fruits" name="fruitlist" class="border-0 form-select-sm bg-light me-3" form="fruitform">
                                    <option value="volvo">Nothing</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-3">
                            <!-- sidebar preserved -->
                        </div>
                        <div class="col-lg-9">
                            <div class="row g-4 justify-content-center">
                                <?php
                                if (count($allProducts) === 0){
                                    echo '<div class="col-12"><div class="p-4 border border-secondary rounded">No products available</div></div>';
                                } else {
                                    foreach($allProducts as $p){
                                        echo render_card_shop($p);
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
