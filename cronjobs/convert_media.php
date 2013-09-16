<?php

// php -d memory_limit=512M runcronjobs.php convertmedia

$cli->output( "Start processing media conversion" );

$contentObjects = array();
$db = eZDB::instance();

$offset = 0;
$limit = 50;

$ini = eZINI::instance( 'xrowvideo.ini' );
$videoConvertArray = $ini->variable( 'xrowVideoSettings', 'ConvertVideoFiles' );
foreach ( $videoConvertArray as $key )
{
    $convertSettings['video'][$key] = array( 'options' => $ini->variable( $key, 'Options' ),
                                             'program' => $ini->variable( $key, 'Program' ) );
}

$audioConvertArray = $ini->variable( 'xrowVideoSettings', 'ConvertAudioFiles' );
foreach ( $audioConvertArray as $key )
{
    $convertSettings['audio'][$key] = array( 'options' => $ini->variable( $key, 'Options' ),
                                             'program' => $ini->variable( $key, 'Program' ) );
}

while( true )
{
    $sql = "SELECT param FROM ezpending_actions WHERE action = 'xrow_convert_media' GROUP BY param ORDER BY created";
    $entries = $db->arrayQuery( $sql,  array( 'limit' => $limit, 'offset' => $offset ) );

    if ( is_array( $entries ) && count( $entries ) > 0 )
    {
        foreach ( $entries as $entry )
        {
            $delEntry = true;
            $params = explode( "-", $entry['param'] );
            $attributeID = $params[0];
            $version = $params[1];
            $attr = eZContentObjectAttribute::fetch( $attributeID, $version );
            if ( $attr instanceof eZContentObjectAttribute )
            {
                $obj = $attr->object();
                // only convert published media files
                if ( $obj instanceof eZContentObject && $obj->Status == eZContentObject::STATUS_PUBLISHED )
                {
                    $cli->output( "Converting media of '" . $obj->attribute( 'name' ) . "'" );
                    $content = $attr->content();
                    $binary = $content['binary'];
                    if ( $binary )
                    {
                        $filePath = $binary->filePath();
                        $file = eZClusterFileHandler::instance(  $filePath );
                        $file->fetch();
                        if ( $filePath{0} != '/' )
                        {
                            $filePath = str_replace( array( "/", "\\" ), array( DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ), $filePath );
                        }
                        $pathParts = pathinfo( $filePath );
                        $mediaType = xrowMedia::checkMediaType( $binary->attribute( 'mime_type' ) );
                        if ( $mediaType == xrowMedia::TYPE_VIDEO )
                        {
                            $root = $content['media']->xml->video;
                            $cSettings = $convertSettings['video'];
                            $cli->output( "File is a video." );
                        }
                        elseif ( $mediaType == xrowMedia::TYPE_AUDIO )
                        {
                            $root = $content['media']->xml->audio;
                            $cSettings = $convertSettings['audio'];
                            $cli->output( "File is a audio." );
                        }
                        else
                        {
                            $cli->output( "Unknown file type: $filePath - skipping conversion" );
                            $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                            $content['media']->saveData();
                            eZDebug::writeError( "Unknown file type: $filePath", 'convert media cronjob' );
                            $db->query( "DELETE FROM ezpending_actions WHERE action = 'xrow_convert_media' AND param = '".$entry['param']."'" );
                            continue;
                        }
                        $root['status'] = xrowMedia::STATUS_CONVERSION_IN_PROGRESS;
                        $content['media']->saveData();
                        // start the conversion process
                        if( $mediaType == xrowMedia::TYPE_VIDEO && $ini->variable( 'xrowVideoSettings', 'UseVideoBitrate' ) == 'enabled' )
                        {
                            $originalFileAttributes = $content['media']->getXMLData( 'video', true );
                            $bitrates = $ini->variable( 'xrowVideoSettings', 'Bitrates' );
                            $convertFile = false;
                            $smallestBitrate = end( $bitrates );
                            foreach( $bitrates as $bitratekey )
                            {
                                if( isset( $ini->BlockValues['Bitrate_' . $bitratekey] ) )
                                {
                                    $convertCommandBlock = $ini->BlockValues['Bitrate_' . $bitratekey];
                                    if( $convertCommandBlock['Height'] <= $originalFileAttributes['height'] )
                                    {
                                        $convertFile = true;
                                        $cli->output( '' );
                                        $cli->output( '--------------------------------------------------------------' );
                                        $cli->output( 'Converting to ' . $bitratekey . '.' );
                                        $cli->output( '---------------------------------------------------------------' );
                                        $cli->output( '' );
                                        $src = convertFile( $bitratekey, $content, $originalFileAttributes, $root, $pathParts, $cSettings, $filePath, $convertCommandBlock, $ini );
                                    }
                                    else
                                    {
                                        $cli->output( '' );
                                        $cli->output( '---------------------------------------------------------------' );
                                        $cli->output( 'Not converting to ' . $bitratekey . ' because original file is smaller.' );
                                        $cli->output( '---------------------------------------------------------------' );
                                        $cli->output( '' );
                                    }
                                }
                            }
                            if( $convertFile === false )
                            {
                                if( isset( $ini->BlockValues['Bitrate_' . $smallestBitrate] ) )
                                {
                                    $convertCommandBlock = $ini->BlockValues['Bitrate_' . $smallestBitrate];
                                    $src = convertFile( $smallestBitrate, $content, $originalFileAttributes, $root, $pathParts, $cSettings, $filePath, $convertCommandBlock, $ini );
                                }
                            }
                        }
                        else
                        {
                            foreach ( $cSettings as $key => $setting )
                            {
                                if ( $key != $pathParts['extension'] )
                                {
                                    $src = execCommand( $root, $content, $pathParts, '', $key, $filePath, $setting );
                                }
                                else
                                {
                                    // file already exists
                                    $origFile = $root->xpath( "//source[@original=1]" );
                                    if ( count( $origFile ) > 0 )
                                    {
                                        $src = $origFile[0];
                                        $content['media']->updateFileInfo( $src );
                                        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
                                    }
                                    $src = execCommand( $root, $content, $pathParts, 'conv.', $key, $filePath, $setting );
                                }
                                // update mime type
                                $src['mimetype'] = $ini->variable( $key, 'MimeType' );
                            }
                        }
                        $root['status'] = xrowMedia::STATUS_CONVERSION_FINISHED;
                        $content['media']->xml->video['width'] = $originalFileAttributes['width'];
                        $content['media']->xml->video['height'] = $originalFileAttributes['height'];
                        $content['media']->xml->video['duration'] = $originalFileAttributes['duration'];
                        $content['media']->saveData();
                        // Update all versioned attribute
                        $conditions = array( 'id' => $attributeID,
                                             'version' => array( '!=', $version ) );
                        $allVersionedAttributesWithTheSameVideo = eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
                                                                                                        null,
                                                                                                        $conditions,
                                                                                                        null,
                                                                                                        null,
                                                                                                        true );
                        if( count( $allVersionedAttributesWithTheSameVideo ) > 0 )
                        {
                            $cli->output( '' );
                            $cli->output( '--------------------------------------------------------------------------------' );
                            $cli->output( 'Update all versioned attributes with the same video or where video is not exist.' );
                            $cli->output( '--------------------------------------------------------------------------------' );
                            $cli->output( '' );
                            xrowMedia::updateGivenAttributesDataText( $allVersionedAttributesWithTheSameVideo, $content, $binary );
                        }
                        $file->deleteLocal();
                    }
                }
                // clear view cache
                eZContentCacheManager::clearObjectViewCacheIfNeeded( $obj->ID );
            }

            ++$offset;
            if ( $delEntry )
            {
                $db->query( "DELETE FROM ezpending_actions WHERE action = 'xrow_convert_media' AND param = '".$entry['param']."'" );
            }
        }

        # delete memory cache
        eZContentObject::clearCache();
    }
    else
    {
        break; // No valid result from ezpending_actions
    }
}
$cli->output( "Done" );
$cli->output( "" );

