<?php
/*
 Some useful, general purpose helper functions.
 */


/*
 Immediately redirects the user to the given page relative to the current page
 Note: this function will crash the script if headers have already been sent!
*/
function redirect($target)
{
	header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $target);
	exit();
}

/*
 Truncates given number (as string!) by removing superfluous 0's
 Optional argument attenuates this somewhat by giving a minimum number of decimal places for the number to have
 */
function truncate_number($n, $mindp = 0)
{
	// Trim whitespace
	$n = trim($n);
	
	// Return blank if not a number
	if(!is_numeric($n)) return "";
	
	// Blank string should not be filled out
	if($n == "") return $n;
	
	// Remove trailing 0's after decimal point
	if(!strpos($n, ".") === false)
	{
		while(substr($n, -1, 1) === "0")
		{
			$n = substr($n, 0, strlen($n) - 1);
		}
		if(substr($n, -1, 1) === ".") $n = substr($n, 0, strlen($n) - 1);
	}
	
	// Remove leading 0's
	while(substr($n, 0, 1) === "0")
	{
		$n = substr($n, 1);
	}
	
	// Fix blank result
	if($n == "") $n = "0";
	
	// Re-append trailing 0's to make up requested decimal places if neccessary
	if($mindp > 0)
	{
		if(!strpos($n, ".") === false)
		{
			// There is already a decimal point
			$extradp = $mindp - (strlen($n) - strrpos($n, ".") - 1);
			for($i = 0; $i < $extradp; $i++)
			{
				$n .= "0";
			}
		} else {
			// There is no decimal point
			$n .= ".";
			for($i = 0; $i < $mindp; $i++)
			{
				$n .= "0";
			}
		}
	}
	
	// Return result
	return $n;
}

/*
 Returns appropriate suffix for given number (as string!)
 */
function getNumberSuffix($n)
{
	$n = trim($n);									// Remove leading and trailing whitespace
	if(!is_numeric($n)) return "th";				// Return default value if not actually a number
	$n = "0$n";										// Prepend a leading zero to ensure following lines work as intended
	if(substr($n, -2, 1) == "1") return "th";		// Values 10-19 always take 'th'
	if(substr($n, -1, 1) == "1") return "st";		// Other values ending in 1 always take 'st'
	if(substr($n, -1, 1) == "2") return "nd";		// Other values ending in 2 always take 'nd'
	if(substr($n, -1, 1) == "3") return "rd";		// Other values ending in 3 always take 'rd'
	return "th";									// Everything else takes 'th'
}

/*
 Returns the quantity given in seconds in minutes and seconds, with the seconds given with the minimum number of decimal places requested (if requested)
 */
function convertToMinutes($t, $mindp = 0)
{
	$fracsec = $t - floor($t);
	$sec = truncate_number((($t - $fracsec) % 60) + $fracsec, $mindp);
	if($sec < 10) $sec = "0$sec";
	$min = ($t - $sec) / 60;
	return "$min:$sec";
}

/*
 Logs an action as the currently logged in user
 */
function logUserAction($actiondata)
{
	// Log action in database
	if(isset($_SESSION['user_id'])) $u_id = $_SESSION['user_id']; else $u_id = 0;
	$query = "INSERT INTO logs (datetime, u_id, actiondata) VALUES (NOW(), '" . mysql_real_escape_string($u_id) . "', '" . mysql_real_escape_string($actiondata) . "')";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
}

/*
 Validates score data for consistency and marks result in database for given event
 */
