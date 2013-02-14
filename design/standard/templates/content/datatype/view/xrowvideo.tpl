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
             $defaultFormat = ezini( 'xrowVideoSettings', 'DefaultVideoForPlayer', 'xrowvideo.ini' )}
        {foreach $media.source as $item}
            {if $item.src|contains( '.flv' )|not()}
                {if $item.src|contains( $defaultFormat )}
                    {set $objects = $objects|append( $item )}
                    {if $item.src|contains( '.mp4' )}
                        {def $ie9_fallback_object = $item}
                    {/if}
                {else}
                    {set $objects_tmp = $objects_tmp|append( $item )}
                {/if}
            {elseif $item.src|contains( $defaultFormat )}
                {set $fallback_object = $item}
            {/if}
        {/foreach}
        {set $objects = $objects|merge( $objects_tmp )}
    {else}
        {def $objects = $media.source}
    {/if}
    {* set for flash fallback only width and height *}
    {set $fallback_attributes = $media_attributes}

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

    <video width="400" height="240" poster="{$image_url}" autoplay controls loop>
        {*<source src="http://ie.microsoft.com/testdrive/ieblog/2011/nov/pp4_blog_demo.mp4" type='video/mp4; codecs="avc1.42E01E,mp4a.40.2"'>*}
        {*<source src="/extension/xrowvideo/design/standard/javascript/899f520f8b3559371d1970a3a9102d92.720p.mp4" type="video/mp4" />*}
        {*<source src="/var/storage/original/video/899f520f8b3559371d1970a3a9102d92.720p.mp4" type="video/mp4" />*}
        {foreach $objects as $item}
            {if $media_tag|eq( 'video' )}
                {if $ie9_fallback_object.src|eq( $item.src )}
                    {def $path = "/extension/xrowvideo/design/standard/javascript/iPostersVideoHD.mp4"}
                {else}
                    {def $path = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}
                {/if}
            {else}
                {def $path = xrowvideo_get_filepath( $attribute.contentobject_id, $attribute.id, $attribute.version, $item.src|rawurlencode )|ezurl()}
            {/if}
            <source src={$path} type="{$item.mimetype}" />
            {undef $path}
        {/foreach}
    </video>
    <div class="leanback-player-{$media_tag}"{if $media_tag|eq( 'audio' )}{$audio_width}{/if}>
        <{$media_tag} {if $media_tag|eq( 'video' )}{$media_attributes}{else}{$control_attributes}{/if}{if $image_url|ne( '' )} poster="{$image_url}"{/if}>
        {*<source src="/extension/xrowvideo/design/standard/javascript/iPostersVideoHD.mp4" type="video/mp4" />*}
        {foreach $objects as $item}
            {if $media_tag|eq( 'video' )}
                {if $ie9_fallback_object.src|eq( $item.src )}
                    {def $path = "/extension/xrowvideo/design/standard/javascript/iPostersVideoHD.mp4"}
                {else}
                    {def $path = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}
                {/if}
            {else}
                {def $path = xrowvideo_get_filepath( $attribute.contentobject_id, $attribute.id, $attribute.version, $item.src|rawurlencode )|ezurl()}
            {/if}
            <source src={$path} type="{$item.mimetype}" />
            {undef $path}
        {/foreach}
            {if and( $media_tag|eq( 'video' ), $fallback_object )}
            {* Fallback Flash *}
            {def $path_fallback = xrowvideo_get_filepath( $attribute.contentobject_id, $attribute.id, $attribute.version, $fallback_object.src|rawurlencode )|ezurl( 'no', 'full' )}
            <object class="leanback-player-flash-fallback" {$fallback_attributes} type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer.swf">
                <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer.swf" />
                <param name="allowFullScreen" value="true" />
                <param name="wmode" value="opaque" />
                <param name="bgcolor" value="#000000" />
                <param name="flashVars" value="config={ldelim}'playlist':['{$image_url}', {ldelim}'url':'{$path_fallback}', 'autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')}, 'autobuffering':true{rdelim}]{rdelim}" />
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