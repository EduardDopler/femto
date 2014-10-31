<?php
/**
 * Global functions.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */


// include private settings
require 'settings/.settings.php';



/* ===== FUNCTIONS ===== */


/* ----- LOCALE ----- */


/**
 * Initialize locale system.
 * Call getLocale function and use determined language code for locale text binding.
 */
function initLocale() {
    mb_internal_encoding('UTF-8');

    $locale = getLocale();
    putenv("LANG=$locale");
    setlocale(LC_ALL, $locale);

    $domain = 'messages';
    $path = dirname(__FILE__) . '/locale';
    bindtextdomain($domain, $path);
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
}


/**
 * Determine language for content.
 * Lookup 'l' value in $_GET/$_POST query. If value is known, return its locale string. If not or not set, return default.
 * @return string Locale value for requested content language, e.g. 'en_US.UTF-8' for request 'en'.
 */
function getLocale() {
    // if single language blog, return default language
    if (SINGLE_LANGUAGE)
        return LANG_DEFAULT_LOCALE;

    // second language requested? (check _GET and _POST)
    if ((!empty($_GET['l']) and
        $_GET['l'] === LANG_2) or
        (!empty($_POST['l']) and
        $_POST['l'] === LANG_2)) {
            $locale = LANG_2_LOCALE;
    // else use default locale
    } else {
        $locale = LANG_DEFAULT_LOCALE;
    }
    return $locale;
}


/**
 * Format Database date/time to locale pattern.
 * @param string $str Date/Time (or both) in Database format to be formatted.
 * @return string Formatted date, time or both in locale pattern.
 */
function localeDate($str) {
    // time only (HH24:MI)
    if (strlen($str) === 5) {
        $time = DateTime::createFromFormat('H:i', $str);
        return $time->format(_('time_fmt'));

    // date only (YYYY-MM_DD)
    } elseif (strlen($str) === 10) {
        $date = DateTime::createFromFormat('Y-m-d', $str);
        return $date->format(_('date_fmt'));

    // date and time
    } else {
        $datetime = new DateTime($str);
        return $datetime->format(_('datetime_fmt'));
    }
}



/* ----- INDEX VIEW ----- */


/**
 * Index View mode.
 * Initialize locale, get requested posts and print them.
 */
function indexview() {
    // init and connect to DB
    $db = dbConnect();

    // get default number of latest posts
    $posts = getPosts($db);
    $clickableTitle = true;
    $withCommentsAbstract = true;

    // we can close the DB connection now.
    $db = null;

    printHeader();

    // returned something?
    if ($posts) {
        // print all posts returned
        foreach ($posts as $post) {
            printPost($post, $clickableTitle, $withCommentsAbstract);
        }
        printMoreButton();
    } else {
        echo "    <p>No posts found.</p>\n";
    }

    printFooter();
}


/**
 * Get a range of posts from database.
 * @param PDO $db Database instance.
 * @param int $start First post we start counting at (default: 0).
 * @param int $count Number of posts to be delivered (default: 3).
 * @return array|false The requested posts range or false if nothing found.
 */
function getPosts($db, $start=0, $count=3) {

    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVPost"
         . " WHERE lang = :lang"
         . " ORDER BY postid DESC"
         . " LIMIT :count OFFSET :start");
        $param = array(
            ':lang' => _('lang'),
            ':start' => $start,
            ':count' => $count);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        // save in $posts, cleanup and return
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $posts;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}



/* ----- SINGLE VIEW ----- */


/**
 * Single View mode.
 * Initialize locale, get post and print it.
 * If post with $postId is not found, show 404.
 * @param int $postId Post ID.
 */
function singleview($postId) {
    // init and connect to DB
    $db = dbConnect();

    // get post and its comments
    $post = getPost($db, $postId);
    $comments = getComments($db, $postId);
    // get latest posts
    if (LINK_LATEST_POSTS > 0)
        $latestPosts = getLatestPosts($db, $postId, LINK_LATEST_POSTS);
    else
        $latestPosts = false;

    // we can close the DB connection now.
    $db = null;

    if ($post) {
        // output content
        printHeader($post);
        printPost($post, false, false, $latestPosts);
        printComments($post, $comments);
        printFooter();
    } else {
        print404();
    }
}



