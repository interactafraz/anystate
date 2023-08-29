<?php 

$file = "states.json";
$timeLimit = 365; //How many days a state should be stored
$previewTimeZone = "Europe/Berlin";//Only for preview output

$stateData = array();
function addToStates($sentData,$fp) {
	global $stateData;
	global $timeLimit;
	
	$timestamp = time();	
	
	if( count($stateData) > 0 ){ //If any entry exists
		foreach ($sentData as $sentKey => $sentValue) {
			if ($sentKey != "time"){
				$entryFound = false;
				$entryExistingCounter = 0;
				
				foreach ($stateData as $entryExisting) { //Check existing entries
					if ($entryFound == false){ //If target entry not found (yet)
						if( array_key_exists($sentKey, $entryExisting) ){ //If entry for sent key has a value (including 0 and NULL)
							$entryFound = true;

							$stateData[$entryExistingCounter]['time'] = $timestamp; //Override
							$stateData[$entryExistingCounter][$sentKey] = $sentValue; //Override
							
							break; //Stop searching in existing entries
						}
					}
					
					$entryExistingCounter = $entryExistingCounter + 1;
				}
				
				if ($entryFound == false){ //If entry not found at all
					$entry = array();
					$entry['time'] = $timestamp;
					$entry[$sentKey] = $sentValue;
					array_push($stateData, $entry); //Add to existing entries	
				}	
			}
			else{
				die ('Error: Key cannot be "time"');
			}
			
		}
	}
	else{ //No entry exists
		foreach ($sentData as $key => $value) {
			if ($sentKey != "time"){
				$entry = array();
				$entry['time'] = $timestamp;
				$entry[$key] = $value;
				array_push($stateData, $entry); //Add to existing entries
			}
			else{
				die ('Error: Key cannot be "time"');
			}
		}
	}
	
	usort($stateData, function($a, $b) {
		return $b['time'] - $a['time'];
	});
	
	$stateData = array_values($stateData); //Rebuild index
	
	rewind($fp);
	ftruncate($fp, 0);
	fwrite($fp, json_encode($stateData)); //Save new data to file
}

if ( file_exists($file) ) { //Check if data exists
	
	$fp = fopen($file, 'r+');
	flock($fp, LOCK_EX); //Lock file to avoid other processes writing to it simlutanously 

	$jsonData = stream_get_contents($fp);
	if($jsonData == ""){
		echo "Could not read Log file (maybe empty)";
		die;
	}
	$stateData = json_decode($jsonData, true);
	
	$entryExistingCounter = 0;
	
	foreach ($stateData as $entryExisting) {
		if ($entryExisting['time'] < date("U",strtotime("-".$timeLimit." days")) ){//If older than limit
			unset($stateData[$entryExistingCounter]);
		}
		$entryExistingCounter = $entryExistingCounter + 1;
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(json_decode($_POST['data'], true))) {
		//Debug
		//$debug = fopen('debug.txt', 'w');
		//fwrite($debug, $_POST); //Save new data to file
		//fclose($debug);
		//Debug
		addToStates( json_decode($_POST['data'], true),$fp );
	}
	elseif( !empty($_GET) && isset($_GET['set']) && isset($_GET['content']) ){
		//Debug
		//echo $data;
		//Debug
		$data = [$_GET['set'] => $_GET['content']];		
		addToStates( $data,$fp );
		echo 'success';
	}
	
	flock($fp, LOCK_UN); //Unlock file for further access
	fclose($fp);
}



?>

<!DOCTYPE html>
<html>
	<head>
		<?php if( !isset($_GET['state']) && !isset($_GET['time']) ){echo "<title>AnyState</title>";} ?>
	</head>
	<body>
		<?php 
			if ( file_exists($file) ) { //Check if data exists
				
				if (!empty($_GET)) { 
					if ( isset($_GET['state']) ){ //If specific value(s) requested
						foreach ($stateData as $entryExisting) { //Get every entry
							foreach ($entryExisting as $key => $value) { //Get key and timestamp values
								if ($_GET['state'] == $key){ //If target key has been found
									echo $value;
									break 2;
								}
							}
						}
					}
					elseif ( isset($_GET['time']) ){ //If time value for specific key requested
						foreach ($stateData as $entryExisting) { //Get every entry
							foreach ($entryExisting as $key => $value) { //Get key and timestamp values
								if ($_GET['time'] == $key){ //If target key has been found
									echo $entryExisting['time'];
									break 2;
								}
							}
							
						}
					}
						
				}
				else {
					foreach ($stateData as $index => $entry) {
						$elementCount = count($entry);
						$counter = 0;
						
						foreach ($entry as $key => $value) {
							$counter = $counter + 1;
							
							if($key == 'time'){
								$time = DateTime::createFromFormat('U', $value)->setTimezone(new DateTimeZone($previewTimeZone))->format('d.m.Y \a\t H:i'); //Convert to datetime object							
								echo $time . ' - ';
							}
							else{ //Value
								if ( is_array($value) ){ //If value is array
										echo $key . ' = ' . 'Array';									
								}
								else{
									echo $key . ' = ' . $value;
								}
							}
							
							
							if( $counter != $elementCount && $key != 'time'){
								echo ' | ';
							}
							
						}
						
						echo "<br>";
					}	
				}
			
				
			}
		?>
	</body>
</html>