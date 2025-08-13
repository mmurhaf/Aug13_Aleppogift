<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once(__DIR__ . '/../secure_config.php');
require_once(__DIR__ . '/../includes/Database.php');


$config = require(__DIR__ . '/../secure_config.php');
$db = new Database($config);

// Fetch categories 
$categories = $db->query("SELECT * FROM categories WHERE status=1")->fetchAll(PDO::FETCH_ASSOC);

// Fetch brands
$brands = $db->query("SELECT * FROM brands WHERE status=1")->fetchAll(PDO::FETCH_ASSOC);

// Initialize filters once
$where = ["p.status = 1"];
$params = [];

// Filter by category
if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $where[] = "p.category_id = :category_id";
    $params['category_id'] = $_GET['category'];
}

// Filter by brand
if (isset($_GET['brand']) && is_numeric($_GET['brand'])) {
    $where[] = "p.brand_id = :brand_id";
    $params['brand_id'] = $_GET['brand'];
}

// --- Compatibility for old product links ---
if (isset($_GET['page']) && $_GET['page'] === 'product_details' && isset($_GET['code'])) {
    $_GET['search'] = trim($_GET['code']);
}

// --- Handle search input from 'search' or 's' ---
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
} elseif (isset($_GET['s'])) {
    $search = trim($_GET['s']);
}

if ($search !== '') {
    if (is_numeric($search)) {
        $where[] = "p.id = :product_id";
        $params['product_id'] = $search;
    } else {
        $where[] = "(p.name_en LIKE :search_en OR p.name_ar LIKE :search_ar)";
        $params['search_en'] = "%$search%";
        $params['search_ar'] = "%$search%";
    }
}



// now build your query with $where and $params...


// Build final WHERE clause
$whereSQL = "WHERE " . implode(" AND ", $where);

