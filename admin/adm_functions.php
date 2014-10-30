<?php
/**
 * Administration functions.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */


// if PHP version is too low for built-in password_* functions,
// use compatibility pack from Anthony Ferrara <ircmaxell@php.net>
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    require 'password_compat.php';
}


/* ===== FUNCTIONS ===== */


/* ----- HEADER/FOOTER ----- */


/**
 * Print admin header.
 * Everything that comes before the article(s). E.g. the <head>, the header, the navigation.
 * @param boolean $printNav Whether navigation should be printed as well.
 * @param string|null $h3 If non-empty, add a H3 heading.
 */
function printHeaderAdm($printNav=true, $h3=null) {
    // link prefix for locale selection
    $link_prefix = '/' . _('lang') . '/';
?><!DOCTYPE html>
<html lang="<?= _('lang') ?>">
<head>
  <meta charset="utf-8">
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>femto Admin Panel — <?= BLOG_TITLE ?></title>
  <link rel="stylesheet" href="femto-admin-<?= CSS_VER ?>.min.css">
  <link rel="icon" sizes="196x196" href="/favicon-196-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="/favicon-196-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="76x76" href="/favicon-76-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="120x120" href="/favicon-120-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="152x152" href="/favicon-152-precomposed.png">
  <script src="femto-admin-<?= JS_VER ?>.min.js" async defer></script>
</head>

<body onload="keepAlive(true)<?= ($h3 == _('logged_in')) ? ' && checkUpdate(\'femto_version.php?use=' . FEMTO_VER . '\')"' : '' ?>">
  <header>
    <h1><a href="<?= $link_prefix ?>"><?= BLOG_TITLE ?></a></h1>
<?php
    if ($printNav) { ?>
    <nav>
      <ul>
        <li><a href="adm_posts.php" class="color1">»&nbsp;<?= _('posts') ?></a></li>
        <li><a href="adm_post_new.php" class="color2">»&nbsp;<?= _('new_post') ?></a></li>
        <li><a href="adm_comments.php" class="color3">»&nbsp;<?= _('comments') ?></a></li>
        <li><a href="adm_authors.php" class="color4">»&nbsp;<?= _('authors') ?></a></li>
        <li><a href="adm_logout.php" class="color5">»&nbsp;<?= _('logout') ?></a></li>
      </ul>
    </nav>
<?php } ?>
  </header>

  <main>
  <article>
<?php
    if (!empty($h3)) {
        echo "    <h3>$h3</h3>\n";
    }
}


/**
 * Print footer.
 * Everything that comes below the article(s). E.g. the footer itself and Piwik.
 */
function printFooterAdm() {
    // link to other language in footer
    // remove language info from current uri first
    $uriQuery = preg_replace('/l=[[:alpha:]]{2}/', '', $_SERVER['QUERY_STRING']);
    $navOtherLang = sprintf('/%s%s?%s', 
      _('other_lang'), $_SERVER['SCRIPT_NAME'], $uriQuery);
?>
  </article>
  </main>

  <footer>
    <p><?= (!empty($_SESSION['username'])) ? str_replace(array('%username%', '%accesslevel%'), array($_SESSION['username'], _('accesslevel_' . $_SESSION['accessLevel'])), _('logged_in_as')) : '' ?></p>
<?php
    if (!SINGLE_LANGUAGE) {
?>
    <p id="otherlang"><a href="<?= $navOtherLang ?>" class="color4">→ <?= _('other_lang_long') ?> version</a></p>
<?php
    }
?>
    <p id="femto"><a href="<?= _('lang') ?>/femto/">femto blog system</a><br> by Eduard Dopler</p>
  </footer>

  <div style="display:none">
    <!-- JS locale strings -->
    <p id="jsConfirmLeave"><?= _('confirmLeave') ?></p>
    <p id="jsDeleteRows"><?= _('confirm_delete_rows') ?></p>
    <p id="jsDeletePost"><?= _('confirm_delete_post') ?></p>
    <p id="jsDeleteAuthor"><?= _('confirm_delete_author') ?></p>
    <p id="jsResetPw"><?= _('confirm_reset_pw') ?></p>
    <p id="jsBlockAuthor"><?= _('confirm_block_author') ?></p>
    <p id="jsEnableField"><?= _('enable_field') ?></p>
    <p id="jsLatinCharsOnly"><?= _('latin_chars_only') ?></p>
    <p id="jsUpdateChecking"><?= _('update_checking') ?></p>
    <p id="jsUpdate_new"><?= _('update_available') ?></p>
    <p id="jsUpdate_critical"><?= _('update_available_critical') ?></p>
    <p id="jsUpdate_ok"><?= _('no_update_available') ?></p>
  </div>

</body>
</html>
<?php
}