function validate_scores($e_id)
{
	/*
	 Are the rank choices consistent? (number of each rank chosen)
	 */
	// Which ranks should there be?
	$query = "SELECT day_scorescheme_scores.place AS place FROM day_scorescheme_scores, day_events";
	$query .= " WHERE day_scorescheme_scores.ss_id=day_events.ss_id AND day_events.e_id='" . mysql_real_escape_string($e_id) . "'";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$lastrank = 0;
	while($row = mysql_fetch_array($result))
	{
		$rankcount[$row['place']] = 0;
		$rankbalance[$row['place']] = 0;
		$lastrank = max(intval($lastrank), intval($row['place']));
	}
	mysql_free_result($result);
	
	// Count ranks
	$query = "SELECT place FROM day_scores WHERE e_id='" . mysql_real_escape_string($e_id) . "'";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	while($row = mysql_fetch_array($result))
	{
		$rankcount[$row['place']]++;
	}
	mysql_free_result($result);
	
	// Produce balanced list
	foreach($rankcount as $placekey => $count)
	{
		if($placekey == 0)
		{
			for($i = $lastrank; $i > $lastrank - $count; $i--)
			{
				if(isset($rankbalance[$i])) $rankbalance[$i]++;
			}
		} else {
			for($i = $placekey; $i < $placekey + $count; $i++)
			{
				if(isset($rankbalance[$i])) $rankbalance[$i]++;
			}
		}
	}
	
	// Check rank balance for consistency
	$invalid = false;
	foreach($rankbalance as $rank => $count)
	{
		if($rank != 0 && $count != 1) $invalid = true;
	}
	
	// Mark error and return function if there was an inconsistency, otherwise continue
	if($invalid)
	{
		// Update database
		$query = "UPDATE day_events SET scoring_status='done_wrong' WHERE e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		
		// Return function
		return;
	}
	
	/*
	 Are the results consistent? (correct order, tie allowances, etc)
	 */
	// Which direction *should* the data be ordered in?
	$query = "SELECT day_scoreschemes.result_order AS result_order FROM day_scoreschemes, day_events";
	$query .= " WHERE day_scoreschemes.ss_id=day_events.ss_id AND day_events.e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$row = mysql_fetch_array($result);
	$correct_order = $row['result_order'];
	mysql_free_result($result);
	
	// Get list of places and results (ignore DNC) and check for inconsistencies
	$query = "SELECT place, result FROM day_scores WHERE e_id='" . mysql_real_escape_string($e_id) . "' AND place<>0 ORDER BY place ASC";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$row = mysql_fetch_array($result);
	$lastplace = $row['place'];
	$lastresult = $row['result'];
	$invalid = false;
	while($row = mysql_fetch_array($result))
	{
		if($lastplace == $row['place'])
		{
			// Tied place
			if($row['result'] != $lastresult) $invalid = true;
		} else {
			// Next place
			if($correct_order == "asc")
			{
				if($row['result'] < $lastresult) $invalid = true;
			} elseif($correct_order == "desc") {
				if($row['result'] > $lastresult) $invalid = true;
			}
		}
		
		$lastplace = $row['place'];
		$lastresult = $row['result'];
	}
	mysql_free_result($result);
	
	// Mark error and return function if there was an inconsistency, otherwise continue
	if($invalid)
	{
		// Update database
		$query = "UPDATE day_events SET scoring_status='done_wrong' WHERE e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		
		// Return function
		return;
	}
	
	/*
	 No problems have been encountered, so mark event as completed correctly and finish
	 */
	// Update database
	$query = "UPDATE day_events SET scoring_status='done_correct' WHERE e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
}

/*
 Returns array of data for each team for the given event
 */
function get_event_teamresults($e_id)
{
	// Get data for this event
	$query = "SELECT COUNT(day_teams.t_id) AS total, day_events.scoring_status AS status FROM day_teams, day_events";
	$query .= " WHERE day_teams.d_id=day_events.d_id AND day_events.e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$row = mysql_fetch_array($result);
	$count_teams = $row['total'];
	mysql_free_result($result);
	
	// Create array
	for($i = 1; $i <= $count_teams; $i++)
	{
		$teamresults[$i]['t_id'] = 0;
		$teamresults[$i]['name'] = "";
		$teamresults[$i]['score'] = "";
		$teamresults[$i]['tiedposition'] = "";
		$teamresults[$i]['finalposition'] = 0;
	}
	
	// Only proceed if event has been scored (correctly or not)
	if($row['status'] != 'not_done')
	{
		// Get total scores, in order, per team for this event
		$query2 = "SELECT day_teams.t_id AS t_id, day_teams.name AS team_name, SUM(day_scores.worth) AS total_score";
		$query2 .= " FROM day_scores, day_teams";
		$query2 .= " WHERE day_scores.e_id='" . mysql_real_escape_string($e_id) . "'";
		$query2 .= " AND day_scores.t_id=day_teams.t_id";
		$query2 .= " GROUP BY day_teams.t_id";
		$query2 .= " ORDER BY total_score DESC";
		$result2 = @mysql_query($query2);
		if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$i = 0;
		$lastscore = 0;
		$lastpos = 1;
		while($row2 = mysql_fetch_array($result2))
		{
			// Populate array
			$i++;
			$teamresults[$i]['t_id'] = $row2['t_id'];
			$teamresults[$i]['name'] = $row2['team_name'];
			$teamresults[$i]['score'] = truncate_number($row2['total_score']);
			if($row2['total_score'] == $lastscore)
			{
				$teamresults[$i - 1]['tiedposition'] = "(=$lastpos<sup>" . getNumberSuffix($lastpos) . "</sup>)";
				$teamresults[$i]['tiedposition'] = "(=$lastpos<sup>" . getNumberSuffix($lastpos) . "</sup>)";
			} else {
				$lastpos = $i;
			}
			$teamresults[$i]['finalposition'] = $lastpos;
			
			$lastscore = $row2['total_score'];
		}
		mysql_free_result($result2);
	}
	
	// Return array
	return $teamresults;
}

