# Append contents to apache virtual host config in apache directory -> for example: sitename.conf. 
# Replace /path/to/repository/root and ServerName/ServerAlias/ServerAdmin appropriately.
# Then:
# 1. sudo a2ensite sitename
# 2. service apache2 reload

<VirtualHost *:80>
ServerName master.openra.net
ServerAlias master.openra.net
ServerAdmin webmaster@master.openra.net
DocumentRoot /path/to/repository/root/

<Directory /path/to/repository/root/>
Options Indexes FollowSymLinks MultiViews
AllowOverride FileInfo
Order allow,deny
allow from all
</Directory>

 <FilesMatch \.php$>
        SetHandler "proxy:fcgi://127.0.0.1:9000"
 </FilesMatch>


LogLevel warn
ErrorLog ${APACHE_LOG_DIR}/error.log
CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
