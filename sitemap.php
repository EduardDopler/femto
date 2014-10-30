<?php
/**
 * Sitemap.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */


require 'functions.php';

// initialize localization
initLocale();


// get posts overview
$db = dbConnect();
$postsOverview = getPostsOverview($db);

// link prefix for locale selection
$linkPrefix = '/' . _('lang') . '/';

printHeader();
?>
  <article>
    <h3><?= _('sitemap') ?></h3>
    <ul>
      <li><a href="<?= $linkPrefix ?>"><?= _('home_blog_overview'); ?></a></li>
      <li><a href="/atom_<?= _('lang') ?>.xml">RSS Feed</a></li>
      <li><a href="<?= $linkPrefix ?>apps/">Apps</a></li>
      <li><a href="<?= $linkPrefix ?>downloads/">Downloads</a></li>
      <li><a href="<?= '/' . _('other_lang') . '/' ?>"><?= _('other_lang_long') ?></a></li>
      <li><a href="<?= $linkPrefix ?>search.php"><?= _('search'); ?></a></li>
      <li><a href="<?= $linkPrefix ?>impressum.php"><?= _('impressum') ?></a></li>
      <li><?= _('posts') ?></li>
      <ul><?php
        echo "\n";
        printPostsOverview($postsOverview); ?>
      </ul>
    </ul>
  </article>
<?php

printFooter();

?>