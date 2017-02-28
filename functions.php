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
		foreach($team as $member) {
			$matchups = get_pokemon_matchups($member, $generation);
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
	function get_pokemon_matchups($name, $generation) {
		global $conn;
				
		//First all types need to be stored
		$query = "select `type1`, `type2` from pokemon where `name` = ?";
		$types = get_types(6);	
		
		//Get the types of the current Pokemon based on its name
		$get_pokemon_types = $conn->prepare($query);
		$get_pokemon_types->bind_param("s", $name);
		$get_pokemon_types->execute();
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
	function get_multiplier_by_type($type, $generation, $types) {
		global $conn;
		$multipliers = array();
		
		//Look at all types and see how each one matches up to the current type in the current generation
		foreach($types as $current_type) {
			$multiplier_query = "select `multiplier` from `matchup` where `attacker` = ? and `defender` = ? and `generation` = ?";
			if($get_multipliers = $conn->prepare($multiplier_query)) {
				$get_multipliers->bind_param("ssi", $current_type, $type, $generation);
				$get_multipliers->execute();
				$get_multipliers->bind_result($multiplier);
			
				while($get_multipliers->fetch()) {
					$multipliers[$current_type] = $multiplier;
				}
				
			}  else {
				echo "error in get_multiplier_by_type</br>";
				echo var_dump($conn->error) . "</br>";
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
			} else {
				echo "error in get_types</br>";
				echo var_dump($conn->error) . "<br/>";
				return false;
			}
	}
	
	//Retrieve the names of all Pokemon in the database who were in $generation
	function get_pokemon_names($generation) {
		global $conn;
		
		$query = "SELECT `name` FROM `pokemon` WHERE `generation` >= ?";
		if($get_pokemon_names = $conn->prepare($query)) {
			$get_pokemon_names->bind_param("i", $generation);
			$get_pokemon_names->execute();
			$get_pokemon_names->bind_result($res);			
			
			$pokemon = Array();
			
			while($get_pokemon_names->fetch()) {
				$pokemon[] = $res;
			}
			
			return $pokemon;
		}	else {
				echo "error in get_types</br>";
				echo var_dump($conn->error) . "<br/>";
				return false;
		}
	}

?>