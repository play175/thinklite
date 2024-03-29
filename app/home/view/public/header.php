<!DOCTYPE html>
<html lang='zh-CN'>
<head>
	<meta class="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title><?php echo empty($title) ? '' : ($title . ' - ') ?>THINKLITE</title>
	<link rel='stylesheet' href='/static/css/style.css' />
</head>
<body>
	<!-- navbar -->
	<div class="navbar navbar--extended">
		<nav class="nav__mobile"></nav>
		<div class="container">
			<div class="navbar__inner">
				<a href="/" class="navbar__logo">THINKLITE</a>
				<nav class="navbar__menu">
					<ul>
						<li <?php echo CONTROLLER == 'Index' ? 'class="active"' : '' ?>><a href="/">首页</a></li>
						<li <?php echo CONTROLLER == 'News' ? 'class="active"' : '' ?>><a href="<?php echo U('news/') ?>">新闻中心</a></li>
						<li <?php echo CONTROLLER == 'About' ? 'class="active"' : '' ?>><a href="<?php echo U('about/') ?>">关于我们</a></li>
					</ul>
				</nav>
				<div class="navbar__menu-mob"><a href="" id='toggle'><svg role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z" class=""></path></svg></a></div>
			</div>
		</div>
	</div>
	