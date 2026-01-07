<?php
session_start();
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
$featuredProducts = array_slice($products, 0, 3);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GameHub | Multipurpose Store</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/admin-login-modal.js"></script>
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>

    <main>
        <section class="hero">
            <div class="container hero-content">
                <div class="tagline">MULTIPURPOSE STORE</div>
                <h1>GAMEHUB</h1>   
                <a class="btn" href="shop.php">SHOP NOW</a>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2 class="section-title">FEATURED PRODUCTS</h2>
                <div class="products-grid">
                    <?php foreach ($featuredProducts as $product) : ?>
                        <article class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                 class="product-image-clickable"
                                 onclick="openProductModal(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)"
                                 style="cursor: pointer;">
                            <div class="product-info">
                                <span><?php echo htmlspecialchars($product['category'], ENT_QUOTES); ?></span>
                                <h4><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></h4>
                                <p class="price">¥<?php echo number_format($product['price'], 2); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section testimonials" id="about">
            <div class="container">
                <h2 class="section-title">WHAT OUR CUSTOMERS SAY</h2>
                <div class="quotes">
                    <div class="quote-card">
                        <p>Fast shipping and excellent customer service. The product was even better than expected. I will definitely be a returning customer.</p>
                        <div class="customer">JENNIFER LEWIS</div>
                    </div>
                    <div class="quote-card">
                        <p>Great user experience on your website. I found exactly what I was looking for at a great price. I will definitely be telling my friends.</p>
                        <div class="customer">ALICIA HEART</div>
                    </div>
                    <div class="quote-card">
                        <p>Thank you for the excellent shopping experience. It arrived quickly and was exactly as described. I will definitely be shopping with you again in the future.</p>
                        <div class="customer">JUAN CARLOS</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta" id="contact">
            <div class="container">
                <h2 class="section-title" style="color:#fff;">GIVE THE GIFT OF GAME PRODUCTS</h2>
                <a class="btn" href="shop.php">PURCHASE A GAME PRODUCTS</a>
            </div>
        </section>
    </main>

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
    </script>
</body>

</html>