/**
 * Print the login form.
 */
function printFormLogin() {
?>
    <h4>Login</h4>
    <form action="index.php" method="post">
      <input type="text" name="username" maxlength="24" placeholder="username" required autofocus><br>
      <input type="password" name="password" maxlength="24" placeholder="password" required><br>
      <input type="submit" name="submit" value="<?= _('login') ?>" onclick="loginWait(this)">
<?php
}


/**
 * Print a welcome message to logged in users.
 * @param string $name Name of user.
 * @param string $lastLogin Last login time of this user.
 */
function printHello($name, $lastLogin) {
    printHeaderAdm(true, _('logged_in'));
    
    printf("    <h4>%s %s.</h4>\n", _('hello'), $name);
    // print last login date, only if non-empty
    if (!empty($lastLogin)) {
        printf('    <p class="hint">%s: %s</p>' . "\n",
            _('field_lastlogin'), $lastLogin);
    }
}


/**
 * Print the update check box.
 */
function printUpdateCheck() {
?>
    <p class="hint">Update Check: <span id="updateCheckResult">Loading…</span></p>
<?php
}



/* ----- LOGIN/ACCESSLEVELS ----- */


/**
 * Check Login credentials against database data.
 * @param PDO $db Database instance.
 * @param string $postUser Username to be checked.
 * @param string $postPass Password to be checked.
 * @return array|boolean User data from database in order to be stored in SESSION or false on failed login.
 */
function checkLogin($db, $postUser, $postPass) {
    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVAdmLogin"
         . " WHERE username = :username");
        $param = array(':username' => $postUser);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        $stmt->bindColumn('authorid', $authorId, PDO::PARAM_INT);
        $stmt->bindColumn('username', $username);
        $stmt->bindColumn('passhash', $passHash);
        $stmt->bindColumn('longname', $longname);
        $stmt->bindColumn('accesslevel', $accessLevel, PDO::PARAM_INT);
        $stmt->bindColumn('lastlogin', $lastLogin);
        $user = $stmt->fetch(PDO::FETCH_BOUND);
        $stmt->closeCursor();

        // check password
        if (password_verify($postPass, $passHash)) {
            // correct, create and return array for SESSION data
            $userData = array(
                'authorId' => $authorId,
                'username' => $username,
                'longname' => $longname,
                'accessLevel' => $accessLevel,
                'lastLogin' => $lastLogin);
            // sleep for 1 seconds to reduce possible bruteforce attack rate (a little bit)
            sleep(1);
            return $userData;
        } else {
            // sleep longer for same reasons
            sleep(3);
            return false;
        }

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


/**
 * Check whether current session user is allowed to access this post or comment.
 * Users with access levels >2 have to be authors of the requested post or the post the comments refer to.
 * @param PDO $db Database instance.
 * @param string $type Type of requested content. Must be 'post' or 'comment'.
 * @param mixed $id Post or comment ID which is requested.
 * @return boolean Whether access is allowed.
 */
