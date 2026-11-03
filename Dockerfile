# SiColMed FrontEnd — PHP 8.3 con Apache
FROM php:8.3-apache
# Apache debe exponer únicamente Public; App permanece fuera del webroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/Public
RUN set -eux; \
    a2enmod rewrite; \
    sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# Autorizar las reglas de rutas amigables en Public/.htaccess.
RUN printf '<Directory /var/www/html/Public>\n    AllowOverride FileInfo Indexes Options\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/sicolmed-routes.conf \
    && a2enconf sicolmed-routes
# Composer genera autoload PSR-4 y admite futuras dependencias.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
# Copia App/, Public/ y el resto de archivos del proyecto.
COPY --chown=www-data:www-data . /var/www/html/
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
# El certificado de idoneidad (PDF) admite hasta 5 MB en la API; PHP trae 2M por defecto.
RUN printf 'upload_max_filesize = 6M\npost_max_size = 8M\n' > /usr/local/etc/php/conf.d/sicolmed-uploads.ini
# El directorio de uploads debe tener permisos para PHP/Apache.
RUN mkdir -p /var/www/html/Public/Assets/Uploads/Perfil \
    && chown -R www-data:www-data /var/www/html/Public/Assets/Uploads
EXPOSE 80
CMD ["apache2-foreground"]