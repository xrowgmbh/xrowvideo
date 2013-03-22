{def $obj = $attribute.object
     $poster = false()
     $media_tag = cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt( 0 ) ), 'video', 'audio' )
     $media = cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt( 0 ) ), $attribute.content.video, $attribute.content.audio )
     $media_attributes = ''
     $control_attributes = 'preload="auto"'
     $fallback_attributes = ''
     $audio_width = ''
     $image_url = ''}
{if and( $attribute.has_content, is_set( $media.source ), $media.source|count|gt( 0 ) )}
    {if is_set( $width )}
        {set $media_attributes = concat( 'width="', $width, '"' )
             $audio_width = concat( ' style="width: ', $width, ';"' )}
    {elseif and( is_set( $attribute.content.settings.width ), $attribute.content.settings.width|trim()|ne( '' ) )}
        {set $media_attributes = concat( 'width="', $attribute.content.settings.width|trim(), '"' )
             $audio_width = concat( ' style="width: ', $attribute.content.settings.width|trim(), ';"' )}
    {elseif and( is_set( $media.width ), $media.width|trim()|ne( '' ) )}
        {set $media_attributes = concat( 'width="', $media.width|trim(), '"' )
             $audio_width = concat( ' style="width: ', $media.width|trim(), ';"' )}
    {/if}
    {if is_set( $height )}
        {set $media_attributes = concat( $media_attributes, ' height="', $height, '"' )}
    {elseif and( is_set( $attribute.content.settings.height ), $attribute.content.settings.height|trim()|ne( '' ) )}
        {set $media_attributes = concat( $media_attributes, ' height="', $attribute.content.settings.height|trim(), '"' )}
    {elseif and( is_set( $media.height ), $media.height|trim()|ne( '' ) )}
        {set $media_attributes = concat( $media_attributes, ' height="', $media.height|trim(), '"' )}
    {/if}
    {if $media_tag|eq( 'video' )}
        {* select the default Video *}
        {def $objects = array()
             $objects_tmp = array()
             $fallback_object = false()
             $fallback_object_tmp = false()
             $defaultFormat = ezini( 'xrowVideoSettings', 'DefaultVideoForPlayer', 'xrowvideo.ini' )}
        {foreach $media.source as $item}
            {if $item.src|contains( '.flv' )|not()}
                {if $item.src|contains( $defaultFormat )}
                    {set $objects = $objects|append( $item )}
                {else}
                    {set $objects_tmp = $objects_tmp|append( $item )}
                {/if}
            {else}
                {set $fallback_object_tmp = $item}
                {if $item.src|contains( $defaultFormat )}
                    {set $fallback_object = $item}
                {/if}
            {/if}
        {/foreach}
        {set $objects = $objects|merge( $objects_tmp )}
    {else}
        {def $objects = $media.source}
    {/if}
    {* set for flash fallback only width and height *}
    {set $fallback_attributes = $media_attributes}
    
    {if and( $fallback_object|not(), $fallback_object_tmp )}
        {set $fallback_object = $fallback_object_tmp}
    {/if}

    {set $control_attributes = concat( ' ', cond( $attribute.content.settings.controls, ' controls="controls"', '' ) )}
    {set $control_attributes = concat( $control_attributes, ' ', cond( $attribute.content.settings.autoplay, ' autoplay', '' ) )}
    {set $control_attributes = concat( $control_attributes, ' ', cond( $attribute.content.settings.loop, ' loop', '' ) )}
    {set $media_attributes = concat( $media_attributes, ' ', $control_attributes )}
    {if and( is_set( $obj.data_map.image ), $obj.data_map.image.has_content )}
        {set $image_url = $obj.data_map.image.content.original.url|ezroot( 'no', 'full' )}
    {/if}

    {run-once}
    {*ezcss_require( 'leanbackPlayer.default.css' )*}
    {ezcss_require( 'leanbackPlayer.modified.css' )}
    {ezscript_require( 'leanbackPlayer.js' )}
    {ezscript_require( 'leanbackPlayer.de.js' )}
    {ezscript_require( 'leanbackPlayer.en.js' )}
    {ezscript_require( 'leanbackPlayer.es.js' )}
    {ezscript_require( 'leanbackPlayer.fr.js' )}
    {ezscript_require( 'leanbackPlayer.nl.js' )}
    {ezscript_require( 'leanbackPlayer.ru.js' )}
    {ezscript_require( 'xrowvideo.js' )}
    {/run-once}

    <div class="leanback-player-{$media_tag}"{if $media_tag|eq( 'audio' )}{$audio_width}{/if}>
        <{$media_tag} {if $media_tag|eq( 'video' )}{$media_attributes}{else}{$control_attributes}{/if}{if $image_url|ne( '' )} poster="{$image_url}"{/if} data-objectid="{$attribute.contentobject_id}">
        {foreach $objects as $item}
            {def $path = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}
            <source src={$path} type="{$item.mimetype}" />
            {undef $path}
        {/foreach}
            {if and( $media_tag|eq( 'video' ), $fallback_object )}
            {* Fallback Flash *}
            {def $path_fallback = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id, '/', $attribute.version, '/', $fallback_object.src|rawurlencode )|ezurl( 'no', 'full' )}
            <object class="leanback-player-flash-fallback" {$fallback_attributes} type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer.swf">
                <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer.swf" />
                <param name="allowFullScreen" value="true" />
                <param name="wmode" value="opaque" />
                <param name="bgcolor" value="#000000" />
                {if $image_url}
                <param name="flashVars" value="config={ldelim}'playlist':['{$image_url}', {ldelim}'url':'{$path_fallback}', 'autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')}, 'autobuffering':true{rdelim}]{rdelim}" />
                {else}
                <param name="flashVars" value="config={ldelim}'clip':{ldelim}'url':'{$path_fallback}','autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')},'autobuffering':true{rdelim}{rdelim}" />
                {/if}
            </object>
            {/if}
            {* Fallback HTML *}
            <div class="leanback-player-html-fallback" {$fallback_attributes}>
                <img src="{$image_url}" {$fallback_attributes} alt="Poster Image" title="No HTML5-Video playback capabilities found. Please download the video(s) below." />
                <div>
                    <strong>Download {if $media_tag|eq( 'video' )}Video{else}Audio{/if}:</strong>
                    {foreach $objects as $key => $item}
                    {def $name_parts = $item.originalfilename|explode( '.' )
                         $last_element = $name_parts|count()|dec()
                         $file_suffix = $name_parts.$last_element}
                    <a href={concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}><nobr>{$file_suffix}{if is_set( $item.width )} ({$item.width} x {$item.height}){/if}</nobr></a>{if $key|lt( $objects|count()|dec() )},{/if}
                    {undef $name_parts $last_element $file_suffix}
                    {/foreach}
                </div>
            </div>
        </{$media_tag}>
    </div>
{else}
    {if $attribute.has_content|not()}
    <p>{'There is no file.'|i18n( 'design/standard/content/datatype' )}</p>
    {else}
    <p>{'The media files will be created soon.'|i18n( 'design/standard/content/datatype' )}</p>
    {/if}
{/if}