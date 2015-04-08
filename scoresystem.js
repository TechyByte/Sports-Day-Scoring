// Live Results refresher timeout id
var lrRID;

// Returns appropriate suffix for given number
function getNumberSuffix(n)
{
	if(n.charAt(n.length - 2) == "1") return "th";			// All numbers 10-19 take 'th'
	if(n.charAt(n.length - 1) == "1") return "st";			// All other numbers ending in 1 take 'st'
	if(n.charAt(n.length - 1) == "2") return "nd";			// All other numbers ending in 2 take 'nd'
	if(n.charAt(n.length - 1) == "3") return "rd";			// All other numbers ending in 3 take 'rd'
	return "th";											// Everything else takes 'th'
}

// Updates Live Results panel repeatedly
function lrUpdate()
{
	// Assuming we're on the Live Results panel
	if(typeof $("#lr_autorefreshtime").val() != "undefined")
	{
		// Call all updaters
		$("#lr_totalscore_updatetrigger").change();
		$("#lr_newrecord_updatetrigger").change();
		$("#lr_highscore_updatetrigger").change();
		$("#lr_scoreprogress_updatetrigger").change();
		$("#lr_eventscore_updatetrigger").change();
		
		// Set timeout
		if($("#lr_autorefreshtime").val() != 0) lrRID = window.setTimeout(lrUpdate, $("#lr_autorefreshtime").val() * 1000); else window.clearTimeout(lrRID);
	}
}

