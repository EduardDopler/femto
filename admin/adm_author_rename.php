<?php
/**
 * Rename an author.
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

// all users can rename themselves, Admin/Mods can rename all
if ($_SESSION['authorId'] !== $authorId and
    $_SESSION['accessLevel'] > 2) {
    printAccessViolation();
    die;
}


printHeaderAdm(true, _('authors'));

// form data sent?
if (!empty($_POST['newName'])) {
    // yes
    $newName = escapeField($_POST['newName']);
    // database (with admin privileges)
    $db = dbConnect(true);
    
    if (updateAuthor($db, $authorId, 'rename', $newName)) {
        printf("    <h4>%s</h4>\n", _('rename_success'));
    } else {
        printf("    <h4>%s</h4>\n", _('rename_failed'));
        printf("    <p>%s</p>\n", _('db_error_on_write'));
    }

// form not sent, print it
} else {
    // fill in old longname as placeholder, if available
    $longname = (!empty($_GET['longname'])) ? $_GET['longname'] : '';
?>
    <h4><?= _('rename') ?></h4>

    <form action="adm_author_rename.php" method="post">
      <input type="hidden" name="authorId" value="<?= $authorId ?>">
      <input type="text" name="newName" placeholder="<?= $longname ?>" maxlength="32" tabindex="1" required><span class="description"><?= _('field_newname') ?></span><br>
      <input type="submit" name="pwChange" value="<?= _('change') ?>" tabindex="2">
    </form>
<?php
}


$db = null;
printFooterAdm();

?>
