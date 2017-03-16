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

function createRedCapEvents($token, $events){
	$data = array();
	
	foreach($events as $e){
		
		$data[] = array(
				'event_name'        => $e['name'],
				'arm_num'           => '1',
				'day_offset'        => '0',
				'offset_min'        => '0',
				'offset_max'        => '0',
				'unique_event_name' => $e['name'].'_arm_1'
		);
	}
	
	$data = json_encode($data);
	file_put_contents("events.txt",$data);
	$fields = array(
			'token'    => $GLOBALS['api_token'],
			'content'  => 'event',
			'action'   => 'import',
			'format'   => 'json',
			'override' => 1,
			'data'     => $data,
	);
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $token);
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




function cloneStudy($studyProtId){
	include '../config/config.php';
	include '../classes/OpenClinicaSoapWebService.php';
	$response = createProject($studyProtId);
	//project created, token received
	if(strlen($response)==32){
		updateTokens($response);
		$projectToken = $response;
		
		//get study metadata
		
		$meta_server = 	new OpenClinicaSoapWebService($openClinicaConfig['ocplay']['ocWSUrl'], $ocUserName, $ocPassword);
		
		// get metadata from server
		$getMetadata = $meta_server->studyGetMetadata ( $studyProtId );
		
		$odmMetaRaw_server = $getMetadata->xpath('//v1:odm');
		$odmMeta_server = simplexml_load_string($odmMetaRaw_server[0]);
		$odmMeta_server->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		
		$studyOID_server = $odmMeta_server->Study->attributes()->OID;
		$studyName_server = $odmMeta_server->Study->GlobalVariables->StudyName;
		
		$events_oc = array();
		$forms_oc = array();
		$groups_oc = array();
		$items_oc = array();
		
		//server events
		foreach ($odmMeta_server->Study->MetaDataVersion->StudyEventDef as $eventDefs){
			$eventId = (string)$eventDefs->attributes()->OID;
			$eventName = (string)$eventDefs->attributes()->Name;
			$refs = array();
			$eventRepeating = (string)$eventDefs->attributes()->Repeating;
			foreach ($eventDefs->FormRef as $formRefs){
				$formRef = (string)$formRefs->attributes()->FormOID;
				$refs[] = $formRef;
			}
			$events_oc[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
		}
		
		//server forms
		foreach ($odmMeta_server->Study->MetaDataVersion->FormDef as $formDefs){
			$formId = (string)$formDefs->attributes()->OID;
			$formName = (string)$formDefs->attributes()->Name;
			$refs = array();
			foreach ($formDefs->ItemGroupRef as $igRefs){
				$igRef = (string)$igRefs->attributes()->ItemGroupOID;
				$refs[] = $igRef;
			}
			$forms_oc[$formId]= array ("name"=>$formName,"refs"=>$refs);
		}
		
		//server groups
		foreach ($odmMeta_server->Study->MetaDataVersion->ItemGroupDef as $igDefs){
			$igId = (string)$igDefs->attributes()->OID;
			$igName = (string)$igDefs->attributes()->Name;
			$refs = array();
			foreach ($igDefs->ItemRef as $iRefs){
				$iRef = (string)$iRefs->attributes()->ItemOID;
				$refs[] = $iRef;
			}
			$groups_oc[$igId]= array ("name"=>$igName,"refs"=>$refs);
		}
		
		//server items
		foreach ($odmMeta_server->Study->MetaDataVersion->ItemDef as $iDefs){
			$iId = (string)$iDefs->attributes()->OID;
			$iName = (string)$iDefs->attributes()->Name;
			$namespaces = $iDefs->getNameSpaces(true);
			$OpenClinica = $iDefs->children($namespaces['OpenClinica']);
			$fOID = array();
			foreach ($OpenClinica as $oc){
				$subelement = $oc->children($namespaces['OpenClinica']);
				foreach ($subelement as $sube){
					$subattr = $sube->attributes();
					$fOID[] = (string)$subattr['FormOID'];
				}
			}
		
			$items_oc[$iId]= array ("name"=>$iName,"foid"=>$fOID);
		}
		
		//CREATE EVENTS
		
		$res = createRedCapEvents($projectToken, $events_oc);
		
		
		printResponse(200,"New project has been created.",$res,null,null);
	}
	else{
		printResponse(1,"Error: ".$response,null,null,null);
	}
	
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
/* 	else if($action == "createProject"){
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
	
 */	
	
	else if($action == "createProject"){
		$pname = $_POST['name'];
	
		if(!empty($pname)){
		
			cloneStudy($pname);
			
		}
		else{
			printResponse(1,"Error! Missing project name.",null,null,null);
		}
	}
	
	
	
	else if($action == "getStudies"){
	
	include '../classes/OpenClinicaSoapWebService.php';
	include '../config/config.php';
	$client = new OpenClinicaSoapWebService($openClinicaConfig['ocplay']['ocWSUrl'], $ocUserName, $ocPassword);
	$getAllStudies = $client->studyListAll();

	$odmMetaRaw = $getAllStudies->xpath('//v1:studies');
	$odmMeta = simplexml_load_string($odmMetaRaw[0]);
//$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

	$studies = array();
	foreach ($getAllStudies->xpath('//v1:study') as $study){
		
		$studyProtocolId =(string) $study[0]->identifier;
		$studyName = (string)$study[0]->name;
		$studyOID = (string)$study[0]->oid;
		
		if (!empty($studyProtocolId)){
			$studies[] = array("id"=>$studyProtocolId,"name"=>$studyName,"oid"=>$studyOID,"redcapapi"=>null);
		}
	
	}
		
		if(!empty($studies)){
			printResponse(200,"OK",$studies,null,null);
		}
		else{
			printResponse(1,"Couldn't determine study list.",null,null,null);
		}
	}
	//ACTION:getStudyMetaData
	else if($action == "getStudyMetaData"){
		$studyProtocolId = $_POST['studyProtocolId'];

		if(empty($studyProtocolId)){
			printResponse(1,"Missing study protocol id.",null,null,null);
		}
		include '../classes/OpenClinicaSoapWebService.php';
		include '../config/config.php';
		
		$meta =	new OpenClinicaSoapWebService($openClinicaConfig['ocplay']['ocWSUrl'], $ocUserName, $ocPassword);
		$getMetadata = $meta->studyGetMetadata ( $studyProtocolId );
		$odmMetaRaw = $getMetadata->xpath('//v1:odm');
		$odmMeta = simplexml_load_string($odmMetaRaw[0]);
		$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		$studyOID = $odmMeta->Study->attributes()->OID;
		$studyName = $odmMeta->Study->GlobalVariables->StudyName;
		
		$events = array();
		$forms = array();
		$evform = array();
		
		foreach ($odmMeta->Study->MetaDataVersion->StudyEventDef as $eventDefs){
			$eventId = (string)$eventDefs->attributes()->OID;
			$eventName = (string)$eventDefs->attributes()->Name;
			$refs = array();
			$eventRepeating = (string)$eventDefs->attributes()->Repeating;
			foreach ($eventDefs->FormRef as $formRefs){
				$formRef = (string)$formRefs->attributes()->FormOID;
				$refs[] = $formRef;
			}
			$events[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
		}
		
		
		//server forms
		foreach ($odmMeta->Study->MetaDataVersion->FormDef as $formDefs){
			$formId = (string)$formDefs->attributes()->OID;
			$formName = (string)$formDefs->attributes()->Name;
			$refs = array();
			foreach ($formDefs->ItemGroupRef as $igRefs){
				$igRef = (string)$igRefs->attributes()->ItemGroupOID;
				$refs[] = $igRef;
			}
			$forms[$formId]= array ("name"=>$formName,"refs"=>$refs);
		}
		
		
		foreach ($events as $ekey=>$ev){
			$formR = $ev['refs'];
			//$evform[$events[$ekey]['name']] = array();
			foreach ($formR as $frkey){
				$evform[$events[$ekey]['name']][] = $forms[$frkey]['name'];

			}
		}
		
		printResponse(200,"Events/forms list",$evform,null,null);
		
		
	}
	
	
	
	
	
	
	
	
	
	//UNKNOWN action
	else{
		printResponse(1,"Unknown action!",null,null,null);
	}
}
