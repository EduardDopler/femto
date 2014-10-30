<?php
/**
 * Posts overview.
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

printHeaderAdm(true, _('posts'));

// print link for feed/sitemap update
?>
    <p class="redright"><a href="adm_update_feedsitemap.php" title="<?= _('update_feed_sitemap_desc') ?>"><?= _('update_feed_sitemap') ?></a></p>
<?php

// database
$db = dbConnect();

try {
    // SQL statement
    // users with an access level >=3 can only see their own posts
    if ($_SESSION['accessLevel'] >= 3) {
        $query = (
            "SELECT *"
         . " FROM FemtoPost"
         . " WHERE authorid = :authorid"
         . " ORDER BY created DESC");
        $param = array(':authorid' => $_SESSION['authorId']);
    // admins/mods see all posts
    } else {
        $query = (
            "SELECT *"
         . " FROM FemtoPost"
         . " ORDER BY created DESC");
        $param = array();
    }
    $stmt = $db->prepare($query);
    $stmt->execute($param);
    $stmt->bindColumn('postid', $postId, PDO::PARAM_INT);
    $stmt->bindColumn('visibility', $visibility);
    $stmt->bindColumn('created', $created);
    $stmt->bindColumn('title', $title);
    $stmt->bindColumn('urltitle', $urlTitle);
    $stmt->bindColumn('content', $content);
    $stmt->bindColumn('lang', $lang);
    $stmt->bindColumn('langreference', $langReference);
    $stmt->bindColumn('modified', $modified);
    $stmt->bindColumn('comvisibility', $comVisibility);
    $stmt->bindColumn('comcount', $comCount);

    // print table
?>
    <table>
      <thead>
        <th>ID</th>
        <th><?= _('field_visibility') ?></th>
        <th>⇩ <?= _('field_created') ?></th>
        <th><?= _('field_lang') ?></th>
        <th><?= _('field_title') ?></th>
        <th>✎</th>
        <th><?= _('comments') ?> (#)</th>
      </thead>
      <tbody>
<?php
    // print posts
    $empty = true;
    while ($stmt->fetch(PDO::FETCH_BOUND)) {
        $empty = false;
        // if post is visible, make a link
        if ($visibility === 'posted') {
            $postLink = '/' . $lang . '/' . $postId . '/';
            $title = sprintf('<a href="%s">%s</a>',
                $postLink, $title);
        }
        // if article has translation ("langReference"), show ID of that article on hover
        if (!empty($langReference))
            $lang = sprintf('<abbr title="referenced ID from other language: %s">%s</abbr>',
                $langReference, $lang);
?>
      <tr>
        <td><?= $postId ?></td>
        <td><?= _($visibility) ?></td>
        <td><abbr title="<?= _('field_modified') ?>: <?= $modified ?>"><?= $created ?></abbr></td>
        <td><?= $lang ?></td>
        <td class="alignleft"><?= $title ?></td>
        <td><a href="adm_post_edit.php?postId=<?= $postId ?>" title="<?= _('edit_desc') ?>"><?= _('edit_short') ?></a></td>
        <td><?= _($comVisibility) ?> <a href="adm_comments.php?selectPostId=<?= $postId ?>" title="<?= _('show_its_comments') ?>">(<?= $comCount ?>)</a></td>
      </tr>
<?php
    }
    // if $empty is still true, print "no data"
    if ($empty)
        echo '          <td colspan="7">No data.</td>' . "\n";

?>
      </tbody>
    </table>
<?php

} catch (PDOException $e) {
    dbError(__FUNCTION__, $e);
}

$db = null;
printFooterAdm();

?>
