<?php

/**
 * xrowvideo is a clone of ezbinaryfiletype
 * @author Georg
 *
 */

class xrowVideoType extends eZBinaryFileType
{
    const MAX_FILESIZE_FIELD = 'data_int1';
    const MAX_FILESIZE_VARIABLE = '_xrowvideo_max_filesize_';

    const DATA_TYPE_STRING = 'xrowvideo';

    /**
     * File conversion status
     */
    const STATUS_NONE = 0;
    const STATUS_GENERATING = 1;
    const STATUS_COMPLETED = 2;

    const ERROR_NO_FFMPEG_INSTALLED = 1;

    function __construct()
    {
        $this->eZDataType( self::DATA_TYPE_STRING,
                           ezpI18n::tr( 'kernel/classes/datatypes', 'xrow Video', 'Datatype name' ),
                           array( 'serialize_supported' => true ) );
    }

/*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        eZBinaryFileType::checkFileUploads();
        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        $mustUpload = false;
        $httpFileName = $base . "_data_binaryfilename_" . $contentObjectAttribute->attribute( "id" );
        $maxSize = 1024 * 1024 * $classAttribute->attribute( self::MAX_FILESIZE_FIELD );

        if ( $contentObjectAttribute->validateIsRequired() )
        {
            $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
            $version = $contentObjectAttribute->attribute( "version" );
            $binary = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
            if ( $binary === null )
            {
                $mustUpload = true;
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                        'A valid file is required.' ) );
                return eZInputValidator::STATE_INVALID;
            }
            if ( $maxSize > 0 && $binary->fileSize() > $maxSize )
            {
                # file size check
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                            'The size of the uploaded file exceeds the maximum upload size: %1 bytes.' ), $maxSize );
                   return eZInputValidator::STATE_INVALID;
            }
        }

        if ( $http->hasPostVariable( $base . "_data_media_width_" . $contentObjectAttribute->attribute( "id" ) ) &&
        $http->hasPostVariable( $base . "_data_media_height_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $width = trim( $http->postVariable( $base . "_data_media_width_" . $contentObjectAttribute->attribute( "id" ) ) );
            $height = trim( $http->postVariable( $base . "_data_media_height_" . $contentObjectAttribute->attribute( "id" ) ) );

            if ( ( $width != '' && !is_numeric( $width ) ) || ( $height != '' && !is_numeric( $height ) ) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                    'Width and height values must be numeric.' ) );
                return eZInputValidator::STATE_INVALID;
            }
        }

        return eZInputValidator::STATE_ACCEPTED;
    }

    function objectAttributeContent( $contentObjectAttribute )
    {
        $binaryFile = eZBinaryFile::fetch( $contentObjectAttribute->attribute( "id" ),
                                           $contentObjectAttribute->attribute( "version" ) );
        $result = array();
        $result['binary'] = $binaryFile;
        $mObj = new xrowMedia( $contentObjectAttribute  );
        $result['settings'] = $mObj->settings;
        $result['video'] = $mObj->getXMLData( 'video' );
        $result['audio'] = $mObj->getXMLData( 'audio' );
        $result['media'] = $mObj;
        $result['pending'] = $mObj->hasPendingAction();
        # ffmpeg check
        if ( !class_exists( 'ffmpeg_movie' ) )
        {
            $result['error'] = self::ERROR_NO_FFMPEG_INSTALLED;
        }
        return $result;
    }

    /**
     * Checks if current HTTP request is asking for current binary file deletion
     * @param eZHTTPTool $http
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    private function isDeletingFile( eZHTTPTool $http, eZContentObjectAttribute $contentObjectAttribute )
    {
        $isDeletingFile = false;
        if ( $http->hasPostVariable( 'CustomActionButton' ) )
        {
            $customActionArray = $http->postVariable( 'CustomActionButton' );
            $attributeID = $contentObjectAttribute->attribute( 'id' );
            if ( isset( $customActionArray[$attributeID . '_delete_binary'] ) )
            {
                $isDeletingFile = true;
            }
        }

        return $isDeletingFile;
    }

    /**
     * Update settings
     * @see eZBinaryFileType::fetchObjectAttributeHTTPInput()
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $mObj = new xrowMedia( $contentObjectAttribute );
        if ( $http->hasPostVariable( $base . "_data_media_is_autoplay_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $mObj->settings['autoplay'] = 1;
        }
        else
        {
            $mObj->settings['autoplay'] = 0;
        }

        if ( $http->hasPostVariable( $base . "_data_media_has_controls_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $mObj->settings['controls'] = 1;
        }
        else
        {
            $mObj->settings['controls'] = 0;
        }

        if ( $http->hasPostVariable( $base . "_data_media_is_loop_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $mObj->settings['loop'] = 1;
        }
        else
        {
            $mObj->settings['loop'] = 0;
        }
        // if there is a pending item take the published version and override this mObj
        $pendingAction = false;
        if( $pendingObj = $mObj->hasPendingAction( true ) )
        {
            $pendingAction = true;
            $params = explode( '-', $pendingObj->param );
            $pendingVersion = $params[1];
            $def = eZContentObjectAttribute::definition();
            $conditions = array( 'id' => $contentObjectAttribute->attribute( "id" ) );
            $custom_conds = ' AND ' . $def['name'] . '.data_text LIKE \'% status="' . xrowMedia::STATUS_CONVERSION_FINISHED . '"%\'' . 
                            ' AND ' . $def['name'] . '.version NOT IN(' . (int)$contentObjectAttribute->Version . ', ' . (int)$pendingVersion . ')';
            if( isset( $mObj->xml->video ) && isset( $mObj->xml->video->source[0] ) && isset( $mObj->xml->video->source[0]->attributes()->src ) )
            {
                $custom_conds .= ' AND ' . $def['name'] . '.data_text LIKE \'% src="' . $mObj->xml->video->source[0]->attributes()->src . '"%\'';
            }
            $videosAttributeWithFullXML = eZPersistentObject::fetchObjectList( $def,
                                                                               null,
                                                                               $conditions,
                                                                               array( 'version' => 'desc' ),
                                                                               array( 'limit' => 1, 'offset' => 0 ),
                                                                               true,
                                                                               false,
                                                                               null,
                                                                               null,
                                                                               $custom_conds );
            if( count( $videosAttributeWithFullXML ) > 0 )
            {
                $content = $videosAttributeWithFullXML[0]->content();
                $binary = $content['binary'];
                if ( $content && $binary )
                {
                    xrowMedia::updateGivenAttributesDataText( array( $contentObjectAttribute ), $content, $binary );
                }
            }
            $mObj = new xrowMedia( $contentObjectAttribute );
        }

        // manual update of the media infos
        if ( $http->hasPostVariable( $base . "_data_media_update_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            if( $pendingAction === false )
            {
                $mObj->updateMediaInfo();
            }
            $mObj->addPendingAction();
        }
        $mObj->setSettings();
        $contentObjectAttribute->setAttribute( 'data_text', $mObj->xml->asXML() );
        $contentObjectAttribute->setContent( $this->objectAttributeContent( $contentObjectAttribute ) );
        return true;

    }

    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
        $sys = eZSys::instance();
        $storage_dir = $sys->storageDirectory();

        $attributeList = array();

        if ( $version == null )
        {
            $binaryFiles = eZBinaryFile::fetch( $contentObjectAttributeID );
            eZBinaryFile::removeByID( $contentObjectAttributeID, null );

            $attributeList = eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
                                                            null,
                                                            array( "id" => $contentObjectAttributeID ) );

            foreach ( $binaryFiles as  $binaryFile )
            {
                $mimeType =  $binaryFile->attribute( "mime_type" );
                list( $prefix, $suffix ) = explode('/', $mimeType );
                $orig_dir = $storage_dir . '/original/' . $prefix;
                $fileName = $binaryFile->attribute( "filename" );

                // Check if there are any other records in ezbinaryfile that point to that fileName.
                $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );

                $filePath = $orig_dir . "/" . $fileName;
                $file = eZClusterFileHandler::instance( $filePath );

                if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                {
                    $file->delete();
                }
            }
        }
        else
        {
            $count = 0;
            $binaryFile = eZBinaryFile::fetch( $contentObjectAttributeID, $version );

            if ( $binaryFile != null )
            {
                $mimeType =  $binaryFile->attribute( "mime_type" );
                list( $prefix, $suffix ) = explode('/', $mimeType );
                $orig_dir = $storage_dir . "/original/" . $prefix;
                $fileName = $binaryFile->attribute( "filename" );

                eZBinaryFile::removeByID( $contentObjectAttributeID, $version );

                // Check if there are any other records in ezbinaryfile that point to that fileName.
                $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );

                $filePath = $orig_dir . "/" . $fileName;
                $file = eZClusterFileHandler::instance( $filePath );
                if ( count( $binaryObjectsWithSameFileName ) < 1 )
                {
                    $attributeList[] = $contentObjectAttribute;
                }
                if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                {
                    $file->delete();
                }
            }
        }

        foreach( $attributeList as $attribute )
        {
            $content = $this->objectAttributeContent( $attribute );
            $mObj = $content['media'];
            # delete converted files
            $mObj->deleteConvertedFiles();

            # empty xml data but keep settings
            if ( isset( $mObj->xml->video ) )
            {
                unset( $mObj->xml->video );
            }
            if ( isset( $mObj->xml->audio ) )
            {
                unset( $mObj->xml->audio );
            }
            $mObj->saveData();
        }
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        eZBinaryFileType::checkFileUploads();
        if ( $action == "delete_binary" )
        {
            $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
            $version = $contentObjectAttribute->attribute( "version" );
            $this->deleteStoredObjectAttribute( $contentObjectAttribute, $version );
            $contentObjectAttribute->setAttribute( 'data_text', '' );
            $contentObjectAttribute->store();
            $contentObjectAttribute->Content = null;
        }
    }
}

eZDataType::register( xrowVideoType::DATA_TYPE_STRING, 'xrowVideoType' );