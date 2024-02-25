#!/usr/bin/env php
<?php
	$server = @$argv[1];
	if ($server == "")
	{
		echo "Usage $argv[0] SERVERNAME\n";
		exit;
	}

	$json = file_get_contents("https://agclxre5zl.execute-api.eu-central-1.amazonaws.com/Prod/GetAllSessions?platform=STEAM&build=114912");
	$obj = json_decode($json, false);

	foreach ($obj->Sessions as $x => $svr)
	{
		if ($svr->Settings->MARS_SERVERID->Value == $server)
		{
			$connected = $svr->NumPublicConnections - $svr->NumOpenPublicConnections;
			//$sessionid = $svr->Settings->SessionID->Value;
			$sessionid = $svr->SessionId;
			$mode = $svr->Settings->MARS_GAMESERVER_MODE->Value;
			echo "$server:\n\tSessionId = $sessionid\n\tMode: $mode\n\tConnected: $connected\n\tAvailable: $svr->NumOpenPublicConnections\n";
			break;
		}
	}
