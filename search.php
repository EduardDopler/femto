<?php
/**
 * Search page.
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

printHeader();

?>
  <article>
    <h3><?= _('search') ?></h3>
    <p><?= str_replace('%search_engine%', SEARCH_ENGINE, _('search_intro')) ?></p>
<?php
    if (SEARCH_ENGINE === 'DuckDuckGo') {
        printf('    <iframe src="https://duckduckgo.com/search.html?site=%s&prefill=%s&focus=yes" style="overflow:hidden;margin:0;padding:0;width:408px;height:40px;" frameborder="0"></iframe>' . "\n",
            $_SERVER['SERVER_NAME'], _('search_term'));
    } else {
        echo "    Search disabled.\n";
    }
?>
  </article>
<?php

printFooter();

?>
