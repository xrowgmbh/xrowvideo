{* DO NOT EDIT THIS FILE! Use an override template instead. *}

{def $obj=$attribute.object
     $poster=false()
     $media_tag=cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt(0) ), 'video', 'audio' )
     $media=cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt(0) ), $attribute.content.video, $attribute.content.audio )}
{if $media_tag|eq( 'video' )}
    {if is_set($width)|not()}
        {def $width=cond( $attribute.content.settings.width|ne(''), $attribute.content.settings.width, $media.width )}
    {/if}
    {if is_set($height)|not()}
        {def $height=cond( $attribute.content.settings.height|ne(''), $attribute.content.settings.height, $media.height )}
    {/if}
{/if}

{if and( is_set( $obj.data_map.image ), $obj.data_map.image.has_content )}
    {set $poster=concat( " poster=", $obj.data_map.image.content.large.url|ezroot )}
{/if}
{if and( $attribute.has_content, is_set( $media.source ), $media.source|count|gt(0) )}
{run-once}
{ezcss_require( 'video-js.css' )}
{ezscript_require( 'video.js' )}

<script>_V_.options.flash.swf = {"video-js.swf"|ezimage};</script>

{/run-once}

<{$media_tag} id="xrow_media_{$attribute.id}"{if $media_tag|eq( 'video' )} class="video-js vjs-default-skin"{/if} {cond( $attribute.content.settings.controls, ' controls="controls"', '' )}{cond( $attribute.content.settings.autoplay, ' autoplay', '' )}{cond( $attribute.content.settings.loop, ' loop', '' )} preload="auto" {if $media_tag|eq( 'video' )}{cond(  $attribute.content.settings.width, concat( 'width="', $attribute.content.settings.width, '"' ), concat( 'width="', $attribute.content.video.width, '"' ) )} {cond( $attribute.content.settings.height, concat( 'height="', $attribute.content.settings.height, '"' ), concat( 'height="', $attribute.content.$media_tag.height, '"' ) )}{/if} {if $media_tag|eq( 'video' )}data-setup='{ldelim}{rdelim}'{cond( $poster, $poster, '')}{/if}>

{foreach $media.source as $item}

  <source src={concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode, '/', $item.originalfilename|rawurlencode )|ezurl} type="{$item.mimetype}">
{/foreach}
</{$media_tag}>
{else}
    {if $attribute.has_content|not()}
    <p>{'There is no file.'|i18n( 'design/standard/content/datatype' )}</p>
    {else}
    <p>{'The media files will be created soon.'|i18n( 'design/standard/content/datatype' )}</p>
    {/if}
{/if}
