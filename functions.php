<?php
	include 'config.php';
	
	$baseDamageLevels = array(
		0, .5, 1, 2
	);
	
	$combinedDamageLevels = array( 
		0, .25, .5, 1, 2, 4
	);

	//Add a new type $type to the database
	function addType($type){
		global $conn;

		$addType = $conn->prepare("INSERT INTO type (name) VALUES(?)");
		$addType->bind_param('s',$type);
		$addType->execute();
		
		$testType = "SELECT name FROM type WHERE name = ?";
		
		$test = $conn->prepare($testType);
		$test->execute();
		
		$res = $test->fetch();
		if($res[0] == $type)
			return true;
		else return false;
	}
	
	//Add a new type match up to the database
	//$att: The pokemon who is attacking (super effective)
	//$def: The pokemon who is defending
	function addStrength($att, $def, $gen){
		global $conn;
		
		$addStrength = $conn->prepare("INSERT INTO strong_against (attacker, defender, generation) VALUES(?, ?, ?)");
		$addStrength->bind_param('sss', $att, $def, $gen);
		$addStrength->execute();
	}
	
	function addWeakness($att, $def, $ineffective, $generation){
		global $conn;
		
		if($ineffective == "true")
			$ineffective = 1;
		else
			$ineffective = 0;
		
		$addWeakness = $conn->prepare("INSERT INTO weak_against (attacker, defender, ineffective, generation) VALUES(?, ?, ?, ?)");
		$addWeakness->bind_param('ssss', $att, $def, $ineffective, $generation);
		$addWeakness->execute();
	}
	
	/**

		Returns an array containing the number of Pokemon that are weak against each type

		$team: An array containing the names of Pokemon in the team being checked
		$generation: The generation being checked
	*/
	function get_aggregate_matchups($team, $generation) {
		global $combinedDamageLevels;
		$team_matchups = array();
		$types = get_types(6);
		
		//Initialize the return value. The number of Pokemon that takes each level of damage from each type is
		//initally 0
		foreach($types as $current_type) {
			foreach($combinedDamageLevels as $level) {
				$team_matchups[$current_type]["" . $level] = 0;
			}
		}
		
		//Find how each team member matches up against each type
		foreach($team->members as $member) {
			$matchups = get_pokemon_matchups($member->name, $member->form, $generation);
			foreach($matchups as $type => $damage) {
				$team_matchups[$type]["" . $damage] ++;
			}
		}

		
		return $team_matchups;
	}
	
	//For a certain Pokemon, find out how much damage it takes from each type and return that as an array with
	//$attacking_type => $amount_of_damage_it_takes
	//$name: The name of the Pokemon
	//$generation: The generation being checked
	function get_pokemon_matchups($name, $form, $generation) {
		error_log('name: ' . $name);
		error_log('form: ' . $form);
		global $conn;

		if($form) {
			error_log('form is true and its value is ' . $form);
		} else {
			error_log('form is false and its value is ' . $form);
		}
				
		//First all types need to be stored
		$query = "select `type1`, `type2` from pokemon where `name` = ?";

		if($form) {
			$query = $query . " AND form = ?";
		}

		//hi
		$types = get_types(6);	
		
		//Get the types of the current Pokemon based on its name (and form if it has one)
		$get_pokemon_types = $conn->prepare($query);
		if($form) {
			$get_pokemon_types->bind_param("ss", $name, $form);
			$get_pokemon_types->execute();
		} else {
			$get_pokemon_types->bind_param("s", $name);
			$get_pokemon_types->execute();
		}
		$get_pokemon_types->bind_result($res1, $res2);

		
		$type1 = "";
		$type2 = "";
		
		while($get_pokemon_types->fetch()) {
			$type1 = $res1;
			$type2 = $res2;
		
			$multipliers = array();
		}
		
		$type1_multipliers = get_multiplier_by_type($type1, $generation, $types);
		
		//Only get the weaknesses for $type2 if the current Pokemon has a second type
		if($type2 != ""){
			$type2_multipliers = get_multiplier_by_type($type2, $generation, $types);
		}
		
		//For Pokemon that have 2 types, it's necessary to multiply damage each of its two types takes
		//from a certain attacking type to get the full damage.
		//For example, if a Pokemon has fire and flying for its types and is hit by a rock type attack,
		//since rock is super effective against both types, the defending Pokemon takes 2 * 2 = 4 times
		//the normal damage
		foreach($types as $current_type) {
			if($type2 != '') {
				$mul = $type1_multipliers[$current_type] * $type2_multipliers[$current_type];
			} else {
				$mul = $type1_multipliers[$current_type];
			}
			$multipliers[$current_type] = $mul;
		}
		
		return $multipliers;
	}
	
	//Get all weaknesses for a single type
	function get_multiplier_by_type($defending_type, $generation, $types) {
		global $conn;
		$multipliers = array();
		
		//Look at all types and see how each one matches up to the current type in the current generation
		foreach($types as $current_type) {
			$multiplier_query = "select `multiplier` from `matchup` where `attacker` = ? and `defender` = ? and `generation` <= ?";
			if($get_multipliers = $conn->prepare($multiplier_query)) {
				$get_multipliers->bind_param("ssi", $current_type, $defending_type, $generation);
				$get_multipliers->execute();
				$get_multipliers->bind_result($multiplier);
			
				while($get_multipliers->fetch()) {
					$multipliers[$current_type] = $multiplier;
				}
				
			}
		}
		return $multipliers;
	}
	
	/**
		Get all types in a certain generation
	*/
	function get_types($generation) {
			global $conn;
			
			$query = "SELECT `name` FROM `types` WHERE `generation` <= ?";
			if($get_types = $conn->prepare($query)){
				$get_types->bind_param("i", $generation);
				$get_types->execute();
			
				$get_types->bind_result($res);
						
				$types = Array();			
				while($get_types->fetch()) {
					$types[] = $res;
				}
				return $types;
			}
	}
	
	//Retrieve the names of all Pokemon in the database who were in $generation
	function get_pokemon_names($generation) {
		global $conn;

		error_log('getting pokemon names');
		
		$query = "SELECT `name` FROM pokemon WHERE `generation` = ?";
		if($get_pokemon_names = $conn->prepare($query)) {
			$get_pokemon_names->bind_param("i", $generation);
			$get_pokemon_names->execute();
			$get_pokemon_names->bind_result($res);			
			
			$pokemon = Array();
			
			while($get_pokemon_names->fetch()) {
				$pokemon[] = $res;
			}
			
			return $pokemon;
		}
	}

	function get_all_pokemon($generation) {
		global $conn;
		
		$query = 'SELECT `name`, `natDexId`, `type1`, `type2`, `alternateForm` FROM pokemon WHERE generation = ?';
		if($get_all_pokemon = $conn->prepare($query)) {
			$get_all_pokemon->bind_param("i", $generation);
			$get_all_pokemon->execute();
			$get_all_pokemon->bind_result($natDexId, $name, $type1, $type2, $alternateForm);

			$pokemon = Array();
			while($get_all_pokemon->fetch()) {
				$pokemon[] = array(
					'natDexId' => $natDexId,
					'name' => $name,
					'type1' => $type1,
					'type2' => $type2,
					'alternateForm' => $alternateForm
				);
			}

			return $pokemon;
		}
	}
	
	//Retrieve types and national dex ID for each Pokemon in $names in $generation
	function get_card_info($team, $generation) {
		global $conn;
		
		$ret_data = array();
		
		foreach($team as $current_member) {
			$query = "SELECT `natDexId`, `type1`, `type2` FROM pokemon WHERE `name` = ? AND `generation` <= ?";
			if($get_card_info = $conn->prepare($query)) {
				$get_card_info->bind_param("si", $current_member, $generation);
				$get_card_info->execute();
				$get_card_info->bind_result($id_res, $type1_res, $type2_res);
			
				$counter = 0;
				$pokemon_found = false;
				while($get_card_info->fetch()) {
					$pokemon_found = true;
					$ret_data[$counter]["name"] = $current_member;
					$ret_data[$counter]["natDexId"] = $id_res;
					$ret_data[$counter]["type1"] = $type1_res;
					$ret_data[$counter]["type2"] = $type2_res;
				}

				if(!$pokemon_found) {
					return '';
				}
			} else return "Connection didn't work!";
			$counter ++;
		}
		
		return $ret_data;	
	}

  function get_user_teams($uname) {
    global $conn;
    
    $ret_data = array();
    
    $query = "SELECT natDexId, teamid, pokemon.name, type1, type2, form from team, pokemon WHERE uname = ? AND pokemon.name = team.name;";
    if($get_user_teams = $conn->prepare($query)) {
		$get_user_teams->bind_param("s", $uname);
      	$get_user_teams->execute();
      	$get_user_teams->bind_result($natDexId, $team_id, $pokemon_name, $type1, $type2, $pokemon_form);
	  
	 	$current_team_members = array();
	  	$current_team_id = -1;
      	while($get_user_teams->fetch()) {
			if($current_team_id != $team_id) {
				$current_team_id = $team_id;
				$ret_data[$team_id]["teamId"] = $team_id;
			}

			$ret_data[$team_id]["members"][] = array(
				"natDexId" => $natDexId,
				"name" => $pokemon_name,
				"type1" => $type1,
				"type2" => $type2,
				"form" => $pokemon_form
			);
		}
		return $ret_data;
    } else return "Connection didn't work";
  }


  /**
   * Add a team to the database
   * $uname: The name of the user to whom the team belongs
   * $an array containing the name and form of all pokemon in the team
   * $team_id (optional): the id of the team being added. New teams should always
   * omit this parameter as it is only meant for modifying an existing team
   */
  function create_team($uname, $team, $team_id = false) {
    $create_team_validate = create_team_validate($uname, $team);
	if($create_team_validate) {
		return $create_team_validate;
	}

	global $conn;

	$max_teamid_query = "SELECT MAX(teamid) from team WHERE uname = ?";

	if($team_id) {
	  $new_team_id = $team_id;
	} else if($get_max_id = $conn->prepare($max_teamid_query)) {
		$new_team_id = 1;
		$get_max_id->bind_param("s", $uname);
		$get_max_id->execute();
		$get_max_id->bind_result($max_id);

		while($get_max_id->fetch()) {
			$new_team_id = $max_id + 1;
		}

		$get_max_id->close();
	} else {
		echo "Connection didn't work I guess";
	}

	foreach($team->members as $current_member) {
		$insert_query = "INSERT INTO team VALUES (?, ?, ?, ?)";
		if($insert_team = $conn->prepare($insert_query)) {
			error_log('inserting ' . $current_member->name);
			$insert_team->bind_param("siss", $uname, $new_team_id, $current_member->name, $current_member->form);
			$insert_team->execute();
		} else {
			error_log("Error(" . $conn->errno . "): " .$conn->error);
		}
	}

  }

	function create_team_validate($uname, $team) {
		global $conn;

		$valid = false;
		//Check to see if the team size is valid (0 to 6)
		if(sizeof($team->members) > 6) {
			return "Cannot create a team of more than 6 pokemon";
		} else if(sizeof($team->members) == 0) {
			return "User must enter at least one pokemon";
		}

		//Check to make sure that the user this team is being created for exists
		$validate_user_query = "SELECT uname from user WHERE uname = ?";
		if($validate_user = $conn->prepare($validate_user_query)) {
			$validate_user->bind_param('s', $uname);
			$validate_user->execute();
			$validate_user->bind_result($res);

			while($validate_user->fetch()) {
				$valid = true;
			}

			if(!$valid) {
				return "Invalid user name " . $uname . " entered";
			}
		}

		//Check to make sure that all of the pokemon on this team exist
		
		foreach($team->members as $current_member) {
			$valid =  false;
			$validate_team_query = "SELECT name FROM pokemon WHERE name = ?";
			if($validate_team = $conn->prepare($validate_team_query)) {
				$validate_team->bind_param("s", $current_member->name);
				$validate_team->execute();
				$validate_team->bind_result($res);

				while($validate_team->fetch()) {
					$valid = true;
				}
				if(!$valid) {
					return "Invalid pokemon name '" . $current_member->name . "' entered";
				}
			} 
		}
		return false;
	}

  /**
   * Remove a team from the database
   * $uname: The name of the user of the team you're removing
   * $team_id: The id of the team being removed
   */
  function remove_team($uname, $team_id) {
	global $conn;

	$query = "DELETE FROM team WHERE uname = ? AND teamid = ?";

	if($remove_team = $conn->prepare($query)) {
		$remove_team->bind_param("si", $uname, $team_id);
		$remove_team->execute();
	}
  }

  /**
   * Modify an existing team by removing the one with that ID and inserting the new one
   * $uname: The name of the user that owns the team
   * $team: An array containing the team
   * $team_id: The id of the team being modified
   */
  function modify_team($uname, $team, $team_id) {
	global $conn;
	$validate_team_id = false;

	$query = "SELECT teamid FROM team WHERE teamid = ?";

	if($validate = $conn->prepare($query)) {
	  $validate->bind_param("i", $team_id);
	  $validate->execute();
	  $validate->bind_result($tid);

	  while($validiate->fetch()) {
		  $validate_team_id = true;
		  break;
	  }
	}

	if($validate_team_id) {
		remove_team($uname, $team_id);
		create_team($uname, $team, $team_id);
  	} else {
		return "Team " . $team_id . " does not exist";
	}
  }

  function user_login($uname, $pass) {
	  global $conn;
	  $login_successful = false;

	  $query = "SELECT uname FROM user WHERE uname = ? AND pass = SHA2(?, 512)";
	  if($verify_login = $conn->prepare($query)) {
		$verify_login->bind_param("ss", $uname, $pass);
		$verify_login->execute();
		$verify_login->bind_result($valid_uname);

		while($verify_login->fetch()) {
			$verify_login->close();
			$login_successful = true;
		}
	  }

	  if($login_successful) {
		$login_successful = create_login_session($uname);
	  }

	  return $login_successful;
  }

  function create_login_session($uname) {
	global $conn;
	$query = "INSERT INTO login_session VALUES (?, ?)";

	$cookie_code = random_string(30);

	if($create_login_session = $conn->prepare($query)) {
		$create_login_session->bind_param("ss", $uname, $cookie_code);//Enter a blank IP for now, will fix this later
		$create_login_session->execute();
		$create_login_session->close();
		return Array('cookie_code' => $cookie_code);
	} else {
		error_log("Error(" . $conn->errno . "): " .$conn->error);
	}
  }

  //Validate a cookie from a username. In the future this will take a username, ip address and specially generated code
  //but for now we're doing this so we can get it up and running
  function validate_cookie($uname, $cookie_code) {
	global $conn;
	$query = "SELECT uname FROM login_session WHERE uname = ? AND cookie_code = ?";

	error_log('validating for ' . $uname . ' with a cookie code of ' . $cookie_code);

	if($validate_cookie = $conn->prepare($query)) {
		$validate_cookie->bind_param("ss", $uname, $cookie_code);
		$validate_cookie->execute();
		$validate_cookie->bind_result($res);

		while($validate_cookie->fetch()) {
			$validate_cookie->close();
			error_log('cookie validated');
			return true;
		}
	}
	error_log('the cookie did not check out');
	return false;
  }

  function create_user($uname, $pass) {
	  global $conn;
	  //First check to see if the user exists already
	  $check_user_query = "SELECT uname FROM user WHERE uname = ?";
	  if($check_user = $conn->prepare($check_user_query)) {
		  $check_user->bind_param('s', $uname);
		  $check_user->execute();
		  $check_user->bind_result($res);

		  //If we found a user with this name, return false because we can't create the new account
		  while($check_user->fetch()) {
			  if($res) {
				  return Array("Error" => "The user name " . $uname . " already exists");
			  }
		  }
	  }

	  $create_user_query = 'INSERT INTO user VALUES(?, SHA2(?, 512))';
	  if($create_user = $conn->prepare($create_user_query)) {
		  $create_user->bind_param('ss', $uname, $pass);
		  $create_user->execute();
	  }
	  return false;
  }

  function random_string($length) {
	$str = "";
	$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	$max = count($characters) - 1;
	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $max);
		$str .= $characters[$rand];
	}
	return $str;
}
?>