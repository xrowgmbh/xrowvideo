<?php

// php -d memory_limit=512M runcronjobs.php convertmedia

$cli->output( "Start processing media conversion" );

$ini = eZINI::instance( 'xrowvideo.ini' );
$contentObjects = array();
$db = eZDB::instance();
// increase the timeouts because big movies need long time to convert
$wait_timeout = 100000;
$interactive_timeout = $wait_timeout;
if($ini->hasVariable( 'xrowVideoSettings', 'WaitingTimeOutTime' ))
{
    $wait_timeout = $ini->variable( 'xrowVideoSettings', 'WaitingTimeOutTime' );
}
$sqlWTO = "SHOW SESSION VARIABLES LIKE 'wait_timeout'";
$resultWTO = $db->arrayQuery($sqlWTO);
if($resultWTO[0]['Value'] < $wait_timeout)
{
    $db->query("SET SESSION wait_timeout=" . $wait_timeout);
}
if($ini->hasVariable( 'xrowVideoSettings', 'InteractiveTimeOutTime' ))
{
    $interactive_timeout = $ini->variable( 'xrowVideoSettings', 'InteractiveTimeOutTime' );
}
$sqlITO = "SHOW SESSION VARIABLES LIKE 'interactive_timeout'";
$resultITO = $db->arrayQuery($sqlITO);
if($resultITO[0]['Value'] < $interactive_timeout)
{
    $db->query("SET SESSION interactive_timeout=" . $interactive_timeout);
}