/*
 Returns array of individual scores for each competitor for the given event
 */
function get_event_individualresults($e_id)
{
	// Fetch individual score data for this event from database
	$query = "SELECT day_scores.place AS place, day_scores.result AS result, day_scoreschemes.result_units AS units, day_scoreschemes.result_units_dp AS units_dp,";
	$query .= " day_competitors.name AS name, day_teams.name AS team_name";
	$query .= " FROM day_scores, day_events, day_scoreschemes, day_competitors, day_teams";
	$query .= " WHERE day_scores.e_id='" . mysql_real_escape_string($e_id) . "'";
	$query .= " AND day_scores.e_id=day_events.e_id";
	$query .= " AND day_events.ss_id=day_scoreschemes.ss_id";
	$query .= " AND day_scores.c_id=day_competitors.c_id";
	$query .= " AND day_competitors.t_id = day_teams.t_id";
	$query .= " ORDER BY day_scores.worth DESC";
	$result = @mysql_query($query);
	
	// Generate output array
	$individualresults = array();
	$i = 0;
	$lastplace = -1;
	while($row = mysql_fetch_array($result))
	{
		$individualresults[$i] = array(	'place' => (($row['place'] == 0) ? 'DNC' : $row['place']),
										'name' => $row['name'], 'team_name' => $row['team_name'],
										'result' => truncate_number($row['result']), 'units' => $row['units'], 'units_dp' => $row['units_dp'],
										'tied' => ($row['place'] == $lastplace)	);
		if($individualresults[$i]['tied'] && isset($individualresults[$i - 1])) $individualresults[$i - 1]['tied'] = true;
		$lastplace = $individualresults[$i]['place'];
		$i++;
	}
	mysql_free_result($result);
	
	// Return output array
	return $individualresults;
}

/*
 Returns array of all new records for the given day
 */
