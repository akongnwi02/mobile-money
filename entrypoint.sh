#!/usr/bin/env bash

sed -i "s/80/$PORT/g" /etc/nginx/sites-available/default.conf

/usr/bin/supervisord -n -c /var/www/supervisord.conf