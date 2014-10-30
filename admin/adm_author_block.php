<?php
/**
 * Block/unblock an author.
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
$authorId = (!empty($_GET['aid'])) ? intval($_GET['aid']) : null;
// action submitted?
$action = (!empty($_GET['action'])) ? $_GET['action'] : null;
$action = ($action === 'block') ? 'true' : 'false';

// only Admins/Mods can block users, but no Admins and themselves
// check second part ("no admins") on database level (see function updateAuthor())
if ($_SESSION['accessLevel'] > 2 and
    $_SESSION['authorId'] === $authorId) {
    printAccessViolation();
    die;
}


printHeaderAdm(true, _('authors'));

// database (with admin privileges)
$db = dbConnect(true);

// block user
if (updateAuthor($db, $authorId, 'block', $action)) {
    printf("    <h4>%s</h4>\n", _('blockauthor_success'));
    printf("    <p>%s</p>\n", _('blockauthor_desc'));
} else {
    printf("    <h4>%s</h4>\n", _('blockauthor_failed'));
    printf("    <p>%s</p>\n", _('db_error_on_write'));
}


$db = null;
printFooterAdm();

?>
