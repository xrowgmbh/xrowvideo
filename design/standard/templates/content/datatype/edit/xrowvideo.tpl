{default attribute_base='ContentObjectAttribute'}
{* fetch the content of the class wich includes this datatype *}
{def $max_filesize = $attribute.contentclass_attribute.data_int1
     $file_button_text = 'add_file'
     $language = ezini( 'xrowVideoSettings', 'Language', 'xrowvideo.ini')
     $runtimes = ezini( 'xrowVideoSettings', 'Runtimes', 'xrowvideo.ini')
     $content=$attribute.content
     $video_extensions=ezini( 'xrowVideoSettings', 'VideoExtensions', 'xrowvideo.ini')|implode(',')
     $contentobject_id = $attribute.contentobject_id
     $width=first_set( $content.video.width, $content.audio.width, $content.settings.width )
     $height=first_set( $content.video.height,  $content.audio.height, $content.settings.height )
     $duration=first_set( $content.video.duration,  $content.audio.duration, 0 )
}
{if and( is_set( $content['error'] ), $content['error']|eq( 1 ) )}
    <p>{'ffmpeg is not installed. Please contact the administrator.'|i18n( 'design/standard/content/datatype' )}</p>
{else}
    {* Current file. *}
    <div class="block">
    <label>{'Current file'|i18n( 'design/standard/content/datatype' )}:</label>
    {if $attribute.has_content}
        {def $media_tag=cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt(0) ), 'video', 'audio' )
             $media=cond( and( is_set( $attribute.content.video ), $attribute.content.video|count|gt(0) ), $attribute.content.video, $attribute.content.audio )}
        <table class="list" cellspacing="0">
        <tr>
            <th>{'Filename'|i18n( 'design/standard/content/datatype' )}</th>
            <th>{'MIME type'|i18n( 'design/standard/content/datatype' )}</th>
            <th>{'Size'|i18n( 'design/standard/content/datatype' )}</th>
            {if $media_tag|eq( 'video' )}
            <th>{'Width'|i18n( 'design/standard/content/datatype' )} x {'Height'|i18n( 'design/standard/content/datatype' )}</th>
            {/if}
            <th>{'Duration'|i18n( 'design/standard/content/datatype' )}</th>
        </tr>
        <tr>
            <td><a target="_blank" href={concat( 'xrowvideo/download/', $contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $content.binary.filename|rawurlencode, '/', $content.binary.original_filename|rawurlencode )|ezurl}>{$content.binary.original_filename}</a></td>
            <td>{$content.binary.mime_type}</td>
            <td>{$content.binary.filesize|si( byte )}</td>
            {if $media_tag|eq( 'video' )}
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
                <th>{'Filename'|i18n( 'design/standard/content/datatype' )}</th>
            {*    <th>{'Codec'|i18n( 'design/standard/content/datatype' )}</th> *}
                <th>{'Size'|i18n( 'design/standard/content/datatype' )}</th>
            </tr>
            {foreach $media.source as $mitem}
            <tr>
            <td><a target="_blank" href={concat( 'xrowvideo/download/', $contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $mitem.src|rawurlencode, '/', $mitem.originalfilename|rawurlencode )|ezurl}>{$mitem.originalfilename|wash}</a>
<{$media_tag} preload="none" controls="controls" 
onerror="alert('Cannot play provided codecs.')"
src={concat( 'xrowvideo/download/', $contentobject_id, '/', $attribute.id,'/', $attribute.version , '/', $mitem.src|rawurlencode, '/', $mitem.originalfilename|rawurlencode )|ezurl} />
</td>
            {*    <td>{'Codec'|i18n( 'design/standard/content/datatype' )} {$video.codecs|wash}</td> *}
                <td>{$mitem.filesize|si( byte )}</td>
            </tr>
            {/foreach}
            </table>
    {else}
        <p>{'The media files will be created soon.'|i18n( 'design/standard/content/datatype' )}</p>
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
                          'plupload.full.js',
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

    $("#editform input[type=submit][name!=DiscardButton]").click( function(e){
        $('input[type=submit]').attr('disabled', 'disabled');
        //alert( $(this).attr( 'name' ) );
        var myhtml = "<input name='" + $(this).attr( 'name' ) + "' type='hidden' value='1' />";
        //alert( myhtml );
        e.preventDefault();
        //var uploader = $('#uploader').pluploadQueue();
        if (uploader.files.length > 0) {
            // When all files are uploaded submit form
            uploader.bind('StateChanged', function() {
                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                    //alert( 'upload done' );
                    $("#editform").append( myhtml );
                    $("#editform").submit();
                }
            });
            // deactivate all submit buttons
            //$('form').submit(function(){
                // On submit disable its submit button
            //    $('input[type=submit]', this).attr('disabled', 'disabled');
            //});
            uploader.start();
        }
        else
        {
            //alert( 'no upload' );
            $("#editform").append( myhtml );
            $("#editform").submit();
        }
        return true;
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

{* deactived width and height *}
{*
<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_width">{'Width'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="text" id="{$attribute_base}_media_{$attribute.id}_width" name="{$attribute_base}_data_media_width_{$attribute.id}" size="5" value="{$content.settings.width|wash}" />
</div>

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_height">{'Height'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="text" id="{$attribute_base}_media_{$attribute.id}_height" name="{$attribute_base}_data_media_height_{$attribute.id}" size="5" value="{$content.settings.height|wash}" />
    &nbsp;
    &nbsp;
    &nbsp;
</div>
*}

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
{* needed for manual conversion update *}

<div class="element">
    <label for="{$attribute_base}_media_{$attribute.id}_update">{'Update file info'|i18n( 'design/standard/content/datatype' )}:</label>
    <input type="checkbox" id="{$attribute_base}_media_{$attribute.id}_update" name="{$attribute_base}_data_media_update_{$attribute.id}" value="1" />
</div>

<div class="break"></div>
</div>
{/if}