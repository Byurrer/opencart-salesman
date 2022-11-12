FROM ubuntu/apache2:latest

COPY apache2.conf /etc/apache2/apache2.conf
COPY host.conf /etc/apache2/sites-available/000-default.conf

RUN apt update && apt install -y libapache2-mod-fcgid nano mc \
    && mkdir -p /var/www/html/ && chown www-data:www-data /var/www/html/

EXPOSE 80

CMD /bin/bash -c "a2enmod proxy && a2enmod proxy_fcgi && a2enmod rewrite && apachectl configtest && service apache2 stop && apachectl -D FOREGROUND"
