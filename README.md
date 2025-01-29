#  MoM Server Replacement Project

Scripts and other things for the game Memories of Mars by Limbic Entertainment and 505 games.

This should be considered a work in progress and partially working or garbage depending on your perspective.

The goal is to replace the missing server that went down on 13 February, 2024.
It might also be usable to replace the function of these servers when the game goes offline 25 June, 2024.

This server, or beacon host, is required for server registration, and for clients to find servers, etc.

## Prod
  This folder contains most or all of what is required to answer requests from servers and clients

  - CreateSession -- This answers server requests and returns a session id sufficient for the server to start. (WORKING)
  - Login
  - GetAllSessions -- For the client to receive an updated server list. (CURRENTLY CRASHES GAME)
  - KeepAliveSession/0 -- Both server and client query this for server status updates. (UNCLEAR)
  - GetPlayerData?accids=0&attribs=dev -- Server queries this for unknown data
  - GetUnlockedPatterns/0 -- Server queries this for unlock blueprints.  Unclear when or how these are saved - WIP

  Potential other calls to be managed:

  - Logout
  - GenerateAuthTicket
  - ValidateAuthTicket
  - CreateAccount
  - DeleteAccount
  - GenerateEncryptionKey
  - RequestEncryptionKey
  - UpdateData

## Server Setup

This is not a simple setup for everyone.

  - Add "agclxre5zl.execute-api.eu-central-1.amazonaws.com" to the hosts file on a machine where your server and client will be run.
  - Add "l32aayf7lh.execute-api.eu-central-1.amazonaws.com" to the hosts file on a machine where your server and client will be run.
  - Add "39cb0ds2zb.execute-api.eu-central-1.amazonaws.com" to the hosts file on a machine where your server and client will be run.
  - Both server and client make requests to a web server, so this should point to a machine running Apache, NGINX, et al.
  - Generate a cert with the above hostname that the server machine will trust.  In my case, I was able to create a cert using my internal Windows Cert Authority, but any CA the machine trusts appears to work.
  - Configure Apache similar to the following with TWO hosts:

### Apache
```
<VirtualHost *:443>
    ServerName agclxre5zl.execute-api.eu-central-1.amazonaws.com
    SSLCertificateFile /etc/pki/tls/certs/agclxre5zl.execute-api.eu-central-1.amazonaws.com.cert
    SSLCertificateKeyFile /etc/pki/tls/private/agclxre5zl.execute-api.eu-central-1.amazonaws.com.key
    DocumentRoot /var/www/mars
    ErrorLog logs/mars_ssl_error_log
    TransferLog logs/mars_ssl_access_log
    <Directory "/var/www/mars">
        AllowOverride All
    </Directory>
    <Location />
        SSLRequireSSL
    </Location>
    <FilesMatch ^>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
<VirtualHost *:443>
    ServerName l32aayf7lh.execute-api.eu-central-1.amazonaws.com
    SSLCertificateFile /etc/pki/tls/certs/l32aayf7lh.execute-api.eu-central-1.amazonaws.com.cert
    SSLCertificateKeyFile /etc/pki/tls/private/l32aayf7lh.execute-api.eu-central-1.amazonaws.com.key
    DocumentRoot /var/www/mars/web
    ErrorLog logs/mars_ssl_error_log
    TransferLog logs/mars_ssl_access_log
    <Directory "/var/www/mars/web">
        AllowOverride All
    </Directory>
    <Location />
        SSLRequireSSL
    </Location>
    <FilesMatch ^>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
<VirtualHost *:443>
    ServerName 39cb0ds2zb.execute-api.eu-central-1.amazonaws.com
    SSLCertificateFile /etc/pki/tls/certs/39cb0ds2zb.execute-api.eu-central-1.amazonaws.com.cert
    SSLCertificateKeyFile /etc/pki/tls/private/39cb0ds2zb.execute-api.eu-central-1.amazonaws.com.key
    DocumentRoot /var/www/mars/web
    ErrorLog logs/mars_ssl_error_log
    TransferLog logs/mars_ssl_access_log
    <Directory "/var/www/mars/web">
        AllowOverride All
    </Directory>
    <Location />
        SSLRequireSSL
    </Location>
    <FilesMatch ^>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>

```

