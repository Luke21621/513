<?php
session_start();
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>GameHub | About Us</title>
	<link rel="stylesheet" href="assets/css/style.css" />
	<script src="assets/js/admin-login-modal.js"></script>
	<script type="text/javascript" src="https://api.map.baidu.com/api?v=3.0&ak=E4805d16520de693a3fe707cdc962045"></script>
	<style>
		/* about page small overrides */
		.about-container { max-width: 980px; margin: 24px auto; padding: 0 16px; }
		.hero-about { display:flex; flex-direction:column; gap:12px; margin:18px 0 }
		@media(min-width:700px){ .hero-about { flex-direction:row; align-items:center } }
		.hero-about .logo { width:140px; height:140px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold }
		.card { background:#fff; border:1px solid #e6e6e6; padding:14px; border-radius:8px }
		.grid { display:grid; gap:16px; grid-template-columns:1fr }
		@media(min-width:900px){ .grid { grid-template-columns: 2fr 1fr } }
		.map-wrap { width:100%; height:320px; border:0; border-radius:6px; overflow:hidden; }
		#baiduMap { width:100%; height:100%; border:0; border-radius:6px; }
	</style>
</head>

<body>
	<?php include __DIR__ . '/header.php'; ?>

	<main>
		<section class="about-container">
			<div class="hero-about">
				<div>
					<h1>About GameHub</h1>
				</div>
			</div>

			<div class="grid">
				<div>
					<div class="card">
						<h2>Company Profile</h2>
						<p>GameHub is an e-commerce project born from a passion for gaming culture and a desire to create tangible products through code. We curate fun, affordable, and gaming-inspired merchandise—including T-shirts, mugs, collectible figures, room decor, and gaming accessories—so you can express your love for games in real life.</p>

						<h3>Our Mission</h3>
						<p>Bring the joy of gaming into your everyday life with carefully selected, high-quality gaming accessories.</p>

						<h3>Contact Us</h3>
						<p>Business Hours: Monday to Friday, 9:00 AM - 5:00 PM<br>
						Email:<a href="mailto:hello@gamehub.example">2162106274@qq.com</a><br>
						Address:Sydney, NSW, Australia</p>
					</div>

					<div class="card" style="margin-top:12px">
						<h3>Why Choose Us</h3>
						<ul>
							<li>Clear and concise interface</li>
							<li>Mobile-friendly</li>
    			            <li>Easy to use</li>
						</ul>
					</div>
				</div>

				<aside>
					<div class="card">
						<h2>Store Location</h2>
						<div class="map-wrap">
							<div id="baiduMap"></div>
						</div>
						<p style="margin-top: 10px; font-size: 0.9rem; color: #666;">
							<a href="https://map.baidu.com/search/Sydney+NSW+Australia/@151.2093,-33.8688,15z" target="_blank" rel="noopener noreferrer">View on Baidu Maps</a>
						</p>
					</div>
				</aside>
			</div>
		</section>
	</main>

	<?php include __DIR__ . '/footer.php'; ?>

	<script type="text/javascript">
		// 初始化百度地图
		function initBaiduMap() {
			// 悉尼市中心的经纬度（澳大利亚，新南威尔士州，悉尼）
			var point = new BMap.Point(151.2093, -33.8688);
			
			// 创建地图实例
			var map = new BMap.Map("baiduMap");
			
			// 设置地图中心点和缩放级别
			map.centerAndZoom(point, 15);
			
			// 启用滚轮缩放
			map.enableScrollWheelZoom(true);
			
			// 添加标注
			var marker = new BMap.Marker(point);
			map.addOverlay(marker);
			
			// 创建信息窗口
			var infoWindow = new BMap.InfoWindow("Sydney, NSW, Australia<br>GameHub Store Location", {
				width: 200,
				height: 100,
				title: "Store Location"
			});
			
			// 点击标注时显示信息窗口
			marker.addEventListener("click", function() {
				map.openInfoWindow(infoWindow, point);
			});
			
			// 添加地图控件
			map.addControl(new BMap.NavigationControl());
			map.addControl(new BMap.ScaleControl());
			map.addControl(new BMap.OverviewMapControl());
		}
		
		// 页面加载完成后初始化地图
		window.onload = function() {
			initBaiduMap();
		};
	</script>
</body>

</html>