function get_newrecords($d_id)
{
	// Get list of all correct scores, the record they should beat and which side of the
	// record they must be on to beat the record
	$newrecords = array();
	$nextrecord = 0;
	$query = "SELECT day_competitors.c_id AS competitor_id, day_competitors.name AS competitor_name, day_teams.t_id AS team_id,";
	$query .= " day_teams.name AS team_name, day_scores.result AS score_result, day_scoreschemes.result_units AS score_units, day_scoreschemes.result_units_dp AS units_dp,";
	$query .= " day_events.e_id AS event_id, day_events.name AS event_name, day_records.result AS record_result, day_scoreschemes.result_order AS result_order";
	$query .= " FROM day_scores, day_competitors, day_teams, day_events, day_scoreschemes, day_records";
	$query .= " WHERE day_scores.d_id='" . mysql_real_escape_string($d_id) . "'";
	$query .= " AND day_scores.c_id=day_competitors.c_id";
	$query .= " AND day_competitors.t_id=day_teams.t_id";
	$query .= " AND day_scores.e_id=day_events.e_id";
	$query .= " AND day_events.ss_id=day_scoreschemes.ss_id";
	$query .= " AND day_events.scoring_status='done_correct'";
	$query .= " AND day_scores.e_id=day_records.e_id";
	$query .= " AND day_scores.place<>'0'";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	while($row = mysql_fetch_array($result))
	{
		// Is this score a new record?
		$is_newrecord = false;
		if(empty($row['record_result']))
		{
			// No record to compare to exists
			// Have we fetched another record for this event to compare to?
			$otherrecord_exists = false;
			foreach($newrecords as $key => $data)
			{
				if($data['event_id'] == $row['event_id'])
				{
					// Another record for this event exists, so compare
					if(	($row['result_order'] == "asc" && $row['score_result'] <= $data['score'])
						|| ($row['result_order'] == "desc" && $row['score_result'] >= $data['score'])	)
					{
						$newrecords[$key]['competitor_id'] = $row['competitor_id'];
						$newrecords[$key]['competitor_name'] = $row['competitor_name'];
						$newrecords[$key]['team_id'] = $row['team_id'];
						$newrecords[$key]['team_name'] = $row['team_name'];
						$newrecords[$key]['score'] = truncate_number($row['score_result']);
						$newrecords[$key]['score_units'] = $row['score_units'];
						$newrecords[$key]['score_units_dp'] = $row['units_dp'];
						$newrecords[$key]['result_order'] = $row['result_order'];
						$newrecords[$key]['event_id'] = $row['event_id'];
						$newrecords[$key]['event_name'] = $row['event_name'];
					}
					$otherrecord_exists = true;
					break;
				}
			}
			// If we did not find another record for this event to compare to, then this
			// is the new record
			if(!$otherrecord_exists) $is_newrecord = true;		
		} else {
			// Record to compare to exists
			if($row['result_order'] == "asc")
			{
				if($row['score_result'] <= $row['record_result']) $is_newrecord = true;
			} elseif($row['result_order'] == "desc") {
				if($row['score_result'] >= $row['record_result']) $is_newrecord = true;
			}
		}
		
		if($is_newrecord)
		{
			// Add to output table
			$newrecords[$nextrecord]['competitor_id'] = $row['competitor_id'];
			$newrecords[$nextrecord]['competitor_name'] = $row['competitor_name'];
			$newrecords[$nextrecord]['team_id'] = $row['team_id'];
			$newrecords[$nextrecord]['team_name'] = $row['team_name'];
			$newrecords[$nextrecord]['score'] = truncate_number($row['score_result']);
			$newrecords[$nextrecord]['score_units'] = $row['score_units'];
			$newrecords[$nextrecord]['score_units_dp'] = $row['units_dp'];
			$newrecords[$nextrecord]['result_order'] = $row['result_order'];
			$newrecords[$nextrecord]['event_id'] = $row['event_id'];
			$newrecords[$nextrecord]['event_name'] = $row['event_name'];
			$nextrecord++;
		}
	}
	mysql_free_result($result);
	
	// Return results array
	return $newrecords;
}

/*
 Returns array of details for the current record for the given event
 */
