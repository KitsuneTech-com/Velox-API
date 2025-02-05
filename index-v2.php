<?php
$apitree = //parse API tree into nested array -- this could be an actual directory tree or a virtual representation
$path = explode("/",$_GET['path']);
$postdata = $_POST;
foreach ($path as $key){
	try {
		$apitree = $apitree[$key];
	}
	catch(Exception $ex){
		//The path is invalid if any portion of it doesn't resolve to an element in $apitree
		die("Invalid path");
	}
}
if (!is_scalar($apitree)){
	//Caller can't use a partial API path
	die("Invalid path");
}
$endpoint = $apitree;

//call $endpoint with $postdata
