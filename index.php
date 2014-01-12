<?php

include("system/jolt.php");
require 'system/idiorm.php';
require 'system/paris.php';
require 'system/models.php';
require 'Services/Twilio.php';
require 'system/functions.php';

$app = new Jolt();
$app->option('source', 'config.ini');

// Connect to the database...
if( $app->option('db.enabled') != false ){
	ORM::configure('mysql:host='.$app->option('db.host').';dbname='.$app->option('db.name'));
	ORM::configure('username', $app->option('db.user') );
	ORM::configure('password', $app->option('db.pass') );
}

// Initialize our Twilio client and store in the local session... 
$client = new Services_Twilio($app->option('twilio.accountsid'), $app->option('twilio.authtoken') );
$fromNumber = $app->option('twilio.from');
$app->store('client',$client);

// Preload photos whenever a matching route has $tag_slug in it
$app->filter('tag_slug', function ($tag_slug){
// Initialize the jolt object as $app
	$app = Jolt::getInstance();

// query the database for the slug...
	$tag = Model::factory('Tag')->where_equal('slug',$tag_slug)->find_one();

// grab all photos connected to this slug...
	$photos = $tag->photos()->find_many();

// store the $tag object in the session
	$app->store('tag', $tag);

// store the $photos object in the session
	$app->store('photos', $photos);
});

// Called from a get method of /tag/$tag_slug
$app->get('/tag/:tag_slug', function($tag_slug){
// Initialize the jolt object as $app
	$app = Jolt::getInstance();

// grab the $tag object from the session
	$tag = $app->store('tag');

// grab the $photos object from the session
	$photos = $app->store('photos');

// render the gallery view from views/gallery.php
	$app->render( 'gallery', array(
		"pageTitle"=>"viewing Photos for {$tag->name}",
		"tag"=>$tag,
		"photos"=>$photos
	));
});

// Called on post /listener from Twilio
$app->post('/listener', function(){
	// Initialize the jolt object as $app
	$app = Jolt::getInstance();

	// Were any images included in this post?
	if ( isset($_POST['NumMedia']) && $_POST['NumMedia'] > 0 ){
		// let's find out what tag this is for... or create a new one...
		$thetag = slugify( $_POST['Body'] );
		$tag  = Model::factory('Tag')->where_equal( 'slug', $thetag )->find_one();

		if( isset($tag->id) && !empty($tag->id) ){
			// The tag existed already, so grab the id
			$tag_id = $tag->id;
		}else{
			// No tag already exists in the db, so we'll create a new one...
			$tag 					= Model::factory('Tag')->create();
			$tag->name 				= $_POST['Body']; 
			$tag->slug		 		= slugify( $_POST['Body'] );
			$tag->save();
			$tag_id = $tag->id();			
		}

		// Cycle through each image that was sent...
		for ($i = 0; $i < $_POST['NumMedia']; $i++){
			// If the contentType of the media was not an image, then continue
			if (strripos($_POST['MediaContentType'.$i], 'image') === False){
				continue;
			}

			// Create a unique filename based on the URL of the image
			$file = sha1($_POST['MediaUrl'.$i]).'.jpg';
			// Mownload the original image and store it in the images/original folder
			file_put_contents('images/original/'.$file, file_get_contents($_POST['MediaUrl'.$i]));
			// Make sure we can write to the image
			chmod ('images/original/'.$file, 01777);

			// Edit image
			$in = 'images/original/'.$file;	// Original filename
			$out = 'images/processed/'.$file;	// New filename
			cropResize($in,$out,250);	// Resize and crop the image if necessary
			chmod ('images/processed/'.$file, 01777);

			// Remove Original Image
			unlink('images/original/'.$file);

			// Create a $photo object and store the image in the database
			$photo 				=	Model::factory('Photo')->create();
			$photo->tag_id		=	$tag_id;
			$photo->file		=	$file;
			$photo->from		=	$_POST['From'];
			$photo->country		=	$_POST['FromCountry'];
			$photo->datetime 	=	date('F jS Y H:i:s e');
			$photo->save();
		}
		
		// Reply to the user's text message with a link to the tag
		$message = $app->store('client')->account->messages->sendMessage(
			$app->option('twilio.from'), // From a valid Twilio number
			$_POST['From'], // Text this number
			"Image(s) Added to <".strtolower(trim($_POST['Body']))."> Photo Wall Link: ".$app->option('site.url')."/tag/".strtolower(trim($_POST['Body']))
		);
		return true;
	}else{
		// No image was included... so reply to the user that there was an error...
		if ( isset($_POST['From']) ){
			$message = $app->store('client')->account->messages->sendMessage(
				$app->option('twilio.from'), // From a valid Twilio number
				$_POST['From'], // Text this number
				"MMS error. Please try sending your image again."
			);
		}
		header('HTTP/1.1 400 Bad Request', true, 400);
		return false;
	}
});


// Home page, called as index of site...
$app->get('/', function(){
	// Initialize the jolt object as $app
	$app = Jolt::getInstance();

	// Grab a total count of all tags
	$tags = Model::factory('Tag')->count();
	if( isset($tags) ){
		// Grab a total count of all photos
		$images = Model::factory('Photo')->count();
		// Grab a list of all tags
		$tagList = Model::factory('Tag')->find_many();
	}else{
		// No tags uploaded yet, so default to default values:
		$tags = 0;
		$images = 0;
		$tagList = array();
	}
	// Render the home page via home.php
	$app->render( 'home',array(
		'tags'=>$tags,
		'tagList' => $tagList,
		'fromNumber' => $app->option('twilio.from'),
		'images'=>$images
	));
});

// Set Jolt to listen to incoming actions...
$app->listen();