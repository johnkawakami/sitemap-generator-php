<?php // vim:ai:et:sw=4:ts=4

use JK\AbstractDatabaseSitemapBuilder;
use JK\DirectorySitemapAdapter;
use JK\ArraySitemapAdapter;

class SFActiveSitemap extends AbstractDatabaseSitemapBuilder {
    function __construct() {
        $this->url = 'http://la.indymedia.org/';
        $this->cachePath = SF_CACHE_PATH.'/sitemap/';
        $this->dsn = 'mysql:dbname='.DB_DATABASE.';host='.DB_HOSTNAME;
        $this->dbPassword = DB_PASSWORD;
        $this->dbUsername = DB_USERNAME;
        $this->myUrl = $this->url.'sitemap.php';
        mkdir($this->cachePath, 0777, true);
    }

    function sitemapUrl() {
        return $this->myUrl;
    }

    function sitemapIndexPath() {
        return $this->sitemapPagePath('index');
    }

    function sitemapPagePath($page) {
        return $this->cachePath.'sitemap_'.$page.'.xml';
    }

    function getCallbackArray() {
        return [
            [ [ $this, 'webcast' ], [ $this, 'webcastUrl' ] ],
            [ [ $this, 'calendar' ], [ $this, 'calendarUrl' ] ],
            [ [ $this, 'files' ], [ $this, 'filesUrl' ] ],
            [ [ $this, 'listOfNames' ], [ $this, 'namesUrl' ] ],
        ];
    }

    function webcast() {
        $pdo = new PDO($this->dsn, $this->dbUsername, $this->dbPassword);
        $stmt = $pdo->prepare('SELECT id,created FROM webcast WHERE display<>"f" and parent_id=0');
        $stmt->execute();
        return $stmt;
    }

    function webcastUrl($row) {
        $created = $row['created'];
        $y = substr($created,0,4);
        $m = substr($created,5,2);
        $id = $row['id'];
        return $this->url."news/$y/$m/$id.php";
    }

    function calendar() {
        $pdo = new PDO($this->dsn, $this->dbUsername, $this->dbPassword);
        $stmt = $pdo->prepare('SELECT event_id FROM event');
        $stmt->execute();
        return $stmt;
    }

    function calendarUrl($row) {
        return $this->url.'calendar/event_display_detail.php?event_id='.$row['event_id'];
    }

    function files() {
        return new DirectorySitemapAdapter('/home/johnk/projects/sitemap-generator-php/test/files/', '/^.+\\.html$/');
    }

    function filesUrl($row) {
        return 'http://example.com/'.$row['name'];
    }

    function listOfNames() {
        return new ArraySitemapAdapter( ['a', 'b', 'c'] );
    }

    function namesUrl($row) {
        return $row['name'];
    }
}
