<?php
	include("config/mars.inc.php");

	$json = GetSessionData(2, '192.168.1.26');
	echo $json;
	exit;
	$jsonString = file_get_contents("/tmp/createsession.txt");
	$req = json_decode($jsonString, false);

	$resp = new Application();
	foreach ($req as $key => $value)
	{
		echo "$key\n";
		$resp->{$key} = $value;
	}

	EditServer($resp->Settings->MARS_SERVERID->Value, $resp->IpAddress, $resp->Port);
	$resp->SessionId = GetSessionId($resp->Settings->MARS_SERVERID->Value);
	$resp->IpAddress = "10.0.1.1";

	$json = json_encode($resp);

	echo $json;
	//header('Content-Type: application/json; charset=utf-8');
	//header("Content-Length: " . strlen($json));
	//file_put_contents("/tmp/resp.log", $json);

	//file_put_contents("/tmp/post.log", print_r($_POST, true));
	//file_put_contents("/tmp/gpc.log", print_r($_REQUEST, true));
	//file_put_contents("/tmp/get.log", print_r($_GET, true));
	//file_put_contents("/tmp/hdr.log", print_r(getallheaders(), true));
?>
