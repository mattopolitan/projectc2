<!-- put in ./www directory -->

<html>
 <head>
  <title>Hello World</title>

  <meta charset="utf-8"> 

  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

</head>
<body>
    <div class="container">
        <?php 
            include 'functions.php';
            $token = json_decode(locationSubmit($_POST["locations"]));
            $response = json_decode(getShortestPath($token->token));
        ?>
            <form method=post>
            Input: <textarea type=text name=locations rows="4" cols="50"></textarea><br>
            <input type=submit value=submit>
            </form>
        <?php

            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Path</th><th>Distance</th><th>Duration</th></tr></thead>';
            // foreach($temp_paths as $path){
            //     echo '<tr>';
            //     echo '<td>' . '0,'.implode(',',$path['path']) . '</td>';
            //     echo '<td>' . $path['distance'] . '</td>';
            //     echo '<td>' . $path['duration'] . '</td>';
            //     echo '</tr>';
            // }
            echo '<tr>';
            echo '<td>' . json_encode($response->path ). '</td>';
            echo '<td>' . $response->total_distance . '</td>';
            echo '<td>' . $response->total_time . '</td>';
            echo '</tr>';
            echo '</table>';
            echo "\n";
        ?>
    </div>
</body>
</html>
