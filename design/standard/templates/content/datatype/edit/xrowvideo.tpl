{default attribute_base='ContentObjectAttribute'}
{* fetch the content of the class wich includes this datatype *}
{def $max_filesize = $attribute.contentclass_attribute.data_int1
     $file_button_text = 'add_file'
     $language = ezini( 'xrowVideoSettings', 'Language', 'xrowvideo.ini')
     $runtimes = ezini( 'xrowVideoSettings', 'Runtimes', 'xrowvideo.ini')
     $content = $attribute.content
     $video_extensions = ezini( 'xrowVideoSettings', 'VideoExtensions', 'xrowvideo.ini')|implode(',')
     $contentobject_id = $attribute.contentobject_id
     $media_tag = cond( and( is_set( $content.video ), $content.video|count|gt( 0 ) ), 'video', 'audio' )
     $media = cond( and( is_set( $content.video ), $content.video|count|gt( 0 ) ), $content.video, $content.audio )
     $width = first_set( $content.video.width, $content.audio.width, $content.settings.width )
     $height = first_set( $content.video.height,  $content.audio.height, $content.settings.height )
     $duration = first_set( $content.video.duration,  $content.audio.duration, 0 )}
{if and( is_set( $content['error'] ), $content['error']|eq( 1 ) )}
    <p>{'ffmpeg is not installed. Please contact the administrator.'|i18n( 'design/standard/content/datatype' )}</p>
{else}
    {* Current file. *}
    <div class="block">
    <label>{'Current file'|i18n( 'design/standard/content/datatype' )}:</label>
    {if $attribute.has_content}
        <table class="list" cellspacing="0">
        <tr>
            <th style="width: 50%">{'Filename'|i18n( 'design/standard/content/datatype' )}</th>
            <th>{'MIME type'|i18n( 'design/standard/content/datatype' )}</th>
            <th>{'Size'|i18n( 'design/standard/content/datatype' )}</th>
            {if and( $media_tag|eq( 'video' ), is_set( $width ) )}
            <th>{'Width'|i18n( 'design/standard/content/datatype' )} x {'Height'|i18n( 'design/standard/content/datatype' )}</th>
            {/if}
            <th>{'Duration'|i18n( 'design/standard/content/datatype' )}</th>
        </tr>
        <tr>
            <td><a target="_blank" href={concat( 'content/download/', $contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $content.binary.filename|rawurlencode, '/', $content.binary.original_filename|rawurlencode )|ezurl}>{$content.binary.original_filename}</a></td>
            <td>{$content.binary.mime_type}</td>
            <td>{$content.binary.filesize|si( byte )}</td>
            {if and( $media_tag|eq( 'video' ), is_set( $width ) )}
            <td>{$width} x {$height}</td>
            {/if}
            <td>{$duration|l10n( 'number' )} {'sec.'|i18n( 'design/standard/content/datatype' )}</td>
        </tr>
        </table>
    {else}
        <p>{'There is no file.'|i18n( 'design/standard/content/datatype' )}</p>
    {/if}
    {if and( is_set( $media.source ), $media.source|count|gt(0) )}
        <label style="margin-top: 1em;">{'Converted files'|i18n( 'design/standard/content/datatype' )}:</label>
        <table class="list" cellspacing="0">
            <tr>
                <th style="width: 50%">{'Filename'|i18n( 'design/standard/content/datatype' )}</th>
                <th>{'MIME type'|i18n( 'design/standard/content/datatype' )}</th>
                <th>{'Size'|i18n( 'design/standard/content/datatype' )}</th>
                {if $media_tag|eq( 'video' )}
                <th>{'Width'|i18n( 'design/standard/content/datatype' )} x {'Height'|i18n( 'design/standard/content/datatype' )}</th>
                {/if}
            </tr>
            {foreach $media.source as $mitem}
            <tr>
                <td>
                    <a target="_blank" href={concat( 'content/download/', $contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $mitem.src|rawurlencode, '/', $mitem.originalfilename|rawurlencode )|ezurl}>{$mitem.originalfilename|wash}</a>
                </td>
                <td>{$mitem.mimetype}</td>
                <td>{$mitem.filesize|si( byte )}</td>
                {if $media_tag|eq( 'video' )}
                <td>{$mitem.width} x {$mitem.height}</td>
                {/if}
            </tr>
            {/foreach}
        </table>
    {elseif $content.pending}
        <p>{'The transcoded media files for this version will be available soon.'|i18n( 'design/standard/content/datatype' )}</p>
    {elseif $attribute.object.data_map.[$attribute.contentclass_attribute_identifier].content.pending}
        <p>{'Please discard this draft or upload a new video file. The current version is currently getting encoded.'|i18n( 'design/standard/content/datatype' )}</p>
    {elseif $attribute.has_content}
        <p>{'The media files are not scheduled for conversion.'|i18n( 'design/standard/content/datatype' )}</p>
    {/if}

