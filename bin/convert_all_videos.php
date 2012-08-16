<?php

$cli->output( "This will add all xrowvideo files to the convertion queue.\nYou need to start the cronjob 'convertmedia' afterwards to start the convertion.");

$db = eZDB::instance();
$sql = "SELECT a.id, version
        FROM
            ezcontentobject_attribute a
        WHERE
            a.data_type_string = 'xrowvideo'";

$offset = 0;
$limit = 10000;
$ts = time();
while ( true )
{
    $result = $db->arrayQuery( $sql, array( 'limit' => $limit, 'offset' => $offset ) );
    if ( count( $result ) > 0 )
    {
        foreach( $result as $item )
        {
            $info = $item['id'] . '-' . $item['version'];
            $cli->output( $info );

            $insertSql = "INSERT INTO ezpending_actions (action,created,param) VALUES( 'xrow_convert_media', '$ts', '$info' )";
            $db->query( $insertSql );
        }
    }
    else
    {
        break;
    }
    $offset += $limit;
}

$cli->output( "Done." );