// In-page dynamics (through jQuery)
$(document).ready(function(){

	// Text input boxes that clear themselves and enable any linked buttons on first focus
	$(".clearOnFirstFocus").focus(function(){
		// If this is the first focus, clear the box and note this is the first focus
		if(!$(this).hasClass("hadFocus")) $(this).attr("value", "");
		$(this).addClass("hadFocus");
		
		// Enable any linked buttons
		$("#btn_" + $(this).attr("id")).removeAttr("disabled");
	});
	
	// Drop down menus that enable / disabled certain buttons if the default value (value = 0) is selected
	$(".disallowOptionZero").change(function(){
		// Is this option zero?
		if($(this).val() == 0)
		{
			// Disable any linked buttons
			$("#btn_" + $(this).attr("id")).attr("disabled", "disabled");
		} else {
			// Enable any linked buttons
			$("#btn_" + $(this).attr("id")).removeAttr("disabled");
		}
	});
	
	// Forms that cannot be submitted
	$(".nosubmit").submit(function(event){
		event.preventDefault();
	});
	
	// Competition Template Settings panel button to add new Team row
	var team_next_row = 0;
	var team_row_count = 0;
	$("#btn_ct_addteam").click(function(){
		// Generate new row
		team_next_row++;
		team_row_count++;
		var newline = "<tr id=\"teamrow" + team_next_row + "\">";
		newline += "<td><input type=\"text\" name=\"teamname[" + team_next_row + "]\" size=\"30\" maxlength=\"50\" id=\"teamnamebox" + team_next_row + "\"></td>";
		newline += "<td><button type=\"button\" id=\"teamremover" + team_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_ct_teams").append(newline);
		$("[id^=\"schemeentrantsbox\"]").change();
		
		// Bindings for remove button
		$("#teamremover" + team_next_row).click(function(){
			team_row_count--;
			$("#teamrow" + $(this).prop("id").substr(11)).remove();
			$("[id^=\"schemeentrantsbox\"]").change();
		});
	});
	
	// Competition Template Settings panel button to add new Subgroup row
	var sub_next_row = 0;
	var sub_row_count = 0;
	$("#btn_ct_addsub").click(function(){
		// Generate new row
		sub_next_row++;
		sub_row_count++;
		var newline = "<tr id=\"subrow" + sub_next_row + "\">";
		newline += "<td><input type=\"text\" name=\"subname[" + sub_next_row + "]\" size=\"30\" maxlength=\"50\" id=\"subnamebox" + sub_next_row + "\"></td>";
		newline += "<td><button type=\"button\" id=\"subremover" + sub_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_ct_subs").append(newline);
		$("[id^=\"eventsublisttrigger\"]").change();
		
		// Bindings for subgroup name box
		$("#subnamebox" + sub_next_row).change(function(){
			$("[id^=\"eventsublisttrigger\"]").change();
		});
		
		// Bindings for remove button
		$("#subremover" + sub_next_row).click(function(){
			sub_row_count--;
			$("#subrow" + $(this).prop("id").substr(10)).remove();
			$("[id^=\"eventsublisttrigger\"]").change();
		});
	});
	
	// Competition Template Settings panel button to add new Scoring Scheme row
	var scheme_next_row = 0;
	var scheme_row_count = 0;
	$("#btn_ct_addscheme").click(function(){
		// Generate new row
		scheme_next_row++;
		scheme_row_count++;
		var newline = "<tr id=\"schemerow" + scheme_next_row + "\">";
		newline += "<td><input type=\"text\" name=\"schemename[" + scheme_next_row + "]\" size=\"20\" maxlength=\"50\" id=\"schemenamebox" + scheme_next_row + "\"></td>";
		newline += "<td><input type=\"text\" name=\"schemeentrants[" + scheme_next_row + "]\" size=\"3\" id=\"schemeentrantsbox" + scheme_next_row + "\"></td>";
		newline += "<td><select name=\"schemeresultorder[" + scheme_next_row + "]\" id=\"schemeorderselect" + scheme_next_row + "\">";
		newline += "<option value=\"desc\">High</option>";
		newline += "<option value=\"asc\">Low</option>";
		newline += "</select></td>";
		newline += "<td><input type=\"text\" name=\"schemeresulttype[" + scheme_next_row + "]\" size=\"15\" maxlength=\"30\" id=\"schemetypebox" + scheme_next_row + "\"></td>";
		newline += "<td><input type=\"text\" name=\"schemeresultunit[" + scheme_next_row + "]\" size=\"10\" maxlength=\"20\" id=\"schemeunitbox" + scheme_next_row + "\"></td>";
		newline += "<td><input type=\"text\" name=\"schemeresultunitdp[" + scheme_next_row + "]\" size=\"3\" id=\"schemeunitdpbox" + scheme_next_row + "\"></td>";
		newline += "<td><button type=\"button\" id=\"schemeremover" + scheme_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		newline += "<tr id=\"scorerow" + scheme_next_row + "\"><td colspan=\"6\">&nbsp;</td></tr>";
		$("#tbl_ct_schemes").append(newline);
		$("[id^=\"evententrantsbox\"]").change();
		
		// Bindings for scheme name box
		$("#schemenamebox" + scheme_next_row).change(function(){
			$("[id^=\"evententrantsbox\"]").change();
		});
		
		// Bindings for entrants per team
		$("#schemeentrantsbox" + scheme_next_row).change(function(){
			$("#scorerow" + $(this).prop("id").substr(17)).empty();
			var newline = "<td colspan=\"6\">";
			for(var i = 1; i <= team_row_count * $(this).val(); i++)
			{
				newline += i + "<sup>" + getNumberSuffix(i.toString()) + "</sup> <input type=\"text\" name=\"score[" + $(this).prop("id").substr(17) + "][" + i + "]\" size=\"1\" id=\"scorebox" + $(this).prop("id").substr(17) + "_" + i + "\"> ";
			}
			newline += "DNC <input type=\"text\" name=\"score[" + $(this).prop("id").substr(17) + "][0]\" size=\"1\" id=\"scorebox" + $(this).prop("id").substr(17) + "_0\">";
			newline += "<br><br></td>";
			$("#scorerow" + $(this).prop("id").substr(17)).append(newline);
			$("[id^=\"evententrantsbox\"]").change();
		});
		
		// Trigger change to create initial row
		$("#schemeentrantsbox" + scheme_next_row).change();
		
		// Bindings for remove button
		$("#schemeremover" + scheme_next_row).click(function(){
			scheme_row_count--;
			$("#schemerow" + $(this).prop("id").substr(13)).remove();
			$("#scorerow" + $(this).prop("id").substr(13)).remove();
			$("[id^=\"evententrantsbox\"]").change();
		});
	});
	
	// Competition Template Settings panel button to add new Event row
	var event_next_row = 0;
	var event_row_count = 0;
	$("#btn_ct_addevent").click(function(){
		// Generate new row
		event_next_row++;
		event_row_count++;
		var newline = "<tr id=\"eventrow" + event_next_row + "\">";
		newline += "<td><input type=\"text\" name=\"eventname[" + event_next_row + "]\" size=\"20\" maxlength=\"50\" id=\"eventnamebox" + event_next_row + "\"></td>";
		newline += "<td><input type=\"text\" name=\"evententrants[" + event_next_row + "]\" size=\"3\" id=\"evententrantsbox" + event_next_row + "\"></td>";
		newline += "<td><select name=\"eventscoreschemes[" + event_next_row + "]\" id=\"eventschemeselect" + event_next_row + "\">";
		newline += "</select></td>";
		newline += "<td><select name=\"eventsubs[][" + event_next_row + "]\" multiple=\"multiple\" size=\"3\" id=\"eventsubselect" + event_next_row + "\">";
		newline += "</select><input type=\"hidden\" name=\"eventsublisttrigger\" id=\"eventsublisttrigger" + event_next_row + "\"></td>";
		newline += "<td><input type=\"checkbox\" name=\"eventcountstolimit[" + event_next_row + "]\" checked=\"checked\" id=\"eventlimitchk" + event_next_row + "\"></td>";
		newline += "<td><button type=\"button\" id=\"eventremover" + event_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_ct_events").append(newline);
		
		// Bindings for entrants per team
		$("#evententrantsbox" + event_next_row).change(function(){
			$("#eventschemeselect" + $(this).prop("id").substr(16)).empty();
			var curboxval = $(this).val()
			var newline = "";
			$("[id^=\"schemeentrantsbox\"]").each(function(){
				if($(this).val() == curboxval)
				{
					newline += "<option value=\"" + $(this).prop("id").substr(17) + "\">" + $("#schemenamebox" + $(this).prop("id").substr(17)).val() + "</option>";
				}
			});
			$("#eventschemeselect" + $(this).prop("id").substr(16)).append(newline);
		});
		
		// Bindings for subgroup list update trigger
		$("#eventsublisttrigger" + event_next_row).change(function(){
			$("#eventsubselect" + $(this).prop("id").substr(19)).empty();
			var curid = $(this).prop("id").substr(19);
			var newline = "";
			$("[id^=\"subnamebox\"]").each(function(){
				newline += "<option value=\"" + $(this).prop("id").substr(10) + "\" id=\"eventsuboption" + curid + "_" + $(this).prop("id").substr(10) + "\">" + $(this).val() + "</option>";
			});
			$("#eventsubselect" + $(this).prop("id").substr(19)).append(newline);
		});
		
		// Trigger change to update initial data
		$("#evententrantsbox" + event_next_row).change();
		$("#eventsublisttrigger" + event_next_row).change();
		
		// Bindings for remove button
		$("#eventremover" + event_next_row).click(function(){
			event_row_count--;
			$("#eventrow" + $(this).prop("id").substr(12)).remove();
		});
	});
	
	// Day Settings panel button to add new Competitor rows
	var competitor_next_row = 0;
	var competitor_row_count = 0;
	$("[id^=\"btn_d_addcompetitor\"]").click(function(){
		// Extract list of separate competitor names
		var newcompetitors = $("#competitorentrybox" + $(this).prop("id").substr(19)).val().split("\n");
		// Clear competitor entry box
		$("#competitorentrybox" + $(this).prop("id").substr(19)).val("");
		// Generate new rows
		for(var newcomp in newcompetitors)
		{
			if(newcompetitors[newcomp] != "")
			{
				competitor_next_row++;
				competitor_row_count++;
				var newline = "<tr id=\"competitorrow" + competitor_next_row + "\">";
				newline += "<td><input type=\"text\" name=\"competitorname[" + $(this).prop("id").substr(19).split("_")[0] + "][" + $(this).prop("id").substr(19).split("_")[1] + "][" + competitor_next_row + "]\"";
				newline += " value=\"" + $.trim(newcompetitors[newcomp]) + "\" size=\"30\" maxlength=\"50\" id=\"competitornamebox" + competitor_next_row + "\"></td>";
				newline += "<td><button type=\"button\" id=\"competitorremover" + competitor_next_row + "\">Remove</button></td>";
				newline += "</tr>";
				$("#datainputrow" + $(this).prop("id").substr(19)).before(newline);
				
				// Bindings for remove button
				$("#competitorremover" + competitor_next_row).click(function(){
					competitor_row_count--;
					$("#competitorrow" + $(this).prop("id").substr(17)).remove();
				});
			}
		}
	});
	
	// Manage Records panel dropdown change event
	$("#drop_mr_eventpicker").change(function(){
		// Send AJAX request and process result
		$.post("ajaxserve_records.php", { e_id: $(this).val() }, function(data)
		{
			var record_data = data.split("\n");
			$("#mr_name").val(record_data[0]);
			$("#mr_team").val(record_data[1]);
			$("#mr_score").val(record_data[2]);
			$("#mr_yearset").val(record_data[3]);
		});
	});
	$("#drop_mr_eventpicker").change();
	
	// Edit Scores panel dropdown change event
	$("[id^=\"drop_es_competitor\"]").change(function(event){
		// Was 'missing from list' selected?
		if($(this).val() == -1)
		{
			// Show name entry box
			$("#tr_es_newcomp" + $(this).prop("id").substr(18)).css("display", "table-row");
		} else {
			// Hide name entry box
			$("#tr_es_newcomp" + $(this).prop("id").substr(18)).css("display", "none");
		}
	});
	
	// Live Results panel container visibility toggle
	$("[id^=\"sv_header\"]").click(function(event){
		event.preventDefault();
		if($("#sv_content" + $(this).prop("id").substr(9)).css("display") == "block")
		{
			$("#sv_content" + $(this).prop("id").substr(9)).css("display", "none");
		} else {
			$("#sv_content" + $(this).prop("id").substr(9)).css("display", "block");
		}
	});
	
	// Live Results panel total scores updater
	$("#lr_totalscore_updatetrigger").change(function(){
		// Send AJAX request and process result
		$.post("ajaxserve_liveresults_currentscores.php", { d_id: $(this).val() }, function(data)
		{
			var scoredata = data.split("\n");
			for(var i in scoredata)
			{
				$("#lr_totalscore_cell" + i).empty();
				$("#lr_totalscore_cell" + i).append(scoredata[i]);
			}
		});
	});
	
	// Live Results panel new records updater
	$("#lr_newrecord_updatetrigger").change(function(){
		// Send AJAX request and process result
		$.post("ajaxserve_liveresults_newrecords.php", { d_id: $(this).val() }, function(data)
		{
			var recorddata = data.split("\n");
			$("#lr_newrecord_list").empty();
			for(var i in recorddata)
			{
				$("#lr_newrecord_list").append("<li>" + recorddata[i] + "</li>");
			}
		});
	});
	
	// Live Results panel individual high scores updater
	$("#lr_highscore_updatetrigger").change(function(){
		// Send AJAX request and process result
		$.post("ajaxserve_liveresults_highscores.php", { d_id: $(this).val() }, function(data)
		{
			var scoredata = data.split("\n");
			for(var i in scoredata)
			{
				$("#lr_highscore_cell" + i).empty();
				$("#lr_highscore_cell" + i).append(scoredata[i]);
			}
		});
	});
	
	// Live Results panel event scoring progress updater
	$("#lr_scoreprogress_updatetrigger").change(function(){
		// Send AJAX request and process result
		$.post("ajaxserve_liveresults_scoreprogress.php", { d_id: $(this).val() }, function(data)
		{
			var progressdata = data.split("\n");
			$("#lr_scoreprogress_bar").css("width", progressdata[2] + "%");
			$("#lr_scoreprogress_bar").empty();
			$("#lr_scoreprogress_bar").append("Events Scored: " + progressdata[0] + " / " + progressdata[1] + " (" + progressdata[2] + "%)");
		});
	});
	
	// Live Results panel event scores updater
	$("#lr_eventscore_updatetrigger").change(function(){
		// Fetch list of expanded events
		var lr_expandedlist = "";
		$("[id^=\"lr_eventscore_expander\"]").each(function(index){
			if($("#lr_eventscore_extradata" + $(this).prop("id").substr(22)).css("display") == "table-row")
			{
				lr_expandedlist += [$(this).prop("id").substr(22)] + ",";
			}
		});
		
		// Send AJAX request and process result
		$.post("ajaxserve_liveresults_eventscores.php", { d_id: $(this).val(), expanded: lr_expandedlist }, function(data)
		{
			$("#lr_eventscore_table").empty();
			$("#lr_eventscore_table").append(data);
			
			// Bindings for event link: extra data visibility toggle
			$("[id^=\"lr_eventscore_expander\"]").click(function(event){
				event.preventDefault();
				if($("#lr_eventscore_extradata" + $(this).prop("id").substr(22)).css("display") == "table-row")
				{
					$("#lr_eventscore_namebox" + $(this).prop("id").substr(22)).attr("rowspan", "1");
					$("#lr_eventscore_extradata" + $(this).prop("id").substr(22)).css("display", "none");
				} else {
					$("#lr_eventscore_namebox" + $(this).prop("id").substr(22)).attr("rowspan", "2");
					$("#lr_eventscore_extradata" + $(this).prop("id").substr(22)).css("display", "table-row");
				}
			});
		});
	});
	
	// Live Results panel update form submit canceller
	$("#lr_refresh_form").submit(function(){
		event.preventDefault();
	});
	
	// Live Results panel manual refresh button
	$("#lr_forcerefresh_button").click(function(){
		// Call all updaters manually, without disturbing the timeout
		$("#lr_totalscore_updatetrigger").change();
		$("#lr_newrecord_updatetrigger").change();
		$("#lr_highscore_updatetrigger").change();
		$("#lr_scoreprogress_updatetrigger").change();
		$("#lr_eventscore_updatetrigger").change();
	});
	
	// Live Results panel automatic refresh timer
	$("#lr_autorefreshtime").change(function(){
		// Reset the timeout
		window.clearTimeout(lrRID);
		if($("#lr_autorefreshtime").val() != 0) lrUpdate();
	});
	
	// Statistical Analysis >> -All panels- button to generate normal table
	$("#btn_gen_norm").click(function(){
		$("#hdn_outputmode").val("normal");
	});
	
	// Statistical Analysis >> -All panels- button to generate new Word document
	$("#btn_gen_doc").click(function(){
		$("#hdn_outputmode").val("document");
	});
	
	// Statistical Analysis >> -All panels- button to generate new Excel spreadsheet
	$("#btn_gen_spread").click(function(){
		$("#hdn_outputmode").val("spreadsheet");
	});
	
	// Statistical Analysis >> Days panel button to add new Day row
	statday_next_row = 0;
	$("#btn_stat_addday").click(function(){
		// Generate new row
		statday_next_row++;
		var newline = "<tr id=\"statdayrow" + statday_next_row + "\">";
		newline += "<td><select name=\"days[" + statday_next_row + "]\" id=\"dayselect_" + statday_next_row + "\">" + statday_dayoptions + "</select></td>";
		newline += "<td><button type=\"button\" id=\"statdayremover" + statday_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_stat_days").append(newline);
		
		// Bindings for remove button
		$("#statdayremover" + statday_next_row).click(function(){
			$("#statdayrow" + $(this).prop("id").substr(14)).remove();
		});
	});
	
	// Statistical Analysis >> Events panel button to add new Event row
	statevent_next_row = 0;
	$("#btn_stat_addevent").click(function(){
		// Generate new row
		statevent_next_row++;
		var newline = "<tr id=\"stateventrow" + statevent_next_row + "\">";
		newline += "<td><select name=\"days[" + statevent_next_row + "]\" id=\"dayselect_" + statevent_next_row + "\">" + statevent_dayoptions + "</select></td>";
		newline += "<td><select name=\"events[" + statevent_next_row + "]\" id=\"eventselect_" + statevent_next_row + "\" disabled=\"disabled\"></select></td>";
		newline += "<td><button type=\"button\" id=\"stateventremover" + statevent_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_stat_events").append(newline);
		
		// Bindings for day selection
		$("[id^=\"dayselect_\"]").change(function(){
			// Update event options
			$("#eventselect_" + $(this).prop("id").substr(10)).empty();
			$("#eventselect_" + $(this).prop("id").substr(10)).append(statevent_eventoptions[$(this).val()]);
			$("#eventselect_" + $(this).prop("id").substr(10)).attr("disabled", false);
		});
		
		// Bindings for remove button
		$("#stateventremover" + statevent_next_row).click(function(){
			$("#stateventrow" + $(this).prop("id").substr(16)).remove();
		});
	});
	
	// Statistical Analysis >> Teams panel button to add new Team row
	statteam_next_row = 0;
	$("#btn_stat_addteam").click(function(){
		// Generate new row
		statteam_next_row++;
		var newline = "<tr id=\"statteamrow" + statteam_next_row + "\">";
		newline += "<td><select name=\"days[" + statteam_next_row + "]\" id=\"dayselect_" + statteam_next_row + "\">" + statteam_dayoptions + "</select></td>";
		newline += "<td><select name=\"teams[" + statteam_next_row + "]\" id=\"teamselect_" + statteam_next_row + "\" disabled=\"disabled\"></select></td>";
		newline += "<td><button type=\"button\" id=\"statteamremover" + statteam_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_stat_teams").append(newline);
		
		// Bindings for day selection
		$("[id^=\"dayselect_\"]").change(function(){
			// Update team options
			$("#teamselect_" + $(this).prop("id").substr(10)).empty();
			$("#teamselect_" + $(this).prop("id").substr(10)).append(statteam_teamoptions[$(this).val()]);
			$("#teamselect_" + $(this).prop("id").substr(10)).attr("disabled", false);
		});
		
		// Bindings for remove button
		$("#statteamremover" + statteam_next_row).click(function(){
			$("#statteamrow" + $(this).prop("id").substr(15)).remove();
		});
	});
	
	// Statistical Analysis >> Competitors panel button to add new Competitor row
	statcompetitor_next_row = 0;
	$("#btn_stat_addcompetitor").click(function(){
		// Generate new row
		statcompetitor_next_row++;
		var newline = "<tr id=\"statcompetitorrow" + statcompetitor_next_row + "\">";
		newline += "<td><select name=\"days[" + statcompetitor_next_row + "]\" id=\"dayselect_" + statcompetitor_next_row + "\">" + statcompetitor_dayoptions + "</select></td>";
		newline += "<td><select name=\"competitors[" + statcompetitor_next_row + "]\" id=\"competitorselect_" + statcompetitor_next_row + "\" disabled=\"disabled\"></select></td>";
		newline += "<td><button type=\"button\" id=\"statcompetitorremover" + statcompetitor_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_stat_competitors").append(newline);
		
		// Bindings for day selection
		$("[id^=\"dayselect_\"]").change(function(){
			// Update competitor options
			$("#competitorselect_" + $(this).prop("id").substr(10)).empty();
			$("#competitorselect_" + $(this).prop("id").substr(10)).append(statcompetitor_competitoroptions[$(this).val()]);
			$("#competitorselect_" + $(this).prop("id").substr(10)).attr("disabled", false);
		});
		
		// Bindings for remove button
		$("#statcompetitorremover" + statcompetitor_next_row).click(function(){
			$("#statcompetitorrow" + $(this).prop("id").substr(21)).remove();
		});
	});
	
	// Statistical Analysis >> Records panel button to add new Record row
	statrecord_next_row = 0;
	$("#btn_stat_addrecord").click(function(){
		// Generate new row
		statrecord_next_row++;
		var newline = "<tr id=\"statrecordrow" + statrecord_next_row + "\">";
		newline += "<td><select name=\"days[" + statrecord_next_row + "]\" id=\"dayselect_" + statrecord_next_row + "\">" + statrecord_dayoptions + "</select></td>";
		newline += "<td><button type=\"button\" id=\"statrecordremover" + statrecord_next_row + "\">Remove</button></td>";
		newline += "</tr>";
		$("#tbl_stat_records").append(newline);
		
		// Bindings for remove button
		$("#statrecordremover" + statrecord_next_row).click(function(){
			$("#statrecordrow" + $(this).prop("id").substr(17)).remove();
		});
	});
	
	// Trigger all dynamic list-builder scripts
	$("#teambuildertrigger").change();
	$("#subbuildertrigger").change();
	$("#schemebuildertrigger").change();
	$("#scorebuildertrigger").change();
	$("#eventbuildertrigger").change();
	$("#competitorlistbuildertrigger").change();
	$("#statspanelbuildertrigger").change();
	
	// Trigger initial load of Live Results panel
	lrUpdate();

});