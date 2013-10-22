<div class="well">
	<h1 class="tag">#<?php echo $tag->name; ?></h1>
</div>
<hr />
<div class="row">
	<div class="col-xs-12 text-center">
		<div id="container">
<?php 	foreach($photos as $photo){	?>
				<?php if (file_exists('images/processed/'.$photo->file)){ ?>
					<div class="well image">
						<div class="wrapper shadow">
							<a href="<?php echo $uri?>/images/processed/<?php echo $photo->file ?>" title="<?php echo $photo->datetime ?>" >
								<img class="img-responsive" src="<?php echo $uri?>/images/processed/<?php echo $photo->file ?>" />
							</a>
							<p class="info"><?php echo $photo->datetime?></p>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
</div>
<script src="//cdnjs.cloudflare.com/ajax/libs/masonry/3.1.1/masonry.pkgd.min.js"></script>
<script type="text/javascript">
	var container = document.querySelector('#container');
	var msnry = new Masonry( container, {
		itemSelector: '.image'
	});
</script>
