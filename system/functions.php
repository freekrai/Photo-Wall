<?php
	function slugify( $string ){
		$string = strtolower( trim($string) );
		$slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
		return $slug;
	}
	
	function cropResize($img,$out='',$dSize=170){
		if( file_exists($img)){
			$x = @getimagesize($img);
			$sw = $x[0];
			$sh = $x[1];
			$yOff = 0;
			$xOff = 0;
			if($sw < $sh) {
				$scale = $dSize / $sw;
				$yOff = $sh/2 - $dSize/$scale/2; 
			} else {
				$scale = $dSize / $sh;
				$xOff = $sw/2 - $dSize/$scale/2; 
			}
			
			$im = @ImageCreateFromJPEG ($img) or // Read JPEG Image
			$im = @ImageCreateFromPNG ($img) or // or PNG Image
			$im = @ImageCreateFromGIF ($img) or // or GIF Image
			$im = false; // If image is not JPEG, PNG, or GIF
			
			if (!$im) {
				readfile ($img);
			} else {
				$thumb = @ImageCreateTrueColor ($dSize,$dSize);
				imagecopyresampled($thumb, $im, 
				0, 0, 
				$xOff,$yOff,
				$dSize, $dSize, 
				$dSize / $scale ,$dSize / $scale);
			}
			if( $out == '' ){
				header('content-type:image/jpeg');
				imagejpeg($thumb);
			}else{
				imagejpeg($thumb, $out);
			}
		}
	}