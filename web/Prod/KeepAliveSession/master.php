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

	if ($_SERVER['REQUEST_METHOD'] === 'PATCH')
	{
		if(!@str_starts_with($_SERVER['HTTP_USER_AGENT'], "Game/1.1.8.114912")) exit;

		include(dirname(__DIR__) . "/config/mars.inc.php");
		if ($debug) `echo "KeepAliveSession received patch request" >> /tmp/keepalivesession`;

		$sessionId = $_SERVER['REQUEST_URI'];
		$sessionId = str_replace("/Prod/KeepAliveSession/", "", $sessionId);
		if ($debug) `echo "Got sessionid $sessionId from URI" >> /tmp/keepalivesession`;

		$json;
		if ($sessionId != "")
		{
			$ipaddr = $_SERVER['REMOTE_ADDR'];
			if ($debug) `echo "KeepAliveSession for $ipaddr, $sessionId" >> /tmp/keepalivesession`;
			KeepAliveSession($sessionId, $ipaddr);
			$json = GetSessionData($sessionId, $ipaddr);
			if ($debug)
			{
				`echo "JSON Returning for $ipaddr, $sessionId" >> /tmp/keepalivesession`;
				file_put_contents("/tmp/keepalivesession.log", $json);
			}
		}

		if ($json != "")
		{
			header('Content-Type: application/json; charset=utf-8');
			header("Content-Length: " . strlen($json));
			echo $json;
		}
	}
?>
