<?php
/*
 Serves record data to Live Results >> Current Event Scores AJAX display.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['d_id']) && isset($_POST['expanded']))
{
	// Count number of teams
	$query = "SELECT COUNT(t_id) AS total FROM day_teams WHERE d_id='" . mysql_real_escape_string($_POST['d_id']) . "'";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	$count_teams = $row['total'];
	mysql_free_result($result);
	
	// Parse expanded event list
	$expandedraw = explode(",", $_POST['expanded']);
	$expandedlist = array();
	foreach($expandedraw as $eventid)
	{
		$expandedlist[$eventid] = true;
	}
	
	// Output header row
	echo "<tr class=\"darkgrey\">\n";
	echo "<th>Event</th>\n";
	for($i = 1; $i <= $count_teams; $i++)
	{
		echo "<th>$i<sup>" . getNumberSuffix($i) . "</sup></th>\n";
	}
	echo "</tr>\n";
	
	// Output data rows
	$query = "SELECT e_id, name FROM day_events WHERE d_id='" . mysql_real_escape_string($_POST['d_id']) . "' AND scoring_status='done_correct'";
	$result = @mysql_query($query);
	while($row = mysql_fetch_array($result))
	{
		// Team scores
		echo "<tr\">\n";
		echo "<th rowspan=\"";
		if(isset($expandedlist[$row['e_id']])) echo "2"; else echo "1";
		echo "\" id=\"lr_eventscore_namebox" . htmlspecialchars($row['e_id']);
		echo "\"><a class=\"headerlink\" href=\"#\" id=\"lr_eventscore_expander" . htmlspecialchars($row['e_id']);
		echo "\">" . htmlspecialchars($row['name']) . "</a></th>\n";
		$teamresults = get_event_teamresults($row['e_id']);
		for($i = 1; $i <= $count_teams; $i++)
		{
			echo "<td>" . htmlspecialchars($teamresults[$i]['name']) . " <i>(" . htmlspecialchars($teamresults[$i]['score']) . ")</i> ";
			echo "<i>" . $teamresults[$i]['tiedposition'] . "</i></td>\n";
		}
		echo "</tr>\n";
		
		// Individual scores
		$individualresults = get_event_individualresults($row['e_id']);
		echo "<tr style=\"display: ";
		if(isset($expandedlist[$row['e_id']])) echo "table-row"; else echo "none";
		echo ";\" id=\"lr_eventscore_extradata{$row['e_id']}\">\n<td colspan=\"$count_teams\">\n";
		echo "<table class=\"datatable borderless\">\n";
		foreach($individualresults as $resultdata)
		{
			echo "<tr>\n<th>" . htmlspecialchars($resultdata['place']);
			if(is_numeric($resultdata['place'])) echo "<sup>" . getNumberSuffix($resultdata['place']) . "</sup>";
			echo "</th>\n<td>" . htmlspecialchars($resultdata['name']) . "</td>\n";
			echo "<td>" . htmlspecialchars($resultdata['team_name']) . "</td>\n";
			echo "<td>";
			if($resultdata['place'] == "DNC")
			{
				echo "-----";
			} else {
				if($resultdata['units'] == "seconds" && $resultdata['result'] >= 60)
				{
					echo convertToMinutes($resultdata['result'], $resultdata['units_dp']) . " minutes";
				} else {
					echo truncate_number($resultdata['result'], $resultdata['units_dp']) . " " . htmlspecialchars($resultdata['units']);
				}
			}
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</td>\n</tr>\n";
	}
	mysql_free_result($result);
}

// Close MySQL connection
mysql_close();
?>