/**
 * Get a single post from database.
 * @param PDO $db Database instance.
 * @param int $postId Post ID.
 * @return array|false The requested post or false if not found.
 */
function getPost($db, $postId) {

    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVPost"
         . " WHERE postid = :postid");
        $param = array(':postid' => $postId);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        // save in $post, cleanup and return
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $post;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}



/**
 * Get comments related to $postId from database.
 * @param PDO $db Database instance.
 * @param int $postId Post ID the comments belong to.
 * @return array Keys are the commentIds, values are again arrays with all comment fields. False on error.
 */
function getComments($db, $postId) {

    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVComment"
         . " WHERE postid = :postid"
         . " ORDER BY commentid DESC");
        $param = array(':postid' => $postId);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        // save in $comments, cleanup and return.
        // for each row: index is first column (commentId), value is array of other columns
        $comments = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $comments;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}



/* ----- HEADER/FOOTER/MORE BUTTON ----- */


/**
 * Print header.
 * Everything that comes before the article(s). E.g. the <head>, the header, the navigation.
 * Use dynamic information from blog post, if any.
 * @param mixed $post Array containing blog post information or 404 (if error page) or null if anything else.
 */
function printHeader($post=null) {
    // link prefix for locale selection
    $linkPrefix = '/' . _('lang') . '/';

    // prepare vars
    if ($post === 404) {
        // title
        $title = '404 – ' . BLOG_TITLE;
        // link to other language in navigation
        $navOtherLang = '/' . _('other_lang') . '/';
        // canonical tag
        $canonicalContent = 'error_404.php';
        $canonical = sprintf('<link rel="canonical" href="%s">', $canonicalContent);
    } else {
        // title
        $title = $post ? $post['title'] . ' – ' . BLOG_TITLE : BLOG_TITLE;
        // description tag
        $descr = $post ? null : '<meta name="description" content="' . BLOG_DESCRIPTION . '">';
        // link to other language in navigation
        $navOtherLang = '/' . _('other_lang') . '/';
        // if this post (if any) has a referenced article in another language, link there
        if (!empty($post) and
            !empty($post['langreference'])) {
                $navOtherLang .= $post['langreference'] . '/';
        }
        // canonical tag
        //  use only for single view sites
        if (!empty($post)) {
            $canonicalContent = sprintf('%s/%s/%s/%s/',
                HOST, _('lang'), $post['postid'], $post['urltitle']);
            $canonical = sprintf('<link rel="canonical" href="%s">', $canonicalContent);
        }
    }

?><!DOCTYPE html>
<html lang="<?= _('lang') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="generator" content="femto">
  <title><?= $title ?></title>
  <?php if (!empty($descr)) echo $descr . "\n" ?>
  <?php if (!empty($canonical)) echo $canonical . "\n"; ?>
  <link rel="stylesheet" href="/femto-<?= CSS_VER ?>.min.css">
  <link rel="icon" sizes="196x196" href="/favicon-196-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="/favicon-196-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="76x76" href="/favicon-76-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="120x120" href="/favicon-120-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="152x152" href="/favicon-152-precomposed.png">
  <link rel="alternate" type="application/atom+xml" href="/atom_<?= LANG_DEFAULT ?>.xml" title="<?= BLOG_TITLE ?> Feed (Atom, deutsch)">
<?php
    if (!SINGLE_LANGUAGE) {
?>
  <link rel="alternate" type="application/atom+xml" href="/atom_<?= LANG_2 ?>.xml" title="<?= BLOG_TITLE ?> Feed (Atom, english)">
<?php
    }
?>
  <script src="/femto-<?= JS_VER ?>.min.js" async defer></script>
</head>

<body onload="calcMath()" id="nav-true">
  <header>
    <h1><a href="<?= $linkPrefix ?>"><?= BLOG_TITLE ?></a></h1>
    <h2><?= BLOG_SUBTITLE ?></h2>
    <nav>
      <a id="show-nav" href="#nav-true" onclick="window.setTimeout(toggleNav,500)">—<br>—<br>—</a>
      <ul id="nav-links">
        <li><a href="<?= $linkPrefix ?>" class="color1">blog</a></li>
        <li><a href="/atom_<?= _('lang') ?>.xml" class="color2">rss feed</a></li>
        <li><a href="<?= $linkPrefix . NAV_LINK_1 ?>/" class="color5"><?= NAV_LINK_1_NAME ?></a></li>
        <li><a href="<?= $linkPrefix . NAV_LINK_2 ?>/" class="color3"><?= NAV_LINK_2_NAME ?></a></li>
<?php
    if (!SINGLE_LANGUAGE) {
?>
        <li><a href="<?= $navOtherLang ?>" class="color4"><?= _('other_lang_long') ?> version</a></li>
<?php
    }
?>
        <li><a href="<?= $linkPrefix ?>search.php" class="color5"><?= _('search') ?></a></li>
      </ul>
    </nav>
  </header>

  <main>
<?php
}


