var types;				//All Pokemon types
var available_pokemon;

jQuery(document).ready(function(){
	
	//Get all Pokemon in generation 6 and earlier and add them to the auto complete
	var p_url = "ajax.php?method=get_pokemon_names&generation=7";
	ajaxCall (p_url, function(result) {
		var res = result.split(":");
		available_pokemon = JSON.parse(res);
		
		for(var i = 1; i <= 6; i ++) {
			jQuery('#team #team-inputs').append("<div class = \"pinput\"><label for=\"p" + i + "\">Pokemon " + i + "<input id = \"p" + i + "\"/></div>");
			jQuery('#p' + i).autocomplete({
				source: available_pokemon
			});
		}
	});

	$('td').attr('width', 100);
	$('td').attr('align', 'center');
	
	/**
		Add a new type to the database. This was used to set up early on
	*/
	jQuery("#addType").click(function(){
		
		var type = jQuery('#ptype').val();
		var url = "ajax.php?method=addType&type=" + type;
		//window.alert("URL = " + url);
		ajaxCall (url, function(result) {
			if(!result){
				window.alert("Something has gone wrong");
				//$('#addType').val('');
			}
			else{
				alert("The type was added successfully");
			}
		});
	});
	
	jQuery("#addType").click(function(){
		var type = jQuery('#ptype').val();
		var url = "ajax.php?method=addType&type=" + type;
		
		ajaxCall (url, function(result) {
			if(!result){
				window.alert("Something has gone wrong");
			}
			else{
				alert("The type was added successfully");
			}
		});
	});
	
	//Add an attacking and defending type to the database where the attack is "Super Effective"
	jQuery("#addStrength").click(function(){
		alert("Function called");
		var att = jQuery('#attacker').val();
		var def = jQuery('#defender').val();
		var gen = jQuery('#generation').val();
		var url = "ajax.php?method=addStrength&attacker=" + att + "&defender=" + def + "&generation=" + gen;
		
		ajaxCall (url, function(result) {
			if(!result){
				window.alert("Something has gone terribly wrong");
			}
			else{
				window.alert(result);
			}
		});
	});
	
	//Add an attacking and defending type to the database where the attack is "Not very effective" or ineffective
	jQuery("#addWeakness").click(function(){
		var att = jQuery('#attacker').val();
		var def = jQuery('#defender').val();
		var gen = jQuery('#generation').val();
		var url = "ajax.php?method=addWeakness&attacker=" + att + "&defender=" + def + "&generation=" + gen;
		
		if(document.getElementById("ineffective").checked == true)
			url = url + "&ineffective=true";
		else
			url= url + "&ineffective=false";
			
		
		ajaxCall (url, function(result) {
			document.write("Result..." + result);
		
			if(!result){
				window.alert("Something has gone terribly wrong");
			}
			else{
				
			}
		});
	});
	
	jQuery("#getMatchups").click(function(){
		var url = "ajax.php?method=getMatchups&type1=fire&type2=flying";
		
		ajaxCall(url, function(result) {
			var arr = result.split("%%");
			var i;
			var s = "";
			
			window.alert(result);
		});
	});
	
	jQuery("#eval").click(function(){
		if(validate()){
			var current_table = jQuery('div#aggregate_table table');
			if(current_table) {
				jQuery('div#aggregate_table table').remove();
			}
						
			if(jQuery("div.card")) {
				jQuery("div.card").remove();
			}		
	
			types = getTypes();
	
			var matchup_url = "ajax.php?method=get_aggregate_matchups&generation=7&team=";
			var info_card_url = "ajax.php?method=get_card_info&generation=7&team=";
			
		
			var team = new Array();
		
			for(var i = 1; i <= 6; i ++){
				var selector = "#p" + i;
				var pokemon = jQuery(selector).val()
			
				if(pokemon != "") {
					team.push(pokemon);
				}
			}

		
			var t = JSON.stringify(team);		
		
			matchup_url = matchup_url + t;
			info_card_url = info_card_url + t;		
			
			ajaxCall(matchup_url, function(result) {
				table = build_table(result);
				build_table_responsive(result);
			});
			
			ajaxCall(info_card_url, function(result) {
				build_info_card(result);
			});	
			
		} else alert("Failed!");
	});
	
	$("#reset").click(function(){
		reset();
		removePokemon();
	});
});

function findType(s){
	var i;
	for(i = 0; i < types.length-1; i ++){
		if(s == types[i])
			return i;
	}
}

function addTypeDamage(){
	var arr = {};
	
	var i;
	for(i = 0; i < types.length; i ++){
		arr[types[i]] = 0;
	}
	
	return arr;
}

