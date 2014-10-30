<?php
/**
 * Comments overview.
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

printHeaderAdm(true, _('comments'));

// database
$db = dbConnect();

// was a postId selected? If not, get all unapproved comments by default
$selectedPostId = (!empty($_GET['selectPostId'])) ? $_GET['selectPostId'] : 'unapproved';

try {
    // get all post titles for selection first and print them
    // admins/mods get all posts
    if ($_SESSION['accessLevel'] <= 2) {
        $query = (
            "SELECT postid, title, comcount"
         . " FROM FemtoPost"
         . " ORDER BY postid DESC");
        $param = array();
    // authors see only their own posts
    } else {
        $query = (
            "SELECT postid, title, comcount"
         . " FROM FemtoPost"
         . " WHERE authorid = :authorid"
         . " ORDER BY postid DESC");
        $param = array(':authorid' => $_SESSION['authorId']);
    }
    $stmt = $db->prepare($query);
    $stmt->execute($param);
    $stmt->bindColumn('postid', $postId, PDO::PARAM_STR);
    $stmt->bindColumn('title', $title);
    $stmt->bindColumn('comcount', $comCount);
?>
    <form action="adm_comments.php" method="get">
      <select name="selectPostId" size="1">
        <option value="unapproved"<?php if ($selectedPostId === 'unapproved') echo ' selected' ?>><?= _('all_unapproved_comments') ?></option>
        <option value="last20"<?php if ($selectedPostId === 'last20') echo ' selected' ?>><?= _('last_20_comments') ?></option>
        <option value="myposts"<?php if ($selectedPostId === 'myposts') echo ' selected' ?>><?= _('belonging_to_my_posts') ?></option>
<?php
        while ($stmt->fetch(PDO::FETCH_BOUND)) {
            // pre-select "selected" ID if possible
            $selectOption = ($postId === $selectedPostId) ? ' selected' : '';
            printf('        <option value="%s"%s>(#%s) %s (%s)</option>' . "\n",
                $postId, $selectOption, $postId, $title, $comCount);
        }
?>
      </select>
      <input type="submit" name="selectId" value="<?= _('select') ?>">
    </form>
<?php
    $stmt->closeCursor();
    
    // get comments
    // if numeric, get comments for this postId
    if (is_numeric($selectedPostId)) {
        $query = (
            "SELECT *"
         . " FROM FemtoVAdmComment"
         . " WHERE postid = :postid"
         . " ORDER BY commentid DESC");
        $param = array(':postid' => $selectedPostId);
    // last 20 comments?
    } elseif ($selectedPostId === 'last20') {
        $query = (
            "SELECT *"
         . " FROM FemtoVAdmComment"
         . " WHERE commentid > 0" // needed for simple replacement later
         . " ORDER BY commentid DESC"
         . " LIMIT 20");
        $param = array();
    // belonging to my posts?
    } elseif ($selectedPostId === 'myposts') {
        $query = (
            "SELECT *"
         . " FROM FemtoVAdmComment"
         . " WHERE authorid = :authorid"
         . " ORDER BY commentid DESC");
        $param = array(':authorid' => $_SESSION['authorId']);
    // else all unapproved
    } else {
        $query = (
            "SELECT *"
         . " FROM FemtoVAdmComment"
         . " WHERE approved = 'false'"
         . " ORDER BY commentid DESC");
        $param = array();
    }
    // authors (accesslevel 3) see only comments belonging to their own posts
    if ($_SESSION['accessLevel'] > 2) {
        $query = str_replace('WHERE', 'WHERE authorid = :authorid2 AND', $query);
        $param += array(':authorid2' => $_SESSION['authorId']);
    }

    $stmt = $db->prepare($query);
    $stmt->execute($param);
    $stmt->bindColumn('commentid', $commentId, PDO::PARAM_INT);
    $stmt->bindColumn('postid', $postId, PDO::PARAM_INT);
    $stmt->bindColumn('created', $created);
    $stmt->bindColumn('longname', $longname);
    $stmt->bindColumn('email', $email);
    $stmt->bindColumn('url', $url);
    $stmt->bindColumn('content', $content);
    $stmt->bindColumn('lang', $lang);
    $stmt->bindColumn('approved', $approved);
    $stmt->bindColumn('ip', $ip);
    $stmt->bindColumn('title', $title);

    // print table
?>
    <form action="adm_comment_edit.php" method="get">
      <div class="commentbuttons">
        &nbsp;&nbsp;┌ <input type="submit" name="action" value="<?= _('approve') ?>" onclick="this.value='approve'" class="textbutton">
        <input type="submit" name="action" value="<?= _('edit') ?>" onclick="this.value='edit'" class="textbutton">
        <input type="submit" name="action" value="<?= _('delete') ?>" onclick="this.value='delete'; return confirmDeleteCom();" class="textbutton">
      </div>
      <table>
        <thead>
          <th><input type="checkbox" name="chkAll" value="all" onclick="" onchange="toggleAllCheckboxes(this); toggleTextbuttons()"></th>
          <th>cID</th>
          <th>pID</th>
          <th><?= _('field_name') ?></th>
          <th><?= _('field_email') ?>/URL/IP</th>
          <th><?= _('field_created') ?></th>
          <th><?= _('field_content') ?></th>
          <th><?= _('field_approved_short') ?></th>
          <th>✎</th>
        </thead>
        <tbody>
<?php
    // print comments
    $empty = true;
    while ($stmt->fetch(PDO::FETCH_BOUND)) {
        $empty = false;
        $postLink = '/' . $lang . '/' . $postId . '/';
        // if url present, insert a link to that, else leave empty
        if (!empty($url))
            $url = sprintf('<a href="%s">%s</a>', $url, $url);
?>
        <tr>
          <td><input type="checkbox" name="chkComment[]" value="<?= $commentId ?>" onchange="toggleTextbuttons()"></td>
          <td><a href="<?= $postLink . '#comment-' . $commentId?>"><?= $commentId ?></a></td>
          <td><abbr title="<?= $title ?>"><a href="<?= $postLink ?>"><?= $postId ?></a></abbr><br><?= $lang ?></td>
          <td class="alignleft"><?= $longname ?></td>
          <td class="alignleft"><?= $email ?><br>
          <?= $url ?><br>
          <?= $ip ?></td>
          <td><?= $created ?></td>
          <td class="alignleft"><?= $content ?></td>
          <td><?= _($approved) ?></td>
          <td><a href="adm_comment_edit.php?adm_comment_edit.php?action=edit&chkComment%5B%5D=<?= $commentId ?>" title="<?= _('edit_desc') ?>"><?= _('edit_short') ?></a></td>
        </tr>
<?php
    }
    // if $empty is still true, print "no data"
    if ($empty)
        echo '          <td colspan="9">No data.</td>' . "\n";
?>
        </tbody>
      </table>
      <div class="commentbuttons">
        &nbsp;&nbsp;└ <input type="submit" name="action" value="<?= _('approve') ?>" onclick="this.value='approve'" class="textbutton">
        <input type="submit" name="action" value="<?= _('edit') ?>" onclick="this.value='edit'" class="textbutton">
        <input type="submit" name="action" value="<?= _('delete') ?>" onclick="this.value='delete'; return confirmDeleteCom();" class="textbutton">
      </div>
    </form>
<?php

} catch (PDOException $e) {
    dbError(__FUNCTION__, $e);
}

$db = null;
printFooterAdm();

?>
