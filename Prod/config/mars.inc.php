<?php
	$databaseFILE = dirname(__DIR__) . "/config/mars.sqlite3";
	$database = new PDO("sqlite:" . $databaseFILE);

	// Check and setup database
	$dbexists = false;

	$stmt = $database->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'mom_servers'");
	$stmt->execute();
	if ($stmt->fetchAll())
	{
		$dbexists = true;
	}

	if (!$dbexists)
	{
		$stmt = $database->prepare("CREATE TABLE mom_servers (name varchar(64), ipaddress varchar(64), port varchar(64), sessionid varchar(64), active INTEGER(1) DEFAULT 0)");
		$stmt->execute();
	}

	// Functions
	function EditServer($servername, $addr, $port, $sessionid = '', $enable=true)
	{
		global $database;
		if ($sessionid == '')
		{
			$sessionid = CreateSessionId();
		}
		$servername = trim($servername);
		$addr = trim($addr);

		$bit = 0;
		if ($enable) $bit = 1;

		$stmt = $database->prepare("DELETE FROM mom_servers WHERE name='$servername'");
		$stmt->execute();

		$stmt = $database->prepare("INSERT OR REPLACE INTO mom_servers VALUES('$servername', '$addr', '$port', '$sessionid', $bit)");
		try
		{
			$stmt->execute();
		}
		catch(PDOException $e)
		{
			file_put_contents("/tmp/editsvr_error", $e->getMessage());
		}
	}

	function GetSessionId($servername)
	{
		global $database;
		$servername = trim($servername);
		$stmt = $database->prepare("SELECT sessionid FROM mom_servers WHERE name='$servername'");

		try
		{
			$stmt->execute();
		}
		catch(PDOException $e)
		{
			file_put_contents("/tmp/getsess_error", $e->getMessage());
		}

		$servers = $stmt->fetchColumn();
		return $servers;
	}

	function CreateSessionId($length=32)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++)
		{
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

        return md5($randomString);
	}

	function DeactivateSession($sessionid)
	{
		global $database;
		$stmt = $database->prepare("DELETE FROM mom_servers WHERE sessionid='$sessionid'");
		$stmt->execute();
	}

	function DeactivateAll()
	{
		global $database;
		$stmt = $database->prepare("DELETE FROM mom_servers");
		$stmt->execute();
	}

	function GetAllServers()
	{
		global $database;
		$output = array();

		$stmt = $database->prepare("SELECT * FROM mom_servers");
		$stmt->execute();

		$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$i = 0;
		foreach($servers as $server)
		{
			$output[$i] = array(
				'Name' => $server['name'],
				'IPAddress' => $server['ipaddress'],
				'Port' => $server['port'],
				"SessionId" => $server['sessionid'],
				"NumPublicConnections" => 2,
				"NumPrivateConnections" => 2,
				"ShouldAdvertise" => true,
				"AllowJoinInProgress" => true,
				"IsLANMatch" => true,
				"IsDedicated" => true,
				"UsesStats" => false,
				"AllowInvites" => true,
				"UsesPresence" => false,
				"AllowJoinViaPresence" => true,
				"AllowJoinViaPresenceFriendsOnly" => false,
				"AntiCheatProtected" => true,
				"BuildUniqueId" => "114912",
				"OwningUserName" => "rfcmom",
				"Settings" => array(
					"MapName" => array(
						"Type" => "String",
						"Value" => "Untitled_0"
					),
					"MARS_SERVERID" => array(
						"Type" => "String",
						"Value" => "mom_rfc_01"
					),
					"LIMBIC_TARGET_PLATFORMS" => array(
						"Type" => "String",
						"Value" => "steam"
					),
					"MARS_AUDIENCE" => array(
						"Type" => "String",
						"Value" => "MoM"
					),
					"MARS_GAMESERVER_MODE" => array(
						"Type" => "String",
						"Value" => "PVE"
					),
					"MARS_GAMESERVER_TYPE" => array(
						"Type" => "Bool",
						"Value" => true
					),
					"Password" => array(
						"Type" => "Bool",
						"Value" => true
					)
				)
			);
			$i++;
		}
		return $output;
	}

	//DeactivateAll();
	//EditServer("RFCs Server", "10.0.1.1", "7777");
	//GetAllServers();

