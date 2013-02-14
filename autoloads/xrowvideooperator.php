<?php

class xrowVideoOperator
{
    function operatorList()
    {
        return array(
            'xrowvideo_get_filepath'
        );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array(
            'xrowvideo_get_filepath' => array(
                'contentobject_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'default' => 0
                ),
                'contentobjectattribute_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'default' => 0
                ),
                'version' => array(
                    'type' => 'integer',
                    'required' => true,
                    'default' => 0
                ),
                'filepath' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => ''
                )
            )
        );
    }

    function modify( $tpl, $operatorName, $operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'xrowvideo_get_filepath':
            {
                if ( isset( $namedParameters['contentobject_id'] ) )
                {
                    $contentObjectID = $namedParameters['contentobject_id'];
                    $contentObjectAttributeID = $namedParameters['contentobjectattribute_id'];
                    $contentObject = eZContentObject::fetch( $contentObjectID );
                    if ( !( $contentObject instanceof eZContentObject ) )
                    {
                        return false;
                    }
                    $currentVersion = $contentObject->attribute( 'current_version' );

                    if ( isset(  $contentObject->Version ) && is_numeric( $contentObject->Version ) )
                         $version = $contentObject->Version;
                    else
                         $version = $currentVersion;

                    $contentObjectAttribute = eZContentObjectAttribute::fetch( $contentObjectAttributeID, $version, true );
                    if ( !( $contentObjectAttribute instanceof eZContentObjectAttribute ) )
                    {
                        return false;
                    }
                    $contentObjectIDAttr = $contentObjectAttribute->attribute( 'contentobject_id' );
                    if ( $contentObjectID != $contentObjectIDAttr or !$contentObject->attribute( 'can_read' ) )
                    {
                        return false;
                    }

                    // Get locations.
                    $nodeAssignments = $contentObject->attribute( 'assigned_nodes' );
                    if ( count( $nodeAssignments ) === 0 )
                    {
                        // oops, no locations. probably it's related object. Let's check his owners
                        $ownerList = eZContentObject::fetch( $contentObjectID )->reverseRelatedObjectList( false, false, false, false );
                        foreach ( $ownerList as $owner )
                        {
                            if ( is_object( $owner ) )
                            {
                                $ownerNodeAssignments = $owner->attribute( 'assigned_nodes' );
                                $nodeAssignments = array_merge( $nodeAssignments, $ownerNodeAssignments );
                            }
                        }
                    }

                    // If exists location that current user has access to and location is visible.
                    $canAccess = false;
                    foreach ( $nodeAssignments as $nodeAssignment )
                    {
                        if ( ( eZContentObjectTreeNode::showInvisibleNodes() || !$nodeAssignment->attribute( 'is_invisible' ) ) and $nodeAssignment->canRead() )
                        {
                            $canAccess = true;
                            break;
                        }
                    }
                    if ( !$canAccess )
                        return false;

                    // If $version is not current version (published)
                    // we should check permission versionRead for the $version.
                    if ( $version != $currentVersion )
                    {
                        $versionObj = eZContentObjectVersion::fetchVersion( $version, $contentObjectID );
                        if ( is_object( $versionObj ) and !$versionObj->canVersionRead() )
                            return false;
                    }

                    $content = $contentObjectAttribute->content();

                    $fileinfo = $content['media']->storedFileInfo( rawurldecode( $namedParameters['filepath'] ) );
                    $fileName = $fileinfo['filepath'];
                    $file = eZClusterFileHandler::instance( $fileName );
                    if ( $fileName != "" and $file->exists() )
                    {
                        $operatorValue = $fileName;
                    }
                    else
                    {
                        eZDebug::writeError( 'The specified file could not be found.' );
                        return false;
                    }
                }
            }break;
        }
    }
}