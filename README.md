# Snowboard Tricks

## Requirements

You need :
- an operating system with PHP 7.4 or more, Composer and Symfony
- a database to save app data

## Intallation

1. Download the project files and open the project with your IDE.
2. Config the .env file at the root of the project. Set the adresse of the database, the akismet key and the upload directory.
3. Install the required libraries with the command : "composer install"
4. Create database table with : "php bin/console doctrine:schema:update --dump-sql --force"
5. Optionally, load the demo data by running the command : "php bin/console doctrine:fixtures:load"
6. Then, launch the application by running the following command "symfony serve".
7. Go to the URL that is given, your website is now ready.

## Upgrade

1. Download the project files and replace old files, then open the project with your IDE.
2. Check the .env file, and fill the new fields if there are any.
3. Update your database with the command : "php bin/console doctrine:migrations:migrate"
