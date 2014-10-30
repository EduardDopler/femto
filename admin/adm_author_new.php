<?php
/**
 * Add a new author.
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
// check access level
if ($_SESSION['accessLevel'] > 2) {
    printAccessViolation();
    die();
}

// database (with admin privileges)
$db = dbConnect(true);

printHeaderAdm(true, _('authors'));

if (empty($_POST['username']) or
    empty($_POST['longname']) or
    empty($_POST['accessLevel'])) {
        echo "    <h4>No or not enough Data.</h4>\n";

} else {
    // check if username is latin-chars-only
    $username = $_POST['username'];
    if (!isLatin($username)) {
        printf("    <h4>%s</h4>\n", _('latin_chars_only'));
        printFooterAdm();
        die();
    }
    // check if access level is numeric
    $accessLevel = $_POST['accessLevel'];
    if (!is_numeric($accessLevel)) {
        echo "    <h4>Invalid Access Level.</h4>\n";
        printFooterAdm();
        die();
    }
    // calculate initial (random) password hash
    $password = createRndPw();
    $passhash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 10]);
    // escape longname
    $longname = escapeField($_POST['longname']);

    // SQL statement
    try {
        // insert
        $query = (
            "INSERT INTO FemtoAuthor"
         . "   (username, passhash, longname, accesslevel)"
         . " VALUES"
         . "   (:username, :passhash, :longname, :accesslevel)");
        $param = array(
            ':username' => $username,
            ':passhash' => $passhash,
            ':longname' => $longname,
            ':accesslevel' => $accessLevel);
        $stmt = $db->prepare($query);
        $stmt->execute($param);

        if ($stmt->rowCount() > 0) {
            printf("    <h4>%s</h4>\n", _('author_created'));
            printf("    <p>%s</p>\n", str_replace('%password%', $password, _('author_created_pwhash')));
            
            // mail event to first admin
            $subject = 'New Author added at ' . BLOG_TITLE;
            $text = sprintf("The new author %s (username %s) has the access level %s.\n"
                . "His/Her initial 10-digit password is %s",
                $longname, $username, $accessLevel, $password);
            $mailSuccess = mailEventToAdmin($subject, $text);
            // build string depending on success
            $strMailedToAdmin = _('mailed_to_admin');
            if ($mailSuccess)
                $strMailedToAdmin = str_replace('%not% ', '', $strMailedToAdmin);
            else
                $strMailedToAdmin = str_replace('%not%', _('not'), $strMailedToAdmin);
            // show status
            printf('    <p class="hint">%s</p>' . "\n", $strMailedToAdmin);
        
        } else {
            // error
            printf("    <h4>%s</h4>\n", _('author_not_created'));
            printf("    <p>%s</p>\n", _('db_error_on_write'));
        }

    } catch (PDOException $e) {
        switch ($e->getCode()) {
            // UNIQUE violation?
            case '23000':
            case '23505':
                printf("<h4>%s</h4>\n", _('username_not_free'));
                break;
            // anything else
            default:
                dbError(__FUNCTION__, $e);
                break;
        }
    }
}


$db = null;
printFooterAdm();

?>