// Count total products for pagination
$countSQL = "SELECT COUNT(*) as total FROM products p $whereSQL";
$totalProducts = $db->query($countSQL, $params)->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination settings
$perPage = 16;
$totalPages = ceil($totalProducts / $perPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($currentPage - 1) * $perPage;

// Final SQL query with pagination
$sql = "SELECT p.*, 
        (SELECT image_path FROM product_images 
         WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image 
        FROM products p 
        $whereSQL 
        ORDER BY p.id DESC
        LIMIT :limit OFFSET :offset";

// Add pagination parameters as integers
$params['limit'] = (int)$perPage;
$params['offset'] = (int)$offset;

// Execute
$products = $db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AleppoGift - Premium Gifts & Souvenirs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
     <!--<link rel="stylesheet" href="assets/css/style.css">-->
	<link rel="stylesheet" href="assets/css/index.css">

</head>
<body>

    <?php require_once('../includes/header.php'); ?>
    <div class="container">
		<!-- Cart Preview -->
		<div id="cartPreview" class="card shadow position-absolute end-0 mt-2 me-4 cart-preview" style="display: none;">
			<div class="card-body">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h5 class="card-title mb-0"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h5>
					<button type="button" class="btn-close" aria-label="Close cart" onclick="toggleCart()"></button>
				</div>
				<div id="cart-items-preview">
					<p class="text-muted text-center py-3">Your cart is empty</p>
				</div>
				<div class="d-grid gap-2 mt-3">
					<a href="cart.php" class="btn btn-primary">View Full Cart</a>
					<a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
				</div>
			</div>
		</div>

    <!-- Main Content -->
    <main class="container my-4">
        <!-- Hero Section -->
        <section class="hero-section text-center mb-5">
            <div class="container">
                <h1 class="display-4 fw-bold mb-3">Premium Gifts From China</h1>
                <p class="lead mb-4">Discover our unique collection of luxury and branded gifts</p>
                <a href="#products" class="btn btn-primary btn-lg px-4">Shop Now</a>
            </div>
        </section>
        <section id="categories" class="mb-5">
            <?php //  require_once('../includes/categories.php') ;?>
			<?php if(1==2){ ?>
            <span class="brand-title">Shop by category: </span> &nbsp;
			<?php foreach ($categories as $cat):?>
			<span class="brand-title">	<a href="#&category=<?= $cat['id']; ?>">		<?= $cat['name_en']; ?> </a> </span> &nbsp;&nbsp;
			<?php endforeach; }?>

            <?php require_once('../includes/brands.php') ;?>
        </section>
        <!-- Filter + Search -->
        <section id="products" class="mb-5">
            <form method="get" action="" class="row g-3 mb-4 bg-white p-4 rounded shadow-sm">
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['name_en']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="brand" class="form-label">Brand</label>
                    <select name="brand" id="brand" class="form-select">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id']; ?>" <?= (isset($_GET['brand']) && $_GET['brand'] == $brand['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($brand['name_en']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                        <input type="text" name="search" id="search" class="form-control ps-5" placeholder="Search products..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100 filter-btn-group">
                            <button type="submit" class="btn btn-primary filter-btn">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="?" class="btn reset-btn" title="Reset filters">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
            </form>
        </section>
                            
        <!-- Pagination -->
         <section class="mb-4">
<!-- Pagination Section -->
<!-- Pagination Section -->
            <div class="pagination-container">
                <div class="pagination-info">Products (<?= $totalProducts ?> found)</div>
                
                <nav class="pagination-nav">
                    <ul class="pagination">
                        <!-- Previous Page Link -->
                        <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- First Page -->
                        <li class="page-item <?= $currentPage == 1 ? 'active' : '' ?>">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        
                        <!-- Second Page -->
                        <?php if ($currentPage <= 4): ?>
                            <li class="page-item <?= $currentPage == 2 ? 'active' : '' ?>">
                                <a class="page-link" href="?page=2">2</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Third Page -->
                        <?php if ($currentPage <= 4): ?>
                            <li class="page-item <?= $currentPage == 3 ? 'active' : '' ?>">
                                <a class="page-link" href="?page=3">3</a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Middle Pages (when current page is in the middle) -->
                        <?php if ($currentPage > 4 && $currentPage < ($totalPages - 3)): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="?page=<?= $currentPage ?>"><?= $currentPage ?></a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Last Three Pages -->
                        <?php if ($currentPage >= ($totalPages - 3)): ?>
                            <li class="page-item <?= $currentPage == ($totalPages - 2) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages - 2 ?>"><?= $totalPages - 2 ?></a>
                            </li>
                            <li class="page-item <?= $currentPage == ($totalPages - 1) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages - 1 ?>"><?= $totalPages - 1 ?></a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Last Page -->
                        <li class="page-item <?= $currentPage == $totalPages ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                        </li>
                        
                        <!-- Next Page Link -->
                        <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

        </section>


        <!-- Products -->
<div class="products-grid">
    <?php foreach ($products as $p): ?>
        <div class="col">
            <div class="card h-100 product-card">
                <!-- Product Badges -->
                <div class="position-absolute top-0 end-0 m-2 badge-container">
                    <?php if (!empty($p['on_sale'])): ?>
                        <span class="badge badge-sale rounded-pill">SALE</span>
                    <?php endif; ?>
                    <?php if (!empty($p['featured'])): ?>
                        <span class="badge badge-featured rounded-pill">FEATURED</span>
                    <?php endif; ?>
                </div>

                <!-- Product Image -->
                <a href="product.php?id=<?= $p['id']; ?>" title="View Details" class="product-image-link">
                    <img src="<?= $p['main_image'] ?: 'assets/images/no-image.png'; ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name_en']); ?>">
                </a>

                <div class="card-body d-flex flex-column">
                    <!-- Product Title -->
                    <h5 class="card-title product-name"><?= htmlspecialchars($p['name_en']); ?></h5>
                    
                    <!-- Product Price -->
                <p class="product-price price my-2">
                        <span class="uae-symbol">&#x00EA;</span><?=number_format($p['price']);?>
                        &nbsp;
                        $<?= number_format($p['price']/3.68, 2); ?>
                        
                </p>
                    
                    <!-- Action Buttons -->
                    <div class="product-actions d-flex justify-content-between align-items-center mt-auto">
                        <!-- Add to Cart -->
                        <form class="add-to-cart-form" method="post">
                            <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                            <input type="hidden" name="quantity" value="1"> 
                            <button type="submit" class="action-btn cart-btn add-to-cart" 
                                    data-id="<?= $p['id']; ?>" 
                                    title="Add to Cart">
                                <i class="fas fa-cart-plus"></i>
                                <span class="btn-label"></span>
                            </button>
                        </form>
                         
                        <a class="share-option whatsapp" 
                                   href="https://wa.me/971561125320?text=<?= urlencode('AleppoGift: I am interested in this product: ' 
                                   . htmlspecialchars($p['name_en']) . ' - AED ' . number_format($p['price'], 0) . ' - Product Code: https://aleppogift.com/product.php?id=' . $p['id']) ?>" 
                                   target="_blank"
                                   title="Share on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                        </a>
                        <!-- Share Button - Simplified -->
                            <!-- Share Button - Uses Web Share API on mobile -->
                            <div class="share-container">
                                <button class="action-btn share-btn" 
                                        title="Share"
                                        onclick="shareProduct(
                                            '<?= htmlspecialchars($p['name_en']) ?>', 
                                            '<?= number_format($p['price'], 2) ?>', 
                                            '<?= $p['id'] ?>',
                                            '<?= 'https://aleppogift.com/product.php?id=' . $p['id'] ?>'
                                        )">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
    <?php endforeach; ?>
</div>



        <div class="text-center mt-4">
            <a href="#top" class="btn btn-secondary">Back to Top</a>
        </div>
    </section>  
                
                


</main>
        <footer class="footer">
            <?php require_once('../includes/footer.php'); ?> 
        </footer>
       
</div>  




    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/main.js"></script>
    <script>
        function shareProduct(name, price, id, url) {
            const shareText = `Check out this product at Aleppo Gift: ${name} - AED ${price} - Product Code: ${id}\n${url}`;

            if (navigator.share) {
                navigator.share({
                    title: name,
                    text: shareText,
                    url: url
                }).then(() => {
                    console.log('Thanks for sharing!');
                }).catch(console.error);
            } else {
                // Fallback (if not supported)
                alert("Sharing is not supported in this browser. Please copy the link manually:\n" + url);
            }
        }
    </script>
</body>
</html>