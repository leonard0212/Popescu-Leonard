FROM php:8.1-apache

# Instalăm extensiile necesare pentru conectarea la MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Activăm mod_rewrite pentru URL-uri prietenoase (opțional, dar recomandat)
RUN a2enmod rewrite