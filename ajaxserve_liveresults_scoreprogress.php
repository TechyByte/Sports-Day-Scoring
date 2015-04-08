<?php
/*
 Serves record data to Live Results >> Event Scoring Progress AJAX display.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['d_id']))
{
	// Count correctly scored events
	$query = "SELECT COUNT(e_id) AS total FROM day_events WHERE d_id='" . mysql_real_escape_string($_POST['d_id']) . "' AND scoring_status='done_correct'";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	$scores_done = $row['total'];
	mysql_free_result($result);
	
	// Count total events
	$query = "SELECT COUNT(e_id) AS total FROM day_events WHERE d_id='" . mysql_real_escape_string($_POST['d_id']) . "'";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	$scores_total = $row['total'];
	mysql_free_result($result);
	
	// Outputs
	echo "$scores_done\n";
	echo "$scores_total\n";
	echo round(100 * $scores_done / $scores_total, 0);
}

// Close MySQL connection
mysql_close();
?>