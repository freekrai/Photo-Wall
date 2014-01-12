<p class="intro" >Text <span><?php echo $fromNumber ?></span> a picture with the name of a tag.  Your image will be displayed on that tag.</p>
<div class="row">
	<div class="col-md-4 col-md-offset-4">
		<p class="stats">Number of Tags: <?php echo $tags; ?></p>
		<p class="stats">Number of Images: <?php echo $images; ?></p>
	</div>
</div>
<hr />
<h3>Tags</h3>
<ul>
<?php
/*
 *	Cycle through each tag and output a list...
 */
?>
<?php 	foreach($tagList as $tag){	?>
	<li>
		<a href="<?php echo $uri?>/tag/<?php echo $tag->slug?>"><?php echo $tag->name?></a>
	</li>
<?php	}	?>
</ul>