/**
 * Print footer.
 * Everything that comes below the article(s). E.g. the footer itself and Piwik.
 */
function printFooter() {
    // link prefix for locale selection
    $linkPrefix = '/' . _('lang') . '/';
?>
  </main>

  <footer>
    <p id="copyright"><?= _('footer_copyright') ?></p>
    <p><a href="<?= $linkPrefix ?>sitemap.php"><?= _('sitemap') ?></a></p>
    <p><a href="<?= $linkPrefix ?>impressum.php"><?= _('impressum') ?></a></p>
    <p id="femto"><a href="https://eduard-dopler.de/en/femto/">femto blog system</a><br> by Eduard Dopler</p>
  </footer>

  <div style="display:none">
    <!-- JS locale strings -->
    <p id="jsLang"><?= _('lang') ?></p>
    <p id="jsLoading"><?= _('loading') ?></p>
    <p id="jsFormEmail"><?= _('form_email') ?></p>
    <p id="jsFormUrl"><?= _('form_url') ?></p>
    <p id="jsFormComment"><?= _('form_comment') ?></p>
  </div>

  <!-- Piwik -->
  <script type="text/javascript">
    var _paq = _paq || [];
    _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
    _paq.push(["setDomains", ["*.<?= $_SERVER['SERVER_NAME'] ?>"]]);
    _paq.push(["trackPageView"]);
    _paq.push(["enableLinkTracking"]);
    (function() {
    var u="<?= HOST . PIWIK_PATH ?>";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "1"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
    })();
  </script>

</body>
</html>
<?php
}


/**
 * Print the button which loads more posts.
 */
function printMoreButton() {
?>
  <p class="buttoncontainer"><a class="loadMore" href="javascript:void(0)" onclick="return loadMore(this)">⇓ &nbsp; <?= _('morebutton_older_posts') ?> &nbsp; ⇓</a></p>
<?php
}



/* ----- PRINT POST, COMMENTS etc ----- */


/**
 * Print the blog post with date and author and flattr button.
 * @param array $post The post to be printed.
 * @param boolean $clickableTitle Whether the titles should be links (default: false).
 * @param boolean $withCommentsAbstract Whether the comments abstract should be printed (default: false).
 * @param boolean $latestPosts Whether the latest posts should be appended (default: false).
 */
function printPost($post, $clickableTitle=false, $withCommentsAbstract=false, $latestPosts=false) {
    // prepare vars
    // title
    if ($clickableTitle) {
        $title = sprintf('<a href="/%s/%s/%s/">%s</a>',
            _('lang'), $post['postid'], $post['urltitle'], $post['title']);
    } else {
        $title = $post['title'];
    }

    ?>
  <article>
    <h3><?= $title ?></h3>
    <p class="postdate vcard"><span class="fn author"><?= $post['author'] ?></span>, <?= localeDate($post['created']) ?></p>
    <!-- begin article content -->
<?= $post['content'] . "\n" ?>
    <!-- end article content -->
<?php

    if (FLATTR)
        printFlattr($post);

    if ($withCommentsAbstract)
        printCommentsAbstract($post);

    if (!empty($latestPosts))
        printLatestPosts($latestPosts);

    echo "  </article>\n";
}


