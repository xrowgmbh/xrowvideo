<?php

$Module = $Params['Module'];


if ( $Params['ContentObjectID'] < 2 )
{
    return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$object = eZContentObject::fetch( (int) $Params['ContentObjectID'] );
$tpl = eZTemplate::factory();
$tpl->setVariable( "object", $object );

$Result = array();
$Result['name'] =  $object->name();
$Result['content'] = $tpl->fetch( "design:xrowvideo/embed/embed.tpl" );
$Result["pagelayout"] = "xrowvideo/embed/pagelayout.tpl";