function build_table(matchups, generation) {
	
	var res = JSON.parse(matchups);
	
	var damage_levels = Array(
		0,
		0.25,
		0.5,
		2,
		4
	);
	
	var aggregate_table = "<table><tr><td></td><td>0</td><td>0.25</td><td>0.5</td><td>2</td><td>4</td><td class=\"blank\"></td><td></td><td>0</td><td>0.25</td><td>0.5</td><td>2</td><td>4</td></tr>";

	var i,j ;

	for (i = 0; i < types.length; i ++) {
		current_type = types[i];
		var resistant  = ""
		var vulnerable = "";
		
		if((res[current_type][0.25] + res[current_type][0.5] + res[current_type][0]) >= 3) {
			resistant = " true";
		}
		
		if(res[current_type][2] + res[current_type][4] >= 3) {
			vulnerable = " true";
		}		
		
		if(i % 2 == 0) {
			if(i % 4 == 0) {
				tr_class = " class = \"even\"";
			}
			else {
				tr_class = "";
			}
			aggregate_table = aggregate_table + "<tr" + tr_class + "><td class=\"type\">" + current_type + "</td>";
		} else {
			aggregate_table = aggregate_table + "<td class=\"blank\"></td>" + "<td class=\"type\">" + current_type + "</td>";
		}
		
		for(j = 0; j < damage_levels.length; j ++) {
			var td_class;
			
			if(j <= 2){
				td_class = "resistant" + resistant;
				
				if(j == 2) {
					td_class += " last";
				} else if(j == 0) {
					td_class += " first";
				}
				
			} else if (j == 3) {
				td_class = "vulnerable first" + vulnerable;
			} else if (j == 4){
				td_class = "vulnerable last" + vulnerable;
				
			}			
			 else {
				td_class = "";
			}
			aggregate_table = aggregate_table + "<td " + "class=\"" + td_class + "\">" + res[current_type][damage_levels[j]] + "</td>";
		}
		
		if(i % 2 != 0) {
			aggregate_table = aggregate_table + "</tr>";
		}
	}

	aggregate_table = aggregate_table + "</table>";
	
	jQuery('#aggregate_table').append(aggregate_table);
}


function build_table_responsive(matchups, generation) {
	
	var res = JSON.parse(matchups);
	
	var damage_levels = Array(
		0,
		0.25,
		0.5,
		2,
		4
	);
	
	var aggregate_table = "<table class=\"responsive\"><tr><td></td><td>0</td><td>0.25</td><td>0.5</td><td>2</td><td>4</td></tr>";

	var i,j ;

	for (i = 0; i < types.length; i ++) {
		current_type = types[i];
		var resistant  = ""
		var vulnerable = "";
		
		//Count the number of Pokemon for whom the attacking type is "Not very effective" or ineffective
		if((res[current_type][0.25] + res[current_type][0.5] + res[current_type][0]) >= 3) {
			resistant = " true";
		}
		
		//Count the number of Pokemon for whom the attacking type is "Super Effective"
		if(res[current_type][2] + res[current_type][4] >= 3) {
			vulnerable = " true";
		}		
		
		//Add the "even" class so that the table rows can alternate between lighter and darker gray
		if(i % 2 == 0) {
			tr_class = " class = \"even\"";
		} else {
			tr_class = "";
		}
		
		aggregate_table = aggregate_table + "<tr" + tr_class + "><td class=\"type\">" + current_type + "</td>";
		
		for(j = 0; j < damage_levels.length; j ++) {
			var td_class;
			
			if(j <= 2){
				td_class = "resistant" + resistant;
				
				if(j == 2) {
					td_class += " last";
				} else if(j == 0) {
					td_class += " first";
				}
				
			} else if (j == 3) {
				td_class = "vulnerable first" + vulnerable;
			} else if (j == 4){
				td_class = "vulnerable last" + vulnerable;
				
			}			
			 else {
				td_class = "";
			}
			aggregate_table = aggregate_table + "<td " + "class=\"" + td_class + "\">" + res[current_type][damage_levels[j]] + "</td>";
		}
		
		if(i % 2 != 0) {
			aggregate_table = aggregate_table + "</tr>";
		}
	}

	aggregate_table = aggregate_table + "</table>";
	
	jQuery('#aggregate_table').append(aggregate_table);
}

//Contains basic info on each Pokemon on the team
function build_info_card(card_info) {
	info = JSON.parse(card_info);
	
	
	var i = 0;
	var pokemon_name, nat_dex_id, type1, type2;
	while(info[i]) {		
		pokemon_name = info[i]["name"];
		nat_dex_id = info[i]["natDexId"];
		type1 = info[i]["type1"];
		type2 = info[i]["type2"];
		
		
		var type2_text = "";
		if(type2) {
			type_2_text = " Type 2: " + type2;
		}
		
		var info_card = "<div class=\"card\"> " +
			"<span class=\"number\">#" + nat_dex_id + "</span><br />" +
			"<span class=\"name\">" + pokemon_name + "</span><br />" +
			"<span class=\"type\">" + "Type 1: " + type1 + "</span><br /><span>" + type_2_text + "</span></div>";
			
		jQuery("div#team_cards").append(info_card);
		i++;
	}
}

function keys(obj){
	var str = "";

   var keys = [];
   for(var key in obj){
      keys.push(key);
	  str = str + " " + key;
   }
   
   return keys;
}

function getTypes() {
	var turl = "ajax.php?method=getTypes";
	ajaxCall (turl, function(result) {
		var res = result.split(":");
		types = JSON.parse(res);
	});
	return types;
}

function ajaxCall(url, f)
{
	jQuery.ajax({
		url:url, 
		success:f
	});
}

//Ensure that only Pokemon chosen from the drop downs are valid
function validate() {
	var i;
	
	for(i = 1; i <= 6; i ++) {
		var selector = "#p" + i;
		var currentPokemon = jQuery(selector).val();
		if(currentPokemon != "" && !(inArray(currentPokemon, available_pokemon))) {
			alert(currentPokemon + " is not a valid Pokemon in this generation");
			return false;
		}
	}
	
	return true;
}

function inArray(needle, haystack) {
	var i;
	for(i = 0; i < haystack.length; i ++) {
		if(needle == haystack[i]) {
			return true;
		}
	}
	
	return false;
}
