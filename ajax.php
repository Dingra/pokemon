<?php
	include 'functions.php';
	require 'config.php';
	
	switch($_GET['method']){
		case "addType":
			global $conn;
			$type = $_GET['type'];
			addType($type);
			break;
			
		case "addStrength":
			global $conn;
			$attacker = $_GET['attacker'];
			$defender = $_GET['defender'];
			$generation = $_GET['generation'];
			
			addStrength($attacker, $defender, $generation);
			break;
			
		case "addWeakness":
			global $conn;
			$attacker = $_GET['attacker'];
			$defender = $_GET['defender'];
			$ineffective = $_GET['ineffective'];
			$generation = $_GET['generation'];
			
			addWeakness($attacker, $defender, $ineffective, $generation);
			break;
			
		case "getTypes":
			global $conn;
			$types = get_types(6);
			
			echo json_encode($types);
			break;
			
		case "getMatchups":
			global $conn;
			$type1 = $_GET['type1'];
			$type2 = $_GET['type2'];
			
			echo getMatchups($type1, $type2);
			break;
			
		case "get_aggregate_matchups":
			global $conn;
			$team = $_GET['team'];
			$matchups = get_aggregate_matchups(json_decode($team), 6);
			
			echo json_encode($matchups);
			break;
			
		case "get_pokemon_names":
			global $conn;
			$generation = $_GET['generation'];
			if($pokemon = get_pokemon_names($generation)) {
				echo json_encode($pokemon);
			}
			break;
	}
?>