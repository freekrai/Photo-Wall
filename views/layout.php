<html>
<head>
	<title><?=$pageTitle?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
	<link href="style.css" rel="stylesheet">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="header">
			<ul class="nav nav-pills pull-right">
				<li class="active"><a href="<?=$uri?>">Home</a></li>
			</ul>
			<h3 class="text-muted">Photo Wall</h3>
		</div>
		<hr />
		<section class="text-center">
			<?=$pageContent?>
		</section>
	</div>
</body>
</html>