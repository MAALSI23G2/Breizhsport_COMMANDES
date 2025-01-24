# Utiliser l'image PHP avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires pour Symfony, Doctrine et RabbitMQ
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    mariadb-client \
    git \
    librabbitmq-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql pcntl sockets \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Activer les modules Apache nécessaires
RUN a2enmod rewrite

# Définir les droits d'accès au dossier pour Apache
RUN chown -R www-data:www-data /var/www/html

# Configurer Git pour éviter les erreurs de "dubious ownership"
RUN git config --global --add safe.directory /var/www/html

# Copier le code de l'application dans le conteneur
COPY . /var/www/html/

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Installer les dépendances de Composer sans exécuter de scripts
RUN if [ -f "composer.json" ]; then composer install --no-interaction --prefer-dist --no-scripts; fi

RUN chown -R www-data:www-data /var/www/html/var
RUN chmod -R 775 /var/www/html/var

ENV DATABASE_URL="mysql://symfony:symfony@db:3306/symfony_breizhsport_order"
ENV RABBITMQ_URI="amqp://user:password@rabbitmq"

# Exposer le port 80
EXPOSE 80

# Démarrer Apache avec un post-install script
CMD composer run-script post-install-cmd && apache2-foreground
