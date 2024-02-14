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
		$stmt = $database->prepare("SELECT * FROM mom_servers");
		$stmt->execute();

		$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($servers as $server)
		{
			print_r($servers);
		}
	}

	//DeactivateAll();
	//EditServer("RFCs Server", "10.0.1.1", "7777");
	//GetAllServers();

