<?php // vim:ai:et:sw=4:ts=4

namespace JK;

/**
 * Abstract class to make sitemaps.
 *
 * This class creates static XML files that are cached to disk.
 * These are retrieved with URLs like:
 *   sitemap.php
 *   sitemap.php?page=1
 *   sitemap.php?page=2
 *
 * This class will generate the sitemaps from callbacks that retrieve
 * database recordsets, and transform the rows into URLs.
 *
 * The assumption is that most CMS generate pages from from 
 * multiple tables.
 *
 * So you should define functions for each table, and each style
 * of URL, and put callbacks to them into one big array. See the documentation
 * for the getCallbackArray() method for details.
 *
 * @author johnk@riceball.com
 */
abstract class AbstractDatabaseSitemapBuilder {

    /**
     * Returns a path to a file that contains an index of sitemaps.
     */
    abstract protected function sitemapIndexPath();

    /**
     * Returns a path to a paginated sitemap. 
     * @param $page int
     */
    abstract protected function sitemapPagePath($page);

    /**
     * Returns this script's URL, sans parameters.
     */
    abstract protected function sitemapUrl();

    /**
     * Returns an array of arrays of callbacks, of the form:
     *
     * array( array( $database_callback, $url_callback ), ... )
     *
     * Where the database callback returns a PDOStatement, or an object
     * that has a fetch() method that works like PCOStatement.
     * 
     * The URL callback takes a row returned by fetch() and 
     * returns the canonical URL to a page on the website.
     *
     * The regenerateSitemap() method calls this method to
     * get all the callbacks, and runs all of them.  
     *
     * Note that, typically, the callbacks are callable
     * methods on this object, so the return value will look like this:
     * [ 
     *   [ [$this, 'tableA'], [$this, 'urlA'] ],
     *   [ [$this, 'tableB'], [$this, 'urlB'] ],
     *   ...
     * ]
     */
    abstract protected function getCallbackArray(); 

    /**
     * Loops over groups of callbacks that interact with the 
     * data source, and transform the returned rows into URLs
     * for inclusion into the sitemap.
     *
     * @return void
     */
    private function regenerateSitemap() {
        $page = 1;
        $count = 0;
        $urls = [];
        $pageurls = [];
        // loop over all the callbacks
        $callbackArray = $this->getCallbackArray();
        foreach($callbackArray as $callbacks) {
            $db_cb = $callbacks[0];
            $url_cb = $callbacks[1];
            // get a result set
            $stmt = call_user_func($db_cb);
            // loop over the result set
            while($row = $stmt->fetch()) {
                $urls[] = call_user_func($url_cb, $row);
                $count++;
                // emit a page after 50k urls
                if ($count==50000) {
                    $this->writeSitemapPage($page, $urls);
                    $pageurls[] = $this->sitemapUrl().'?page='.$page;
                    $page++;
                    $urls = [];
                    $count = 0;
                }
            }
        }
        // emit a page for the remaining urls
        if (count($urls) > 0) {
            $this->writeSitemapPage($page, $urls);
            $pageurls[] = $this->sitemapUrl().'?page='.$page;
        }
        $this->writeSitemapIndex($pageurls);
    }

    private function writeSitemapPage($page, $urlArray) {
        $path = $this->sitemapPagePath($page);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($urlArray as $url) {
            $xml .= '<url><loc>'.$url.'</loc></url>';
        }
        $xml .= '</urlset>';
        file_put_contents($path, $xml);
    }

    private function writeSitemapIndex($urlArray) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($urlArray as $url) {
            $xml .= '<sitemap><loc>'.$url.'</loc></sitemap>';
        }
        $xml .= '</sitemapindex>';
        file_put_contents($this->sitemapIndexPath(), $xml);
    }

    /**
     * Helper that caches the sitemap.
     */
    public function cachedSitemapPrinter($page) {
        if (file_exists($this->sitemapIndexPath())) {
            $stat = stat($this->sitemapIndexPath());
            $mtime = $stat['mtime'];
            $diff = time() - $mtime;
            if ($diff > 24*60*60) {
                $this->regenerateSitemap();
            }
        } else {
            $this->regenerateSitemap();
        }

        /*
         * Read the cached file, and spit it out.
         */
        $text = file_get_contents($this->sitemapPagePath($page));

        header("Content-type: application/xml");
        echo $text;
        exit();
    }

    /**
     * Helper to get the page query param.
     */
    public function getPageParameter() {
        $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT );
        if ($page == '') {
            $page = 'index';
        }
        return $page;
    }

    /*
     * Override this to alter the application behavior.
     */
    public function run() {
        $page = $this->getPageParameter();
        $this->cachedSitemapPrinter($page);
    }
}

