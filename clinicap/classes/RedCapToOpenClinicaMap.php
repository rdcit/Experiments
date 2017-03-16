<?php



class RedCapToOpenClinicaMap{
	
	/*
	 * RedCap protocolId = OpenClinica protocolId 
	 * (the main link between the project and the study)
	 */
	protected $protocolId;
	protected $sourceXmlURI;
	
	/*
	 * RedCap Project MetaData 
	 * @var unknown
	 */
	protected $eventsSource = array();
	protected $formsSource = array();
	protected $groupsSource = array();
	protected $itemsSource = array();
	
	/*
	 * OpenClinica Study MedaData
	 */
	protected $eventsTarget = array();
	protected $formsTarget = array();
	protected $groupsTarget = array();
	protected $itemsTarget = array();
	
	/*
	 * Sync rules/maps
	 */
	protected  $syncDataMap = array();
	protected  $syncStudyMap = array();
	protected  $syncSubjectMap = array();
	

	public function __construct($rcXmlURI) {
		
		$this->sourceXmlURI = $rcXmlURI;
		
	}

	/*
	 * GETTERS
	 */
	public function getProtocolId(){
		return $this->protocolId;
	}
	
	public function getSourceXmlURI(){
		return $this->sourceXmlURI;
	}
	
	public function getEventsSource(){
		return $this->eventsSource;
	}
	
	public function getFormsSource(){
		return $this->formsSource;
	}	
	
	public function getGroupsSource(){
		return $this->groupsSource;
	}
	
	public function getItemsSource(){
		return $this->itemsSource;
	}
	
	public function getEventsTarget(){
		return $this->eventsTarget;
	}
	
	public function getFormsTarget(){
		return $this->formsTarget;
	}
	
	public function getGroupsTarget(){
		return $this->groupsTarget;
	}
	
	public function getItemsTarget(){
		return $this->itemsTarget;
	}
	
	public function getSyncDataMap(){
		return $this->syncDataMap;
	}
	
