<?php

$Module = array( 'name' => 'xrow Video Module and Views' );

$ViewList = array();
$ViewList['download'] = array(
    'default_navigation_part' => 'ezcontentnavigationpart',
    'script' => 'download.php',
    'params' => array( 'ContentObjectID', 'ContentObjectAttributeID', 'Version', 'File' )
 );
$ViewList['upload'] = array(
    'script' => 'upload.php',
    'functions' => array( 'upload' ),
    'params' => array( 'AttributeID', 'Version', 'Language', 'Random' )
    );

$FunctionList = array();

$FunctionList['upload'] = array();

?>
