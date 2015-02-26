<?php
	//error_reporting(E_ALL);
	//ini_set('display_errors', 1);
	header("Content-Type: application/json");
	if(isset($_GET["IGN"]) && $_GET["IGN"] != ""){
		$IGN = $_GET["IGN"];
		$IGN = preg_replace('/[^a-zA-Z0-9_]/','',$IGN);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.mojang.com/users/profiles/minecraft/".$IGN);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		$response = curl_exec($ch);
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($code == 204){
			$json = '{"status":"error","error":"Username not found."}';
		}else{
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
			array_splice($usernames, array_search($name, $usernames), 1);
			if(count($usernames)==0){
				$json = '{"status":"error","error":"This user has no old usernames."}';
			}else{
				$output = array("status"=>"success","usernames"=>$usernames,"currentName"=>$name);
				$json = json_encode($output);
			}
		}
	}else{
		$json = '{"status":"error","error":"Please enter a username."}';
	}
	echo $json;
?>