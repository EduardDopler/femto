<?php
/**
 * Update the post.
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

// data submitted?
if (empty($_POST['postId']) or !is_numeric($_POST['postId'])) {
    echo "    <h4>No data.</h4>\n";
} else {
    // database (with admin privileges)
    $db = dbConnect(true);

    // check access violation
    if (!checkAccessAllowed($db, 'post', $_POST['postId'])) {
        printAccessViolation(true);
        die();
    }
    // SQL statement
    try {
        $query = (
            "UPDATE FemtoPost"
         . " SET"
         . "   visibility = :visibility,"
         . "   created = :created,"
         . "   title = :title,"
         . "   urltitle = :urltitle,"
         . "   content = :content,"
         . "   authorid = :authorid,"
         . "   lang = :lang,"
         . "   langreference = :langreference,"
         . "   modified = :modified,"
         . "   comvisibility = :comvisibility,"
         . "   comcount = :comcount"
         . " WHERE postid = :postid");
        // insert params and replace empty strings with NULL so the database will do its constraints
        $param = array(
            ':visibility' => (empty($_POST['visibility'])) ? null : $_POST['visibility'],
            ':created' => (empty($_POST['created'])) ? null : $_POST['created'],
            ':title' => (empty($_POST['title'])) ? null : $_POST['title'],
            ':urltitle' => (empty($_POST['urlTitle'])) ? null : $_POST['urlTitle'],
            ':content' => (empty($_POST['content'])) ? null : $_POST['content'],
            ':authorid' => (empty($_POST['authorId'])) ? $_SESSION['authorId'] : $_POST['authorId'],
            ':lang' => (empty($_POST['lang'])) ? null : $_POST['lang'],
            ':langreference' => (empty($_POST['langReference'])) ? null : $_POST['langReference'],
            ':modified' => (empty($_POST['modified'])) ? null : $_POST['modified'],
            ':comvisibility' => (empty($_POST['comVisibility'])) ? null : $_POST['comVisibility'],
            ':comcount' => (empty($_POST['comCount'])) ? 0 : $_POST['comCount'],
            ':postid' => $_POST['postId']);
        // authors can only set their own author ID
        if ($_SESSION['accessLevel'] > 2)
            $param = array_replace($param, array(':authorid' => $_SESSION['authorId']));

        $stmt = $db->prepare($query);
        $stmt->execute($param);
        if ($stmt->rowCount() > 0) {
            echo "    <h4>" . _('post_updated') . "</h4>\n";
            // show hint for updating feed/sitemap.xml
            printf('    <p class="hint"><a href="adm_update_feedsitemap.php" title="%s">%s</a>' . "\n",
                _('update_feed_sitemap_desc'), _('update_feed_sitemap'));
        } else {
            echo "    <h4>" . _('post_not_updated') . "</h4>\n";
            echo "    <p>" . _('db_error_on_write') . "</p>\n";
        }

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


$db = null;
printFooterAdm();

?>
