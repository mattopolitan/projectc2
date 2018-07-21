<?php 
	include 'functions.php';
	
	if($_SERVER['REQUEST_METHOD'] === 'GET'){

		$json_return = getShortestPath();

	}else if($_SERVER['REQUEST_METHOD'] === 'POST'){

		$json_return = locationSubmit();

	}else{ // Return Error if neither GET or POST request

		$json_return = '{ "status": "failure", "error": "ERROR_DESCRIPTION" }';

	}

	echo $json_return; // Return json result

?>
