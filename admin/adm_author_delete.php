<?php
/**
 * Delete Author.
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

// only Admins can delete users
if ($_SESSION['accessLevel'] !== 1) {
    printAccessViolation();
    die;
}

printHeaderAdm(true, _('authors'));

// admins cannot delete themselves
if ($_SESSION['authorId'] === $authorId) {
    printf("    <h4>%s</h4>\n", _('deleteauthor_not_yourself'));
    printFooterAdm();
    die();
}


// database (with admin privileges)
$db = dbConnect(true);

// SQL statement
try {
    // delete user
    $query = (
        "DELETE FROM FemtoAuthor"
     . " WHERE authorid = :authorid");
    $param = array(':authorid' => $authorId);
    $stmt = $db->prepare($query);
    $stmt->execute($param);

    if ($stmt->rowCount() > 0) {
        printf("    <h4>%s</h4>\n", _('author_deleted'));

    } else {
        // error
        printf("    <h4>%s</h4>\n", _('author_not_deleted'));
        printf("    <p>%s</p>\n", _('db_error_on_write'));
    }

} catch (PDOException $e) {
    switch ($e->getCode()) {
        // Foreign Key violation?
        case '23000':
        case '23505':
            printf("<h4>%s</h4>\n", _('delauthor_failed_has_posts'));
            break;
        // anything else
        default:
            dbError(__FUNCTION__, $e);
            break;
    }
}


$db = null;
printFooterAdm();

?>
