<?php
/**
 * Authors overview.
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

printHeaderAdm(true, _('authors'));

// database
$db = dbConnect();

try {
    // SQL statement
    // users with an access level >=3 can only see their own data
    if ($_SESSION['accessLevel'] >= 3) {
        $query = (
            "SELECT *"
         . " FROM FemtoAuthor"
         . " WHERE authorid = :authorid");
        $param = array(':authorid' => $_SESSION['authorId']);
    } else {
        $query = (
            "SELECT *"
         . " FROM FemtoAuthor"
         . " ORDER BY authorid");
        $param = array();
    }
    $stmt = $db->prepare($query);
    $stmt->execute($param);
    $stmt->bindColumn('authorid', $authorId, PDO::PARAM_INT);
    $stmt->bindColumn('username', $username);
    $stmt->bindColumn('longname', $longname);
    $stmt->bindColumn('created', $created);
    $stmt->bindColumn('accesslevel', $accessLevel, PDO::PARAM_INT);
    $stmt->bindColumn('blocked', $blocked);
    $stmt->bindColumn('lastlogin', $lastLogin);
    // make blocked boolean
    //$blocked = ($blocked === 'true') ? true : false;

    // print table
    // fill an extra column for blocking/deleting users if accessLevel < 2 (Admin/Mod)
    $userSettings = ($_SESSION['accessLevel'] <= 2) ? sprintf('%s/%s', _('block'), _('delete')) : '';
?>
    <table>
      <thead>
        <th>ID</th>
        <th><?= _('field_username') ?></th>
        <th><?= _('field_name') ?></th>
        <th><?= _('field_registered') ?></th>
        <th><?= _('field_accesslevel') ?></th>
        <th><?= _('field_lastlogin') ?></th>
        <th><?= _('field_password') ?></th>
        <th><?= $userSettings ?></th>
      </thead>
      <tbody>
<?php
    // print authors
    $empty = true;
    while ($stmt->fetch(PDO::FETCH_BOUND)) {
        $empty = false;
        // show only settings which users are allowed to change:
        // all users can rename themselves, Admin/Mods can rename all
        $rename = ($_SESSION['authorId'] === $authorId or $_SESSION['accessLevel'] <= 2) ? sprintf('<a href="adm_author_rename.php?aid=%s&longname=%s" title="%s">✎</a>', $authorId, $longname, _('rename')) : '';
        // all users can change only their own password
        $passwordChange = ($_SESSION['authorId'] === $authorId) ? sprintf('[<a href="adm_author_pwchange.php?aid=%s">%s</a>]', $authorId, _('change_pw')) : '';
        // only Admins can reset all passwords, Mods can reset all non-admin passwords
        $passwordReset = ($_SESSION['accessLevel'] === 1 or ($_SESSION['accessLevel'] === 2 and $accessLevel !== 1)) ? sprintf('[<a href="adm_author_pwreset.php?aid=%s" onclick="return confirmFirst(\'ResetPw\')">%s</a>]', $authorId, _('reset_pw')) : '';
        // only Admins can delete users, but not themselves
        $userDelete = ($_SESSION['accessLevel'] === 1 and $_SESSION['authorId'] !== $authorId) ? sprintf('[<a href="adm_author_delete.php?&aid=%s" onclick="return confirmFirst(\'DeleteAuthor\')">%s</a>]', $authorId, _('delete')) : '';
        // only Admins/Mods can block users, but no Admins and not themselves
        // determine current blocking status first
        $blockUnblock = ($blocked === 'true') ? _('unblock') : _('block');
        $action = ($blocked === 'true') ? 'unblock' : 'block';
        $userBlock = ($_SESSION['accessLevel'] <= 2 and $accessLevel !== 1 and $_SESSION['authorId'] !== $authorId) ? sprintf('[<a href="adm_author_block.php?aid=%s&action=%s" onclick="return confirmFirst(\'BlockAuthor\')">%s</a>]', $authorId, $action, $blockUnblock) : '';
?>
      <tr>
        <td><?= $authorId ?></td>
        <td><?= $username ?></td>
        <td><?= $longname . ' &nbsp; ' . $rename ?> </td>
        <td><?= $created ?></td>
        <td><?= $accessLevel . ' ' . _('accesslevel_' . $accessLevel) ?></td>
        <td><?= $lastLogin ?></td>
        <td><?= $passwordChange . ' &nbsp; ' . $passwordReset ?></td>
        <td><?= $userBlock . ' &nbsp; ' . $userDelete ?></td>
      </tr>
<?php
    }
    // if $empty is still true, print "no data"
    if ($empty)
        echo '          <td colspan="8">No data.</td>' . "\n";

} catch (PDOException $e) {
    dbError(__FUNCTION__, $e);
}

?>
      </tbody>
    </table>
<?php
// Mods and Admins can create new users
if ($_SESSION['accessLevel'] <= 2) {
?>
    <hr>
    <h4><?= _('new_author') ?></h4>

    <form action="adm_author_new.php" method="post">
      <input type="text" name="username" maxlength="16" tabindex="1" onchange="checkLatinOnly(this)" required><span class="description"><?= _('field_username') ?></span><br>
      <input type="text" name="longname" maxlength="32" tabindex="2" required><span class="description"><?= _('field_name') ?></span><br>
      <select name="accessLevel" size="1" tabindex="3">
        <option value="1" >1 <?= _('accesslevel_1') ?></option>
        <option value="2" >2 <?= _('accesslevel_2') ?></option>
        <option value="3" >3 <?= _('accesslevel_3') ?></option>
      </select><span class="description"><?= _('field_accesslevel') ?></span><br>
      <input type="submit" name="authorCreate" value="<?= _('create') ?>" tabindex="4">
    </form>

    <details>
      <summary><?= _('field_accesslevel') ?></summary>
      <p><b>1 <?= _('accesslevel_1') ?>:</b> <?= _('accesslevel_1_desc') ?><br>
      <b>2 <?= _('accesslevel_2') ?>:</b> <?= _('accesslevel_2_desc') ?><br>
      <b>3 <?= _('accesslevel_3') ?>:</b> <?= _('accesslevel_3_desc') ?></p>
    </details>

<?php
}


$db = null;
printFooterAdm();

?>
