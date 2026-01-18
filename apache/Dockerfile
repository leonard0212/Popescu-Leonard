FROM php:8.1-apache

# Instalăm pachete de sistem și extensii PHP uzuale
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
	   libzip-dev zip unzip libpng-dev libonig-dev libicu-dev libxml2-dev \
	&& docker-php-ext-install mysqli pdo pdo_mysql zip intl mbstring \
	&& docker-php-ext-enable mysqli \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/*

# Activăm mod_rewrite pentru URL-uri prietenoase
RUN a2enmod rewrite