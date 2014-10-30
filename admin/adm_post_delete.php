<?php
/**
 * Delete a post.
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

printHeaderAdm(true, _('post'));

// action==new or postId submitted or none?
if (empty($_GET['postId']) or !is_numeric($_GET['postId'])) {
        echo "    <h4>No Data.</h4>\n";
} else {
    // database (with admin privileges)
    $db = dbConnect(true);

    // check access violation
    // authors (accesslevel 3) must not delete
    if ($_SESSION['accessLevel'] > 2) {
        printAccessViolation(true);
        die();
    }

    // SQL statement
    try {
        $query = (
            "DELETE"
         . " FROM FemtoPost"
         . " WHERE postid = :postid");
        $param = array(':postid' => $_GET['postId']);
        $stmt = $db->prepare($query);
        $stmt->execute($param);

        if ($stmt->rowCount() > 0)
            echo "    <h4>" . _('post_deleted') . "</h4>\n";
        else
            echo "    <h4>" . _('post_not_deleted') . "</h4>\n";

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


$db = null;
printFooterAdm();

?>