/**
 * Print paragraph with numeric information about comments for this post.
 * Print only when comVisibility is not 'hidden'.
 * @param array $post The post you want the comments information about.
 */
function printCommentsAbstract($post) {
    if ($post['comvisibility'] !== 'hidden') {
        $count = intval($post['comcount']);
        $abstractText = sprintf("%d %s",
            $count, ngettext('comment', 'comments', $count));
        $url = sprintf('/%s/%s/%s#comments-area',
            _('lang'), $post['postid'], $post['urltitle']);
        $abstractHtmlLink = sprintf('<a href="%s">%s</a>',
            $url, $abstractText);
        printf('    <p class="comments-abstract">%s</p>' . "\n",
            $abstractHtmlLink);
    }
}


/**
 * Print comments area.
 * If not hidden or disabled, show form for new comments and all comments so far.
 * comVisibility can be one of
 *  - 'visible'   (show form and comments),
 *  - 'closed' (show only existing comments)
 *  - 'hidden' (do not show anything)
 * @param array $post The post the comments are related to.
 * @param array $comments The comments for the $post.
 */
function printComments($post, $comments) {
    if ($post['comvisibility'] !== 'hidden') {
?>
  <div id="comments-area">
    <h3><?= _('comments') ?></h3>
<?php

        echo "\n";
        // New Comment form, if new comments allowed
        if ($post['comvisibility'] === 'visible') {
            $postUrl = sprintf('/%s/%s/%s',
                _('lang'), $post['postid'], $post['urltitle']);
            printFormNewComment($post['postid'], $postUrl);
        } else {
            echo '<!-- new comments disabled. -->';
        }


        // comments so far
        echo "    <h4>" . _('com_so_far') . "</h4>\n";

        if ($post['comcount'] === '0' or empty($comments)) {
            echo '    ' . _('com_no_comments') . "\n";
        } else {
            // iterate through comments
            // keys are the commentIds, values are the comment's fields
            foreach ($comments as $commentId => $fields) {
                // comment approved?
                if ($fields['approved'] === 'true') {
                    $content = $fields['content'];
                    // make date clickable for persistent link
                    $created = sprintf('<a href="#comment-%s">%s</a>',
                        $commentId, localeDate($fields['created']));
                    // make name clickable, if url present
                    if (!empty($fields['url'])) {
                        $name = sprintf('<a href="%s">%s</a>',
                            $fields['url'], $fields['longname']);
                    } else {
                        $name = $fields['longname'];
                    }
                } else {
                    // not approved: no links, no content, just a notice
                    $created = localeDate($fields['created']);
                    $name = substr($fields['longname'], 0, 1) . '…' . substr($fields['longname'], -1); //first and last char
                    $content = '<span style="color:#aaa">' . _('com_not_approved') . '</span>';
                }

?>
    <div id="comment-<?= $commentId ?>">
      <span class="bracket">&nbsp;</span>
      <div class="comment-fields">
      <span class="comment-date"><?= $created ?></span>
      <span><strong><?= $name ?></strong> <?= _('com_says') ?></span>
      <div><?= $content ?></div>
      </div>
    </div>
<?php
            }
        }
        echo "  </div>\n\n";
    }
}


/**
 * Print the form for new comments.
 * @param string $postId Post ID the form belongs to.
 * @param string $postUrl URL for the post.
 */
