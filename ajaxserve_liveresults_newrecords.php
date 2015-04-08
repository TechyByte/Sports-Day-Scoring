<?php
/*
 Serves record data to Live Results >> New Records AJAX display.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['d_id']))
{
	// Fetch table of new records using helper function
	$newrecords = get_newrecords($_POST['d_id']);
	
	// Produce output from this table
	$i = 0;
	foreach($newrecords as $recorddata)
	{
		if($i > 0) echo "\n";
		$i++;
		echo htmlspecialchars($recorddata['competitor_name']) . " (" . htmlspecialchars($recorddata['team_name']) . ") - ";
		if($recorddata['score_units'] == 'seconds' && $recorddata['score'] >= 60)
		{
			echo htmlspecialchars(convertToMinutes($recorddata['score'], $recorddata['score_units_dp'])) . " minutes, ";
		} else {
			echo htmlspecialchars(truncate_number($recorddata['score'], $recorddata['score_units_dp'])) . " " . htmlspecialchars($recorddata['score_units']) . ", ";
		}
		echo htmlspecialchars($recorddata['event_name']);
	}
}

// Close MySQL connection
mysql_close();
?>