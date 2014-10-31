femto
=====

femto blog system

femto lives up to its name: Being a minimalistic system, it satisfies all my requirements for a blog system (and maybe yours as well): **simple**, **speedy** and **secure**.
femto is no overkill software. It tries to do one thing well, without bugging you. Nevertheless/Therefore the feature list tells its own tale:

### Features
* multilingual,
* comments function,
* simple, modern and responsive design,
* high-level security standards,
* convenient administration,
* multi user and permissions administration,
* ATOM Feed and sitemap.xml generation,
* clean code and
* of course, Open Source and Free Software.

### Install
See the [INSTALL](https://github.com/EduardDopler/femto/blob/master/INSTALL) file. (There will be an installation wizard soon. Stay tuned.)

##### Requirements
* HTTP Server with URL rewriting
   * Apache2 with mod_rewrite (.htaccess included), recommended: mod_expires, mod_deflate
   * *or* nginx with http_rewrite_module (config files included), recommended: http_headers_module
* PHP >= 5.3.7, recommended: 5.5.0
   * with PDO
   * with locale (Gettext) support
* Database
   * MySQL >= 5.0.2
   * *or* PostgreSQL >= 9.0
   * with support for Views, Triggers/Functions, recommended: Create User and Grant Privileges

### More?
You can find a short description on the author’s blog page: https://eduard-dopler.de/en/femto/
