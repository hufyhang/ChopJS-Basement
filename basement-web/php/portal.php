<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

$con=mysqli_connect("31.22.4.32","feifeiha_public","p0OnMM722iqZ","feifeiha_big_db");

// Check connection
if (mysqli_connect_errno($con))
{
   echo "{\"code\": 500, \"status\": \"error\", \"error\": \"Failed to connect to MySQL: " . mysqli_connect_error(). "\"}";
}
else {
    $response = "{\"code\": 200, \"items\" : [";
    $result = mysqli_query($con,
               "SELECT DISTINCT AppName FROM Basement");
    while($row = mysqli_fetch_array($result))
    {
        $response = $response . "\"" . $row["AppName"] . "\",";
    }

    $response = rtrim($response, ",");

    $response = $response . "]}";
}

mysqli_close($con);
echo $response;
?>
