<?php
/**
 * Handle comments sent to posts.
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


// POST data sent to script? Then process. Else void.
if (isset($_POST['longname'],
    $_POST['email'],
    $_POST['math'],
    $_POST['result'],
    $_POST['postId'],
    $_POST['postUrl'],
    $_POST['content'])) {
        return processComment();
}


/**
 * Check comment via spamcheck and formatcheck.
 * Route to Error printing and exit if any of those returns false.
 * Else runs saveComment.
 */
function processComment() {
    if (!spamcheck()) {
        printCommentResult('err_spam');
        return false;
    }

    if (!formatcheck()) {
        printCommentResult('err_format');
        return false;
    }

    // everything fine so far
    // init and connect to DB
    $db = dbConnect();
    // save comment to DB and inform admin
    $commentId = saveComment($db);
    // saved?
    if (!$commentId) {
        $db = null;
        printCommentResult('err_db');
        return false;
    } else {
        printCommentResult('saved', $commentId);
        mailCommentToAdmin($db, $commentId);
        $db = null;
        return true;
    };
}


/**
 * Spam detection.
 * See code comments.
 * @return boolean Whether check passed.
 */
function spamcheck() {
    // spam check 1: field "check" must be empty
    if ($_POST['check'] !== '')
        return false;
    // spam check 2: fields "math" and "result" must equal
    if ($_POST['math'] !== $_POST['result'])
        return false;
    // spam check 3: field "result" must not be "99"
    if ($_POST['result'] === '99')
        return false;

    return true;
}


/**
 * Check posted name, e-mail and content for minimum length.
 * To simplify this test, it only checks the length.
 * The maximum length is checked to prevent high load on malicious extra large POST data passed to escaping functions.
 * @return boolean Whether check passed.
 */
function formatcheck() {
    // name check
    if (strlen($_POST['longname']) < 2 or
        strlen($_POST['longname']) > 32)
        return false;
    // e-mail check
    if (strlen($_POST['email']) < 6 or
        strlen($_POST['email']) > 64)
        return false;
    // content check
    //  do not check max length here because if it is high, we have double load; here and later when escaping
    if (strlen($_POST['content']) < 1)
        return false;

    return true;
}


/**
 * Save new comment to database.
 * Escape input first, then write to DB, then update post info.
 * @param PDO $db Database instance.
 * @return int|boolean ID of new comment or false on error.
 */
function saveComment($db) {
    // SQL statement
    try {
        $query = (
            "INSERT INTO FemtoComment"
         . "   (postid, created, longname, email, url, content, lang, ip)"
         . " VALUES"
         . "   (:postid, CURRENT_TIMESTAMP, :longname, :email, :url, :content, :lang, :ip)");
        $param = array(
            ':postid' => $_POST['postId'],
            ':longname' => escapeComment($_POST['longname']),
            ':email' => escapeComment($_POST['email']),
            ':url' => (empty($_POST['url'])) ? null : escapeComment($_POST['url']),
            ':content' => escapeComment($_POST['content']),
            ':lang' => _('lang'),
            ':ip' => preg_replace('#(?:\.\d+){2}$#', '.x.x', $_SERVER['REMOTE_ADDR']));
        $stmt = $db->prepare($query);
        $stmt->execute($param);

        // mind the comcount entry in the corresponding post table
        // is updated by a DB trigger automatically
        
        if ($stmt->rowCount() > 0) {
            // get the commentId we just inserted
            //  PostgreSQL requires the sequence name, MySQL does not
            $seq = (DBDRIVER === 'pgsql') ? 'femtocomment_commentid_seq' : null;
            $commentId = $db->lastInsertId($seq);
            // return commentId of new comment
            return $commentId;
        } else {
            // something went wrong with database
            return false;
        }

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}


/**
 * Inform admin about new comment, send all comment data.
 * Get this data from DB to be sure that data is stored.
 * @param PDO $db Database instance.
 * @param int $commentId Comment ID (does not need to be escaped, inhereted from database).
 * @return boolean Return value of PHP's mail function.
 */
function mailCommentToAdmin($db, $commentId) {
    try {
        // SQL statement
        $query = (
            "SELECT *"
         . " FROM FemtoVMailComment"
         . " WHERE commentid = :commentid");
        $param = array(':commentid' => $commentId);
        $stmt = $db->prepare($query);
        $stmt->execute($param);
        $stmt->bindColumn('commentid', $commentId, PDO::PARAM_INT);
        $stmt->bindColumn('postid', $postId, PDO::PARAM_INT);
        $stmt->bindColumn('created', $created);
        $stmt->bindColumn('longname', $longname);
        $stmt->bindColumn('email', $email);
        $stmt->bindColumn('url', $url);
        $stmt->bindColumn('content', $content);
        $stmt->bindColumn('ip', $ip);
        $stmt->bindColumn('title', $title);

        $subject = "New Comment in your Blog";
        $str = "New Comment in your Blog.\n\n\n";
        $comment = $stmt->fetch(PDO::FETCH_BOUND);

        $str .= "*Post:* (ID: $postId) $title\n"
              . "*Date:* $created\n"
              . "*Name:* $longname\n"
              . "*Email:* $email\n"
              . "*URL:* $url\n"
              . "*IP:* " . substr($ip, 0, 6) . "...\n"
              . "*Comment:* (ID: $commentId)\n$content\n\n\n";
        // add admin links
        $adminUrl = HOST . '/admin/adm_comment_edit.php?action=%s&chkComment=' . $commentId;
        $str .= sprintf("*Approve:* $adminUrl\n", "approve")
              . sprintf("*Edit:* $adminUrl\n", "edit")
              . sprintf("*Delete:* $adminUrl\n", "delete");

        // wrap lines at 70 chars
        $lines = explode("\n", $str);
        $body = '';
        foreach ($lines as $line) {
            $line = wordwrap($line, 70);
            $body .= $line . "\n";
        }

        // mail now
        return mail(ADMIN_EMAIL, $subject, $body);

    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
        return false;
    }
}


/**
 * Generate feedback page for operations depending on success/error.
 * @param string $type Identifier for type of message to be printed.
 * @param mixed $commentId ID of inserted comment to be printed if comment was saved.
 * @return boolean Function finished without errors.
 */
function printCommentResult($type, $commentId=null) {
    printHeader();
    echo "  <article>\n";

    // print success or error message
    switch ($type) {
        case 'err_spam':
            echo _('comment_result_spam');
            break;
        case 'err_format':
            echo _('comment_result_format');
            break;
        case 'err_db':
            echo _('comment_result_internal_error');
            break;
        case 'saved':
            echo _('comment_result_saved');
    }
    echo "\n";

    // Back-link to post, or comment if successful
    if (!empty($commentId)) {
        printf('    <p><a href="%s#comment-%s">%s</a></p>' . "\n",
            $_POST['postUrl'], $commentId, _('back_to_comment'));
    } else {
        echo _('click_back_button');
    }

    echo "  </article>\n";
    printFooter();

    return true;
}

?>