function printFormNewComment($postId, $postUrl) {
    $actionUrl = '/' . _('lang') . '/comments.php';
?>
    <h4><?= _('com_your_comment') ?>:</h4>
    <form action="<?= $actionUrl ?>" method="post" accept-charset="UTF-8">
      <input type="text" name="longname" placeholder="<?= _('com_name') ?>" maxlength="32" onfocus="fieldFocus(this)" onblur="fieldCheck(this)"><span class="checkmark">✔</span><br>
      <input type="email" name="email" placeholder="<?= _('com_email') ?>" maxlength="64" onfocus="fieldFocus(this)" onblur="fieldCheck(this)"><span class="checkmark">✔</span><br>
      <input type="url" name="url" placeholder="<?= _('com_homepage') ?>" maxlength="255" onfocus="fieldFocus(this)" onblur="fieldCheck(this)"><span class="checkmark">✔</span><br>
      <input type="text" name="math" maxlength="32" placeholder="<?= _('com_activate_javascript') ?>" id="math" onfocus="fieldFocus(this)" onblur="fieldCheck(this)"><span class="checkmark">✔</span><br>
      <input type="hidden" name="result" id="result" value="99">
      <input type="hidden" name="postId" value="<?= $postId ?>">
      <input type="hidden" name="postUrl" value="<?= $postUrl ?>">
      <input type="text" name="check" placeholder="<?= _('com_leave_empty') ?>" value=""><br class="invisible">
      <textarea name="content" placeholder="<?= _('comment') ?>..." onfocus="fieldFocus(this)"></textarea><br>
      <input type="submit" name="submit" value="<?= _('com_submit') ?>">
    </form>

<?php
}


/**
 * Print the flattr link.
 * @param array $post The post the flattr link belongs to.
*/
function printFlattr($post) {
    $url = sprintf('%s/%s/%s/%s/',
        HOST, _('lang'), $post['postid'], $post['urltitle']);
    $title = str_replace(' ', '%20', $post['title']);
    $lang = _('lang_code');
    echo '    <p class="flattr"><a href="https://flattr.com/submit/auto?user_id=' . FLATTR_ID . '&amp;url=' . urlencode($url) . '&amp;title=' . $title . '&amp;description=Blog%20post&amp;language=' . $lang . '&amp;category=text" title="' . _('flattr_me') . '">flattr</a></p>' . "\n";
}


/**
 * Escape comment field.
 * Everything but code tags and convert line endings for HTML.
 * Also count if more opened than closed tags present, append one then.
 * @param string $str Comment field to be escaped.
 * @return string Escaped comment.
 */
function escapeComment($str) {
    // escape entities and control characters
    $search = array('&', '<', '>', "\t", "\r");
    $replace = array('&amp;', '&lt;', '&gt;', '&nbsp;&nbsp;', '');
    $str = str_replace($search, $replace, $str);
    // re-replace <code> tag
    $search = array('&lt;code&gt;', '&lt;/code&gt;');
    $replace = array('<code>', '</code>');
    $str = str_replace($search, $replace, $str);

    // if more open code tags than closed tags, insert a close tag
    if (substr_count($str, '<code>') > substr_count($str, '</code>')) {
        $str .= '</code>';
    }

    // delete more than two rows of empty lines
    while (substr_count($str, "\n\n\n") > 0)
        $str = str_replace("\n\n\n", "\n\n", $str);
    // transform new lines
    $str = str_replace("\n", "<br>\n", $str);

    return $str;
}

/**
 * Unescape comment in order to show it in comment edit mode in the admin panel.
 * @param string $str Comment field to be unescaped.
 * @return string Unescaped comment.
 */
function unescapeComment($str) {
    // unescape entities and control characters
    $search = array('&amp;', '&lt;', '&gt;', "<br>\n");
    $replace = array('&', '<', '>', "\n");
    $str = str_replace($search, $replace, $str);

    return $str;
}



/* ----- Latest Posts ----- */