function convertFile( $bitratekey, $content, $file_attributes, $root, $pathParts, $cSettings, $filePath, $convertCommandBlock, $ini )
{
    if( $ini->hasVariable( 'xrowVideoSettings', 'ConvertCommandReplace' ) )
    {
        $convertCommandReplace = $ini->variable( 'xrowVideoSettings', 'ConvertCommandReplace' );
        $keepProportion = $ini->variable( 'xrowVideoSettings', 'KeepProportion' );
        if( $keepProportion != 'enabled' )
        {
            $newWidth = $convertCommandBlock['Width'];
        }
        else
        {
            $newWidth = round( $file_attributes['width'] * $convertCommandBlock['Height'] / $file_attributes['height'] );
            // check if new height is divisible by 2 because the libx264 returns an error: [libx264 @ 0x97b660] height not divisible by 2 (384x241)
            if( $newWidth %2 != 0 )
            {
                $newWidth = $newWidth - 1;
            }
        }
        $bitrate = '-s ' . $newWidth . 'x' . $convertCommandBlock['Height'];
        foreach( $convertCommandBlock as $convertCommandItem => $convertCommandItemValue )
        {
            if( isset( $convertCommandReplace[$convertCommandItem] ) )
                $bitrate .= ' ' . $convertCommandReplace[$convertCommandItem] . ' ' . $convertCommandItemValue;
        }
        foreach ( $cSettings as $key => $setting )
        {
            if ( $key != $pathParts['extension'] )
            {
                $src = execCommand( $root, $content, $pathParts, $bitratekey . '.', $key, $filePath, $setting, $bitrate );
            }
            else
            {
                // file already exists
                $origFile = $root->xpath( "//source[@original=1]" );
                if ( count( $origFile ) > 0 )
                {
                    $src = $origFile[0];
                    $content['media']->updateFileInfo( $src );
                    $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
                }
                $src = execCommand( $root, $content, $pathParts, $bitratekey. '.conv.', $key, $filePath, $setting, $bitrate );
            }
            // update mime type
            $src['mimetype'] = $ini->variable( $key, 'MimeType' );
        }
    }
    return $src;
}

function execCommand( $root, $content, $pathParts, $file_suffix, $key, $filePath, $setting, $bitrate = '' )
{
    GLOBAL $cli;
    $newFileName = xrowMedia::newFileName( $pathParts, $file_suffix . $key );
    $src = $content['media']->registerFile( $newFileName, $root );

    $command = $content['media']->buildCommandLine( $filePath,
                                                    $newFileName,
                                                    $setting,
                                                    $bitrate );

    $cli->output( '# ' . $command );
    $ok = exec( $command );

    # check file and set status
    if ( file_exists( $newFileName ) )
    {
        $info = stat( $newFileName );
        if ( $info['size'] > 0 )
        {
            $convertedFile = eZClusterFileHandler::instance( $newFileName );
            $mime = eZMimeType::findByURL( $newFileName );
            $convertedFile->fileStore( $newFileName, 'binaryfile', false, $mime['name'] );
            $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
            $content['media']->updateFileInfo( $src );
            $convertedFile->deleteLocal();
            $src['show'] = 1;
            return $src;
        }
        else
        {
            $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
            eZDebug::writeError( "Converted file has 0 bytes", 'xrowvideo - convert media' );
            return null;
        }
    }
    else
    {
        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
        eZDebug::writeError( "Converted file doesn't exist", 'xrowvideo - convert media' );
        return null;
    }
}