function checkAccessAllowed($db, $type, $id) {
    // allow everything for Admins/Mods
    if ($_SESSION['accessLevel'] <= 2)
        return true;

    // other authors can access only their own posts and comments referring to them
    switch ($type) {
        case 'post':
            $query = (
                "SELECT COUNT(*)"
             . " FROM FemtoPost"
             . " WHERE authorid = :authorid"
             . "   AND postid = :id");
            break;

        case 'comment':
            $query = (
                "SELECT COUNT(*)"
             . " FROM FemtoComment"
             . "   JOIN FemtoPost ON (FemtoComment.postid = FemtoPost.postid)"
             . " WHERE authorid = :authorid"
             . "   AND commentid = :id");
            break;
    }
    $param = array(
        ':authorid' => $_SESSION['authorId'],
        ':id' => $id);
    // apply
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        
        // lines return? grant access?
        if ($stmt->fetchColumn() > 0)
            return true;
        else
            return false;

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


/**
 * Check if $str consists of latin chars only.
 * @param string $str String to be checked.
 * @return boolean Whether string is latin-char-only.
 */
function isLatin($str) {
    // remove all non-latin chars and check if string is altered
    if (mb_strlen($str) === mb_strlen(preg_replace('/[^a-zA-Z0-9]+/', '', $str)))
        return true;
    else
        return false;
}


/**
 * Create a random 10-digit ASCII password.
 * @return string Random password.
 */
function createRndPw() {
    // chars for password without some ambiguous ones
    $allowedChars = 'ABCDEFGHKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!?#$%&+@()=';
    $len = strlen($allowedChars);

    // create 10 digit password
    $pw = '';
    for ($i=0; $i < 10; $i++) {
        $rand = mt_rand(0, $len);
        $pw .= substr($allowedChars, $rand, 1);
    }

    return $pw;
}


/**
 * Update data of an author in the database.
 * @param PDO $db Database instance.
 * @param int $authorId Author ID of the author to be updated.
 * @param string $action Action to be performed (e.g. 'rename', 'lastlogin').
 * @param string|null $data New value of the action, if needed.
 */
function updateAuthor($db, $authorId, $action, $data=null) {
    // SQL statement
    // query template
    $query = (
        "UPDATE FemtoAuthor"
     . " SET %field%"
     . " WHERE authorid = :authorid");
    $param = array(':authorid' => $authorId);

    // which action?
    switch ($action) {
        case 'lastlogin':
            $query = str_replace('%field%', 'lastlogin = CURRENT_TIMESTAMP', $query);
            break;

        case 'rename':
            $query = str_replace('%field%', 'longname = :longname', $query);
            $param += array(':longname' => $data);
            break;
        
        case 'pwreset':
            // add accesslevel check if sessions does not belong to an admin
            if ($_SESSION['accessLevel'] !== 1)
                $query .=  " AND accesslevel > '1'";
            // no break!
        case 'rehash':
        case 'pwchange':
            $query = str_replace('%field%', 'passhash = :passhash', $query);
            $passhash = password_hash($data, PASSWORD_BCRYPT, ["cost" => 10]);
            $param += array(':passhash' => $passhash);
            break;

        case 'block':
            $query = str_replace('%field%', "blocked = :blocked", $query);
            $param += array(':blocked' => $data);
            // add accesslevel check, as admins cannot be blocked
            $query .=  " AND accesslevel != '1'";
    }
    // apply
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        // return result
        return $stmt->rowCount();

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


/**
 * Send an email to the first author, informing him/her about an event.
 * @param string $subject Email subject.
 * @param string $text Email body.
 */
function mailEventToAdmin($subject='Your Blog', $text) {
    // wrap lines at 70 chars
    $lines = explode("\n", $text);
    $body = '';
    foreach ($lines as $line) {
        $line = wordwrap($line, 70);
        $body .= $line . "\n";
    }

    // mail now
    return mail(ADMIN_EMAIL, $subject, $body);
}



/* ----- FEED/SITEMAP ----- */


/**
 * Get data of the latest (or all) posts for feed and sitemap creation.
 * @param PDO $db Database instance.
 * @param string $lang Language of posts to get. If null, get all.
 * @param int $limit Number of posts to get. If null, get 100.
 * @return array|boolean Array of posts or false on error.
 */
function getPostMetadata($db, $lang=null, $limit=100) {
    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVPostmetadata"
         . "   "
         . " ORDER BY created DESC"
         . " LIMIT :limit");
        $param = array(':limit' => $limit);
        // $lang and/or $limit set? add params then
        if (!empty($lang)) {
            $queryAdd = " WHERE lang = :lang";
            $query = str_replace('   ', $queryAdd, $query);
            $param += array(':lang' => $lang);
        }
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
 * Create Atom Feed.
 * @param array $feedData Array of posts returned by getPostMetadata().
 * @return boolean|string Whether creation went well or a string containing a warning.
 */
function createFeed($feedData) {
    // xml head
    //  use date and lang of last post (index=0)
    $feedLang = $feedData[0]['lang'];
    $feedLastUpdate = new DateTime($feedData[0]['modified']);
    $feedLastUpdate = $feedLastUpdate->format(DateTime::ATOM);
    $outputHead = (
        '<?xml version="1.0" encoding="utf-8"?>' . "\n"
      . '<feed xmlns="http://www.w3.org/2005/Atom"  xml:lang="' . $feedLang . '">' . "\n"
      . '  <author>' . "\n"
      . '    <name>' . FEED_MAIN_AUTHOR . '</name>' . "\n"
      . '  </author>' . "\n"
      . '  <title>' . BLOG_TITLE . ' (' . $feedLang . ')</title>' . "\n"
      . '  <subtitle>' . BLOG_SUBTITLE . '</subtitle>' . "\n"
      . '  <id>' . HOST . '/' . $feedLang . '/</id>' . "\n"
      . '  <link rel="alternate" href="' . HOST . '/' . '" />' . "\n"
      . '  <link rel="self" href="' . HOST . '/atom_' . $feedLang . '.xml" />' . "\n"
      . '  <icon>' . HOST . '/favicon-feed.png</icon>' . "\n"
      . '  <updated>' . $feedLastUpdate . '</updated>' . "\n");

    // feed entries
    $outputEntries = '';
    foreach ($feedData as $entry) {
        // get content and encode some entities
        $content = $entry['content'];
        $search = array('<br>', '&', '<', '>', "\t", "\r");
        $replace = array('<br />', '&amp;', '&lt;', '&gt;', '&amp;nbsp;&amp;nbsp;', '');
        $content = str_replace($search, $replace, $content);
        // build Atom datetime
        $modified = new DateTime($entry['modified']);
        $modified = $modified->format(DateTime::ATOM);
        // build post URLs
        $postUrlId = HOST . '/' . $entry['lang'] . '/' . $entry['postid'] . '/';
        $postUrlFull = $postUrlId . $entry['urltitle'] . '/';
        // add to output
        $outputEntries .= (
            '  <entry>' . "\n"
          . '    <title>' . $entry['title'] . '</title>' . "\n"
          . '    <author>' . "\n"
          . '      <name>' . $entry['author'] . '</name>' . "\n"
          . '    </author>' . "\n"
          . '    <link href="' . $postUrlFull . '" />' . "\n"
          . '    <id>' . $postUrlId . '</id>' . "\n"
          . '    <updated>' . $modified . '</updated>' . "\n"
          . '    <content type="html">' . $content . '</content>' . "\n"
          . '  </entry>' . "\n");
    }
    // concat output
    $output = $outputHead . $outputEntries . '</feed>';

    // write feed file
    $filename = '../atom_' . $feedLang . '.xml';
    if ($f = @fopen($filename, 'wb')) {
        $bytesWritten = fwrite($f, $output);
        fclose($f);

        // compare expected file size with actual size
        if ($bytesWritten === strlen($output)) {
            return true;
        } else {
            return sprintf('[WARN] New feed file has not expected length (offset: %s).',
                $bytesWritten - strlen($output));
        }

    } else {
        printf("    %s\n", _('fopen_error'));
    }
}



/* ----- Sitemap.xml ----- */


/**
 * Create a sitemap.xml for SEO.
 * @param array $posts Array of posts returned by getPostMetadata().
 * @return boolean|string Whether creation went well or a string containing a warning.
 */
function createSitemap($posts) {
    // read sitemap static content from template
    $outputStatic = file_get_contents('sitemap_header.xml');
    // replace variables e.g. HOST
    $search = array(
        '%HOST%',
        '%NAV_LINK_1%',
        '%NAV_LINK_2%',
        '%LANG_DEFAULT%',
        '%LANG_2%');
    $replace = array(
        HOST,
        NAV_LINK_1,
        NAV_LINK_2,
        LANG_DEFAULT,
        LANG_2);
    // in single language mode, insert default language two times
    if (SINGLE_LANGUAGE)
        $replace[4] = LANG_DEFAULT;
    $outputStatic = str_replace($search, $replace, $outputStatic);

    // feed entries
    $outputPosts = '';
    foreach ($posts as $post) {
        $outputPosts .= (
            '  <url>' ."\n"
          . '    <loc>' . HOST . '/' . $post['lang'] . '/' . $post['postid'] . '/' . $post['urltitle'] . '/</loc>' . "\n"
          . '    <priority>' . SITEMAP_POST_PRIORITY . '</priority>' . "\n"
          . '  </url>' . "\n");
    }

    // concat output
    $output = $outputStatic . $outputPosts . '</urlset>';

    // write sitemap.xml
    if ($f = @fopen('../sitemap.xml', 'wb')) {
        $bytesWritten = fwrite($f, $output);
        fclose($f);

        // compare expected file size with actual size
        if ($bytesWritten === strlen($output)) {
            return true;
        } else {
            return sprintf('[WARN] New feed file has not expected length (offset: %s).',
                $bytesWritten - strlen($output));
        }

    } else {
        printf("    %s\n", _('fopen_error'));
    }
}



/* ----- POSTING ----- */


/**
 * Print text tools for post editing.
 */
function printTextTools() {
?>
      <div class="tools">
        <input type="button" value="h4" accesskey="h" title="key: h" onclick="insertTag(this, false, true)">
        <input type="button" value="p" accesskey="p" title="key: p" onclick="insertTag(this, false, true)">
        <input type="button" value="em" accesskey="e" title="key: e" onclick="insertTag(this)">
        <input type="button" value="strong" accesskey="s" title="key: s" onclick="insertTag(this)">
        <input type="button" value="code" accesskey="c" title="key: c" onclick="insertTag(this)">
        <input type="button" value="pre" accesskey="r" title="key: r" onclick="insertTag(this, false, true)">
        <input type="button" value="ul" accesskey="u" title="key: u" onclick="insertTag(this, false, true)">
        <input type="button" value="ol" accesskey="o" title="key: o" onclick="insertTag(this, false, true)">
        <input type="button" value="li" accesskey="l" title="key: l" onclick="insertTag(this, false, true)">
        <input type="button" value="br" accesskey="b" title="key: b" onclick="insertTag(this, true, true)">
        <input type="button" value="img" accesskey="i" title="key: i" onclick="insertTag(this, true)">
        <input type="button" value="a" accesskey="a" title="key: a" onclick="insertTag(this)">
      </div>
<?php
}


/**
 * Escape field string.
 * Escape all HTML entities, so the field can be written to the DB.
 * Used for the title string of posts.
 * @param string $str String to be escaped.
 * @return string Escaped string.
 */
function escapeField($str) {
    // escape entities and control characters
    $search = array('&', '<', '>', "\t", "\r");
    $replace = array('&amp;', '&lt;', '&gt;', '&nbsp;&nbsp;', '');
    $str = str_replace($search, $replace, $str);

    return $str;
}



/* ----- ERRORS ----- */


/**
 * Print the 403 message, wrapped in valid HTML.
 */
function print403() {
    printHeaderAdm(false, '403 Forbidden');
    printFormLogin();
    printFooterAdm();
}


/**
 * Print an access violation message, wrapped in valid HTML.
 * @param boolean $noHeader Whether the HTML (admin) header should be prepended.
 */
function printAccessViolation($noHeader=false) {
    // if no HTML header requested, use H4 heading
    if ($noHeader)
        printf("    <h4>403 %s</h4>\n", _('access_violation'));
    else
        printHeaderAdm(true, '403 ' . _('access_violation'));
    
    printf('    <p>%s</p>' . "\n",
        _('sorry_not_allowed'));
    printf('    <a href="javascript:history.back()">» %s</a>' . "\n",
        _('back'));
    printFooterAdm();
}

?>
