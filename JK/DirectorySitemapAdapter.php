<?php // vim:ai:et:sw=4:ts=4

namespace JK;

/**
 * Implements enough of the PDOStatement interface
 * to loop over a directory of files, returning
 * them as associative arrays.
 * 
 * Usage:
 * 
 *     $dsa = new DirectorySitemapAdapter( '/path/', '/.+\\.html/' );
 *     // $dsa will act like a PDOStatement with a result.
 *
 * The array format is:
 *
 * [ 'name' => 'the-file-name' ]
 */
class DirectorySitemapAdapter {
    function __construct($dir, $regex = null) {
        $this->dh = dir($dir);
        $this->regex = $regex;
    }

    function fetch() {
        while( $entry = $this->dh->read() ) {
            if ($this->regex === null || preg_match($this->regex, $entry)) {
                return [ 'name' => $entry ];
            }
        }
        // otherwise, we have finished reading files
        return false;
    }
}
