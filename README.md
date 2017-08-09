
<p align="center"><img src="https://arliden.com/images/kilvin-icon-small.png"></p>


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

 - Insure you have a server meeting the above requirements. [Laravel Homestead](https://laravel.com/docs/5.4/homestead) is a superb development environment for Kilvin CMS.
 - Clone this GitHub repo onto your server.
 - Configure your web server's document / web root to be the ./public directory.
 - Run the following [Composer](https://getcomposer.org) command to install Kilvin's code dependencies: `composer install --no-interaction --prefer-dist --optimize-autoloader`.
 - Until I make this public and can make this a Composer package, run these commands:
   - `cp .env.example .env`
   - `php cms/artisan key:generate`
 - Permissions. Insure that the following files and directories are writeable on your server. Homestead is set up to allow this automatically:
   - .env
   - cms/config/cms.php
   - cms/storage
   - cms/templates
   - public/images
 - Create a database for your new site in MySQL
 - Direct your browser to the install.php file on your new site. Example: http://mysite.com/install.php


## Multiple Sites

 - Weblogs, Fields, Categories, Statuses, Member Groups, Members, and most preferences are CMS-specific
 - Templates, Pages, and Stats are Site-specific
 - Member Groups can have access to certain site's in the CP, allowing them to access only those sites' Templates and Pages
 - Weblog access is done on the group level.


## Kilvin CMS Sponsors

We would like to extend our thanks to the following sponsors for helping fund Kilvin CMS development. If you are interested in becoming a sponsor, please visit the Kilvin CMS [Patreon page](http://patreon.com/reedmaniac):

- **[Paul Burdick](https://paulburdick.me)** - The laziest man on the planet.



## Security Vulnerabilities

If you discover a security vulnerability within Kilvin CMS, please send an e-mail to Paul Burdick at paul@reedmaniac.com. All security vulnerabilities will be promptly addressed.

## License

The Kilvin CMS is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

