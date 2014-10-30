<?php
/**
 * Index file.
 *
 * Check if user requested a specific post ID (via $_GET['pid']).
 * If yes, serve that one. Else run Index View mode.
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

// single view or index view
if (isset($_GET['pid']) and
	strlen($_GET['pid']) <= 11 and
    is_numeric($_GET['pid']) and
    $_GET['pid'] > 1) {
        singleview($_GET['pid']);
} else {
    indexview();
}


?>