### NGINX

Uncomment the ssl section in the default nginx.conf and adjust as follows.  You will need a 'valid' cert and key (that you trust).
```
    server {
        listen       443 ssl http2;
        listen       [::]:443 ssl http2;
        server_name  _;
        root         /usr/share/nginx/html;

        ssl_certificate "/etc/pki/nginx/agclxre5zl.execute-api.eu-central-1.amazonaws.com.pem";
        ssl_certificate_key "/etc/pki/nginx/private/agclxre5zl.execute-api.eu-central-1.amazonaws.com.key";
        ssl_session_cache shared:SSL:1m;
        ssl_session_timeout  10m;
        ssl_ciphers PROFILE=SYSTEM;
        ssl_prefer_server_ciphers on;

        # Load configuration files for the default server block.
        include /etc/nginx/default.d/*.conf;

        error_page 404 /404.html;
        location = /404.html {
        }

        error_page 500 502 503 504 /50x.html;
        location = /50x.html {
        }

        location / {
            try_files $uri $uri/ @extensionless-php;
            root           /usr/share/nginx/html;
            fastcgi_pass   unix:/run/php-fpm/www.sock;
            #fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }

        # These two are important for ensuring we run the right scripts (no .php except for master.php.
        location /Prod/KeepAliveSession {
            rewrite ^(.*)$ master.php last;
        }

        location @extensionless-php {
            rewrite ^(.*)$ $1.php last;
        }
```

## More notes

  - Scripts require at least php version 8.X
  - Note that I am suggesting that you use php-fpm to run these scripts.  The "FilesMatch ^" config is part of what's need to serve php in non .php files
  - Configure php-fpm using the file targeting your server and socket file, e.g. "/etc/php-fpm.d/www.conf".  Add the following line at the bottom of the file and restart php-fpm:

```
security.limit_extensions =
```

  - Unpack the contents of the Prod directory from this repo into the root of your web server.
  - For the above configuration, this would end up as /var/www/mars/Prod.


## Protocol Info
  The server and clients appear to exchange json data, which is fairly simple if you know the format.

  I was able to guess (correctly?) the server response for a MoM server.

  The server sends:

```json
{
  "SessionId": "0",
  "NumPublicConnections": 2,
  "NumPrivateConnections": 0,
  "ShouldAdvertise": false,
  "AllowJoinInProgress": true,
  "IsLANMatch": false,
  "IsDedicated": true,
  "UsesStats": false,
  "AllowInvites": true,
  "UsesPresence": false,
  "AllowJoinViaPresence": true,
  "AllowJoinViaPresenceFriendsOnly": false,
  "AntiCheatProtected": false,
  "BuildUniqueId": "114912",
  "OwningUserName": "rfcmom",
  "IpAddress": "192.168.11.11",
  "Port": 7777,
  "Settings": {
    "MapName": {
      "Type": "String",
      "Value": "Untitled_0"
    },
    "MARS_SERVERID": {
      "Type": "String",
      "Value": "mom_rfc_01"
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
    "Password": {
      "Type": "Bool",
      "Value": true
    }
  }
}
```

  ...and we return, e.g.:

```json
{
  "SessionId": "3",
  "NumPublicConnections": 2,
  "NumPrivateConnections": 0,
  "ShouldAdvertise": false,
  "AllowJoinInProgress": true,
  "IsLANMatch": false,
  "IsDedicated": true,
  "UsesStats": false,
  "AllowInvites": true,
  "UsesPresence": false,
  "AllowJoinViaPresence": true,
  "AllowJoinViaPresenceFriendsOnly": false,
  "AntiCheatProtected": false,
  "BuildUniqueId": "114912",
  "OwningUserName": "rfcmom",
  "IpAddress": "192.168.11.11",
  "Port": 7777,
  "Settings": {
    "MapName": {
      "Type": "String",
      "Value": "Untitled_0"
    },
    "MARS_SERVERID": {
      "Type": "String",
      "Value": "mom_rfc_01"
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
    "Password": {
      "Type": "Bool",
      "Value": true
    }
  }
}
```

Later, the server will call UpdateSession, and we update their sessionid.
