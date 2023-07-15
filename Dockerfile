# Use an official PHP runtime as the base image
FROM php:latest

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the PHP application code into the container
COPY . /var/www/html

# Install any dependencies required by your PHP application
RUN docker-php-ext-install vlucas/phpdotenv

# Expose the PHP application port
EXPOSE 80

# Start the PHP development server
CMD ["php", "-S", "0.0.0.0:80"]
