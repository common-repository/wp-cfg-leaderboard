<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); 
function wpcfg_shortcode_leaderboard( $atts, $content = null ) {

	$defaults = array(
		'affiliateid'      => get_option('wpcfg_affiliateid'),
		'addcolumns' 	   => '', // values: age,weight,height,division
		'showevents' 	   => '1,2,3,4,5,6'
	);
	
	extract( shortcode_atts( $defaults, $atts ) );
		$showcols =array_filter(array_map('trim',explode(',',$addcolumns)));
		$showevt =array_filter(array_map('trim',explode(',',$showevents)));
		$text="";
	

		if (!$affiliateid)
		{
			$text.="Please set affiliateid in plugin parameters or shortcode attribute";
		}
		else
		{
			//debug
			//$affiliateid="";
			$text.= '
	<div class="filter-panel">
		<label>Division: 
		<select id="divisionselect">
		<option value="0">All</option>
		<option value="1" selected>Men 16-54</option>
		<option value="2">Women 16-54</option>
		<option value="14">Boys 14-15</option>
		<option value="15">Girls 14-15</option>
		<option value="16">Boys 16-17</option>
		<option value="17">Girls 16-17</option>
		<option value="18">Men 35-39</option>
		<option value="19">Women 35-39</option>
		<option value="12">Men 40-44</option>
		<option value="13">Women 40-44</option>
		<option value="3">Men 45-49</option>
		<option value="4">Women 45-49</option>
		<option value="5">Men 50-54</option>
		<option value="6">Women 50-54</option>
		<option value="7">Men 55-59</option>
		<option value="8">Women 55-59</option>
		<option value="9">Men 60+</option>
		<option value="10">Women 60+</option>
		</select></label>
		
		<label>Occupation: 
		<select id="professionselect">
		<option value="-1">All</option>
		<option value="0">Military</option>
		<option value="1">Law Enforcement</option>
		<option value="2">Firefighter</option>
		<option value="3">EMT/Paramedic</option>
		<option value="4">Registered Nurse</option>
		<option value="5">Medical Doctor</option>
		<option value="6">School Teacher</option>
		<option value="7">Student</option>
		<option value="8">Garage CrossFitter</option>
		</select></label>
 
</div>
	<div id="jsGrid"></div>
	
	<script>';
			$lineprinted=false;
			$text.= "var clients = [";
			for ($division=1; $division <16;$division++)
			{
				if (3==$division)
					$division=7;
				if (11==$division)
					$division=14;
			$totalrank=0;	

			$data = array(  "affiliate" => $affiliateid,
							"division"=> $division);    
			$url = sprintf("%s?%s", "https://games.crossfit.com/competitions/api/v1/competitions/open/2018/leaderboards",  http_build_query($data));    					
			$result=wp_remote_get( $url );
			if ( is_array( $result ) ) {
				$result = $result['body']; 
			}						
			
			$result = json_decode($result);

			if (isset($result->dataType))
			{
				    			
			foreach ($result->leaderboardRows as $line)
			{
				$totalrank++;
				if ($lineprinted)
				{
					$text.= ",";
				}
				else
				{
					$lineprinted=true;
				}
				$text.= "{";
				$text.= '"TotalRank": "'.$line->overallRank.'"';
				$text.= ',"TotalScore": "'.$line->overallScore.'"';
				$athlete = $line->entrant;
				$text.= ',"Name": "'.$athlete->lastName.', '.$athlete->firstName.'"';
				if (!empty($showcols) && in_array('age',$showcols))
					$text.= ',"Age": "'.$athlete->age.'"';
				//$text.= ',"Gender": "'.$athlete->gender.'"';
				$text.= ',"Division": "'.$athlete->divisionId.'"';
				$text.= ',"Division2": "'.$division.'"';
				$text.= ',"Profession": '.$athlete->profession.'';
										
				if (!empty($showcols) && in_array('height',$showcols))
				{	
					$height = substr($athlete->height,0,strpos($athlete->height," "));
					if ("us"==get_option('wpcfg_measurements'))
					{		
						if ("c"==substr($athlete->height,strpos($athlete->height," ")+1,1))
							$height = round($height/2.54,0);
					}
					else
					{		
						if ("i"==substr($athlete->height,strpos($athlete->height," ")+1,1))
							$height = round($height*2.54,0);
					}
					$text.= ',"Height": "'.$height.'"';
				}
				
				if (!empty($showcols) && in_array('weight',$showcols))
				{
					$weight = substr($athlete->weight,0,strpos($athlete->weight," "));
					if ("us"==get_option('wpcfg_measurements'))
					{		
						if ("k"==substr($athlete->weight,strpos($athlete->weight," ")+1,1))
							$weight = round($weight*2.20462,1);
					}
					else
					{		
						if ("l"==substr($athlete->weight,strpos($athlete->weight," ")+1,1))
							$weight = round($weight/2.20462,1);
					}	
					$text.= ',"Weight": "'.$weight.'"';
				}
					
				$scores = $line->scores;
				foreach ($scores as $score)
				{
				$text.= ',"Rank'.$score->ordinal.'": '.$score->rank.'';
				
		
				$scorenum = substr($score->scoreDisplay,0,strpos($score->scoreDisplay," "));
				if ("us"==get_option('wpcfg_measurements'))
				{		
					if ("k"==substr($score->scoreDisplay,strpos($score->scoreDisplay," ")+1,1))
						$scorenum = round($scorenum*2.20462,0);
				}
				else
				{		
					if ("l"==substr($score->scoreDisplay,strpos($score->scoreDisplay," ")+1,1))
						$scorenum = round($scorenum/2.20462,0);
				}
				
				$text.= ',"Score'.$score->ordinal.'": "'.$score->scoreDisplay.'"';
				}
			
				$text.= "}\n";
				
			}
			}
			else
			{
			}
			}
			$text.= "];";

			$text.='
			
	var divisions = [
		 { Name: "", Id: "" },
        { Name: "Men 18-34", Id: "1" },
        { Name: "Women 16-54", Id: "2" },
        { Name: "Men 45-49", Id: "3" },
        { Name: "Women 45-49", Id: "4" },
        { Name: "Men 50-54", Id: "5" },
        { Name: "Women 50-54", Id: "6" },
        { Name: "Men 55-59", Id: "7" },
        { Name: "Women 55-59", Id: "8" },
        { Name: "Men 60+", Id: "9" },
        { Name: "Women 60+", Id: "10" },
        { Name: "Men 40-44", Id: "12" },
        { Name: "Women 40-44", Id: "13" },
        { Name: "Boys 14-15", Id: "14" },
		{ Name: "Girls 14-15", Id: "15" },
		{ Name: "Boys 16-17", Id: "16" },
		{ Name: "Girls 16-17", Id: "17" },
		{ Name: "Men 35-39", Id: "18" },
		{ Name: "Women 35-39", Id: "19" }
		
    ];
	
	
	jQuery(function($) {
    $("#jsGrid").jsGrid({
        width: "100%",
        //height: "100%",
 
        inserting: false,
        editing: false,
        sorting: true,
		filtering: false,
        controller: {
            loadData: function (filter) {
                
				
			    return clients.filter(function (e) { 
					var joined= e.Profession >>> filter.Profession;
					return (
						(!filter.Division||(e.Division===filter.Division))&&
						(!filter.Division2||(e.Division2===filter.Division2))&&
						(!filter.Profession||(joined%2 ==1)));
						})}},
		 
		pageSize: 50,
        paging: true,
		
        data: clients,
 
        fields: [
			{ name: "TotalRank", title: "Rank", align: "center", type: "number", width: 12 ,filtering: false,visible: true},
			{ name: "TotalScore", title: "Points", align: "center", type: "number", width: 15 ,filtering: false,visible: true},
			{ name: "Name", type: "text",  validate: "required",width:80 ,filtering:false},';
			
			if (!empty($showcols) && in_array('age',$showcols))
            {
				$text.='{ name: "Age", align: "center", type: "number", width: 30 ,filtering:false,visible:true},';
			}
			
			if (!empty($showcols) && in_array('division',$showcols))
            {
				$text.='{ name: "Division",  align: "center", type: "select", items: divisions, valueField: "Id", textField: "Name" , autosearch: true, width: 30, visible:true},';
			}
			else
			{
				$text.='{ name: "Division",  type: "select",  items: divisions, valueField: "Id", textField: "Name" , autosearch: true, width: 30, visible:false},';
			}
			if (!empty($showcols) && in_array('weight',$showcols))
            {
				$text.='{ name: "Weight", align:"center", type: "number", width: 30 ,visible: true},';
			}
			
			if (!empty($showcols) && in_array('height',$showcols))
            {
				$text.='{ name: "Height", align:"center", title: "Height",type: "text", width: 50,filtering:false,visible: true },';
			}
			
			$text.='{ name: "Division2", type: "text", autosearch: true, width: 30,autosearch:true,filtering:true,visible:false},';
			
			if (!empty($showevt) && in_array('1',$showevt))
            {
				$text.='{ name: "Rank1", align: "center", title: "18.1", type: "number", width: 25,filtering:false },
			   { name: "Score1",align: "center", title: "Res", type: "text", width: 30,filtering:false },';
			}
         
			if (!empty($showevt) && in_array('2',$showevt))
            {
				$text.='{ name: "Rank2", align: "center", title: "18.2", type: "number", width: 25,filtering:false },
			   { name: "Score2", align: "center",title: "Res", type: "text", width: 30,filtering:false },';
			}
			if (!empty($showevt) && in_array('3',$showevt))
            {
				$text.='{ name: "Rank3", align: "center", title: "18.2A", type: "number", width: 25,filtering:false },
			   { name: "Score3", align: "center",title: "Res", type: "text", width: 30,filtering:false },';
			}
			if (!empty($showevt) && in_array('4',$showevt))
            {
				$text.='{ name: "Rank4", align: "center", title: "18.3", type: "number", width: 25,filtering:false },
			   { name: "Score4", align: "center",title: "Res", type: "text", width: 30,filtering:false },';
			}
			if (!empty($showevt) && in_array('5',$showevt))
            {
				$text.='{ name: "Rank5", align: "center", title: "18.4", type: "number", width: 25,filtering:false },
			   { name: "Score5", align: "center",title: "Res", type: "text", width: 30,filtering:false },';
			}
			if (!empty($showevt) && in_array('6',$showevt))
            {
				$text.='{ name: "Rank6", align: "center", title: "18.5", type: "number", width: 25,filtering:false },
			   { name: "Score6", align: "center",title: "Res", type: "text", width: 30,filtering:false },';
			}
			$text.=' { name: "Profession", title: "Beruf",type: "number", autosearch: true,visible: false},
            
        ]
    });
	var grid = $("#jsGrid").data("JSGrid"); 
	var gridFilter = grid.getFilter(); //get grid original filter
	gridFilter.Division2="1";
	gridFilter.Division="";
	gridFilter.Profession="";
	
	grid.search(gridFilter); //call server with filter
	});
	
	function wpcfg_setFilter(jq)
	{
		 var division = jq("#divisionselect").val();
		
		var grid = jq("#jsGrid").data("JSGrid"); 
		var gridFilter = grid.getFilter(); //get grid original filter
		switch (division)
		{
			case "0": gridFilter.Division2=""; gridFilter.Division="";break;
			case "1": gridFilter.Division2="1"; gridFilter.Division="";break;
			case "2": gridFilter.Division2="2"; gridFilter.Division="";break;
			case "3": gridFilter.Division2=""; gridFilter.Division="3";break;
			case "4": gridFilter.Division2=""; gridFilter.Division="4";break;
			case "5": gridFilter.Division2=""; gridFilter.Division="5";break;
			case "6": gridFilter.Division2=""; gridFilter.Division="6";break;
			case "7": gridFilter.Division2="7"; gridFilter.Division="";break;
			case "8": gridFilter.Division2="8"; gridFilter.Division="";break;
			case "9": gridFilter.Division2="9"; gridFilter.Division="";break;
			case "10": gridFilter.Division2="10"; gridFilter.Division="";break;
			case "12": gridFilter.Division2=""; gridFilter.Division="12";break;
			case "13": gridFilter.Division2=""; gridFilter.Division="13";break;
			case "14": gridFilter.Division2=""; gridFilter.Division="14";break;
			case "15": gridFilter.Division2=""; gridFilter.Division="15";break;
			case "16": gridFilter.Division2=""; gridFilter.Division="16";break;
			case "17": gridFilter.Division2=""; gridFilter.Division="17";break;
			case "18": gridFilter.Division2=""; gridFilter.Division="18";break;
			case "19": gridFilter.Division2=""; gridFilter.Division="19";break;
			
			default: gridFilter.Division2=""; gridFilter.Division="";break;
			
		}
		var profession = jq("#professionselect").val();
		
		if (profession == -1)
			gridFilter.Profession="";
		else
			gridFilter.Profession=profession;

		grid.search(gridFilter); //call server with filter

		
	}
	
		
	jQuery(function($) {
	$("#divisionselect").on("change",function() {		
			wpcfg_setFilter($);

    })});
	
	jQuery(function($) {
	$("#professionselect").on("change",function() {
        wpcfg_setFilter($);
    })});
	
</script>
			';
		if (get_option('wpcfg_showpoweredby')=='on')
		{
			$text.='<div style="margin-top: 8px; font-size: 12px;text-align: center;" >Leaderboard provided by <a target="_blank"  rel="noopener noreferrer"  href="http://amrap42.28a.de/wp-cfg-leaderboard/">WP CFG Leaderboard plugin</a> for WordPress.</div>';
			
		}

		}
	if ( isset( $text ) && ! empty( $text ) && ! is_feed() ) {
		return do_shortcode( $text );
	}
}

if ( ! shortcode_exists( 'wpcfg_leaderboard' ) ) {
	add_shortcode( 'wpcfg_leaderboard', 'wpcfg_shortcode_leaderboard' );
}






