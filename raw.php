<?php
/**
 * Print raw posts data.
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

/**
 * Get and print the posts listed in the POST values.
 */
if (isset($_POST['start'], $_POST['count']) and
    is_numeric($_POST['start']) and
    is_numeric($_POST['count']) and
    $_POST['start'] > 0 and
    $_POST['count'] > 0) {
        // init and connect to DB
        $db = dbConnect();
        // get posts
        $posts = getPosts($db, $_POST['start'], $_POST['count']);
        $clickableTitle = true;
        $withCommentsAbstract = true;
        // if successful
        if ($posts) {
            // print all posts returned
            foreach ($posts as $post) {
                printPost($post, $clickableTitle, $withCommentsAbstract);
            }
            printMoreButton();
        } else {
            echo _('the_end');
        }
}

?>
