FROM php:8.2-apache

# Install mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

#Copy the script to run it into the dockerfile
COPY rights.sh /usr/local/bin/

# Clean Windows line endings (CRLF -> LF) to prevent crash
RUN sed -i 's/\r$//' /usr/local/bin/rights.sh

#Give admin rights to the bash script
RUN chmod +x /usr/local/bin/rights.sh

#Set it as the entrypoint of the script that composes the docker
ENTRYPOINT ["rights.sh"]