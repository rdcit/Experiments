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

//TODO: check session, permission here

if(count($_POST)>0){

	$action = $_POST['action'];
	//ACTION: getStudies
	if($action == "getStudies"){
		
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
}