function get_currentrecord($e_id)
{
	// Fetch day of this event, its year, its scoring units and the minimum decimal places for the scoring units from database
	$query = "SELECT day_events.d_id AS d_id, days.year AS year, day_scoreschemes.result_units AS result_units, day_scoreschemes.result_units_dp AS units_dp";
	$query .= " FROM day_events, days, day_scoreschemes WHERE day_events.d_id=days.d_id AND day_events.ss_id=day_scoreschemes.ss_id";
	$query .= " AND e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$row = mysql_fetch_array($result);
	$d_id = $row['d_id'];
	$currentyear = $row['year'];
	$eventscoreunits = $row['result_units'];
	$unitsdp = $row['units_dp'];
	mysql_free_result($result);
	
	// Fetch old record for this event from the database and save to result array
	$currentrecord = array();
	$query = "SELECT name_competitor, name_team, result, yearset FROM day_records WHERE e_id='" . mysql_real_escape_string($e_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	$currentrecord['competitor_name'] = $row['name_competitor'];
	$currentrecord['team_name'] = $row['name_team'];
	$currentrecord['score'] = truncate_number($row['result']);
	$currentrecord['score_units'] = $eventscoreunits;
	$currentrecord['score_units_dp'] = $unitsdp;
	$currentrecord['yearset'] = $row['yearset'];
	$currentrecord['brokenthisday'] = false;
	mysql_free_result($result);
	
	// Fetch list of broken records for this event's day
	$newrecords = get_newrecords($d_id);
	
	// Locate all broken records for this event
	foreach($newrecords as $recorddata)
	{
		if($recorddata['event_id'] == $e_id)
		{
			// Is this record better than the last one, or simply equal to it?
			if($recorddata['score'] == $currentrecord['score'])
			{
				// Equal to, so append the record
				$currentrecord['competitor_name'] .= ' / ' . $recorddata['competitor_name'];
				$currentrecord['team_name'] .= ' / ' . $recorddata['team_name'];
				$currentrecord['score'] = $recorddata['score'];
				$currentrecord['yearset'] = $currentyear;
				$currentrecord['brokenthisday'] = true;
			} elseif(	($recorddata['result_order'] == "asc" && $recorddata['score'] < $currentrecord['score'])
						|| ($recorddata['result_order'] == "desc" && $recorddata['score'] > $currentrecord['score'])
						|| empty($currentrecord['score'])	) {
				// Better than, so replace the record
				$currentrecord['competitor_name'] = $recorddata['competitor_name'];
				$currentrecord['team_name'] = $recorddata['team_name'];
				$currentrecord['score'] = $recorddata['score'];
				$currentrecord['yearset'] = $currentyear;
				$currentrecord['brokenthisday'] = true;
			}
		}
	}
	
	// Return result array
	return $currentrecord;
}

/*
 Returns array of current total scores for all teams for the given day
 */
function get_currenttotalscores($d_id)
{
	// Fetch score data from database
	$query = "SELECT day_teams.t_id AS t_id, day_teams.name AS teamname, day_teams.initscore AS prior_score, SUM(day_scores.worth) AS total_score,";
	$query .= " (day_teams.initscore+SUM(day_scores.worth)) AS final_score";
	$query .= " FROM day_scores, day_teams, day_events";
	$query .= " WHERE day_scores.d_id='" . mysql_real_escape_string($d_id) . "'";
	$query .= " AND day_scores.t_id=day_teams.t_id";
	$query .= " AND day_scores.e_id=day_events.e_id";
	$query .= " AND day_events.scoring_status='done_correct'";
	$query .= " GROUP BY day_teams.t_id";
	$query .= " ORDER BY final_score DESC";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	
	// Generate output array
	$totalscores = array();
	$i = 0;
	$lasttotalscore = 0;
	while($row = mysql_fetch_array($result))
	{
		$i++;
		$totalscores[$i] = array(	't_id' => $row['t_id'], 'teamname' => $row['teamname'], 'place' => $i,
									'prior_score' => $row['prior_score'], 'total_score' => $row['total_score'],
									'final_score' => $row['final_score'], 'tied' => ($row['final_score'] == $lasttotalscore)	);
		if($totalscores[$i]['tied'] && isset($totalscores[$i - 1]))
		{
			$totalscores[$i]['place'] = $totalscores[$i - 1]['place'];
			$totalscores[$i - 1]['tied'] = true;
		}
		$lasttotalscore = $row['final_score'];
	}
	mysql_free_result($result);
	
	// Return output array
	return $totalscores;
}

/*
 Returns array of event positions achieved per team for all teams for the given day
 */
function get_totalteampositions($d_id)
{
	// Prepare counter for each team of amount of scores in each position
	$eventwincount = array();
	$query = "SELECT t_id, name FROM day_teams WHERE d_id='" . mysql_real_escape_string($d_id) . "'";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		$eventwincount[$row['t_id']]['name'] = $row['name'];
		for($i = 1; $i <= mysql_num_rows($result); $i++)
		{
			$eventwincount[$row['t_id']]['position'][$i] = 0;
		}
	}
	mysql_free_result($result);
	
	// Count up all positions scored
	$query = "SELECT e_id FROM day_events WHERE d_id='" . mysql_real_escape_string($d_id) . "' AND scoring_status='done_correct'";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		$eventresults = get_event_teamresults($row['e_id']);
		foreach($eventresults as $eventdata)
		{
			$eventwincount[$eventdata['t_id']]['position'][$eventdata['finalposition']]++;
		}
	}
	mysql_free_result($result);
	
	// Return output array
	return $eventwincount;
}

/*
 Returns array of total score and ranking for the given subgroup for each team on the same day as the subgroup
 */
