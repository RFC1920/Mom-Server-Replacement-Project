#!/usr/bin/env php
<?php
	$json = file_get_contents("https://agclxre5zl.execute-api.eu-central-1.amazonaws.com/Prod/GetAllSessions?platform=STEAM&build=114912");
	$obj = json_decode($json, false);

	echo "Server Name                                             SessionId   Mode    Connected  Available\n";
	echo "------------------------------------------------------------------------------------------------\n";

	$total = 0;
	$avail = 0;
	foreach ($obj->Sessions as $x => $svr)
	{
		//$name = str_pad($svr->Settings->MARS_SERVERID->Value, 28, " ", STR_PAD_RIGHT);
		$name = str_pad($svr->OwningUserName, 55, " ", STR_PAD_RIGHT);
		$connected = str_pad($svr->NumPublicConnections - $svr->NumOpenPublicConnections, 10, " ", STR_PAD_RIGHT);
		$total += (int)trim($connected);
		$avail += (int)trim($svr->NumOpenPublicConnections);
		//$sessionid = $svr->Settings->SessionID->Value;
		$sessionid = str_pad($svr->SessionId, 12, " ", STR_PAD_RIGHT);
		$mode = str_pad($svr->Settings->MARS_GAMESERVER_MODE->Value, 5,  " ", STR_PAD_RIGHT);
		echo "$name\t$sessionid$mode\t$connected$svr->NumOpenPublicConnections\n";
	}

	echo "------------------------------------------------------------------------------------------------\n";
	echo "Total active players $total -- Available free slots $avail\n";