	public function getSyncStudyMap(){
		return $this->syncStudyMap;
	}
	
	
	
	
	/**
	 * Loads the RedCap ODM XML and gives value to RedCap Project MetaData variables 
	 * 
	 */
	public function loadSourceMetaData(){
		$uri = $this->sourceXmlURI;
		$odmMeta = simplexml_load_file($uri);
		$odmMeta->registerXPathNamespace('redcap', 'http://projectredcap.org');
		
		$projectOID = $odmMeta->Study->attributes()->OID;
		$this->protocolId = $odmMeta->Study->GlobalVariables->ProtocolName;

		//client events
		foreach ($odmMeta->Study->MetaDataVersion->StudyEventDef as $eventDefs){
			$eventId = (string)$eventDefs->attributes()->OID;
			$eventName = (string)$eventDefs->attributes()->Name;
			$refs = array();
			$eventRepeating = (string)$eventDefs->attributes()->Repeating;
			foreach ($eventDefs->FormRef as $formRefs){
				$formRef = (string)$formRefs->attributes()->FormOID;
				$refs[] = $formRef;
			}
			$this->eventsSource[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
		}
		
		//client forms
		foreach ($odmMeta->Study->MetaDataVersion->FormDef as $formDefs){
			$formId = (string)$formDefs->attributes()->OID;
			
			$namespaces = $formDefs->getNameSpaces(true);
			$xsi = $formDefs->attributes($namespaces['redcap']);
			$formName =  (String)$xsi['FormName'];

			$refs = array();
			foreach ($formDefs->ItemGroupRef as $igRefs){
				$igRef = (string)$igRefs->attributes()->ItemGroupOID;
				$refs[] = $igRef;
			}
			$this->formsSource[$formId]= array ("name"=>$formName,"refs"=>$refs);
		}
		
		//client groups
		foreach ($odmMeta->Study->MetaDataVersion->ItemGroupDef as $igDefs){
			$igId = (string)$igDefs->attributes()->OID;
			$igName = (string)$igDefs->attributes()->Name;
			$refs = array();
			foreach ($igDefs->ItemRef as $iRefs){
				$iRef = (string)$iRefs->attributes()->ItemOID;
				$refs[] = $iRef;
			}
			$this->groupsSource[$igId]= array ("name"=>$igName,"refs"=>$refs);
		}
		
		//client items
		foreach ($odmMeta->Study->MetaDataVersion->ItemDef as $iDefs){
			$iId = (string)$iDefs->attributes()->OID;
			$iName = (string)$iDefs->attributes()->Name;
		
			$this->itemsSource[$iId]= array ("name"=>$iName);
		}
		
		$isSite=false;
		foreach ($odmMeta->Study as $studyDefs){
			$studyOID = (string)$studyDefs->attributes()->OID;
			$studyName = (string)$studyDefs->GlobalVariables->ProtocolName;
			$this->syncStudyMap[$studyName]=array("name"=>$studyName, "clientoid"=>$studyOID, "issite"=>$isSite);
			$isSite=true;
		}
		
		
	}
	
	/**
	 * Connects to the OpenClinica instance and gives value to the OpenClinica MetaData variables
	 */
	public function loadTargetMetaData(){
		
		include_once 'classes/OpenClinicaSoapWebService.php';
		include 'config/config.php';
		
		$meta = new OpenClinicaSoapWebService($openClinicaConfig['ocplay']['ocWSUrl'], $ocUserName, $ocPassword);
		
		// get metadata from server
		$getMetadata = $meta->studyGetMetadata ( $this->protocolId );
		
		$odmMetaRaw = $getMetadata->xpath('//v1:odm');
		
		$odmMeta = simplexml_load_string($odmMetaRaw[0]);
		
		$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		
		$studyOID = $odmMeta->Study->attributes()->OID;
		$studyName = $odmMeta->Study->GlobalVariables->StudyName;
		
		
		//server events
		foreach ($odmMeta->Study->MetaDataVersion->StudyEventDef as $eventDefs){
			$eventId = (string)$eventDefs->attributes()->OID;
			$eventName = (string)$eventDefs->attributes()->Name;
			$refs = array();
			$eventRepeating = (string)$eventDefs->attributes()->Repeating;
			foreach ($eventDefs->FormRef as $formRefs){
				$formRef = (string)$formRefs->attributes()->FormOID;
				$refs[] = $formRef;
			}
			$this->eventsTarget[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
		}
		
		//server forms
		foreach ($odmMeta->Study->MetaDataVersion->FormDef as $formDefs){
			$formId = (string)$formDefs->attributes()->OID;
			$formName = (string)$formDefs->attributes()->Name;
			$pos = strrpos($formName, "-");
			$formName = trim(substr($formName, 0, $pos));
			$refs = array();
			foreach ($formDefs->ItemGroupRef as $igRefs){
				$igRef = (string)$igRefs->attributes()->ItemGroupOID;
				$refs[] = $igRef;
			}
			$this->formsTarget[$formId]= array ("name"=>$formName,"refs"=>$refs);
		}
		
		//server groups
		foreach ($odmMeta->Study->MetaDataVersion->ItemGroupDef as $igDefs){
			$igId = (string)$igDefs->attributes()->OID;
			$igName = (string)$igDefs->attributes()->Name;
			$refs = array();
			foreach ($igDefs->ItemRef as $iRefs){
				$iRef = (string)$iRefs->attributes()->ItemOID;
				$refs[] = $iRef;
			}
			$this->groupsTarget[$igId]= array ("name"=>$igName,"refs"=>$refs);
		}
		
		//server items
		foreach ($odmMeta->Study->MetaDataVersion->ItemDef as $iDefs){
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
		
			$this->itemsTarget[$iId]= array ("name"=>$iName,"foid"=>$fOID);
		}
		
		
		foreach ($odmMeta->Study as $studyDefs){
			$studyOID = (string)$studyDefs->attributes()->OID;
			$studyName = (string)$studyDefs->GlobalVariables->ProtocolName;
			if (isset($this->syncStudyMap[$studyName])){
				$this->syncStudyMap[$studyName]["serveroid"]=$studyOID;
			}
		
		}
	}
	
	
	/**
	 * Creates the mapping between the RC project and the OC study using the MetaData variables.
	 * Gives value to the Sync map/rules variables.
	 */
	public function createSyncDataMap(){
		
		foreach ($this->eventsSource as $ekey=>$ev){
			$formR = $ev['refs'];
			foreach ($formR as $frkey){
				$igRef = $this->formsSource[$frkey]['refs'];
				foreach ($igRef as $igkey){
					$irefs = $this->groupsSource[$igkey]['refs'];
					//display all the items associated with the current form version
					foreach ($irefs as $ikey){
						$mapID = $this->eventsSource[$ekey]['name'].'::'.$this->formsSource[$frkey]['name'].'::'.$this->itemsSource[$ikey]['name'];
						$clientOIDComp = $ekey.'::'.$frkey.'::'.$igkey.'::'.$ikey;
						$this->syncDataMap[$mapID]=array('client'=>$clientOIDComp,'server'=>null);
					}
				}
			}
		}
		
		foreach ($this->eventsTarget as $ekey=>$ev){
			$formR = $ev['refs'];
			foreach ($formR as $frkey){
				$igRef = $this->formsTarget[$frkey]['refs'];
				foreach ($igRef as $igkey){
					$irefs = $this->groupsTarget[$igkey]['refs'];
					//display all the items associated with the current form version
					foreach ($irefs as $ikey){
						//$mapID = $events_server[$ekey]['name'].'::'.$forms_server[$firstFR]['name'].'::'.$groups_server[$igr]['name'].'::'.$items_server[$item]['name'];
						$mapID = $this->eventsTarget[$ekey]['name'].'::'.$this->formsTarget[$frkey]['name'].'::'.$this->itemsTarget[$ikey]['name'];
						$serverOIDComp = $ekey.'::'.$frkey.'::'.$igkey.'::'.$ikey;
						//echo $mapID.'<br/>';
						if(isset($this->syncDataMap[$mapID])){
							$this->syncDataMap[$mapID]['server']=$serverOIDComp;
						}
					}
				}
			}
		}
		
	}
	
	
	
	public function syncSubjects(){
		
		include_once 'classes/OpenClinicaSoapWebService.php';
		include 'config/config.php';
		
		
		$wsOC = new OpenClinicaSoapWebService($openClinicaConfig['ocplay']['ocWSUrl'], $ocUserName, $ocPassword);

		$odmMetaRC = simplexml_load_file($uri);
		$odmMetaRC->registerXPathNamespace('redcap', 'http://projectredcap.org');
		
		
		
		foreach ($odmMetaRC->ClinicalData as $clientClinicalDataNode){
			$siteID_server = null;
		
			$studyOID_client = (string)$clientClinicalDataNode->attributes()->StudyOID;
			foreach($_SESSION['syncStudies'] as $sskey=>$ss){
				if($ss['clientoid'] == $studyOID_client){
					if($ss['issite']){
						$prefix = $ocUniqueProtocolId_server." - ";
						$str = $sskey;
						if (substr($str, 0, strlen($prefix)) == $prefix) {
							$siteID_server = substr($str, strlen($prefix));
						}
		
		
					}
				}
			}
		
			//echo $studyOID_client." = ".$ocUniqueProtocolId_server." + ".$siteID_server."<br/>";
		
			foreach($clientClinicalDataNode->SubjectData as $clientSubjectDataNode){
		
				$subjID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->StudySubjectID;
				$clientSubjOID = (string)$clientSubjectDataNode->attributes()->SubjectKey;
		
				$isStudySubject = $meta_server->subjectIsStudySubject($ocUniqueProtocolId_server,
						$siteID_server, $subjID);
		
				// if the current subject is existing in OC Server
				if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
					$servStudSubjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
					$finalSubjects[$subjID] = array("subjID"=>$subjID,"serverSubjOID"=>$servStudSubjOID,"clientSubjOID"=>$clientSubjOID,"existed"=>true, "error"=>null);
		
				}
				else{
					//import subject and determine subject OID for $finalSubjects
		
					$personID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->UniqueIdentifier;
					$secondaryID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->SecondaryID;
					$DOB = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->DateOfBirth;
					$gender = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->Sex;
					$enrollmentDate = Date('Y-m-d');
		
					if(empty($secondaryID)) $secondaryID=null;
					if(empty($personID)) $personID=null;
					if(empty($DOB)) $DOB=null;
					if(empty($gender)) $gender=null;
		
		
					$createSubject = $meta_server->subjectCreateSubject($ocUniqueProtocolId_server,
							$siteID_server, $subjID, $secondaryID,
							$enrollmentDate, $personID, $gender, $DOB);
		
					//if creation is successful, try to determine the subject's OID
					if ($createSubject->xpath('//v1:result')[0]=='Success'){
		
						$isStudySubject = $meta_server->subjectIsStudySubject($ocUniqueProtocolId_server,
								$siteID_server, $subjID);
		
						if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
							$servStudSubjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
							$finalSubjects[$subjID] = array("subjID"=>$subjID,"serverSubjOID"=>$servStudSubjOID,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>null);
						}
						else {
							$err = (string)$isStudySubject->xpath('//v1:error')[0];
							$finalSubjects[$subjID] = array("clientSubjID"=>$subjID,"serverSubjOID"=>null,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>$err);
						}
		
		
					}
					else {
						$err = (string)$createSubject->xpath('//v1:error')[0];
						$finalSubjects[$subjID] = array("clientSubjID"=>$subjID,"serverSubjOID"=>null,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>$err);
					}
				}
		
		
			}
		
		
		}
		
		
		
		
		
		
		
	}

}

?>