<?php

$cli->output( "Start processing media conversion" );

$contentObjects = array();
$db = eZDB::instance();

$offset = 0;
$limit = 50;

$ini = eZINI::instance( 'xrowvideo.ini' );

$videoConvertArray = $ini->variable( 'xrowVideoSettings', 'ConvertVideoFiles' );
foreach ( $videoConvertArray as $key )
{
    $convertSettings['video'][$key]['options'] = $ini->variable( $key, 'Options' );
    $convertSettings['video'][$key]['program'] = $ini->variable( $key, 'Program' );
}

$audioConvertArray = $ini->variable( 'xrowVideoSettings', 'ConvertAudioFiles' );
foreach ( $audioConvertArray as $key )
{
    $convertSettings['audio'][$key]['options'] = $ini->variable( $key, 'Options' );
    $convertSettings['audio'][$key]['program'] = $ini->variable( $key, 'Program' );
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

                # only convert published media files
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
                        if (  $mediaType == xrowMedia::TYPE_VIDEO )
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
                        # start the conversion process
                        foreach ( $cSettings as $key => $setting )
                        {
                            if ( $key != $pathParts['extension'] )
                            {
                                $newFileName = xrowMedia::newFileName( $pathParts, $key );
                                $src = $content['media']->registerFile( $newFileName, $root );

                                $command = $content['media']->buildCommandLine( $filePath,
                                                                                $newFileName,
                                                                                $setting['program'],
                                                                                $setting['options'] );
                                $cli->output( '# ' . $command );

                                $ok = exec( $command );
                                # check file and set status

                                if ( file_exists( $newFileName ) )
                                {
                                    $info = stat( $newFileName );

                                    if ( $info['size'] > 0 )
                                    {
                                        $convertedFile = eZClusterFileHandler::instance( $newFileName );
                                        if ( $convertedFile->exists() )
                                        {
                                            $convertedFile->delete();
                                            $convertedFile = eZClusterFileHandler::instance( $newFileName );
                                        }
                                        $mime = eZMimeType::findByURL( $newFileName );
                                        $convertedFile->fileStore( $newFileName, 'binaryfile', false, $mime['name'] );
                                        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
                                        $content['media']->updateFileInfo( $src );
                                        $convertedFile->deleteLocal();
                                        $src['show'] = 1;
                                    }
                                    else
                                    {
                                        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                                        eZDebug::writeError( "Converted file has 0 bytes", 'xrowvideo - convert media' );
                                    }
                                }
                                else
                                {
                                    $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                                    eZDebug::writeError( "Converted file doesn't exist", 'xrowvideo - convert media' );
                                }
                            }
                            else
                            {
                                # file already exists
                                $origFile = $root->xpath( "//source[@original=1]" );
                                if ( count( $origFile ) > 0 )
                                {
                                    $src = $origFile[0];
                                    $content['media']->updateFileInfo( $src );
                                    $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
                                }
                                # create new file
                                $newFileName = xrowMedia::newFileName( $pathParts, 'conv.' . $key );

                                $src = $content['media']->registerFile( $newFileName, $root );

                                $command = $content['media']->buildCommandLine( $filePath,
                                                                                $newFileName,
                                                                                $setting['program'],
                                                                                $setting['options'] );
                                eZDebug::writeDebug( $command, 'cli' );
                                $ok = exec( $command );

                                # check file and set status
                                if ( file_exists( $newFileName ) )
                                {
                                    $info = stat( $newFileName );

                                    if ( $info['size'] > 0 )
                                    {
                                        $convertedFile = eZClusterFileHandler::instance( $newFileName );
                                        #if ( $convertedFile->exists() )
                                        #{
                                        #    $convertedFile->deleteFile();
                                        #    $convertedFile = eZClusterFileHandler::instance( $newFileName );
                                        #}
                                        $mime = eZMimeType::findByURL( $newFileName );
                                        $convertedFile->fileStore( $newFileName, 'binaryfile', false, $mime['name'] );

                                        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_FINISHED );
                                        $content['media']->updateFileInfo( $src );
                                        $convertedFile->deleteLocal();
                                        $src['show'] = 1;
                                    }
                                    else
                                    {
                                        $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                                        eZDebug::writeError( "Converted file has 0 bytes", 'xrowvideo - convert media' );
                                    }
                                }
                                else
                                {
                                    $content['media']->setStatus( $src, xrowMedia::STATUS_CONVERSION_ERROR );
                                    eZDebug::writeError( "Converted file doesn't exist", 'xrowvideo - convert media' );
                                }
                            }
                            # update mime type
                            $src['mimetype'] = $ini->variable( $key, 'MimeType' );
                        }
                        $root['status'] = xrowMedia::STATUS_CONVERSION_FINISHED;
                        $content['media']->saveData();
						$file->deleteLocal();
                    }
                }

                # clear view cache
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

$cli->output( "Done." );
