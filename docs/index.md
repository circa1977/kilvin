
# Kilvin CMS

## About Kilvin

Kilvin CMS is a content management system built on top of the [Laravel framework](https://laravel.com). More details will be forthcoming (like documentation!) just as soon as I finish the darn thing.


## Installing Kilvin CMS

### Server Requirements
 - PHP 7.0 or later with safe mode disabled
 - MySQL 5.5.0 or later, with the InnoDB storage engine installed
 - A web server (Apache, Nginx, IIS)
 - OpenSSL PHP Extension
 - PDO PHP Extension
 - Mbstring PHP Extension
 - Tokenizer PHP Extension

### Installation

 - Insure you have a server meeting the above requirements. Set up your new site on the server. [Laravel Homestead](https://laravel.com/docs/5.4/homestead) is a superb development environment for Kilvin CMS.
 - Pull down the GitHub files into your site's directory on the server. The 'public/' directory is the root of your public site.
 - Permissions. Insure that the following files and directories are writeable on your server:
   - .env
   - cms/config/cms.php
   - cms/storage
   - cms/templates
   - public/images
 - Create a database for your new site in MySQL
 - Run the following [Composer](https://getcomposer.org) command from within the 'cms' folder: `composer install --no-interaction --prefer-dist --optimize-autoloader`.  It installs all the code dependencies that Kilvin CMS requires.
 - Direct your browser to the install.php file on your new site. Example: http://mysite.com/install.php


## Kilvin CMS Sponsors

We would like to extend our thanks to the following sponsors for helping fund Kilvin CMS development. If you are interested in becoming a sponsor, please visit the Kilvin CMS [Patreon page](http://patreon.com/reedmaniac):

- **[Paul Burdick](https://paulburdick.me)** - The laziest man on the planet.



## Security Vulnerabilities

If you discover a security vulnerability within Kilvin CMS, please send an e-mail to Paul Burdick at paul@reedmaniac.com. All security vulnerabilities will be promptly addressed.

## License

The Kilvin CMS is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

