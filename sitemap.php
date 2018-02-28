<?php // vim:ai:et:sw=4:ts=4

include 'autoload.php';

use SFActiveSitemap;

// these defines should be in a config file
define( 'SF_CACHE_PATH', dirname(__FILE__).'/tmp' );
define( 'DB_HOSTNAME', 'localhost');
define( 'DB_DATABASE', 'la_indymedia_org');
define( 'DB_USERNAME', 'la_indymedia');
define( 'DB_PASSWORD', 'la_indymedia');

mkdir(SF_CACHE_PATH, 0777, true);

$sitemap = new SFActiveSitemap();
$sitemap->run();
