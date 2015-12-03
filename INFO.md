
** nginx virtual host example using php5-fpm (with proper rewrite rules)

```
server {
        listen 80;
        server_name master.openra.net;
        root /path/to/repository/root/;
        autoindex off;

        rewrite_log on;
        index index.php;

        location / {
                try_files $uri $uri/ =404;
                if (!-f $request_filename) {
                        rewrite ^/(.*)$ /$1.php last;
                }
        }
        location ~ \.php$ {
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                fastcgi_pass unix:/var/run/php5-fpm.sock;
                fastcgi_index index.php;
                include fastcgi.conf;
        }

        error_log /path/to/nginx-error.log;
        access_log /path/to/nginx-access.log;
}
```


```
Place repository under unprivileged user. Change `group` of files (using chown) to one which is used by nginx
```
