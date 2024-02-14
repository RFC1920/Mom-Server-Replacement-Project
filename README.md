# MemoriesOfMars
Scripts and other things for this game

This should be considered a work in progress and partially working or garbage depending on your perspective.

The goal is to replace the missing server that went down on 13 February, 2024.

This server, or beacon host, is required for server registration, and for clients to find servers, etc.

## Prod
  This folder contains most or all of what is required to answer requests from servers and clients

  - CreateSession -- This answers server requests and returns a session id sufficient for the server to start. (WORKING)
  - GetAllSessions -- For the client to receive an updated server list.
  - KeepAliveSession/0 -- Both server and client query this for server status updates.

## Server Setup

This is not a simple setup for everyone.

  - Add "agclxre5zl.execute-api.eu-central-1.amazonaws.com" to the hosts file on a machine where your server and client will be run.
  - Both server and client make requests to a web server, so this should point to a machine running Apache, NGINX, et al.
  - Generate a cert with the above hostname that the server machine will trust.  In my case, I was able to create a cert using my internal Windows Cert Authority, but any CA the machine trusts appears to work.
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
    </Directory>
    <Location />
        SSLRequireSSL
    </Location>
	<FilesMatch ^>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
```

  - Note that I am suggesting that you use php-fpm to run these scripts.  The "FilesMatch ^" config is part of what's need to serve php in non .php files
  - Configure php-fpm using the file targeting your server and socket file, e.g. "/etc/php-fpm.d/www.conf".  Add the following line at the bottom of the file and restart php-fpm:

```
security.limit_extensions =
```

  - Unpack the contents of the Prod directory from this repo into the root of your web server.
  - For the above configuration, this would end up as /var/www/mars/Prod.

... more to come

