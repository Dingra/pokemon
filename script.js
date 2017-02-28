var types;				//All Pokemon types
var available_pokemon;

jQuery(document).ready(function(){
	
	//Get all Pokemon in generation 6 and earlier and add them to the auto complete
	var p_url = "ajax.php?method=get_pokemon_names&generation=6";
	ajaxCall (p_url, function(result) {
		var res = result.split(":");
		available_pokemon = JSON.parse(res);
		
		for(var i = 1; i <= 6; i ++) {
			jQuery('#team').prepend("<div class = \"pinput\"><label for=\"p" + i + "\">Pokemon " + i + "<input id = \"p" + i + "\"/></div>");
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
		types = getTypes();
		console.log("Clicked");
	
		var url = "ajax.php?method=get_aggregate_matchups&team=";
		
		var team = new Array();
		
		for(var i = 1; i <= 6; i ++){
			var selector = "#p" + i;
			var pokemon = jQuery(selector).val()
			
			if(pokemon != "") {
				team.push(pokemon.toLowerCase());
			}
		}

		
		var t = JSON.stringify(team);
		
		url = url + t;
		
		console.log("Url is: " + url);
		
		ajaxCall(url, function(result) {
			var table = build_table(result);
		});		
	});
	
	$("#reset").click(function(){
		reset();
		removePokemon();
	});
});

//Called by the reset button.  Removes all inputs apart from first pokemon from the team evaluator page
function removePokemon(){
	jQuery("#p2").hide();
	jQuery("#p3").hide();
	jQuery("#p4").hide();
	jQuery("#p5").hide();
	jQuery("#p6").hide();
	
	jQuery(".add").hide();
	jQuery("#p2add").show();
	
	jQuery(".t1").append("<option value = 'none'>none</option>");
	jQuery(".t1").val("none");
}

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
	console.log(types);
	
	var res = JSON.parse(matchups);
	
	var damage_levels = Array(
		0,
		0.25,
		0.5,
		1,
		2,
		4
	);
	
	var aggregate_table = "<table><tr><td></td><td>0</td><td>0.25</td><td>0.5</td><td>1</td><td>2</td><td>4</td><td class=\"blank\"></td><td></td><td>0</td><td>0.25</td><td>0.5</td><td>1</td><td>2</td><td>4</td></tr>";

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
				
			} else if (j == 4) {
				td_class = "vulnerable first" + vulnerable;
			} else if (j == 5){
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
	console.log("Getting types");
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