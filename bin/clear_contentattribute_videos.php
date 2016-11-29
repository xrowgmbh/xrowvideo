<?php 
set_time_limit ( 0 );
ini_set( "memory_limit", '3G' );
require 'autoload.php';
$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Video attribute update.\n\n"."Simple update script for Video attribute"),
                                     'use-session' => false) );
$script->startup();
$script->initialize();

#login xrow user
$adminUserID = 357217;
$user = eZUser::fetch( $adminUserID );
$user->loginCurrent();

$classIDs = array(59, 60);
$objectVideoAttributeIdentifier = 'file';
$dom = new DomDocument();
$cli->output("Script is start.");
foreach ($classIDs as $classID) {
    $objects = eZContentObject::fetchList( true, array('contentclass_id' => $classID) );
    foreach ($objects as $object) {
        $objectID = $object->attribute( 'id' );
        $classidentifier = $object->attribute( 'class_identifier' );
        $cli->output( "ObjectID: " . $objectID . "| Klasse: " . $classidentifier );

        $versions = $object->versions(true);
        foreach ( $versions as $version ) {
            $cli->output( "Version: " . $version->attribute('version') );
            $objectDataMap = $version->attribute('data_map');
            if ( isset( $objectDataMap[ $objectVideoAttributeIdentifier ] ) )
            {
                $objectVideoAttribute = $objectDataMap[ $objectVideoAttributeIdentifier ];
                if ( $objectVideoAttribute->attribute( 'has_content' ) )
                {
                    $video_xml_content = $objectVideoAttribute->DataText;
                    $dom->loadXML($video_xml_content);
                    $xpath = new DOMXpath($dom);

                    if ($classidentifier === 'file_video') {
                        $elements= $xpath->evaluate('/media/video/source[@mimetype="video/webm" or @mimetype="video/x-flv"]' );
                    } elseif ($classidentifier === 'file_audio') {
                        $elements= $xpath->evaluate('/media/audio/source[@mimetype="audio/ogg"]' );
                    }
                    foreach( $elements as $element ) {
                        $element->parentNode->removeChild($element);
                    }
                    $new_video_xml_content =  $dom->saveXML();
                    
                    if ( $objectVideoAttribute->attribute( 'data_type_string' ) == 'xrowvideo' )
                    {
                        $objectVideoAttribute->setAttribute( 'data_text', $new_video_xml_content );
                        $objectVideoAttribute->store();
                    }
                }
            }
        }
        eZContentCacheManager::clearContentCacheIfNeeded( $objectID );
    }
}
$cli->output("Script is done.");
$script->shutdown();
?>