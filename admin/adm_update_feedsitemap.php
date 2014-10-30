<?php
/**
 * Update the blog feed and sitemap.xml.
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


printHeaderAdm(true, _('feed_sitemap'));

// database
$db = dbConnect();

// form data sent?
if (!empty($_POST['feedLang'])) {
    // yes
    $done = false;
    // update feed
    if (!empty($_POST['feedNumPosts']) and
        !empty($_POST['feedLang'])) {
        printf("    <h4>%s</h4>\n", _('create_feed'));
        // get data
        $feedData = getPostMetadata($db, $_POST['feedLang'], $_POST['feedNumPosts']);
        // create feed, if something was returned
        if (!empty($feedData)) {
            $result = createFeed($feedData);
            // print result
            if ($result === true)
                printf("    <p>%s</p>\n", _('success'));
            else
                printf("    %s\n", $result);
            $done = true;
        }
    }

    // update sitemap.xml
    if (!empty($_POST['sitemap'])) {
        printf("<h4>%s</h4>", _('create_sitemap'));
        // get data
        $posts = getPostMetadata($db);
        // create feed
        $result = createSitemap($posts);
        // print result
        if ($result === true)
            printf("    <p>%s</p>\n", _('success'));
        else
            printf("    %s\n", $result);
        $done = true;
    }

    // done something?
    if (!$done) {
        printf("    %s\n", _('nothing_to_do'));
    }

// form not sent, print it
} else {
    // SQL statement
    try {
        // get languages
        $query = (
            "SELECT DISTINCT lang"
         . " FROM FemtoPost");
        $languages = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        // print description and form
?>
    <h4><?= _('update_feed_sitemap') ?></h4>
    <p><?= _('update_feedsitemap_howto') ?></p>
    <hr>
    <form action="adm_update_feedsitemap.php" method="post">
      <input type="text" name="feedNumPosts" maxlength="3" value="<?= DEFAULT_NUM_FEED_POSTS ?>" placeholder="<?= DEFAULT_NUM_FEED_POSTS ?>" tabindex="1" required><span class="description">Feed: <?= _('num_feed_posts') ?></span><br>
      <select name="feedLang" size="1" tabindex="2" required>
<?php
        foreach ($languages as $lang) {
?>
        <option><?= $lang['lang'] ?></option>
<?php
        }
?>
      </select><span class="description">Feed: <?= _('language') ?></span><br>
      <select name="sitemap" size="1" tabindex="3">
        <option value="1" selected><?= _('update_sitemap_true') ?></option>
        <option value="0"><?= _('update_sitemap_false') ?></option>
      </select><span class="description">sitemap.xml</span><br>
      <hr>
      <input type="submit" name="update" value="<?= _('update') ?>" tabindex="4">
    </form>
<?php

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


$db = null;
printFooterAdm();

?>
