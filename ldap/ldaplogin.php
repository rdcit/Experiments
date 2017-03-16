<?php
	
include 'settings.php';
// connect to ldap server
$ldapconn = ldap_connect($adServer)
    or die("Could not connect to LDAP server.");

if ($ldapconn) {

    // binding to ldap server
    $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

    // verify binding
    if ($ldapbind) {
        echo "LDAP bind successful...</br>";
	echo "Retrieving users:<br/><br/>";

         $dn="OU=OpenClinicaOU,DC=ocsecure,DC=local";

	   $attributes = array("displayname", "email");

    $filter = "(cn=*)";

    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);


    $result = ldap_search($ldapconn, $dn, $filter, $attributes);

    $entries = ldap_get_entries($ldapconn, $result);

    for ($i=0; $i<$entries["count"]; $i++)
    {
		if(!empty($entries[$i]["displayname"][0]))
        echo $entries[$i]["displayname"][0]."<br/>";
    }
	//var_dump($entries);
    ldap_unbind($ldapconnect);

    } else {
        echo "LDAP bind failed...";
    }

}


?>

