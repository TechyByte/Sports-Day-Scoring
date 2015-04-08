<?php
/*
 Serves record data to Live Results >> Individual High Scores AJAX display.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['d_id']))
{
	// Fetch high scores
	$highscores = get_individualhighscores($_POST['d_id']);
	
	// Output high score data
	$firstloopdone1 = false;
	foreach($highscores as $key => $data)
	{
		// Separator
		if($firstloopdone1) echo "\n";
		$firstloopdone1 = true;
		
		// Names
		if(isset($data['name']))
		{
			$firstloopdone2 = false;
			foreach($data['name'] as $namedata)
			{
				if($firstloopdone2) echo "<br>";
				$firstloopdone2 = true;
				echo htmlspecialchars($namedata);
			}
		}
		
		echo "\n";
		
		// Scores
		if(isset($data['highscore'])) echo htmlspecialchars(truncate_number($data['highscore']));
		
		echo "\n";
		
		// Teams
		if(isset($data['team']))
		{
			$firstloopdone2 = false;
			foreach($data['team'] as $teamdata)
			{
				if($firstloopdone2) echo "<br>";
				$firstloopdone2 = true;
				echo htmlspecialchars($teamdata);
			}
		}
	}
}

// Close MySQL connection
mysql_close();
?>