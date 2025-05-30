<?php 
header('Access-Control-Allow-Origin: *'); 

$file = "states.json";
$timeLimit = 365; //How many days a state should be stored
$previewTimeZone = "Europe/Berlin"; //Only for preview output
$triggerWebhookUrl = "http://n8n:5678/webhook/push-anystate-state-to-mqtt"; //URL that gets called after every entry update (optional, leave empty to disable)

$stateData = array();

function callTriggerWebhook($sentData){
	global $triggerWebhookUrl;
	
	if($triggerWebhookUrl != ""){
		$ch = curl_init($triggerWebhookUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 150);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sentData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_exec($ch);
		curl_close($ch);
	}
	
}

function addToStates($sentData,$fp) {
	global $stateData;
	
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
							
							callTriggerWebhook($sentData);
							
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
					
					callTriggerWebhook($sentData);
				}
			}
			else{
				die ('Error: Key cannot be "time"');
			}
			
		}
	}
	else{ //No entry exists
		foreach ($sentData as $key => $value) {
			if ($key != "time"){
				$entry = array();
				$entry['time'] = $timestamp;
				$entry[$key] = $value;
				array_push($stateData, $entry); //Add to existing entries
				
				callTriggerWebhook($sentData);
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

if ( !file_exists($file) ) { //Check if data does not exist yet
	$fp = fopen($file, 'w+');
	flock($fp, LOCK_EX); //Lock file to avoid other processes writing to it simlutanously 
	fwrite($fp, json_encode(new stdClass)); //Save empty data to file	
	flock($fp, LOCK_UN); //Unlock file for further access
	fclose($fp);
}

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

//Debug
//if ($_SERVER['REQUEST_METHOD'] === 'POST'){
	//$debug = fopen('debug.txt', 'w');
	//fwrite($debug, print_r($_POST, true)); //Save array data to file
	//fwrite($debug, $_POST['data']); //Save data content to file
	//fclose($debug);		
//}
//Debug

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(json_decode($_POST['data'], true))) {
	addToStates( json_decode($_POST['data'], true),$fp );
}
elseif( !empty($_GET) && isset($_GET['set']) && isset($_GET['content']) ){
	//Debug
	//echo $data;
	//Debug
	$sentValue = $_GET['content'];
	
	if( preg_match('/^\d+$/', $sentValue) ){ //If whole number
		$sentValue = (int)$sentValue;
	}
	elseif( preg_match('/^\d+\.\d+$/', $sentValue) || preg_match('/^\.\d+$/', $sentValue)){ //If number with decimals (like "1.2" or ".5")
		$sentValue = (float)$sentValue;
	}		
	
	$data = [$_GET['set'] => $sentValue];		
	addToStates( $data,$fp );
	echo 'success';
	die;
}

flock($fp, LOCK_UN); //Unlock file for further access
fclose($fp);


if (!empty($_GET)) { 
	if( isset($_GET['state']) ){ //If specific value(s) requested
		
		foreach ($stateData as $entryExisting) { //Get every entry
			foreach ($entryExisting as $key => $value) { //Get key and timestamp values
				if ($_GET['state'] == $key){ //If target key has been found
					
					if( isset($_GET['format']) && $_GET['format'] == "json" ){ //If requested as JSON
						if( is_array($value) ){ //If value is array
							header('Content-Type: application/json; charset=utf-8');
							echo json_encode($value);
						}
						else{
							$jsonArray = ["value" => $value];
							header('Content-Type: application/json; charset=utf-8');
							echo json_encode($jsonArray);
						}
						
					}
					else{
						echo $value;
					}					
							
					break 2;
				}
			}
		}
		
		die;
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
		die;
	}
	elseif( isset($_GET['filter']) ){ //If data should be filtered
		if($_GET['filter'] == "values"){ //If only keys and values requested
			$dataFiltered = array();
			if ( file_exists($file) ) { //Check if data exists
			
				foreach ($stateData as $index => $entry) { //Get every entry
					foreach ($entry as $key => $value) { //Get keys and values
						if($key != 'time'){ 
							$dataFiltered[$key] = $value;
						}						
					}						
				}
				
			}
			
			if( isset($_GET['format']) && $_GET['format'] == "json" ){ //If requested as JSON			
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($dataFiltered);				
			}
		}
		
		die;
	}
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
		?>
	</body>
</html>