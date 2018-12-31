<?php
	include 'functions.php';
	require 'config.php';
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: POST, GET');

  $method;
  if(isset($_GET['method'])) {
    $method = $_GET['method'];
  } else if(isset($_POST['method'])) {
    $method = $_POST['method'];
  }

	switch($method){
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
			$team = $_POST['team'];
			$generation = $_POST['generation'];
			$matchups = get_aggregate_matchups(json_decode($team), $generation);
			
			echo json_encode($matchups);
			break;
			
		case "get_pokemon_names":
			global $conn;
			$generation = $_POST['generation'];
			if($pokemon = get_pokemon_names($generation)) {
				echo json_encode($pokemon);
			} else {
				echo "Nothing";
			}
			break;
		
		case "get_all_pokemon":
			$generation = $_POST['generation'];
			if($pokemon = get_all_pokemon($generation)) {
				echo json_encode($pokemon);
			}
			break;
			
		case "get_card_info":
			global $conn;
			$team = $_POST['team'];
			$generation = $_POST['generation'];
			if($card_info = get_card_info($team, $generation)) {
				echo json_encode($card_info);
			} else {
				echo "Could not retrieve card data<br />";
			}
			break;
      
    	case "get_user_teams":
     		$uname = $_POST['uname'];
   	 		if($user_teams = get_user_teams($uname)) {
				echo json_encode($user_teams);
      		} else {
     	 	  echo json_encode("");
     		}
		break;
			
		case "create_team":
			$uname = $_POST['uname'];
			$team = $_POST['team'];
			$res = create_team($uname, json_decode($team));
			if($res) {
				$ret = json_encode(array('error' => $res));
				error_log("Returning... " . $ret);
				echo $ret;
			} else echo false;
		break;

		case "remove_team":
			$uname = $_POST['uname'];
			$team_id = $_POST['teamid'];
			remove_team($uname, $team_id);
		break;

		case "modify_team":
			$uname = $_POST['uname'];
			$team_id = $_POST['teamid'];
			$team = $_POST['team'];
			modify_team($uname, $team_id, $team);
		break;

		case "user_login":
			$uname = $_POST['uname'];
			$pass = $_POST['pass'];
			$res = json_encode(user_login($uname, $pass));
			error_log('returning ' . $res);
			echo $res;
		break;

		case "validate_cookie":
			$uname = $_POST['uname'];
			$cookie_code = $_POST['cookie_code'];
			$res = validate_cookie($uname, $cookie_code);
			echo $res;
			break;

		case "create_user": {
			$uname = $_POST['uname'];
			$pass = $_POST['pass'];
			$res = create_user($uname, $pass);
			if($res) {
				return json_encode($res);
			} else  return false;
		}
	}
?>
