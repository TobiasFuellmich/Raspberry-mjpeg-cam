# Raspberry-mjpeg-cam
# Installation

1. install lighttpd

- The config can look like this:

```
server.modules = (
	"mod_access",
	"mod_alias",
	"mod_compress",
	"mod_redirect",
#       "mod_rewrite",
)

server.document-root        = "/var/www/html"
server.upload-dirs          = ( "/var/cache/lighttpd/uploads" )
server.errorlog             = "/var/log/lighttpd/error.log"
server.pid-file             = "/var/run/lighttpd.pid"
server.username             = "www-data"
server.groupname            = "www-data"
server.port                 = 8080


index-file.names            = ( "index.php", "index.html", "index.lighttpd.html" )
url.access-deny             = ( "~", ".inc", ".lock" )
static-file.exclude-extensions = ( ".php", ".pl", ".fcgi" )

compress.cache-dir          = "/var/cache/lighttpd/compress/"
compress.filetype           = ( "application/javascript", "text/css", "text/html", "text/plain" )

# default listening port for IPv6 falls back to the IPv4 port
## Use ipv6 if available
#include_shell "/usr/share/lighttpd/use-ipv6.pl " + server.port
include_shell "/usr/share/lighttpd/create-mime.assign.pl"
include_shell "/usr/share/lighttpd/include-conf-enabled.pl"
```

2. install HAProxy

Only if you want the Websocket Server to be on Port 80.
if not you have to change the Websocket adress in index.php (line 36).
- The config can look like this:

```
global
    maxconn     4096 # Total Max Connections. This is dependent on ulimit
    nbproc      1

defaults
    mode        http

frontend all_http
    bind *:80
    timeout client 86400000
    default_backend www_backend
    acl is_websocket hdr(Upgrade) -i WebSocket
    acl is_websocket hdr_beg(Host) -i ws

    use_backend socket_backend if is_websocket

backend www_backend
    balance roundrobin
    option forwardfor # This sets X-Forwarded-For
    timeout server 30000
    timeout connect 4000
    server apiserver 127.0.0.1:8080 weight 1 maxconn 4 check

backend socket_backend
    balance roundrobin
    option forwardfor # This sets X-Forwarded-For
    timeout queue 5000
    timeout server 86400000
    timeout connect 86400000
    server apiserver 127.0.0.1:7216 weight 1 maxconn 4 check
```

3. install python-picamera

4. activate Picamera

```
-> sudo raspi-config
-> Interfacing Options
-> Camera
-> Yes
```
