<?php
$servername = "localhost";
$database = "u216054559_ep3bs";
$username = "u216054559_ep3bs";
$password = "Profitbadminton123*";
 
// Create connection
 
$conn = mysqli_connect($servername, $username, $password, $database);
 
// Check connection
 
if (!$conn) {
 
    die("Connection failed: " . mysqli_connect_error());
 
}
echo "Connected successfully";
mysqli_close($conn);
?>