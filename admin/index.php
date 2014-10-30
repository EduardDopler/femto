<?php
/**
 * Administration login page.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */


session_start();
require '../functions.php';
require 'adm_functions.php';

// initialize localization
initLocale();


// if logged in, show Hello
if (isset($_SESSION['longname'])) {
    printHello($_SESSION['longname'], $_SESSION['lastLogin']);

// login request?
} elseif (isset($_POST['username'], $_POST['password'])) {
    // database (with admin privileges)
    $db = dbConnect(true);

    $postUser = strtolower($_POST['username']);
    $postPass = $_POST['password'];
    if ($userData = checkLogin($db, $postUser, $postPass)) {
        // login successful
        // update last login field for this user
        updateAuthor($db, $userData['authorId'], 'lastlogin');
        // rehash password (as maybe a security issue is fixed meanwhile)
        updateAuthor($db, $userData['authorId'], 'rehash', $postPass);
        // append user data to session cookie
        $_SESSION = $_SESSION + $userData;
        // show Hello
        printHello($_SESSION['longname'], $_SESSION['lastLogin']);
        // show version and update check
        printUpdateCheck();

    } else {
        // login failed
        printHeaderAdm(false, 'Administration');
        
        printf("    <h4>%s</h4>\n",
            _('login_failed'));
        printf("    <p>%s</p>\n", _('login_failed_error'));
    }

} else {
    // not logged in, no request, so show login box
    printHeaderAdm(false, 'Administration');
    printFormLogin();
}

// print footer in any case
printFooterAdm();

?>