/**
 * Get a list of the $limit latest post titles without this post ($postId).
 * @param PDO $db Database instance.
 * @param int $postId Post ID that should not be printed.
 * @param int $limit Number of posts to get.
 * @return array|boolean Latest posts without this one ($postId) or false on DB error.
*/
function getLatestPosts($db, $postId, $limit) {
    
    try {
        // SQL statement
        $query = (
            "SELECT postid, title, urltitle"
         . " FROM FemtoVPostoverview"
         . " WHERE postid != :postid"
         . "   AND lang = :lang"
         . " ORDER BY postid DESC"
         . " LIMIT :limit");
        $param = array(
            ':postid' => $postId,
            ':lang' => _('lang'),
            ':limit' => $limit);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        
        // save in $latestPosts, cleanup and return
        $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $latestPosts;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}


/**
 * Print a list of latest post titles as returned by getLatestPosts().
 * @param array $latestPosts The list of the latest post titles.
*/
function printLatestPosts($latestPosts) {
?>
    <div id="latest-posts">
      <h3><?= _('latest_posts') ?></h3>
      <ul>
<?php
   foreach ($latestPosts as $post) {
       printf('        <li><a href="/%s/%s/%s/">%s</a></li>' . "\n",
            _('lang'), $post['postid'], $post['urltitle'], $post['title']);
    }
?>
      </ul>
    </div>
<?php
}



/* ----- Sitemap.php ----- */


/**
 * Get post ids, titles, urlTitles and publication date (month/year).
 * @param PDO $db Database instance.
 * @return array|false The requested posts range or false if nothing found.
 */
function getPostsOverview($db) {
    
    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVPostoverview"
         . " WHERE lang = :lang"
         . " ORDER BY postid DESC");
        $param = array(':lang' => _('lang'));
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        
        // save in $posts, cleanup and return
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $posts;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}


/**
 * Print data delivered by postsOverview() transformed in a html list lines.
 * A list line is a clickable title and a date information (month/year).
 * @param array $postsOverview array(id, title, urlTitle, month/year).
 */
function printPostsOverview($postsOverview) {
    $list = '';
    foreach ($postsOverview as $post) {
        $list .= sprintf('        <li><a href="/%s/%s/%s/">%s</a> (%s)</li>',
            _('lang'), $post['postid'], $post['urltitle'], $post['title'], $post['monthyear']) . "\n";
    }
    echo $list;
}



/* ----- Database ----- */


/**
 * Connect to Database and set charset
 * @param boolean $adm Admin login (default: false).
 * @return PDO Database instance.
 */
function dbConnect($adm=false) {
    // prepare username, password string
    //  enable admin privileges on DB?
    if ($adm) {
        $username = DBUSER_ADM;
        $password = DBPASS_ADM;
    } else {
        $username = DBUSER;
        $password = DBPASS;
    }

    // prepare options
    $options = array(
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    // prepare Data Source Name
    // depending on databse driver
    switch (DBDRIVER) {
        case 'mysql':
            $dsn = sprintf('mysql:dbname=%s;host=%s;charset=utf8',
                DBNAME, DBHOST);
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
            break;
        case 'pgsql':
            if (DB_PGSQL_USE_SOCKET)
                $dsn = sprintf('pgsql:dbname=%s',
                    DBNAME);
            else
                $dsn = sprintf('pgsql:dbname=%s host=%s',
                    DBNAME, DBHOST);
            break;
        case 'sqlite':
            $dsn = sprintf('sqlite:%s',
                    DB_SQ3_FILE);
            // overwrite username, password
            $username = null;
            $password = null;
            break;
        default:
            die('No Database Driver selected.');
            break;
    }
    
    // create and return DB object
    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        exit(1);
    }
}


/**
 * Print an error message depending on DEBUG status.
 * No error details when in production mode (!DEBUG).
 * @param string $function Function name where the error occured.
 * @param mixed $e Error handling object.
 */
function dbError($function, $e) {
    if (DEBUG)
        printf("\nDamn. Database error.\n$function failed: %s\n", $e->getMessage());
    else
        printf("\n%s\n", _('db_error'));
}



/* ----- Errors ----- */


/**
 * Print the 404 message, wrapped in valid HTML.
 */
function print404() {
    printHeader(404);
    echo _('article_404') . "\n";
    printFooter();
}

?>
