<?php
	class MapName
	{
		public $Type; //String
		public $Value; //String
	}

	class MARS_SERVERID
	{
		public $Type; //String
		public $Value; //String
	}

	class LIMBIC_TARGET_PLATFORMS
	{
		public $Type; //String
		public $Value; //String
	}

	class MARS_AUDIENCE
	{
		public $Type; //String
		public $Value; //String
	}

	class MARS_GAMESERVER_MODE
	{
		public $Type; //String
		public $Value; //String
	}

	class MARS_GAMESERVER_TYPE
	{
		public $Type; //String
		public $Value; //boolean
	}

	class Password
	{
		public $Type; //String
		public $Value; //boolean
	}

	class Settings
	{
		public $MapName; //MapName
		public $MARS_SERVERID; //MARS_SERVERID
		public $LIMBIC_TARGET_PLATFORMS; //LIMBIC_TARGET_PLATFORMS
		public $MARS_AUDIENCE; //MARS_AUDIENCE
		public $MARS_GAMESERVER_MODE; //MARS_GAMESERVER_MODE
		public $MARS_GAMESERVER_TYPE; //MARS_GAMESERVER_TYPE
		public $Password; //Password
	}

	class Application
	{
		public $SessionId; //String
		public $NumPublicConnections; //int
		public $NumPrivateConnections; //int
		public $ShouldAdvertise; //boolean
		public $AllowJoinInProgress; //boolean
		public $IsLANMatch; //boolean
		public $IsDedicated; //boolean
		public $UsesStats; //boolean
		public $AllowInvites; //boolean
		public $UsesPresence; //boolean
		public $AllowJoinViaPresence; //boolean
		public $AllowJoinViaPresenceFriendsOnly; //boolean
		public $AntiCheatProtected; //boolean
		public $BuildUniqueId; //String
		public $OwningUserName; //String
		public $IpAddress; //String
		public $Port; //int
		public $Settings; //Settings
	}

	//class Sessions
	//{
	//	public $SessionIds; // array
	//}