{* Remove button. *}
{if $content.binary}
    <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_delete_binary]" value="{'Remove'|i18n('design/standard/content/datatype')}" title="{'Remove the file from this draft.'|i18n( 'design/standard/content/datatype' )}" />
{else}
    <input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_delete_media]" value="{'Remove'|i18n('design/standard/content/datatype')}" disabled="disabled" />
{/if}
<!-- Load Queue widget CSS and jQuery -->
{ezcss_require( array( 'jquery.plupload.queue.css',
                       'xrowvideo.css' ) )}

<!-- Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now) -->
<script src="//bp.yahooapis.com/2.4.21/browserplus-min.js"></script>

<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
{ezscript_require( array( 'ezjsc::jquery',
                          'ezjsc::jqueryui',
                          'plupload.full.min.js',
                          'jquery.plupload.queue/jquery.plupload.queue.js',
                          concat( 'i18n/', $language, '.js' ) ) )}

{literal}
<script>
// Convert divs to queue widgets when the DOM is ready
$(function() {
    {/literal}
    var files=new Array();

    {if $content.binary}
        files[0]=new plupload.File('{$content.binary.filename}','{$content.binary.original_filename}',{$content.binary.filesize});
        files[0].status = plupload.DONE;
        files[0].percent = 100;
    {/if}
    {literal}

    function randomString() {
        var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
        var string_length = 10;
        var randomstring = '';
        for (var i=0; i<string_length; i++) {
            var rnum = Math.floor(Math.random() * chars.length);
            randomstring += chars.substring(rnum,rnum+1);
        }
        return randomstring;
    }
    var uploader = new plupload.Uploader({
        // General settings
        url : '{/literal}{concat( "xrowvideo/upload/",$attribute.id,"/",$attribute.version,"/",$attribute.language_code)|ezurl(no)}{literal}/' + randomString(),
        runtimes : '{/literal}{$runtimes}{literal}',
{/literal}{if $max_filesize|gt(0)}
        max_file_size: '{$max_filesize}mb',

        {/if}{literal}

        chunk_size: '1mb',
        max_retries: 3,
        unique_names: false,
        no_files_with_same_name: true,
        upload_on_publish: true,
        rename: false,
        language: '{/literal}{$language}{literal}',
        max_number_of_files: 1,

        // Specify what files to browse for
        filters: [
            {title : {/literal}"{"video files"|i18n( 'xrowvideo/edit' )}{literal}", extensions : "{/literal}{$video_extensions}{literal}"}
        ],

        // Silverlight settings
        silverlight_xap_url: '/extension/xrowvideo/design/standard/javascript/plupload.silverlight.xap',
        // Flash settings
        flash_swf_url: '/extension/xrowvideo/design/standard/javascript/plupload.flash.swf',

        multipart_params: { ezxform_token: $("#ezxform_token_js").attr( 'title' ) },

        browse_button : 'pickfiles',
        container : 'container',

    } );

    // Client side form validation
    var doupload = true;

    $("#editform input[type=submit]").each(function(){
        if(($(this).attr('name') + '').indexOf('DiscardButton', 0) === -1 && ($(this).attr('name') + '').indexOf('RelationUploadNew', 0) === -1)
        {
            $(this).click( function(e){
                $('input[type=submit]').attr('disabled', 'disabled');
//              alert( $(this).attr( 'name' ) );
                var myhtml = "<input name='" + $(this).attr( 'name' ) + "' type='hidden' value='1' />";
                e.preventDefault();
                if (uploader.files.length > 0) {
                    // When all files are uploaded submit form
                    uploader.bind('StateChanged', function() {
                        if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                            $("#editform").append( myhtml );
                            $("#editform").submit();
                        }
                    });
                    uploader.start();
                }
                else
                {
                    $("#editform").append( myhtml );
                    $("#editform").submit();
                }
                return true;
            });
        }
    });
    
    uploader.bind('Init', function(up, params) {
        $('#filelist').html("<!-- Current runtime: " + params.runtime + " -->");
    });

    uploader.init();

    uploader.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
            $('#filelist').html('');
            if ( up.files.length > 1 )
            {
                uploader.removeFile( up.files[0] );
            }
            $('#filelist').append(
                '<div id="' + file.id + '">' +
                file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
        });

        up.refresh(); // Reposition Flash/Silverlight
    });

    uploader.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
    });

    uploader.bind('Error', function(up, err) {
        $('#filelist').append("<div>{/literal}{"Error"|i18n( 'xrowvideo/edit' )}{literal}: " + err.code +
            ", {/literal}{"Message"|i18n( 'xrowvideo/edit' )}{literal}: " + err.message +
            (err.file ? ", {/literal}{"File"|i18n( 'xrowvideo/edit' )}{literal}: " + err.file.name : "") +
            "</div>"
        );

        up.refresh(); // Reposition Flash/Silverlight
    });

    uploader.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
    });

});

