<?php
include 'config/config.php';

class CliniCap{

	private $ocODMXmlURI;
	
	
	
	
	
	public function loadOCMetaData($ocODMXmlURI){
		
		$ocXML = simplexml_load_file($ocODMXmlURI);
		$ocXML->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		
		$ocStudyOID = $ocXML->Study->attributes()->OID;
		$ocStudyName = $ocXML->Study->GlobalVariables->ProtocolName;
		
		
		
		
		
	}
	
	
	
}

?>