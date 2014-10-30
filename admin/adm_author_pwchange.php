<?php
/**
 * Change an author's password.
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

// all users can change only their own password
if ($_SESSION['authorId'] !== $authorId) {
    printAccessViolation();
    die;
}


printHeaderAdm(true, _('authors'));

// form data sent?
if (!empty($_POST['newPass1'])) {
    // yes
    // check if new password is too short
    if (mb_strlen(trim($_POST['newPass1'])) < MIN_PW_LENGTH) {
        printf("    <h4>%s</h4>\n",
            str_replace('%num%', MIN_PW_LENGTH, _('pwchange_too_short')));
        printFooterAdm();
        die();
    }
    // check if new passwords are equal
    if (trim($_POST['newPass1']) !== trim($_POST['newPass2'])) {
        printf("    <h4>%s</h4>\n",
            _('pwchange_not_equal'));
        printFooterAdm();
        die();
    }
    // check old password
    // database (with admin privileges)
    $db = dbConnect(true);
    if (!checkLogin($db, $_SESSION['username'], $_POST['oldPass'])) {
        printf("    <h4>%s</h4>\n",
            _('pwchange_old_wrong'));
        printFooterAdm();
        die();
    }
    // all checks passed
    if (updateAuthor($db, $authorId, 'pwchange', $_POST['newPass1'])) {
        printf("    <h4>%s</h4>\n", _('pwchange_success'));
    } else {
        printf("    <h4>%s</h4>\n", _('pwchange_failed'));
        printf("    <p>%s</p>\n", _('db_error_on_write'));
    }

} else {
    // form not sent, print it
?>
    <h4><?= _('pwchange') ?></h4>

    <form action="adm_author_pwchange.php" method="post">
      <input type="hidden" name="authorId" value="<?= $authorId ?>">
      <input type="password" name="oldPass" maxlength="96" tabindex="1" required><span class="description"><?= _('field_oldpass') ?></span><br>
      <input type="password" name="newPass1" maxlength="96" tabindex="2" required><span class="description"><?= _('field_newpass1') ?></span><br>
      <input type="password" name="newPass2" maxlength="96" tabindex="3" required><span class="description"><?= _('field_newpass2') ?></span><br>
      <input type="submit" name="pwChange" value="<?= _('change') ?>" tabindex="4">
    </form>
<?php
}


$db = null;
printFooterAdm();

?>
