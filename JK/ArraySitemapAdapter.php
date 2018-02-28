<?php // vim:ai:et:sw=4:ts=4

namespace JK;

/**
 * Implements enough of the PDOStatement interface
 * to loop over an array of strings, returning
 * them as associative arrays.
 * 
 * The array format is:
 *
 * [ 'name' => 'the-file-name' ]
 */
class ArraySitemapAdapter {
    function __construct($array) {
        $this->array = array_merge($array, []);
    }

    function fetch() {
        $value = array_pop($this->array);
        if ($value == NULL) {
            return false;
        }
        return [ 'name' => $value ];
    }
}
