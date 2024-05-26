<?php
/*
    MoM Data Server Replacement Project
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

		if ($debug) `echo GOT HERE 1 >> /tmp/editserver.log`;
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
		$port       = @$data->Settings->Port != 0 ? $data->Settings->Port : 7777;
		$beacon     = @$data->Settings->BeaconPort != 0 ? $data->Settings->BeaconPort : 15000;
		//$type       = @$data->Settings->MARS_GAMESERVER_TYPE->Value != 0 ? $data->Settings->MARS_GAMESERVER_TYPE->Value : 1;
		$type = 1;
		$password = 0;
		if (property_exists($data, 'Password'))
		{
			$password = $data->Settings->Password->Value == "" ? 0 : 1;
		}

		if ($debug) `echo GOT HERE 2 >> /tmp/editserver.log`;
		$time = time();

		$stmt = $database->prepare("DELETE FROM mom_servers WHERE serverid='$servername'");
		$stmt->execute();
		$active = $sessionid > 0 ? 1 : 0;

		if ($debug) `echo GOT HERE 3 >> /tmp/editserver.log`;
		$sql = "INSERT OR REPLACE INTO mom_servers VALUES("
			. "$sessString, '" . $data->NumPublicConnections . "', '" . $data->NumPrivateConnections . "', $shouldadv, $allowjoin, $islan, $isded, "
			. "$usestats, $allowinv, $usepres, $allowpresjoin, $allowjoinpresfr, $anticheat,"
			. "'" . $data->BuildUniqueId . "', '" . RemoveTicks($data->OwningUserName) . "', '" . $addr . "', "
			. $port . "," . $beacon . ", '" . $data->Settings->MapName->Value . "',"
			. "'" . $data->Settings->MARS_SERVERID->Value . "',"
			. "'" . $data->Settings->LIMBIC_TARGET_PLATFORMS->Value . "', '" . $data->Settings->MARS_AUDIENCE->Value . "',"
			. "'" . $data->Settings->MARS_GAMESERVER_MODE->Value . "', " . $type . ", "
			. $password . ", '$time', $active)";

		if ($debug) file_put_contents("/tmp/sql.log", $sql);
		$stmt = $database->prepare($sql);
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

	function RemoveTicks($input)
	{
		return str_replace("'", '', $input);
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

	function SessionMaintenance()
	{
		global $debug;
		global $database;
		global $keepalive_seconds;

		// Check session timestamps and deactivate servers who have not checked in since time() - $keepalive_seconds
		$stmt = $database->prepare("SELECT sessionid, timestamp FROM mom_servers");
		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/gettimestamps_error", $e->getMessage());
		}

		$todelete = array();

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$old = $data['timestamp'];
			$sess = $data['sessionid'];
			$time = time();
			if ($old < ($time - $keepalive_seconds))
			{
				array_push($todel, $sessionid);
			}
		}

		$delete = implode(',', $todel);
		$stmt = $database->prepare("UPDATE mom_servers SET active=0 WHERE sessionid IN ($delete)");
		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/setinactive_error", $e->getMessage());
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

	function GetAllServersCrap()
	{
		global $debug;
		global $database;
		$output = array();

		$stmt = $database->prepare("SELECT * FROM mom_servers WHERE active=1");
		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/getall_error", $e->getMessage());
		}

		$output['Sessions'] = [];
//		while ($data = $stmt->fetch(PDO::FETCH_ASSOC))
//		{
//			$serverid = $data['serverid'];
//			$sessionid = $data['sessionid'];
//			if ($serverid == "") continue;
//			$output['Sessions'][$sessionid] = array(
//				"SessionId"  => $data['sessionid'],
//				"UserId"     => $data['owner'],
//				"IsLAN"      => $data['islan'],
//				"IsPresence" => $data['usepres']
//			);
//		}
		return $output;
	}

	function GetAllServers()
	{
		global $debug;
		global $database;
		$output = array();

		$stmt = $database->prepare("SELECT * FROM mom_servers WHERE active=1");
		try
		{
			$stmt->execute();
		}
		catch (PDOException $e)
		{
			if ($debug) file_put_contents("/tmp/getall_error", $e->getMessage());
		}

		//$servers = $stmt->fetchAll();//PDO::FETCH_ASSOC);

		/*"SessionId": "1792890477",
            "AllowInvites": true,
            "AllowJoinInProgress": true,
            "AllowJoinViaPresence": true,
            "AllowJoinViaPresenceFriendsOnly": false,
            "AntiCheatProtected": false,
            "IsDedicated": true,
            "IsLANMatch": false,
            "ShouldAdvertise": true,
            "BuildUniqueId": "114912",
            "UsesPresence": false,
            "UsesStats": false,
            "NumPrivateConnections": 0,
            "NumPublicConnections": 10,
            "NumOpenPublicConnections": 10,
            "Settings": {
                "BeaconPort": {
                    "Type": "Int32",
                    "Value": "29820"
                },
                "LIMBIC_TARGET_PLATFORMS": {
                    "Type": "String",
                    "Value": "steam"
                },
                "MARS_AUDIENCE": {
                    "Type": "String",
                    "Value": "MoM"
                },
                "MARS_GAMESERVER_MODE": {
                    "Type": "String",
                    "Value": "PVE"
                },
                "MARS_GAMESERVER_TYPE": {
                    "Type": "Bool",
                    "Value": true
                },
                "MARS_SERVERID": {
                    "Type": "String",
                    "Value": "GPortal_USEA1_STEAM_1281973"
                },
                "MapName": {
                    "Type": "String",
                    "Value": "Untitled_0"
                },
                "Password": {
                    "Type": "Bool",
                    "Value": true
                },
                "Region": {
                    "Type": "String",
                    "Value": "USEA1"
                },
                "SessionID": {
                    "Type": "Int32",
                    "Value": "1792890477"
                }
            },
            "OwningUserName": "MoM CircleDCowboy",
            "IpAddress": "176.57.143.77",
            "Port": 29800,
            "OfficialServer": false,
            "PublicIpAddress": "176.57.143.77"
        }
		*/
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$serverid = $data['serverid'];
			if ($serverid == "") continue;
			$output[$serverid] = array(
				"SessionId" => $data['sessionid'],
				"NumPublicConnections" => $data['numpub'],
				"NumPrivateConnections" => $data['numpriv'],
				"ShouldAdvertise" => $data['shouldadv'] == 1 ? true : false,
				"AllowJoinInProgress" => $data['allowjoin'] == 1 ? true : false,
				"IsLANMatch" => $data['islan'] == 1 ? true : false,
				"IsDedicated" => $data['isded'] == 1 ? true : false,
				"UsesStats" => $data['usestats'] == 1 ? true : false,
				"AllowInvites" => $data['allowinv'] == 1 ? true : false,
				"UsesPresence" => $data['usepres'] == 1 ? true : false,
				"AllowJoinViaPresence" => $data['allowpresjoin'] == 1 ? true : false,
				"AllowJoinViaPresenceFriendsOnly" => $data['allowjoinpresfr'] == 1 ? true : false,
				"AntiCheatProtected" => $data['anticheat'] == 1 ? true : false,
				"BuildUniqueId" => $data['build'],
				"OwningUserName" => $data['owner'],
				"IpAddress" => $data['ipaddress'],
				"Port" => $data['port'],
				"Settings" => array(
					"BeaconPort" => array(
						"Type"  => "Int32",
						"Value" => $data['beacon']
					),
					"MapName" => array(
						"Type"  => "String",
						"Value" => "Untitled_0"
					),
					"MARS_SERVERID" => array(
						"Type" => "String",
						"Value" => $data['serverid']
					),
					"LIMBIC_TARGET_PLATFORMS" => array(
						"Type"  => "String",
						"Value" => $data['platform']
					),
					"MARS_AUDIENCE" => array(
						"Type" => "String",
						"Value" => "MoM"
					),
					"MARS_GAMESERVER_MODE" => array(
						"Type" => "String",
						"Value" => $data['mode'],
					),
					"MARS_GAMESERVER_TYPE" => array(
						"Type" => "Bool",
						"Value" => $data['gametype'] == 1 ? true : false
					),
					"Password" => array(
						"Type" => "Bool",
						"Value" => $data['password'] == 1 ? true : false
					)
				)
			);
		}
		return $output;
	}

