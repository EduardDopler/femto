<?php
/**
 * Check for updates.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */


require '../functions.php';

// get current version information
// either by cURL or file_get_contents
if (function_exists('curl_version')) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, UPDATE_SERVER_FILE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $current = curl_exec($curl);
    curl_close($curl);
} else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
    $current = @file_get_contents(UPDATE_SERVER_FILE);
} else {
    echo 'Sorry, PHP_allow_url_fopen and cURL disabled.';
    die();
}

// compare and return answer
if (!empty($_GET['use'])) {
    if (version_compare($_GET['use'], $current, '>='))
        echo 'ok';
    else
        echo 'new'; 
}

?>