function get_teamsubgroupscores($sub_id)
{
	// Create output table
	$query = "SELECT day_competitors.t_id AS t_id, SUM(day_scores.worth) AS total_score";
	$query .= " FROM day_scores, day_competitors";
	$query .= " WHERE day_scores.c_id=day_competitors.c_id";
	$query .= " AND day_competitors.sub_id='" . mysql_real_escape_string($sub_id) . "'";
	$query .= " GROUP BY day_competitors.t_id";
	$query .= " ORDER BY SUM(day_scores.worth) DESC";
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	$teamsubgroupscores = array();
	$i = 0;
	while($row = mysql_fetch_array($result))
	{
		$i++;
		$teamsubgroupscores[$row['t_id']] = array('position' => $i, 'score' => $row['total_score']);
	}
	mysql_free_result($result);
	
	// Return output array
	return $teamsubgroupscores;
}

/*
 Returns array of highest individual scores for each subgroup for the given day
 */
function get_individualhighscores($d_id)
{
	// Prepare output table
	// (necessary to deal with zero or many competitors with highest score)
	$output = array();
	$query = "SELECT sub_id, name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($d_id) . "' ORDER BY sub_id ASC";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		$output[$row['sub_id']]['subname'] = $row['name'];
		$output[$row['sub_id']]['highscore'] = 1;				// Minimum high score is 1, to prevent everyone being high scorer when no events have taken place
		$output[$row['sub_id']]['name'] = array();
		$output[$row['sub_id']]['team'] = array();
	}
	mysql_free_result($result);
	
	// Fill output table
	$query = "SELECT day_competitors.name AS name, day_teams.name AS team_name, day_competitors.sub_id AS sub_id, SUM(day_scores.worth) AS worth";
	$query .= " FROM day_competitors, day_teams, day_scores, day_events";
	$query .= " WHERE day_scores.d_id='" . mysql_real_escape_string($d_id) . "'";
	$query .= " AND day_competitors.c_id=day_scores.c_id";
	$query .= " AND day_competitors.t_id=day_teams.t_id";
	$query .= " AND day_events.scoring_status='done_correct'";
	$query .= " AND day_events.e_id=day_scores.e_id";
	$query .= " GROUP BY day_competitors.c_id";
	$query .= " ORDER BY day_competitors.sub_id ASC, worth DESC, day_competitors.name ASC";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		foreach($output as $key => $data)
		{
			// For each subgroup against each competitor's total score, save the competitor
			// to the subgroup's highscore if their total score is greater than the currently
			// saved total score
			if($row['sub_id'] == $key && $row['worth'] >= $output[$key]['highscore'])
			{
				$output[$key]['highscore'] = htmlspecialchars($row['worth']);				// Save score
				$output[$key]['name'][] = $row['name'];										// Save name
				$output[$key]['team'][] = $row['team_name'];								// Save team
			}
		}
	}
	mysql_free_result($result);
	
	// Return output array
	return $output;
}

/*
 Returns array of all competitors (by id) who have competed in too many events on the given day
 */
function get_competitorsoverlimit($d_id)
{
	// Array for results
	$competitorsoverlimit = array();
	
	// Fetch maximum events per competitor for this day from database
	$query = "SELECT max_events_per_competitor FROM days WHERE d_id='" . mysql_real_escape_string($d_id) . "' LIMIT 1";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	if(mysql_num_rows($result) == 1) $max_events = $row['max_events_per_competitor']; else return $competitorsoverlimit;
	mysql_free_result($result);
	
	// Fetch number of events all competitors have taken part in on this day
	$query = "SELECT day_scores.c_id AS c_id, COUNT(day_scores.c_id) AS eventcount FROM day_scores, day_events";
	$query .= " WHERE day_scores.d_id='" . mysql_real_escape_string($d_id) . "' AND day_scores.c_id<>0";
	$query .= " AND day_events.counts_to_limit='1' AND day_scores.e_id=day_events.e_id";
	$query .= " GROUP BY day_scores.c_id";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		if($row['eventcount'] > $max_events) $competitorsoverlimit[] = $row['c_id'];
	}
	mysql_free_result($result);
	
	// Return result array
	return $competitorsoverlimit;
}
?>