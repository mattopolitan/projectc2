FROM php:7.0.30-apache 
RUN docker-php-ext-install mysqli

COPY ./my-httpd.conf /usr/local/apache2/conf/httpd.conf
#ADD php.ini /usr/local/etc/php

RUN a2enmod rewrite
