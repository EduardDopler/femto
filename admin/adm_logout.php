<?php
/**
 * Logout script.
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


// logout
session_unset();

printHeaderAdm(false, _('logged_out'));
echo "    <h4>" . _('bye') . "</h4>\n";
printFooterAdm();

?>