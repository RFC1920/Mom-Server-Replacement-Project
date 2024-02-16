<?php
/*
    MoM Server Replacement Project
    Copyright (c) 2024 RFC1920 <desolationoutpostpve@gmail.com>

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License v2.0.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

    Optionally you can also view the license at <http://www.gnu.org/licenses/>.
*/

	$databaseFILE = dirname(__DIR__) . "/config/mars.sqlite3";
	$database = new PDO("sqlite:" . $databaseFILE);

	$keepalive_seconds = 120;
	$debug = true;

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
		$stmt = $database->prepare("CREATE TABLE mom_servers
			(sessionid integer primary key autoincrement, numpub varchar, numpriv varchar, shouldadv bit, allowjoin bit, islan bit, isded bit,
			 usestats bit, allowinv bit, usepres bit, allowpresjoin bit, allowjoinpresfr bit, anticheat bit,
			 build varchar, owner varchar, ipaddress varchar, port int, mapname varchar, serverid varchar,
			 platform varchar, audience varchar, mode varchar, gametype bit, password bit, timestamp varchar DEFAULT '0', active bit)"
		);
		$stmt->execute();
	}

	#$data = file_get_contents('createsession.json');
	#$resp = json_decode($data, false);
	#$id = EditServer($resp);
	#echo $id;
	#exit;
	// Functions
	if (!function_exists('getallheaders'))
	{
		function getallheaders()
		{
			$headers = [];
			foreach ($_SERVER as $name => $value)
			{
				if (substr($name, 0, 5) == 'HTTP_')
				{
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			return $headers;
		}
	}

	function patchMethod()
	{
		parse_str(file_get_contents('php://input'), $_PATCH);
		$body = [];
		if (@is_array($_PATCH))
		{
			foreach ($_PATCH as $key => $value)
			{
				$body[$key] = $value;
			}

		}
		return $body;
	}

	//function EditServer($servername, $addr, $port, $sessionid = '')
	function EditServer($data)
	{
		global $debug;
		global $database;
		//$sessionid = "0";
		$sessionid = $data->SessionId;
		$sessString = $sessionid;
		if ((int)$sessionid == 0)
		{
			$sessString = 'null';
		}

		//if ($data->SessionId == "0")
		//{
		//	$sessionid = CreateSessionId();
		//}
		$servername = trim($data->Settings->MARS_SERVERID->Value);
		$addr = trim($data->IpAddress);
		$shouldadv = $data->ShouldAdvertise ? 1 : 0;
		$allowjoin = $data->AllowJoinInProgress ? 1 : 0;
		$islan     = $data->IsLANMatch ? 1 : 0;
		$isded     = $data->IsDedicated ? 1 : 0;
		$usestats  = $data->UsesStats ? 1 : 0;
		$allowinv  = $data->AllowInvites ? 1 : 0;
		$usepres   = $data->UsesPresence ? 1 : 0;
		$allowpresjoin = $data->AllowJoinViaPresence ? 1 : 0;
		$allowjoinpresfr = $data->AllowJoinViaPresenceFriendsOnly ? 1 : 0;
		$anticheat  = $data->AntiCheatProtected ? 1 : 0;

		$time = time();

		$stmt = $database->prepare("DELETE FROM mom_servers WHERE serverid='$servername'");
		$stmt->execute();
		$active = $sessionid > 0 ? 1 : 0;

		$stmt = $database->prepare("INSERT OR REPLACE INTO mom_servers VALUES("
			. "$sessString, '" . $data->NumPublicConnections . "', '" . $data->NumPrivateConnections . "', $shouldadv, $allowjoin, $islan, $isded, "
			. "$usestats, $allowinv, $usepres, $allowpresjoin, $allowjoinpresfr, $anticheat,"
			. "'" . $data->BuildUniqueId . "', '" . $data->OwningUserName . "', '" . $addr . "', "
			. $data->Port . ", '" . $data->Settings->MapName->Value . "',"
			. "'" . $data->Settings->MARS_SERVERID->Value . "',"
			. "'" . $data->Settings->LIMBIC_TARGET_PLATFORMS->Value . "', '" . $data->Settings->MARS_AUDIENCE->Value . "',"
			. "'" . $data->Settings->MARS_GAMESERVER_MODE->Value . "', " . $data->Settings->MARS_GAMESERVER_TYPE->Value . ", "
			. $data->Settings->Password->Value . ", '$time', $active)"
		);
		try
		{
			$stmt->execute();
			$sessionid = $database->lastInsertID();
		}
		catch(PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/editsvr_error", $e->getMessage());
		}
		return $sessionid;
	}

	function GetSessionData($sessionid, $ipaddr)
	{
		global $debug;
		global $database;
		$stmt = $database->prepare("SELECT * FROM mom_servers WHERE sessionid=$sessionid");
		try
		{
			$stmt->execute();
		}
		catch(PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/getsessdata_error", $e->getMessage());
		}

		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$output = array(
			"SessionId" => $data[0]['sessionid'],
			"NumPublicConnections" => $data[0]['numpub'],
			"NumPrivateConnections" => $data[0]['numpriv'],
			"ShouldAdvertise" => $data[0]['shouldadv'],
			"AllowJoinInProgress" => $data[0]['allowjoin'],
			"IsLANMatch" => $data[0]['islan'],
			"IsDedicated" => $data[0]['isded'],
			"UsesStats" => $data[0]['usestats'],
			"AllowInvites" => $data[0]['allowinv'],
			"UsesPresence" => $data[0]['usepres'],
			"AllowJoinViaPresence" => $data[0]['allowpresjoin'],
			"AllowJoinViaPresenceFriendsOnly" => $data[0]['allowjoinpresfr'],
			"AntiCheatProtected" => $data[0]['anticheat'],
			"BuildUniqueId" => $data[0]['build'],
			"OwningUserName" => $data[0]['owner'],
			"IpAddress" => $data[0]['ipaddress'],
			"Port" => $data[0]['port'],
			"Settings" => array(
				"MapName" => array(
					"Type"  => "String",
					"Value" => "Untitled_0"
				),
				"MARS_SERVERID" => array(
					"Type" => "String",
					"Value"> $data[0]['serverid']
				),
				"LIMBIC_TARGET_PLATFORMS" => array(
					"Type"  => "String",
					"Value" => $data[0]['platform']
				),
				"MARS_AUDIENCE" => array(
					"Type" => "String",
					"Value" => "MoM"
				),
				"MARS_GAMESERVER_MODE" => array(
					"Type" => "String",
					"Value" => $data[0]['mode'],
				),
				"MARS_GAMESERVER_TYPE" => array(
					"Type" => "Bool",
					"Value" => $data[0]['gametype']
				),
				"Password" => array(
					"Type" => "Bool",
					"Value" => $data[0]['password']
				)
			)
		);

		return json_encode($output, false);
	}

	function GetSessionId($servername)
	{
		global $debug;
		global $database;
		$servername = trim($servername);
		$stmt = $database->prepare("SELECT sessionid FROM mom_servers WHERE serverid='$servername'");

		try
		{
			$stmt->execute();
		}
		catch(PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/getsess_error", $e->getMessage());
		}

		$servers = $stmt->fetchColumn();
		return $servers;
	}

	function CreateSessionId()
	{
		$id = rand(1, 65535);
		return $id;
		//$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		//$charactersLength = strlen($characters);
		//$randomString = '';
		//for ($i = 0; $i < $length; $i++)
		//{
		//	$randomString .= $characters[random_int(0, $charactersLength - 1)];
		//}

		//return md5($randomString);
	}

	function UpdateSession($servername, $id)
	{
		global $debug;
		global $database;

		$time = time();
		$active = $id > 0 ? 1 : 0;
		if ($debug) `echo "Updating session for $servername, new sessionid = $id >> /tmp/update.log`;
		$stmt = $database->prepare("UPDATE mom_servers SET sessionid='$id', timestamp='$time', active=$active WHERE serverid='$servername'");
		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/update_error", $e->getMessage());
		}
	}

	function KeepAliveSession($sessionid, $ipaddr)
	{
		global $debug;
		global $database;

		$stmt = $database->prepare("SELECT sessionid FROM mom_servers WHERE ipaddress='$ipaddr' AND sessionid=$sessionid");

		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/keepalive_error", $e->getMessage());
		}

		$session = $stmt->fetchColumn();
		if ($session != "")
		{
			$time = time();
			$stmt = $database->prepare("UPDATE mom_servers SET timestamp='$time' WHERE sessionid='$session'");
			try
			{
				$stmt->execute();
			}
			catch (PDOException $e)
			{
				if ($debug) file_put_contents("/tmp/keepalive_error", $e->getMessage());
			}
		}
	}

	function DeactivateSession($sessionid)
	{
		global $debug;
		global $database;
		$stmt = $database->prepare("DELETE FROM mom_servers WHERE sessionid='$sessionid'");
		$stmt->execute();
	}

	function DeactivateAll()
	{
		global $debug;
		global $database;
		$stmt = $database->prepare("DELETE FROM mom_servers");
		$stmt->execute();
	}

	function GetAllServers()
	{
		global $debug;
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

