<?php
/**
 * Private settings.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */

/* ===== VARS/SETTINGS ===== */

// show errors? Turn off in production mode.
//  see also error_reporting around line 73
define('DEBUG', false);
// database (mysql or pgsql)
define('DBDRIVER', 'mysql');
define('DBHOST', 'localhost');
define('DBUSER', 'femto');
define('DBPASS', 'your_database_password');
define('DBUSER_ADM', 'femto_adm');
define('DBPASS_ADM', 'another_database_password');
define('DBNAME', 'femto');
define('DB_PGSQL_USE_SOCKET', true);
// host information
//  mind http://bit.ly/1pzsyOx for $_SERVER exploitation
define('PROTOCOL', getProtocol());
define('HOST', PROTOCOL . '://' . $_SERVER['SERVER_NAME']);
// version information
define('FEMTO_VER', '0.1');
define('CSS_VER', '0.1');
define('JS_VER', '0.1');
// blog information
define('BLOG_TITLE', 'Your femto');
define('BLOG_SUBTITLE', '#femto #blog #system');
define('BLOG_DESCRIPTION', 'This is my femto blog. I like blogging.');
// admin data
define('ADMIN_NAME', 'Your Name');
define('ADMIN_EMAIL', 'your_email_address');
// locale information
define('LANG_DEFAULT', 'en');
define('LANG_DEFAULT_LOCALE', 'en_US.UTF-8');
define('LANG_2', 'de');
define('LANG_2_LOCALE', 'de_DE.UTF-8');
// single language blog?
define('SINGLE_LANGUAGE', false);
// navigation links
define('NAV_LINK_1', 'link');
define('NAV_LINK_1_NAME', 'link');
define('NAV_LINK_2', 'downloads');
define('NAV_LINK_2_NAME', 'downloads');
// show flattr button below posts?
define('FLATTR', true);
define('FLATTR_ID', 'your_flattr_id');
// show links to latest posts below posts? how many?
define('LINK_LATEST_POSTS', 4);
// enable Piwik?
define('PIWIK', true);
define('PIWIK_PATH', '/stats/');
// search engine
define('SEARCH_ENGINE', 'DuckDuckGo');
// feed
define('FEED_MAIN_AUTHOR', 'Your Name');
// sitemap
define('SITEMAP_POST_PRIORITY', '0.9');
// admin settings
//  minimum length for passwords
define('MIN_PW_LENGTH', 6);
//  default number of posts for feed creation
define('DEFAULT_NUM_FEED_POSTS', 10);
// update server URL
define('UPDATE_SERVER_FILE', 'https://eduard-dopler.de/femto_version.php?use=' . FEMTO_VER);

// disable error reporting for security reasons
error_reporting(0);


/**
 * Determine Protocol (HTTP or HTTPS) used.
 * @return string "http" or "https" depending on protocol used.
 */
function getProtocol() {
    return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
}

?>
