FROM php:apache
MAINTAINER David A. Lareo "dalareo@gmail.com"

RUN apt-get update -y \
	&& apt-get install -y git libmcrypt-dev libldap2-dev \
	&& docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \

	&& docker-php-ext-install mcrypt ldap

RUN git clone https://github.com/simplesamlphp/simplesamlphp.git /var/simplesamlphp

RUN rm -rf /var/simplesamlphp
RUN git clone https://github.com/simplesamlphp/simplesamlphp.git /var/simplesamlphp

RUN cp -r /var/simplesamlphp/config-templates/* /var/simplesamlphp/config/
RUN cp -r /var/simplesamlphp/metadata-templates/* /var/simplesamlphp/metadata/

RUN a2enmod ssl
RUN mkdir -p /etc/apache2/ssl
ADD ./etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
ADD ./etc/apache2/ssl /etc/apache2/ssl

WORKDIR /var/simplesamlphp
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install

EXPOSE 443
