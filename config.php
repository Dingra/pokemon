<?php
     	global $conn;
        $server = "localhost:3306";
        $username = "drew";
        $password = "ghosts";
        $db = "pokemon";
        $conn = null;

	error_reporting(E_ALL);


                if($conn === null){
                try
                {
                        $conn = new mysqli($server,$username,$password,$db);
                }
                catch(PDOException $e)
                {
                        echo "message: " . $e -> getMessage();
                }


        }
?>
