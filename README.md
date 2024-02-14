# MemoriesOfMars
Scripts and other things for this game

This should be considered a work in progress and partially working or garbage depending on your perspective.

The goal is to replace the missing server that went down on 13 February, 2024.

This server, or beacon host, is required for server registration, and for clients to find servers, etc.

## Prod
  This folder contains most or all of what is required to answer requests from servers and clients

  - CreateSession -- This answers server requests and returns a session id sufficient for the server to start. (WORKING)
  - GetAllSessions -- For the client to receive an updated server list.
  - KeepAliveSession -- Both server and client query this for server status updates.

## Server Setup

This is not a simple setup for everyone.

  - Add "agclxre5zl.execute-api.eu-central-1.amazonaws.com" to the hosts file on a machine where your server and client will be run.
  - Both server and client make requests to a web server, so this should point to a machine running Apache, NGINX, et al.
  - Configure Apache similar to the following:

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
		#Options FollowSymLinks -MultiViews ExecCGI
    </Directory>
    <Location />
        SSLRequireSSL
    </Location>
	<FilesMatch ^>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
```

  - Unpack the contents of the Prod directory from this repo into the root of your web server.
  - For the above configuration, this would end up as /var/www/mars/Prod.

... more to come

