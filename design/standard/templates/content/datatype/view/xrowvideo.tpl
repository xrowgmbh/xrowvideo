{def $obj = $attribute.object
     $poster = false()
     $media_tag = cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt( 0 ) ), 'video', 'audio' )
     $media = cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt( 0 ) ), $attribute.content.video, $attribute.content.audio )
     $media_attributes = ''
     $control_attributes = 'preload="auto"'
     $fallback_attributes = ''
     $audio_width = ''
     $image_url = ''
     $default_width = 400
     $default_height = 240}
{if and( $attribute.has_content, is_set( $media.source ), $media.source|count|gt( 0 ) )}
    {def $track = fetch('content', 'list', hash('parent_node_id', $attribute.object.main_node_id,
                                                'class_filter_type', 'include',
                                                'class_filter_array', array('video_track'),
                                                'limit', 1 ))[0]}
    {if $media_tag|eq( 'video' )}
        {* select the default Video *}
        {def $objects = array()
             $default_item = false()
             $objects_tmp = array()
             $fallback_object = false()
             $fallback_object_tmp = false()
             $defaultFormat = ezini( 'xrowVideoSettings', 'DefaultVideoForPlayer', 'xrowvideo.ini' )}
        {foreach $media.source as $item}
            {if $item.src|contains( '.flv' )|not()}
                {if $item.src|contains( $defaultFormat )}
                    {set $default_item = $item
                         $objects = $objects|append( $item )}
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

    {if and( $fallback_object|not(), $fallback_object_tmp )}
        {set $fallback_object = $fallback_object_tmp}
    {/if}

    {if is_set( $width )}
        {set $media_attributes = concat( 'width="', $width, '"' )
             $audio_width = concat( ' style="width: ', $width, ';"' )}
    {elseif and( is_set( $attribute.content.settings.width ), $attribute.content.settings.width|trim()|ne( '' ) )}
        {set $media_attributes = concat( 'width="', $attribute.content.settings.width|trim(), '"' )
             $audio_width = concat( ' style="width: ', $attribute.content.settings.width|trim(), ';"' )}
    {elseif $default_item}
        {set $media_attributes = concat( 'width="', $default_item.width, '"' )}
    {else}
        {set $media_attributes = concat( 'width="', $default_width, '"' )
             $audio_width = concat( ' style="width: ', $default_width, ';"' )}
    {/if}

    {if is_set( $height )}
        {set $media_attributes = concat( $media_attributes, ' height="', $height, '"' )}
    {elseif and( is_set( $attribute.content.settings.height ), $attribute.content.settings.height|trim()|ne( '' ) )}
        {set $media_attributes = concat( $media_attributes, ' height="', $attribute.content.settings.height|trim(), '"' )}
    {elseif $default_item}
        {set $media_attributes = concat( $media_attributes, ' height="', $default_item.height, '"' )}
    {else}
        {set $media_attributes = concat( $media_attributes, ' height="', $default_height, '"' )}
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
    {if ezini( 'xrowVideoSettings', 'EnableTrackingwithGA', 'xrowvideo.ini' )|eq('enabled')}
        {ezscript_require( 'leanbackPlayer.ext.googleAnalyticsTracking.pack.js' )}
    {/if}
    {*ezscript_require( 'flash_detect_min.js' )*}
    {if ezini( 'xrowVideoSettings', 'EnableTrackingwithGA', 'xrowvideo.ini' )|eq('enabled')}
        {ezscript_require( 'xrowvideo_withtracking.js' )}
        <input type="hidden" name="hiddenleanbacktrackingGAID" id="hiddenleanbacktrackingGAID" value="{ezini( 'xrowVideoSettings', 'TrackingGAID', 'xrowvideo.ini' )}" />
    {else}
        {ezscript_require( 'xrowvideo.js' )}
    {/if}
    {/run-once}

    <div class="leanback-player-{$media_tag}"{if $media_tag|eq( 'audio' )}{$audio_width}{/if} {if $media_tag|eq( 'video' )}style="width:{$width}px;height:{$height}px;"{/if}>
        <!--[if gt IE 8]>
        <{$media_tag} {if $media_tag|eq( 'video' )}{$media_attributes}{else}{$control_attributes}{/if}{if $image_url|ne( '' )} poster="{$image_url}"{/if} data-objectid="{$attribute.contentobject_id}">
        <![endif]-->
        <!--[if lt IE 9]>
        <div {if $media_tag|eq( 'video' )}{$media_attributes}{else}{$control_attributes}{/if}{if $image_url|ne( '' )} poster="{$image_url}"{/if} data-objectid="{$attribute.contentobject_id}">
        <![endif]-->
        <!--[if !IE]>-->
        <{$media_tag} {if $media_tag|eq( 'video' )}{$media_attributes}{else}{$control_attributes}{/if}{if $image_url|ne( '' )} poster="{$image_url}"{/if} data-objectid="{$attribute.contentobject_id}">
        <!--<![endif]-->
            {foreach $objects as $item}
                {def $path = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}
                <source src={$path} type="{$item.mimetype}" />
                {undef $path}
            {/foreach}
            {foreach $objects as $item}
                {if $item.mimetype|contains('audio/mp3')}
                    {def $path_audio = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl('no','full')}
                {/if}
            {/foreach}
            {foreach $track.object.all_languages as $key => $language}
                {def $track_item = fetch('content', 'node', hash('node_id', $track.node_id,
                                                                 'language_code', $language.locale))
                     $src = concat( 'content/download/', $track_item.data_map.file.contentobject_id, '/', $track_item.data_map.file.id,'/', $track_item.data_map.file.version , '/', $track_item.data_map.file.content.original_filename|rawurlencode )|ezurl('no','full')
                     $kind = $track_item.data_map.kind.class_content.options[$track_item.data_map.kind.content.0].name
                     $lang = get_language($track_item.data_map.file.language_code)}
                <track enabled="true"{if $kind|eq('subtitles')} type="text/vtt"{/if} kind="{$kind}" label="{$track_item.name|wash()}" srclang="{$lang}" src="{$src}"></track>
                {undef $track_item $src $kind $lang}
            {/foreach}
            {undef $track}
            {if and( $media_tag|eq( 'video' ), $fallback_object )}
                {* Fallback Flash *}
                {def $path_fallback = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id, '/', $attribute.version, '/', $fallback_object.src|rawurlencode )|ezurl( 'no', 'full' )}
                <object class="leanback-player-flash-fallback" {$fallback_attributes} type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer.swf">
                    <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer.swf" />
                    <param name="allowFullScreen" value="true" />
                    <param name="wmode" value="transparent" />
                    <param name="bgcolor" value="#000000" />
                    {if $image_url}
                    <param name="flashVars" value="config={ldelim}'playlist':['{$image_url}', {ldelim}'url':'{$path_fallback}', 'autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')}, 'autobuffering':true{rdelim}]{rdelim}" />
                    {else}
                    <param name="flashVars" value="config={ldelim}'clip':{ldelim}'url':'{$path_fallback}','autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')},'autobuffering':true{rdelim}{rdelim}" />
                    {/if}
                </object>
            {elseif $media_tag|eq( 'audio' )}
                <object class="flow-player-flash-fallback" width="400" height="30" type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer-3.2.16.swf">
                    <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer-3.2.16.swf"/>
                    <param value="true" name="allowfullscreen"/>
                    <param name="wmode" value="transparent" />
                    <param value="always" name="allowscriptaccess"/>
                    <param value="high" name="quality"/>
                    <param value="#000000" name="bgcolor"/>
                    <param name="flashvars" value="config={ldelim}'plugins':{ldelim}'controls':{ldelim}'fullscreen':false,'height':30,'autoHide':false{rdelim}{rdelim},'clip':{ldelim}'autoPlay':false,'url':'{$path_audio}'{rdelim},'playlist':[{ldelim}'autoPlay':false,'url':'{$path_audio}'{rdelim}]{rdelim}"/>
                </object>
            {/if}
            {* Fallback HTML *}
            <div class="leanback-player-html-fallback" {$fallback_attributes}>
                {if $media_tag|eq( 'video' )}
                    <img src="{$image_url}" {$fallback_attributes} alt="Poster Image" title="No HTML5-Video playback capabilities found. Please download the video(s) below." />
                {/if}
            </div>
        <!--[if gt IE 8]>
           </{$media_tag}>
        <![endif]-->
        <!--[if lt IE 9]>
           </div>
        <![endif]-->
        <!--[if !IE]>-->
           </{$media_tag}>
        <!--<![endif]-->
    </div>

    {if $media_tag|eq( 'video' )}
        <div class="extra-flash-video" style="display:none;width:{$width}px;height:{$height}px;">
            {* Fallback Flash *}
            {def $path_fallback = concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id, '/', $attribute.version, '/', $fallback_object.src|rawurlencode )|ezurl( 'no', 'full' )}
                <object class="leanback-player-flash-fallback extra-flash-video" {$fallback_attributes} type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer.swf">
                   <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer.swf" />
                   <param name="allowFullScreen" value="true" />
                   <param name="wmode" value="transparent" />
                   <param name="bgcolor" value="#000000" />
                   <param name="flashVars" value="config={ldelim}'clip':{ldelim}'url':'{$path_fallback}','autoPlay':{cond( $attribute.content.settings.autoplay, 'true', 'false')},'autobuffering':true{rdelim}{rdelim}" />
                </object>
            {* Fallback HTML *}
            <div class="leanback-player-html-fallback" {$fallback_attributes}>
                {if $media_tag|eq( 'video' )}
                    <img src="{$image_url}" {$fallback_attributes} alt="Poster Image" title="No HTML5-Video playback capabilities found. Please download the video(s) below." />
                {/if}
            </div>
        </div>

        {if is_set($download)|not()}
            <div class="change-video video_with_html5"><p><span class="flash-version">Flash-Version</span><b class="separator-video"> | </b><span class="video-download">Video-Download</span></p></div>
            <div class="change-video video_with_nohtml5" style="display:none;"><p><span class="video-download">Video-Download</span></p></div>
            <div class="download-info" style="display:none;clear:left;">
                 <strong>Download Video:</strong>
                 {foreach $objects as $key => $item}
                    {def $name_parts = $item.originalfilename|explode( '.' )
                        $last_element = $name_parts|count()|dec()
                        $file_suffix = $name_parts.$last_element}
                    <a href={concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}><nobr>{$file_suffix}{if is_set( $item.width )} ({$item.width} x {$item.height}){/if}</nobr></a>{if $key|lt( $objects|count()|dec() )},{/if}
                    {undef $name_parts $last_element $file_suffix}
                 {/foreach}
            </div>
        {/if}
    {else}
        <!--[if gte IE 8]>
        {if is_set($download)|not()}
            <div class="download-info">
                <strong>Download Audio:</strong>
                {foreach $objects as $key => $item}
                    {def $name_parts = $item.originalfilename|explode( '.' )
                        $last_element = $name_parts|count()|dec()
                        $file_suffix = $name_parts.$last_element}
                    <a href={concat( 'xrowvideo/download/', $attribute.contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $item.src|rawurlencode )|ezurl()}><nobr>{$file_suffix}{if is_set( $item.width )} ({$item.width} x {$item.height}){/if}</nobr></a>{if $key|lt( $objects|count()|dec() )},{/if}
                    {undef $name_parts $last_element $file_suffix}
                {/foreach}
            </div>
        {/if}
        <![endif]-->
    {/if}
{else}
    {if $attribute.has_content|not()}
        <p>{'There is no file.'|i18n( 'design/standard/content/datatype' )}</p>
    {elseif $content.pending}
        <p>{'The media files will be created soon.'|i18n( 'design/standard/content/datatype' )}</p>
    {/if}
{/if}