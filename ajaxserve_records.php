<?php
/*
 Serves record data to Manage Records AJAX form.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['e_id']))
{
	// Fetch and output requested record from database
	$query = "SELECT name_competitor, name_team, result, yearset FROM day_records WHERE e_id='" . mysql_real_escape_string($_POST['e_id']) . "' LIMIT 1";
	$result = @mysql_query($query);
	$row = mysql_fetch_array($result);
	echo htmlspecialchars($row['name_competitor']) . "\n";
	echo htmlspecialchars($row['name_team']) . "\n";
	echo truncate_number($row['result']) . "\n";
	echo htmlspecialchars($row['yearset']) . "\n";
	mysql_free_result($result);
}

// Close MySQL connection
mysql_close();
?>