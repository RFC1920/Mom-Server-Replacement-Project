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

	// Config
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

	include(dirname(__DIR__) . "/config/functions.php");

