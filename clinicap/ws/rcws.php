<?php
include '../config/config.php';
include '../classes/OpenClinicaSoapWebService.php';

function printResponse($responseCode, $message, $response1, $response2, $response3){
	$responseArray = array (
			'serviceName' => 'RDCIT-CliniCap',
			'responseCode' => $responseCode,
			'message' => $message,
			'data' => $response1,
			'data2' => $response2,
			'data3' => $response3
	);

	header ( "Access-Control-Allow-Origin: *" );
	header ( 'Content-Type: application/json; charset=utf-8');
	echo json_encode ( $responseArray );
	die();
}

function updateTokens($token){
	$file = '../tokens/ch686.txt';
	$redcapapis = file_get_contents($file);
	$projectTokens = json_decode($redcapapis);
	
	$projectTokens[]=$token;
	$serializedData = json_encode($projectTokens);
	file_put_contents($file, $serializedData);
}

function getRedCapProject($token){
	$fields = array(
			'token'   => $token,
			'content' => 'project',
			'format'  => 'json'
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS['api_url']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Set to TRUE for production use
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function createProject($projectName){

	$record = array(
			'project_title' => $projectName,
			'purpose'       => 0
	);
	
	$data = '['.json_encode($record).']';
	
	$fields = array(
			'token'   => $GLOBALS['super_api_token'],
			'content' => 'project',
			'data'    => $data,
			'format'  => 'json'
	);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS['api_url']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Set to TRUE for production use
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

//TODO: check session, permission here

if(count($_POST)>0){

	$action = $_POST['action'];
	//ACTION: getProjects
	if($action == "getProjects"){
		$file = '../tokens/ch686.txt';
		
		//$tokens = array("60E78D151BFF7F6AC6640D84434E5AEC","B4548AC371409D7C96CF8990FC2AD6BF");
		//$serializedData = json_encode($tokens);
		//file_put_contents($file, $serializedData);
		
		$redcapapis = file_get_contents($file);
		$projectTokens = json_decode($redcapapis);
		$projects = array();
		
		foreach($projectTokens as $id=>$t){
			$projects[] = json_decode(getRedCapProject($t),true);
		}
		
		if(!empty($projects)){
			printResponse(200,"OK",$projects,null,null);
		}
		else{
			printResponse(1,"Error! Wrong credentials or user has got no projects.",null,null,null);
		}
	}
	
	//ACTION: createProject
	else if($action == "createProject"){
		$pname = $_POST['name'];
		
		if(!empty($pname)){
			$response = createProject($pname);
			
			if(strlen($response)==32){
				updateTokens($response);
				printResponse(200,"New project has been created.",null,null,null);
			}
			else{
				printResponse(1,"Error: ".$response,null,null,null);
			}
		}
		else{
			printResponse(1,"Error! Missing project name.",null,null,null);
		}
	}
	//UNKNOWN action
	else{
		printResponse(1,"Unknown action!",null,null,null);
	}
}