</script>

{/literal}
<div id="container" style="margin-top:1em;">
    <div id="filelist">{"No runtime found."|i18n( 'xrowvideo/edit' )}</div>
    <br />
    <button class="button" id="pickfiles" href="#" title="{"Select file"|i18n( 'xrowvideo/edit' )}">{"Select file"|i18n( 'xrowvideo/edit' )}</button>
</div>

<div class="block">
<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_controls">{'Controller'|i18n( 'design/standard/content/datatype' )}:</label>
    <input id="{$attribute_base}_media_{$attribute.id}_controls" type="checkbox" name="{$attribute_base}_data_media_has_controls_{$attribute.id}" value="1" {if $content.settings.controls|int}checked="checked"{/if} />
</div>

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_autoplay">{'Autoplay'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="checkbox" id="{$attribute_base}_media_{$attribute.id}_autoplay" name="{$attribute_base}_data_media_is_autoplay_{$attribute.id}" value="1" {if $content.settings.autoplay|int}checked="checked"{/if} />
</div>

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_loop">{'Loop'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="checkbox" id="{$attribute_base}_media_{$attribute.id}_loop" name="{$attribute_base}_data_media_is_loop_{$attribute.id}" value="1" {if $content.settings.loop|int}checked="checked"{/if} />
</div>

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_init_subtitle">{'Initialize subtitle'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="checkbox" id="{$attribute_base}_media_{$attribute.id}_init_subtitle" name="{$attribute_base}_data_media_init_subtitle_{$attribute.id}" value="1" {if $content.settings.init_sub|int}checked="checked"{/if} />
</div>

{* needed for manual conversion update *}

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_update">{'Update file info'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="checkbox" id="{$attribute_base}_media_{$attribute.id}_update" name="{$attribute_base}_data_media_update_{$attribute.id}" value="1" />
</div>

<div class="break"></div>
</div>
{/if}
