<?php
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);

$categories = array_values(array_unique(array_map(fn ($item) => $item['category'], $products)));
sort($categories);

$query = trim($_GET['q'] ?? '');
$selectedCategory = $_GET['category'] ?? 'all';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

$filteredProducts = array_filter($products, function ($product) use ($query, $selectedCategory, $minPrice, $maxPrice) {
    if ($query && stripos($product['name'], $query) === false && stripos($product['tagline'], $query) === false) {
        return false;
    }

    if ($selectedCategory !== 'all' && $product['category'] !== $selectedCategory) {
        return false;
    }

    if ($minPrice !== null && $product['price'] < $minPrice) {
        return false;
    }

    if ($maxPrice !== null && $product['price'] > $maxPrice) {
        return false;
    }

    return true;
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub | Shop</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
    <style>
        .product-quantity-control {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s;
        }
        
        .quantity-btn:hover {
            background: var(--light);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 0.4rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .add-to-cart-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

   

    <section class="container shop-layout">
        <aside class="filters">
            <h4>FILTER</h4>
            <form method="GET">
                <div class="filter-group">
                    <label for="search">Search products</label>
                    <input type="text" id="search" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES); ?>" placeholder="Minecraft, mug…" />
                </div>
                <div class="filter-group">
                    <label for="category">Categories</label>
                    <select id="category" name="category">
                        <option value="all" <?php echo $selectedCategory === 'all' ? 'selected' : ''; ?>>All</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>" <?php echo $selectedCategory === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category, ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Filter by price</label>
                    <input type="number" name="min_price" step="0.01" min="0" placeholder="Min $" value="<?php echo $minPrice ?? ''; ?>" style="margin-bottom:0.5rem;">
                    <input type="number" name="max_price" step="0.01" min="0" placeholder="Max $" value="<?php echo $maxPrice ?? ''; ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit">Apply</button>
                    <button type="button" class="secondary" onclick="window.location='shop.php'">Reset</button>
                </div>
            </form>

            <div class="filter-group" style="margin-top:2rem;">
                <label>Quick categories</label>
                <ul class="categories">
                    <li><a class="<?php echo $selectedCategory === 'all' ? 'active' : ''; ?>" href="?category=all">All Products</a></li>
                    <?php foreach ($categories as $category) : ?>
                        <li>
                            <a class="<?php echo $selectedCategory === $category ? 'active' : ''; ?>" href="?category=<?php echo urlencode($category); ?>">
                                <?php echo htmlspecialchars($category, ENT_QUOTES); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <div>
            <div class="shop-grid">
                <?php if (count($filteredProducts) === 0) : ?>
                    <p>没有符合条件的商品，试试调整筛选。</p>
                <?php else : ?>
                    
                    <?php foreach ($filteredProducts as $product) : ?>
                        <article class="shop-card">
                            <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                 class="product-image-clickable"
                                 onclick="openProductModal(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)"
                                 style="cursor: pointer;">
                            <div class="product-info">
                                <div class="badge"><?php echo htmlspecialchars($product['category'], ENT_QUOTES); ?></div>
                                <h4><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></h4>
                                <p style="color:var(--muted); font-size:0.9rem;"><?php echo htmlspecialchars($product['tagline'], ENT_QUOTES); ?></p>
                                <p class="price">¥<?php echo number_format($product['price'], 2); ?></p>
                                <form method="POST" action="cart.php" class="add-to-cart-wrapper">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <div class="product-quantity-control">
                                        <button type="button" class="quantity-btn" onclick="decreaseQuantity(<?php echo $product['id']; ?>)">-</button>
                                        <input type="number" name="quantity" id="quantity_<?php echo $product['id']; ?>" class="quantity-input" value="1" min="1" max="99" required>
                                        <button type="button" class="quantity-btn" onclick="increaseQuantity(<?php echo $product['id']; ?>)">+</button>
                                    </div>
                                    <button type="submit" style="width:100%; padding:0.6rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">Add to Cart</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/footer.php'; ?>
    
    <!-- Product Detail Modal -->
    <div id="productModal" class="product-modal">
        <div class="product-modal-content">
            <span class="product-modal-close" onclick="closeProductModal()">&times;</span>
            <div class="product-modal-body">
                <div class="product-modal-image">
                    <img id="modalProductImage" src="" alt="">
                </div>
                <div class="product-modal-details">
                    <h2 id="modalProductName"></h2>
                    <p id="modalProductBrand" class="product-brand"></p>
                    <p id="modalProductDescription" class="product-description"></p>
                    
                    <div class="product-detail-content">
                        <div id="modalProductDetail" class="product-detail-text"></div>
                    </div>
                    
                    <div class="product-modal-price">
                        <span id="modalProductPrice"></span>
                    </div>
                    
                    <form method="POST" action="cart.php" class="product-modal-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" id="modalProductId">
                        <div class="product-modal-quantity">
                            <label>Qty:</label>
                            <input type="number" name="quantity" id="modalQuantity" value="1" min="1" max="99" required>
                        </div>
                        <button type="submit" class="product-modal-add-cart">ADD TO CART</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Detailed product information for each product
        const productDetails = {
            1: "Premium 100% cotton t-shirt featuring vibrant pixel art graphics of iconic Minecraft characters. Machine washable with color-fast design. Perfect for casual wear and gaming sessions.",
            2: "Soft fleece hoodie with dynamic Fortnite Battle Royale design. Features spacious front pocket, adjustable hood, and ribbed cuffs. Warm and breathable fabric with fade-resistant print.",
            3: "Ceramic Pikachu mug with unique lightning bolt handle. Heat-safe up to 200°F, dishwasher and microwave safe. Perfect for daily coffee or tea enjoyment.",
            4: "6-inch vinyl Crewmate figure with classic spacesuit design. Vibrant colors and detailed sculpting. Perfect for desk display or gaming collection.",
            5: "Ceramic pot set with Animal Crossing leaf patterns. Multiple sizes for succulents, herbs, or decorative plants. Durable and functional for desk gardens.",
            6: "Programmable RGB LED desk strip with remote control. Energy-efficient with multiple lighting modes. Creates immersive cyberpunk gaming atmosphere.",
            7: "Leather-bound notebook with fantasy map cover design. High-quality paper with ribbon bookmark and elastic closure. Ideal for campaign notes and adventures.",
            8: "Limited edition foil card collection featuring League of Legends champions. Premium cardstock with glossy finish. Perfect for collectors and display.",
            9: "Limited edition metallic print collectible capturing Star Trek essence. Premium finish stands out in any collection. Rare find for Federation enthusiasts.",
            10: "Authentic cotton headband with Hidden Leaf Village metal crest. Adjustable design for comfortable extended wear. Perfect for cosplay and conventions.",
            11: "Ultra-soft 10-inch Sonic plush with detailed stitching. Premium materials perfect for display or gifting. Must-have for Sonic fans of all ages.",
            12: "Travel sling bag with multiple compartments for gaming gear. Durable materials with stealthy gamer design. Ideal for travel, commuting, or daily use.",
            13: "Zinc alloy Master Sword keychain based on The Legend of Zelda. Durable metal construction with authentic design. Perfect accessory for Zelda fans.",
            14: "Seven Gods badge brooch set inspired by Genshin Impact's Teyvat. Features Wind, Rock, and Thunder God designs. Classic elements for collectors.",
            15: "Tactical water bottle inspired by Apex Legends supply boxes. Durable construction with gaming-themed design. Perfect for staying hydrated during matches.",
            16: "Pure cotton canvas apron with Stardew Valley theme. Functional design for cooking or crafting. Brings farm life charm to your kitchen.",
            17: "Pixel-style night light featuring Overwatch's D.Va hero. LED technology with authentic pixel art design. Perfect for gaming room ambiance.",
            18: "Notebook with Minecraft redstone circuit pattern cover. Replicates classic game design. Ideal for notes, sketches, or gaming plans.",
            19: "Q-version plush keychain of Hollow Knight's Little Knight. Adorable design recreating the protagonist's look. Perfect for keys or bag decoration.",
            20: "Scented candle inspired by Elden Ring's Golden Tree. Sacred symbol of the Lands Between. Creates atmospheric gaming environment with warm glow."
        };

        function openProductModal(product) {
            const modal = document.getElementById('productModal');
            document.getElementById('modalProductImage').src = product.image;
            document.getElementById('modalProductName').textContent = product.name;
            document.getElementById('modalProductBrand').textContent = 'by ' + product.category;
            document.getElementById('modalProductDescription').textContent = product.tagline || 'Premium quality product for gaming enthusiasts.';
            
            // Set detailed product information
            const detailText = document.getElementById('modalProductDetail');
            detailText.textContent = productDetails[product.id] || 'This premium quality product is designed for gaming enthusiasts who appreciate attention to detail and authentic designs. Made with high-quality materials and featuring official licensed artwork, this item is perfect for collectors, players, and fans alike.';
            
            document.getElementById('modalProductPrice').textContent = '¥' + parseFloat(product.price).toFixed(2);
            document.getElementById('modalProductId').value = product.id;
            document.getElementById('modalQuantity').value = 1;

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeProductModal();
            }
        });

        function increaseQuantity(productId) {
            const input = document.getElementById('quantity_' + productId);
            const currentValue = parseInt(input.value) || 1;
            if (currentValue < 99) {
                input.value = currentValue + 1;
            }
        }
        
        function decreaseQuantity(productId) {
            const input = document.getElementById('quantity_' + productId);
            const currentValue = parseInt(input.value) || 1;
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        }
    </script>
</body>

</html>