$offset = 0;
$limit = 50;

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
            // Check if there is more than one action for this attribute
            $checkParamSql = "SELECT param FROM ezpending_actions WHERE action = 'xrow_convert_media' AND param LIKE '".$attributeID."-%' ORDER BY created DESC";
            $checkParamEntries = $db->arrayQuery( $checkParamSql );
            if (count($checkParamEntries) > 0) {
                // Get the newest
                $latestParamEntry = $checkParamEntries[0];
                $params = explode( "-", $latestParamEntry['param'] );
                $attributeID = $params[0];
                $version = $params[1];
                // Remove the old
                $db->query( "DELETE FROM ezpending_actions WHERE action = 'xrow_convert_media' AND param LIKE '".$attributeID."-%' AND param NOT IN ('".$latestParamEntry['param']."')" );
            }
            $attr = eZContentObjectAttribute::fetch( $attributeID, $version );
            if ( $attr instanceof eZContentObjectAttribute )
            {
                $obj = $attr->object();
                // only convert published media files
                if ( $obj instanceof eZContentObject && $obj->Status == eZContentObject::STATUS_PUBLISHED )
                {
                    $cli->output( "Converting media of '" . $obj->attribute( 'name' ) . "' with ObjectID #" . $obj->attribute('id') );
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
                            $cli->output( "File is an audio." );
                        }
                        else
                        {
                            $cli->output( "Unknown file type: $filePath - skipping conversion" );
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
                            if (!isset($originalFileAttributes['height']) || (int)$originalFileAttributes['height'] == 0 || !isset($originalFileAttributes['width']) || (int)$originalFileAttributes['width'] == 0) {
                                $cli->output( "ERROR during converting video '" . $obj->attribute( 'name' ) . "' (ObjectID " . $obj->attribute('id') . "). Width or height are empty." );
                                eZDebug::writeError( "ERROR during converting video '" . $obj->attribute( 'name' ) . "' (ObjectID " . $obj->attribute('id') . "). Width or height are empty." );
                                $origFile = $root->xpath( "//source[@original=1]" );
                                if ( count( $origFile ) > 0 )
                                {
                                    $src = $origFile[0];
                                    $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                                }
                                continue;
                            }
                            $bitrates = $ini->variable( 'xrowVideoSettings', 'Bitrates' );
                            $convertFile = false;
                            $convertFileOriginal = false;
                            $convertFileOriginalBlock = false;
                            $smallestBitrate = end( $bitrates );
                            $counter = 0;
                            foreach( $bitrates as $bitratekey )
                            {
                                if( isset( $ini->BlockValues['Bitrate_' . $bitratekey] ) )
                                {
                                    $convertCommandBlock = $ini->BlockValues['Bitrate_' . $bitratekey];
                                    if( $convertCommandBlock['Height'] <= $originalFileAttributes['height'] )
                                    {
                                        $convertFile = true;
                                        // check whether the originalFile height is also in the $convertCommandBlock['Height']
                                        if( $convertCommandBlock['Height'] == $originalFileAttributes['height'] && $convertFileOriginal === false )
                                        {
                                            $convertFileOriginal = true;
                                        }
                                        elseif( $convertCommandBlock['Height'] < $originalFileAttributes['height'] && $convertFileOriginalBlock === false )
                                        {
                                            $convertFileOriginalBlock = $convertCommandBlock;
                                        }
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
                                        $cli->output( 'Not converting to ' . $bitratekey . ' because original file (height: ' . $originalFileAttributes['height'] . ') is smaller.' );
                                        $cli->output( '---------------------------------------------------------------' );
                                        $cli->output( '' );
                                    }
                                }
                            }
                            if( $convertFileOriginal === false )
                            {
                                $cli->output( '' );
                                $cli->output( '--------------------------------------------------------------' );
                                $cli->output( 'Converting additionally to ORIGINAL HEIGHT ' . $originalFileAttributes['height'] . ' because all other conversions were to small.' );
                                $cli->output( '---------------------------------------------------------------' );
                                $cli->output( '' );
                                $src = convertFile( $originalFileAttributes['height'] . 'p', $content, $originalFileAttributes, $root, $pathParts, $cSettings, $filePath, $convertFileOriginalBlock, $ini, true );
                            }
                            if( $convertFile === false )
                            {
                                if( isset( $ini->BlockValues['Bitrate_' . $smallestBitrate] ) )
                                {
                                    $convertCommandBlock = $ini->BlockValues['Bitrate_' . $smallestBitrate];
                                    $src = convertFile( $smallestBitrate, $content, $originalFileAttributes, $root, $pathParts, $cSettings, $filePath, $convertCommandBlock, $ini );
                                }
                            }
                            $content['media']->xml->video['width'] = $originalFileAttributes['width'];
                            $content['media']->xml->video['height'] = $originalFileAttributes['height'];
                            $content['media']->xml->video['duration'] = $originalFileAttributes['duration'];
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
                        
                        //Update Video Content - Ticket#9611-Start
                        if (isset($content['binary']->Filename)) {
                            $file_name_temp = explode('.',$content['binary']->Filename);
                            $file_name = $file_name_temp[0];
                        }
                        if (isset($content['media']->xml->video)) {
                            $source_array = (array)$content['media']->xml->video;
                            foreach($source_array['source'] as $key => $source) {
                                if(strpos((string)$source->attributes()->src,$file_name) === false) {
                                    unset($source_array['source'][$key][0]);
                                }
                            }
                        }
                        // End
                        //die(var_dump($content));
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

function convertFile( $bitratekey, $content, $file_attributes, $root, $pathParts, $cSettings, $filePath, $convertCommandBlock, $ini, $originalHeight = false )
{
    if( $ini->hasVariable( 'xrowVideoSettings', 'ConvertCommandReplace' ) )
    {
        $convertCommandReplace = $ini->variable( 'xrowVideoSettings', 'ConvertCommandReplace' );
        $keepProportion = '';
        if( $ini->hasVariable( 'xrowVideoSettings', 'KeepProportion' ) )
        {
            $keepProportion = $ini->variable( 'xrowVideoSettings', 'KeepProportion' );
        }
        if( $originalHeight )
        {
            $height = $file_attributes['height'];
            if( $keepProportion != 'enabled' )
            {
                $width = round( $file_attributes['height'] * 16 / 9 );
            }
            else
            {
                $width = $file_attributes['width'];
            }
        }
        else
        {
            $height = $convertCommandBlock['Height'];
            if( $keepProportion != 'enabled' )
            {
                $width = $convertCommandBlock['Width'];
            }
            else
            {
                $width = round( $file_attributes['width'] * $convertCommandBlock['Height'] / $file_attributes['height'] );
            }
        }
        // check if new height is divisible by 2 because the libx264 returns an error: [libx264 @ 0x97b660] height not divisible by 2 (384x241)
        if( $width %2 != 0 )
        {
            $width = $width - 1;
        }
        $bitrate = '-s ' . $width . 'x' . $height;
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
    $cli = eZCLI::instance();
    $newFileName = xrowMedia::newFileName( $pathParts, $file_suffix . $key );
    $src = $content['media']->registerFile( $newFileName, $root );

    $command = $content['media']->buildCommandLine( $filePath,
                                                    $newFileName,
                                                    $setting,
                                                    $bitrate );

    $cli->output( '# ' . $command );
    $ok = exec( $command );
    DBKeepalive("doctrine.dbal.default_connection");
    DBKeepalive("doctrine.dbal.cluster_connection");
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

function sendErrorMail( $mail_errorstring )
{
    $ini = eZINI::instance( 'site.ini' );
    $xrowvideo_ini = eZINI::instance( 'xrowvideo.ini' );
    if( $xrowvideo_ini->hasVariable( 'ErrorSettings', 'ReceiverArray' ) && count( $xrowvideo_ini->variable( 'ErrorSettings', 'ReceiverArray' ) ) > 0 )
    {
        ezcMailTools::setLineBreak( "\n" );
        $mail = new ezcMailComposer();
        $mail->charset = 'utf-8';
        $mail->from = new ezcMailAddress( $ini->variable( 'MailSettings', 'EmailSender' ), $ini->variable( 'SiteSettings', 'SiteName' ), $mail->charset );
        $mail->returnPath = $mail->from;
        $mail->subject = 'xrowvideo error during conversion';
        $mail->plainText = $mail_errorstring . " mail sent from: " . eZSys::hostname() . "(" . eZSys::serverURL() . ")";
        $mail->build();

        $receiverArray = $xrowvideo_ini->variable( 'ErrorSettings', 'ReceiverArray' );
        $transport = new ezcMailMtaTransport();
        foreach ( $receiverArray as $receiver )
        {
            $mail->addTo( new ezcMailAddress( $receiver, '', $mail->charset ) );
        }
        if( !$transport->send( $mail ) )
        {
            eZDebug::writeError( "Can't send error mail after not moving a node (xrowworkflow).", __METHOD__ );
        }
    }
}

function DBKeepalive( $connection = "doctrine.dbal.default_connection" ){
    $container = ezpKernel::instance()->getServiceContainer();
    $db = $container->get( $connection );
    if ($db->ping() === false) {
        $db->close();
        $db->connect();
    }
    $db->query("SET SESSION wait_timeout=86400");
    $db->query("SET SESSION interactive_timeout=86400");
}
