<?php

$Module = array( 'name' => 'xrow Video Module and Views' );

$ViewList = array();
$ViewList['embed'] = array(
    'default_navigation_part' => 'ezcontentnavigationpart',
    'script' => 'embed.php',
    'params' => array( 'ContentObjectID' )
 );
$ViewList['download'] = array(
    'default_navigation_part' => 'ezcontentnavigationpart',
    'script' => 'download.php',
    'params' => array( 'ContentObjectID', 'ContentObjectAttributeID', 'Version', 'File' )
 );
$ViewList['upload'] = array(
    'script' => 'upload.php',
    'params' => array( 'AttributeID', 'Version', 'Language', 'Random' )
);

$FunctionList = array();

?>