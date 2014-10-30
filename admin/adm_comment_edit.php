<?php
/**
 * Edit comments.
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

printHeaderAdm(true, _('comment'));

// data submitted?
if (empty($_GET['chkComment'])) {
    echo "    <h4>No data.</h4>\n";
} else {
    // database (with admin privileges)
    $db = dbConnect(true);
    
    // all checked comment IDs
    $checkedIds = $_GET['chkComment'];
    // if commentId is not an array (even if just one), transform it into one
    if (!is_array($checkedIds))
        $checkedIds = array($checkedIds);

    // check access violation (if one fails, all fail)
    foreach ($checkedIds as $commentId) {
        if (!checkAccessAllowed($db, 'comment', $commentId)) {
            printAccessViolation(true);
            die();
        }
    }

    // prepare IN() query for SQL statement
    // create an array with as many '?' as comments are checked
    $inQuery = implode(',', array_fill(0, count($checkedIds), '?'));

    try {
        // which action? (default: edit)
        $action = (!empty($_GET['action'])) ? $_GET['action'] : 'edit';
        // SQL statement
        switch ($action) {
            case 'approve':
                $query = (
                    "UPDATE FemtoComment"
                 . " SET approved = 'true'"
                 . " WHERE commentid IN ($inQuery)");
                break;
            case 'delete':
                // check access violation
                // authors (accesslevel 3) must not delete
                if ($_SESSION['accessLevel'] > 2) {
                    printAccessViolation(true);
                    die();
                }
                $query = (
                    "DELETE"
                 . " FROM FemtoComment"
                 . " WHERE commentid IN ($inQuery)");
                // mind the comcount entry in the corresponding post table
                // is updated by a DB trigger automatically
                break;
            case 'edit':
            default:
                $query = (
                    "SELECT *"
                 . " FROM FemtoComment"
                 . " WHERE commentid IN ($inQuery)"
                 . " ORDER BY commentid DESC");
                
                // prepare also a COUNT query for Edit Mode
                $queryCount = (
                    "SELECT COUNT(*)"
                 . " FROM FemtoComment"
                 . " WHERE commentid IN ($inQuery)");
                $stmtCount = $db->prepare($queryCount);
                // fill placeholders '?' with actual IDs
                foreach ($checkedIds as $k => $checkedId)
                    $stmtCount->bindValue(($k+1), $checkedId, PDO::PARAM_INT);

                $stmtCount->execute();
                break;
        }

        // prepare statement for all actions
        $stmt = $db->prepare($query);
        // fill placeholders '?' with actual IDs
        foreach ($checkedIds as $k => $checkedId)
            $stmt->bindValue(($k+1), $checkedId, PDO::PARAM_INT);

        $stmt->execute();

        // print number of approved/deleted rows 
        if ($action !== 'edit') {
            $resultLocale = _('result_comment_edit');
            $search = array('%action%', '%numSuccess%', '%numAll%');
            $replace = array(strtoupper($action), $stmt->rowCount(), count($checkedIds));
            $result = str_replace($search, $replace, $resultLocale);
            echo "    <h4>$result</h4>\n";
        } else {
            // Edit Mode
            // any results from COUNT query?
            if ($stmtCount->fetchColumn() > 0) {
                // yes, bind columns
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
                // print edit comment form(s)
?>
    <h4><?= _('edit_comment') ?></h4>
    <hr>
    <form action="adm_comment_update.php" method="post">
<?php
                while ($stmt->fetch(PDO::FETCH_BOUND)) {
                    // unescape content
                    $content = unescapeComment($content);
?>
      <input type="text" name="commentId[]" maxlength="10" value="<?= $commentId ?>" title="<?= _('do_not_change') ?>" readonly><span class="description"><?= _('field_commentid') ?></span><br>
      <input type="text" name="postId[]" maxlength="10" value="<?= $postId ?>" title="<?= _('changes_not_recommended') ?>" onclick="enableField(this)" readonly><span class="description"><?= _('field_postid') ?></span><br>
      <input type="text" name="lang[]" maxlength="2" value="<?= $lang ?>" title="<?= _('changes_not_recommended') ?>" onclick="enableField(this)" readonly><span class="description"><?= _('field_language') ?></span><br>
      <input type="text" name="created[]" maxlength="25" value="<?= $created ?>" title="YY-MM-DD HH:MM:SS"><span class="description"><?= _('field_created') ?></span><br>
      <input type="text" name="longname[]" maxlength="32" value="<?= $longname ?>"><span class="description"><?= _('field_name') ?></span><br>
      <input type="text" name="email[]" maxlength="64" value="<?= $email ?>"><span class="description"><?= _('field_email') ?></span><br>
      <input type="text" name="url[]" maxlength="255" value="<?= $url ?>"><span class="description"><?= _('field_url') ?></span><br>
      <input type="text" name="ip[]" maxlength="15" value="<?= $ip ?>"><span class="description">IP</span><br>
      <select name="approved[]" size="1">
        <option value="true"<?php if ($approved === 'true') echo ' selected' ?>><?= _('true') ?></option>
        <option value="false"<?php if ($approved === 'false') echo ' selected' ?>><?= _('false') ?></option>
      </select><span class="description"><?= _('field_approved') ?></span><br>
      <textarea name="content[]"><?= $content ?></textarea><span class="description"><?= _('field_content') ?></span><br>
      <hr>
<?php
                }
?>
      <input type="submit" name="edit" value="<?php if (count($checkedIds) > 1) echo _('edit_all'); else echo _('edit') ?>">
    </form>
<?php

            } else {
                // no rows returned
                echo "    <h4>No data.</h4>\n";
            }
        }
    } catch (PDOException $e) {
        dbError(__FUNCTION__, $e);
    }
}


$db = null;
printFooterAdm();

?>
