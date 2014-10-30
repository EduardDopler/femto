<?php
/**
 * "About Us" document.
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

// obscure email (a bit)
$search = array('@', '-', '.');
$replace = array('<span>&#x0040;</span>', '&#x002D;', '&#x002E;');
$adminEmail = str_replace($search, $replace, ADMIN_EMAIL);


printHeader();
?>
  <article>
    <h3><?= _('impressum') ?></h3>
    <div style="padding:1em; border:2px dashed #ccc; width:72%; margin:3em 0pt; max-width:20em;">
      <?= ADMIN_NAME ?><br>
	  <?= ADMIN_EMAIL ?><br>
	</div>

<?php
echo _('impressum_text') . "\n";

if (PIWIK) {
	echo '    <iframe frameborder="no" width="600px" height="200px" src="' . PIWIK_PATH . 'index.php?module=CoreAdminHome&action=optOut&language=' . _('lang') . '"></iframe>' . "\n";
}

echo '  </article>' . "\n";

printFooter();

?>
