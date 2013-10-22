<?php

include("system/jolt.php");
require 'system/idiorm.php';
require 'system/paris.php';
require 'system/models.php';
require 'Services/Twilio.php';
require 'system/functions.php';

$app = new Jolt();
$app->option('source', 'config.ini');

if( $app->option('db.enabled') != false ){
	ORM::configure('mysql:host='.$app->option('db.host').';dbname='.$app->option('db.name'));
	ORM::configure('username', $app->option('db.user') );
	ORM::configure('password', $app->option('db.pass') );
}

$client = new Services_Twilio($app->option('twilio.accountsid'), $app->option('twilio.authtoken') );
$fromNumber = $app->option('twilio.from');

$app->store('client',$client);

// preload photos whenever a matching route has :tag in it
$app->filter('tag_slug', function ($tag_slug){
	$app = Jolt::getInstance();
	$tag = Model::factory('Tag')->where_equal('slug',$tag_slug)->find_one();
	$photos = $tag->photos()->find_many();
	$app->store('tag', $tag);
	$app->store('photos', $photos);
});

$app->get('/tag/:tag_slug', function($tag_slug){
	$app = Jolt::getInstance();
	$tag = $app->store('tag');
	$photos = $app->store('photos');
	$app->render( 'gallery', array(
		"pageTitle"=>"viewing Photos for {$tag->name}",
		"tag"=>$tag,
		"photos"=>$photos
	));
});

$app->post('/listener', function(){
	$app = Jolt::getInstance();
	if ( isset($_POST['NumMedia']) && $_POST['NumMedia'] > 0 ){
		//	let's find out what tag this is for.. or create a new one..
		$thetag = slugify( $_POST['Body'] );
		$tag  = Model::factory('Tag')->where_equal( 'slug', $thetag )->find_one();
		if( isset($tag->id) && !empty($tag->id) ){
			$tag_id = $tag->id;
		}else{
			//	no tag already exists...
			$tag 					= Model::factory('Tag')->create();
			$tag->name 				= $_POST['Body']; 
			$tag->slug		 		= slugify( $_POST['Body'] );
			$tag->save();
			$tag_id = $tag->id();			
		}
		for ($i = 0; $i < $_POST['NumMedia']; $i++){
			if (strripos($_POST['MediaContentType'.$i], 'image') === False){
				continue;
			}
			$file = sha1($_POST['MediaUrl'.$i]).'.jpg';
			file_put_contents('images/original/'.$file, file_get_contents($_POST['MediaUrl'.$i]));
			chmod ('images/original/'.$file, 01777);

			// Edit image
			$in = 'images/original/'.$file;
			$out = 'images/processed/'.$file;
			cropResize($in,$out,250);
			chmod ('images/processed/'.$file, 01777);

			// Remove Original Image
			unlink('images/original/'.$file);

			$photo 				=	Model::factory('Photo')->create();
			$photo->tag_id		=	$tag_id;
			$photo->file		=	$file;
			$photo->from		=	$_POST['From'];
			$photo->country		=	$_POST['FromCountry'];
			$photo->datetime 	=	date('F jS Y H:i:s e');
			$photo->save();
		}
		$message = $app->store('client')->account->messages->sendMessage(
			$app->option('twilio.from'), // From a valid Twilio number
			$_POST['From'], // Text this number
			"Image(s) Added to <".strtolower(trim($_POST['Body']))."> Photo Wall Link: ".$app->option('site.url')."/tag/".strtolower(trim($_POST['Body']))
		);
		return true;
	}else{
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

$app->get('/', function(){
	$app = Jolt::getInstance();
	$tags = Model::factory('Tag')->count();
	if( isset($tags) ){
		$images = Model::factory('Photo')->count();
		$tagList = Model::factory('Tag')->find_many();
	}else{
		$tags = 0;
		$images = 0;
		$tagList = array();
	}
	$app->render( 'home',array(
		'tags'=>$tags,
		'tagList' => $tagList,
		'fromNumber' => $app->option('twilio.from'),
		'images'=>$images
	));
});

$app->listen();