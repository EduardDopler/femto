<?php
/**
 * Update comments.
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

printHeaderAdm(true, _('comment'));

// data submitted?
if (empty($_POST['commentId'])) {
    echo "    <h4>No data.</h4>\n";
} else {
    // database
    $db = dbConnect();

    // check access violation (if one fails, all fail)
    foreach ($_POST['commentId'] as $commentId) {
        if (!checkAccessAllowed($db, 'comment', $commentId)) {
            printAccessViolation(true);
            die();
        }
    }
    
    // SQL statement
    try {
         $query = (
            "UPDATE FemtoComment"
         . " SET"
         . "   postid = :postid,"
         . "   created = :created,"
         . "   longname = :longname,"
         . "   email = :email,"
         . "   url = :url,"
         . "   content = :content,"
         . "   lang = :lang,"
         . "   approved = :approved,"
         . "   ip = :ip"
         . " WHERE commentid = :commentid");

        // execute on every comment (escape content)
        $numChanged = 0;
        for ($i=0; $i < count($_POST['commentId']); $i++) {
            $param = array(
                ':postid' => $_POST['postId'][$i],
                ':created' => $_POST['created'][$i],
                ':longname' => $_POST['longname'][$i],
                ':email' => $_POST['email'][$i],
                ':url' => $_POST['url'][$i],
                ':content' => escapeComment($_POST['content'][$i]),
                ':lang' => $_POST['lang'][$i],
                ':approved' => $_POST['approved'][$i],
                ':ip' => $_POST['ip'][$i],
                ':commentid' => $_POST['commentId'][$i]);
            $stmt = $db->prepare($query);
            $stmt->execute($param);
            $numChanged += $stmt->rowCount();
        }

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
    $result = sprintf("Action EDIT successfully performed on %d/%d comments.",
        $numChanged, count($_POST['commentId']));
    echo "    <h4>$result</h4>\n";
}


$db = null;
printFooterAdm();

?>
