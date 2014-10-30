<?php
/**
 * Reset an authors's password.
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


// 403?
if (!isset($_SESSION['username'])) {
    print403();
    die();
}
// logged in

// author ID submitted?
//  check POST first, if empty, try GET
if (!empty($_POST['authorId'])) {
    $authorId = intval($_POST['authorId']);
} else {
    if (!empty($_GET['aid']))
        $authorId = intval($_GET['aid']);
    else
        $authorId = null;
}

// only Admins can reset all passwords, Mods can reset all non-admin passwords
// check second part on database level (see function updateAuthor())
if ($_SESSION['accessLevel'] > 2) {
    printAccessViolation();
    die;
}


printHeaderAdm(true, _('authors'));

// database (with admin privileges)
$db = dbConnect(true);

// calculate new (random) password and safe its hash
$password = createRndPw();
if (updateAuthor($db, $authorId, 'pwreset', $password)) {
    printf("    <h4>%s</h4>\n", _('pwreset_success'));
    printf("    <p>%s</p>\n", str_replace('%password%', $password, _('pwreset_pwhash')));
} else {
    printf("    <h4>%s</h4>\n", _('pwreset_failed'));
    printf("    <p>%s</p>\n", _('db_error_on_write'));
}


$db = null;
printFooterAdm();

?>
