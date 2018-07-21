<?php 
	function computePermutations($array) {
	    $result = [];

	    $recurse = function($array, $start_i = 0) use (&$result, &$recurse) {
	        if ($start_i === count($array)-1) {
	            array_push($result, $array);
	        }

	        for ($i = $start_i; $i < count($array); $i++) {
	            //Swap array value at $i and $start_i
	            $t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;

	            //Recurse
	            $recurse($array, $start_i + 1);

	            //Restore old order
	            $t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;
	        }
	    };

	    $recurse($array);

	    return $result;
	}
	
	function tokenGeneration($val){
		$token = $token = hash('MD5',$val);
		return substr($token,0,8).'-'.substr($token,8,4).'-'.substr($token,12,4).'-'.substr($token,16,4).'-'.substr($token,20,12);
	}

	function locationSubmit($post_val = null){
		if(is_null($post_val)) $post_val = file_get_contents('php://input');
		$token = tokenGeneration($post_val);
		$json_return = '{ "token": "'.$token.'"}';
		try{
			// Insert the result into Database
	        $conn = mysqli_connect('db', 'user', 'test', "myDb");

	        if (mysqli_connect_errno()) throw new Exception("Failed to connect to MySQL: " . mysqli_connect_error()); // Check Database connection

	        $query = "INSERT INTO `Response` (`token`, `path`) VALUES ('{$token}', '{$post_val}') ON DUPLICATE KEY UPDATE `token` = `token`";

	        $result = mysqli_query($conn, $query);

	        if($result === false) throw new Exception("Insert Query Error"); // Check if the INSERT query succesful

	        mysqli_close($conn);

		}catch(Exception $e) {
			$json_return = 
			'{ "status": "failure", "error": "ERROR_DESCRIPTION" }';
		}
		return $json_return;
	}

	function getShortestPath($token = null){
		try {
			if(is_null($token)) $token = $_GET['route'];
			if(empty($token)) throw new Exception("Empty Token") ; 

			$conn = mysqli_connect('db', 'user', 'test', "myDb");
		    if (mysqli_connect_errno()) throw new Exception("Failed to connect to MySQL: " . mysqli_connect_error()) ; 

		    // Get corresponding result from Database
		    $query = "SELECT `path` FROM `Response` WHERE token = '{$token}' LIMIT 1";
		    $result = mysqli_query($conn, $query);
		    $return = [];
		    while ($row = mysqli_fetch_row($result)){
		        $return = $row;
		    };

		    mysqli_close($conn);

		    if(empty($return)) throw new Exception("No result") ; 

		    $path_submit = json_decode($return[0]);

	        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=';
	        $origins = $path_submit;
	        $destinations = array_slice($path_submit,1); // Omit the first starting location

	        //Get Origins
	        $get_origins = implode('|', array_map(function ($entry) {
	              return $entry[0].','.$entry[1];
	            }, $origins));

	        //Get Destinations
	        $get_destinations = implode('|', array_map(function ($entry) {
	              return $entry[0].','.$entry[1];
	            }, $destinations));

	        $url .= $get_origins.'&destinations='.$get_destinations.'&key=AIzaSyDbSH6koYpjldtMHakJXpyOXJ-jxVZ9-2c';

	        $ch = curl_init($url);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        if( ($response = curl_exec($ch) ) === false) throw new Exception('Curl error: ' . curl_error($ch)); 
	        
	        curl_close($ch);

	        if(strpos($response, 'NOT_FOUND') !== false) throw new Exception('Destination not found'); // Return error when any one of the location is not found
			
	        $response = json_decode($response);

	        //Brutal Force 
	        $path = range(1,count($path_submit)-1);
	        // $paths = computePermutations(range(1,count($path_submit)-1)); //Compute all possible paths, e.g. [1,2,3,...]

	        $temp_paths = [];
	        $shortest_distance = 0;
	        $shortest_path_no = 0; // key of array $paths
	        foreach($paths as $path_no => $path){
	            $temp_paths[$path_no] = [ 'path' => $path , 'total_distance' => 0, 'total_time' => 0];
	            $last_location = 0; // Start from Origin
	            // Calculate the total distance & total time
	            foreach ($path as $location) {
	                $temp_paths[$path_no]['total_distance'] += $response->rows[$last_location]->elements[$location-1]->distance->value; 
	                $temp_paths[$path_no]['total_time'] += $response->rows[$last_location]->elements[$location-1]->duration->value; 
	                $last_location = $location; 
	            }
	            if($shortest_distance == 0 || $shortest_distance > $temp_paths[$path_no]['total_distance']){
	                $shortest_distance = $temp_paths[$path_no]['total_distance'];
	                $shortest_path_no = $path_no;
	            }
	        }

	        $estimated_time = $temp_paths[$shortest_path_no]['total_time'];
	        $shortest_path = $temp_paths[$shortest_path_no]; // e.g. [ 'path' => [0,1,2,3] , 'total_distance' => 18000, 'total_time' => 2000]

			//Compose longitude & latitude of shortest path, e.g. [ ["22.372081", "114.107877"], ["22.284419", "114.159510"], ["22.326442", "114.167811"] ]
	        $temp_shortest_path = [$path_submit[0]];
	        foreach($shortest_path['path'] as $path_no){
	            $temp_shortest_path[] = $path_submit[$path_no];
	        }

	        $shortest_path = json_encode($temp_shortest_path);
		    $json_return = '{ "status": "success", "path": '.$shortest_path.', "total_distance": '.$shortest_distance.', "total_time": '.$estimated_time.'}';

		}catch(Exception $e) {
			$json_return = 
			'{ "status": "failure", "error": "ERROR_DESCRIPTION" }';
		}
		return $json_return;
	}

?>