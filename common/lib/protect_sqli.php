<?php


function array_map_recursive( $func, $arr ) {
    $newArr = array();
    foreach( $arr as $key => $value ) {
        $newArr[ $key ] = ( is_array( $value ) ? array_map_recursive( $func, $value ) : $func( $value ) );
    }
    return $newArr;
}
/*
 * Magic quotes existed only on old PHP installations.  Unconditionally
 * calling stripslashes() corrupts legitimate backslashes on modern PHP.
 *
 * This file deliberately does not attempt SQL or HTML sanitization.  SQL
 * protection belongs to bound parameters and HTML protection belongs to the
 * output context.
 */
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $_POST   = array_map_recursive('stripslashes', $_POST);
    $_GET    = array_map_recursive('stripslashes', $_GET);
    $_COOKIE = array_map_recursive('stripslashes', $_COOKIE);
}
