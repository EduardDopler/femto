<?php
/**
 * Edit a post.
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

// postId submitted?
if (empty($_GET['postId']) or !is_numeric($_GET['postId'])) {
    echo "    <h4>No Data.</h4>\n";

} else {
    // database
    $db = dbConnect();

    // check access violation
    if (!checkAccessAllowed($db, 'post', $_GET['postId'])) {
        printAccessViolation(true);
        die();
    }

    // SQL statement
    try {
        // get authors
        // admins/mods get authors from database
        if ($_SESSION['accessLevel'] <= 2) {
            $queryAuthors = (
                "SELECT authorid, longname"
             . " FROM FemtoAuthor");
            $authors = $db->query($queryAuthors)->fetchAll(PDO::FETCH_ASSOC);
        // authors see only themselves
        } else {
            $authors = array(array(
                'authorid' => $_SESSION['authorId'],
                'longname' => $_SESSION['longname']));
        }

        // get posts for langReference
        $queryPosts = (
            "SELECT postid, lang, title"
         . " FROM FemtoPost"
         . " ORDER BY created DESC");
        $posts = $db->query($queryPosts)->fetchAll(PDO::FETCH_ASSOC);

        // prepare a COUNT query first in order to check if postId exists
        $queryCount = (
            "SELECT COUNT(*)"
         . " FROM FemtoPost"
         . " WHERE postid = :postid");
        $param = array(':postid' => $_GET['postId']);
        $stmtCount = $db->prepare($queryCount);
        $stmtCount->execute($param);
    
        if ($stmtCount->fetchColumn() > 0) {
            // postId found, get post
            $queryPost = (
                "SELECT *"
             . " FROM FemtoPost"
             . " WHERE postid = :postid");
            $param = array(':postid' => $_GET['postId']);
            $stmt = $db->prepare($queryPost);
            $stmt->execute($param);
            // bind columns
            $stmt->bindColumn('postid', $postId, PDO::PARAM_INT);
            $stmt->bindColumn('lang', $lang);
            $stmt->bindColumn('visibility', $visibility);
            $stmt->bindColumn('created', $created);
            $stmt->bindColumn('modified', $modified);
            $stmt->bindColumn('title', $title);
            $stmt->bindColumn('urltitle', $urlTitle);
            $stmt->bindColumn('authorid', $authorId);
            $stmt->bindColumn('langreference', $langReference);
            $stmt->bindColumn('comvisibility', $comVisibility);
            $stmt->bindColumn('comcount', $comCount);
            $stmt->bindColumn('content', $content);
            $stmt->fetch(PDO::FETCH_BOUND);
?>
    <h4><?= _('edit_post') ?></h4>
    <hr>
    <form action="adm_post_update.php" method="post">
      <input type="text" name="postId" maxlength="10" value="<?= $postId ?>" title="<?= _('do_not_change') ?>" readonly required><span class="description"><?= _('field_postid') ?></span><br>
      <input type="text" name="lang" maxlength="2" value="<?= $lang ?>" placeholder="<?= $lang ?>" title="<?= _('changes_not_recommended') ?>" onclick="enableField(this)" readonly required><span class="description"><?= _('field_language') ?></span><br>
      <select name="visibility" size="1" tabindex="1" required>
        <option value="posted"<?php if ($visibility === 'posted') echo ' selected' ?>><?= _('posted') ?></option>
        <option value="hidden"<?php if ($visibility === 'hidden') echo ' selected' ?>><?= _('hidden') ?></option>
        <option value="draft"<?php if ($visibility === 'draft') echo ' selected' ?>><?= _('draft') ?></option>
      </select><span class="description"><?= _('field_visibility') ?></span><br>
      <input type="text" name="created" maxlength="25" value="<?= $created ?>" placeholder="<?= $created ?>" title="YYYY-MM-DD HH:MM:SS" tabindex="2" required><span class="description"><?= _('field_created') ?> <a href="javascript:void(0);" title="<?= _('now_desc') ?>" onclick="return getNow('created')">(<?= _('now') ?>)</a></span><br>
      <input type="text" name="modified" maxlength="25" value="<?= $modified ?>" placeholder="<?= $modified ?>" title="YYYY-MM-DD HH:MM:SS" tabindex="3" required><span class="description"><?= _('field_modified') ?> <a href="javascript:void(0);" title="<?= _('now_desc') ?>" onclick="return getNow('modified')">(<?= _('now') ?>)</a></span><br>
      <input type="text" name="title" value="<?= $title ?>" placeholder="<?= $title ?>" tabindex="4" onblur="generateUrlTitle(false)" required><span class="description"><?= _('field_title') ?></span><br>
      <input type="text" name="urlTitle" maxlength="100" value="<?= $urlTitle ?>" placeholder="<?= $urlTitle ?>" tabindex="5" required><span class="description"><?= _('field_urltitle') ?> <a href="javascript:void(0);" title="<?= _('from_title_desc') ?>" onclick="return generateUrlTitle(true)">(<?= _('from_title') ?>)</a></span><br>
      <select name="authorId" size="1" tabindex="6">
<?php
            foreach ($authors as $author) {
?>
        <option value="<?= $author['authorid'] ?>"<?php if ($author['authorid'] === $authorId) echo ' selected' ?>>#<?= $author['authorid'] . ' ' . $author['longname'] ?></option>
<?php
            }
?>
      </select><span class="description"><?= _('field_authorid') ?></span><br>
      <select name="langReference" size="1" tabindex="8">
        <option value="0"><?= _('none') ?></option>
<?php
        foreach ($posts as $post) {
?>
        <option value="<?= $post['postid'] ?>"<?php if ($post['postid'] === $langReference) echo ' selected' ?>>#<?= $post['postid'] . ' (' . $post['lang'] . ') ' . $post['title'] ?></option>
<?php
        }
?>
      </select><span class="description"><?= _('field_langreference') ?></span><br>
      <select name="comVisibility" size="1" tabindex="8" required>
        <option value="visible"<?php if ($comVisibility === 'visible') echo ' selected' ?>><?= _('visible') ?></option>
        <option value="hidden"<?php if ($comVisibility === 'hidden') echo ' selected' ?>><?= _('hidden') ?></option>
        <option value="closed"<?php if ($comVisibility === 'closed') echo ' selected' ?>><?= _('closed') ?></option>
      </select><span class="description"><?= _('field_comvisibility') ?></span><br>
      <input type="text" name="comCount" maxlength="6" value="<?= $comCount ?>" placeholder="<?= $comCount ?>" title="<?= _('changes_not_recommended') ?>" onclick="enableField(this)" tabindex="9" readonly required><span class="description"><?= _('field_comcount') ?></span><br>
      <textarea name="content" tabindex="10" onkeypress="window.confirmLeave=true" required><?= $content ?></textarea><span class="description"><?= _('field_content') ?></span><br>
<?php
        printTextTools();
?>
      <hr>
      <input id="previewbutton" type="button" name="postPreview" value="<?= _('preview') ?>" tabindex="11" onclick="showPreview()">
      <input type="submit" name="edit" value="<?= _('edit') ?>" tabindex="11" onclick="window.confirmLeave=false">
    </form>
    <p class="redright"><a href="adm_post_delete.php?postId=<?= $postId ?>" onclick="return confirmFirst('DeletePost')"><?= _('delete_post') ?></a></p>
  </article>
  <hr>
  <article id="previewarea">
  </article>
<?php

        } else {
            // postId not found
            echo "    <h4>No data.</h4>\n";
        }

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


$db = null;
printFooterAdm();

?>
