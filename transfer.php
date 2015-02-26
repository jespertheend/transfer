<?php
	//error_reporting(E_ALL);
	//ini_set('display_errors', 1);

	require "../../panel/toolKit.php";

	header("Content-Type: application/json");
	set_time_limit(600);
	if(empty($_GET["old"]) || empty($_GET["cur"])){
		returnError("old or new user is not set");
	}
	$oldName = preg_replace('/[^a-zA-Z0-9_]/','',$_GET["old"]);
	$curName = preg_replace('/[^a-zA-Z0-9_]/','',$_GET["cur"]);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.mojang.com/users/profiles/minecraft/".$curName);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	$response = curl_exec($ch);
	$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($code == 204){
		returnError("'cur' user not found");
	}
	$data = json_decode($response, true);
	$uuid = $data["id"];
	$name = $data["name"];

	$ch2 = curl_init();
	curl_setopt($ch2, CURLOPT_URL, "https://api.mojang.com/user/profiles/".$uuid."/names");
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch2, CURLOPT_HEADER, FALSE);
	$response2 = curl_exec($ch2);
	curl_close($ch2);
	$data2 = json_decode($response2, true);

	$usernames = [];
	foreach ($data2 as $value){
		array_push($usernames,$value["name"]);
	}
	if(!in_array($oldName, $usernames)){
		returnError("'".$curName."' username does not have '".$oldName."' as old username");
	}








	cmd('tellraw '.$curName.' {color:red,translate:a.general.requestTransfer,with:[{translate:a.general.requestTransfer.here,color:blue,hoverEvent:{action:show_text,value:{translate:a.general.hoverLink.click}},clickEvent:{action:run_command,value:"/trigger a set 579"}}]}');
	$i = 0;
	$confirmed = false;
	while($i <= 6 && $confirmed == false){
		sleep(10);
		$log = getLog(true);
		if(preg_match_all("@\n\[[0-9:]+\] \[".$curName.": Set score of a for player ".$curName." to 578\]@", $log, $matches2, PREG_OFFSET_CAPTURE) >= 1){
			$latestRequestPos = strrpos($log, 'tellraw '.$curName.' {color:red,translate:a.general.requestTransfer,with:[{translate:a.general.requestTransfer.here,color:blue,hoverEvent:{action:show_text,value:{translate:a.general.hoverLink.click}},clickEvent:{action:run_command,value:"/trigger a set 579"}}]}');
			$latestConfirmPos = $matches2[0][count($matches2[0])-1][1];
			if($latestConfirmPos >= $latestRequestPos){
				$confirmed = true;
			}
		}
		$i++;
	}
	if(!$confirmed){
		returnError("You were not online or you didn't click the link in the chat.");
	}
	cmd("kick ".$curName." Transferring scores...");
	cmd("ban ".$curName." Transferring scores...");
	sleep(2);




	cmd("scoreboard players list ".$oldName);
	sleep(2);
	$log = getLog(false);
	if(preg_match_all('@\n\[[0-9:]+\] \[Server thread\/INFO\]: Player '.$oldName.' has no scores recorded\r\n@', $log, $pregMatchNoObjectives, PREG_OFFSET_CAPTURE)>=1){
		$latestNoScoresChar = $pregMatchNoObjectives[0][count($pregMatchNoObjectives[0])-1][1];
	}else{
		$latestNoScoresChar = 0;
	}
	if(preg_match_all('@\n\[[0-9:]+\] \[Server thread\/INFO\]: Showing \d+ tracked objective\(s\) for '.$oldName.':(\r\n)@', $log, $startOffsetArray, PREG_OFFSET_CAPTURE)>=1){
		$startOffset = $startOffsetArray[1][count($startOffsetArray[1])-1][1];
	}else{
		$startOffset = 0;
	}
	if($latestNoScoresChar>=$startOffset){
		cmd("pardon ".$curName);
		returnError("no scores found for '.$oldName.'.");
	}

	preg_match_all('@\[[0-9:]+] \[Server thread/INFO\]: - .+: -?\d+ \(([a-zA-Z0-9]+)\)@', $log, $pregMatchObjectives, PREG_PATTERN_ORDER, $startOffset);
	$objectives = $pregMatchObjectives[1];
	foreach($objectives as $objective){
		cmd("scoreboard players operation ".$curName." ".$objective." = ".$oldName." ".$objective);
		sleep(1);
	}
	cmd("pardon ".$curName);
	cmd("scoreboard players reset ".$oldName);
	echo '{"result":"success"}';

	function returnError($error){
		$json = array("result"=>"error","error"=>$error);
		echo json_encode($json);
		exit();
	}
?>