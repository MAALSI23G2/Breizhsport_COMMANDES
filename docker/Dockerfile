# Utiliser l'image PHP avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires pour Symfony et Doctrine
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    mariadb-client \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql

# Activer les modules Apache nécessaires
RUN a2enmod rewrite

# Copier le code de l'application dans le conteneur
COPY . /var/www/html/

# Définir les droits d'accès au dossier pour Apache
RUN chown -R www-data:www-data /var/www/html

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances Symfony
RUN if [ -f "composer.json" ]; then composer install --no-interaction --prefer-dist; fi

# Appliquer les optimisations de Composer
RUN if [ -f "composer.json" ]; then composer dump-autoload --optimize; fi

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]
