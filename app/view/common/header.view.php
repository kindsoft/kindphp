<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo $pageTitle; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<?php KindPHP::css('/bootstrap/css/bootstrap.min.css'); ?>
	<!--[if IE 6]>
	<?php KindPHP::css('/bootstrap/css/bootstrap-ie6.css'); ?>
	<![endif]-->
</head>

<body>

	<div class="container">

	<!-- Navbar
	================================================== -->
	<section id="navbar">
		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
					<a class="brand" href="#">应用名</a>
					<div class="btn-group pull-right">
						<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
							<i class="icon-user"></i> 用户名
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="#">退出登录</a></li>
							<li class="divider"></li>
							<li><a href="#">关于应用</a></li>
						</ul>
					</div>
					<div class="nav-collapse">
						<ul class="nav">
							<li class="active"><a href="#">菜单名</a></li>
							<li><a href="#">菜单名</a></li>
							<li><a href="#">菜单名</a></li>
							<li><a href="#">菜单名</a></li>
						</ul>
					</div><!-- /.nav-collapse -->
				</div>
			</div><!-- /navbar-inner -->
		</div><!-- /navbar -->
	</section>

