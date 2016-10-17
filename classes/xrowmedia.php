<?php

/**
 * xrowMedia handles the xml of the xrow video datatype
 * @author Georg Franz
 *
 *  <media>
 *      <video width="320" height="240" duration="">
            <source src="movie.mp4" type="video/mp4" codecs="vp8.0, vorbis" />
            <source src="movie.ogg" type="video/ogg" codecs="vp8.0, vorbis" />
        </video>
        <!-- optional, if audio -->
        <audio duration="60">
            <source src="song.ogg" type="audio/ogg" />
            <source src="song.mp3" type="audio/mpeg" />
        </audio>
        <settings autoplay="1" controls="1" loop="1" width='' height='' />
    </media>

 */

class xrowMedia
{
    function __construct( eZContentObjectAttribute $contentObjectAttribute )
    {
        $xmlString = $contentObjectAttribute->attribute( 'data_text' );
        if ( trim( $xmlString ) == '' )
        {
            $xmlString = "<?xml version='1.0'?><media />";
        }
        $this->xml = simplexml_load_string( $xmlString );
        if ( !( $this->xml instanceof SimpleXMLElement ) )
        {
            eZDebug::writeError( $xmlString, 'xrowVideo - xml error' );
        }

        $this->attribute = $contentObjectAttribute;
        $this->updateSettings();
    }

    public function getXMLData( $root, $original = false )
    {
        $result = array();
        $nArray = array();
        if ( isset( $this->xml->$root ) )
        {
            foreach( $this->xml->$root->attributes() as $key => $item )
            {
                $result[$key] = (string) $item;
            }
            if ( $this->xml->$root->count() )
            {
                $i = 0;
                # get children which are not the original and have a good status
                if( $original === false )
                {
                    $nArray =  $this->xml->$root->xpath( "//source[@show=1 and @status = " . self::STATUS_CONVERSION_FINISHED . "]" );
                }
                else
                {
                    $nArray =  $this->xml->$root->xpath( "//source[@original=1 and @status = " . self::STATUS_CONVERSION_FINISHED . "]" );
                }
                if( count( $nArray ) > 0 )
                {
                    foreach ( $nArray as $key => $item )
                    {
                        foreach ( $item->attributes() as $akey => $aitem )
                        {
                            $result['source'][$i][$akey] = (string) $aitem;
                        }
                        $i++;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * set settings to xml
     */
    public function setSettings()
    {
        foreach ( $this->settings as $key => $item )
        {
            $this->xml->settings[$key] = $item;
        }
    }

    /**
     * get settings from xml
     */
    public function updateSettings()
    {
        if ( !isset( $this->xml->settings ) )
        {
            $mysettings = $this->xml->addChild( 'settings' );
            foreach( $this->settings as $key => $item )
            {
                $mysettings[$key] = $item;
            }
        }
        foreach ( $this->settings as $key => $item )
        {
            $this->settings[$key] = (string) $this->xml->settings[$key];
        }
    }

    public function hasPendingAction( $like = false )
    {
        $info = $this->attribute->ID . '-' . $this->attribute->Version;

        $cond = array( 'param' => $info,
                       'action' => "xrow_convert_media" );
        if( $like )
        {
            $cond['param'] = array( 'like', $this->attribute->ID . '-%' );
        }
        $obj = eZPendingActions::fetchObject( eZPendingActions::definition(), null, $cond );
        if ( $obj )
        {
            return $obj;
        }
        else
        {
            return false;
        }
    }

    public function addPendingAction()
    {
        $info = $this->attribute->ID . '-' . $this->attribute->Version;

        $row = array( 'param' => $info,
                      'created' => time(),
                      'action' => "xrow_convert_media" );

        $obj = new eZPendingActions( $row );
        $obj->store();
    }

    private function isAudio( FFMpeg\FFProbe\DataMapping\StreamCollection $collection )
    {
        if ( count( $collection->videos() ) === 0 )
        {
            return true;
        }
        else
        {
            # MP3 files with an MJPEG image are considered as a video codec, but we want to handle them as audio
            # See Ticket #9856
            foreach($collection->videos() as $video)
            {
                if ($video->get("codec_name") === "mjpeg")
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Update the information of a uploaded media file
     * The local copy needs to be deleted outside
     */
    public function updateMediaInfo()
    {
        if ( $this->attribute->hasContent() )
        {
            eZDebug::writeDebug( 'update media info', __METHOD__ );
            $content = $this->attribute->content();
            if ( isset( $this->xml->video ) )
            {
                unset( $this->xml->video );
            }
            if ( isset( $this->xml->audio ) )
            {
                unset( $this->xml->audio );
            }
            $binary = $content['binary'];
            if ( $binary instanceof eZBinaryFile )
            {
                # new object, get data...
                $filePath = $binary->filePath();
                $file = eZClusterFileHandler::instance( $filePath );
                if ( !file_exists( $filePath ) )
                {
                    $file->fetch();
                }

                if ( $filePath{0} != '/' )
                {
                    $docRoot = eZSys::rootDir();
                    $filePath = $docRoot . DIRECTORY_SEPARATOR . $filePath;
                    $filePath = str_replace( array( "/", "\\" ), array( DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ), $filePath );
                }

                $this->addInfo( $filePath, $binary->attribute( 'mime_type' ) );
                $file->deleteLocal();
            }
            else
            {
                eZDebug::writeError( 'xrowvideo: Object '.$this->attribute->ID . ', version' . $this->attribute->Version.', has no binary file' );
            }
        }
    }

    /**
     * Add video meta information
     * @param ffmpeg_movie $mov
     * @param string $mimeType
     * @param string $filePath
     */
    public function addInfo( $filePath, $mimeType )
    {
        $probe = FFMpeg\FFProbe::create();
        $collection = $probe->streams( $filePath );
        if ( !self::isAudio( $collection ) )
        {
            eZDebug::writeDebug( 'File is a video', __METHOD__ );
            $stream = $collection->videos()->first();
            $dimensions = $stream->getDimensions();
            $format = $probe->format( $filePath );

            if ( !isset( $this->xml->video ) )
            {
                $video = $this->xml->addChild( 'video' );
            }
            $height = $dimensions->getHeight();
            if ( $height > 0 )
            {
                $this->xml->video['height'] = $height;
            }
            $width = $dimensions->getWidth();
            if ( $width > 0 )
            {
                $this->xml->video['width'] = $width;
            }
            $duration = (float) $format->get("duration");
            if ( $duration > 0 )
            {
                $this->xml->video['duration'] = $duration;
            }
            $this->xml->video['status'] = self::STATUS_NEEDS_CONVERSION;
        }
        elseif ( self::isAudio( $collection ) )
        {
            eZDebug::writeDebug( 'File is a audio', __METHOD__ );
            $stream = $collection->audios()->first();
            $format = $probe->format( $filePath );
            if ( !isset( $this->xml->audio ) )
            {
                $audio = $this->xml->addChild( 'audio' );
            }
            $duration = (float) $format->get("duration");
            $this->xml->audio['duration'] = $duration;
            $this->xml->audio['status'] = self::STATUS_NEEDS_CONVERSION;
        }
    }

    /**
     * Checks the content type
     * @param string $contentType
     * @return bool
     */
    static function checkMediaType( $contentType )
    {
        $types = explode( '/', mb_strtolower( $contentType ) );
        $type = $types[0];
        if ( $type == 'audio' )
        {
            return self::TYPE_AUDIO;
        }
        elseif ( $type == 'video' )
        {
            return self::TYPE_VIDEO;
        }
        else
        {
            return self::TYPE_UNKNOWN;
        }
    }

    /**
     * get the new filename
     * @param string $filePath
     * @param string $extension
     */
    static function newFileName( $fileParts, $extension )
    {
        $filePath = $fileParts['dirname'] . DIRECTORY_SEPARATOR . $fileParts['filename'] . '.' . $extension;
        $filePath = str_replace( array( "/", "\\" ), array( DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ), $filePath );
        return $filePath;
    }

    /**
     * Build the command line according to settings
     * @param $original_file : original full file path
     * @param $converted_file : converted full file path
     */
    public function buildCommandLine( $original_file, $converted_file, $settings, $bitrate )
    {
        $array_search = array();
        $array_search[] = '/<original_file>/';
        $array_search[] = '/<bitrate>/';
        $array_search[] = '/<options>/';
        $array_search[] = '/<converted_file>/';
        $option = '';
        $ini = eZINI::instance( 'xrowvideo.ini' );
        foreach( $settings['options'] as $opt )
        {
            if ( trim( $opt ) == "-s" )
            {
                // if maxwidth is higher than the original width
                $optStr = '';
                if ( isset( $this->xml->video ) )
                {
                    if ( $ini->hasVariable( 'xrowVideoSettings', 'MaxVideoWidth' ) )
                    {
                        $maxWidth = $ini->variable( 'xrowVideoSettings', 'MaxVideoWidth' );
                        $result = $this->xml->xpath( "//source[@original=1]" );
                        if ( count( $result ) > 0 )
                        {
                            $source = $result[0];
                            if ( !isset(  $source['width'] ) || !isset( $source['height'] ) || (string) $source['width'] == '' || (string) $source['height'] == '' )
                            {
                                $this->updateFileInfo( $source );
                            }
                            if ( isset( $source['width'] ) && (int) $source['width'] > 0 && (int) $source['width'] > $maxWidth )
                            {
                                $newHeight =  round( $maxWidth * (int) $source['height'] / (int) $source['width'], 0 );
                                // the new height must be direct number
                                if ( $newHeight % 2 > 0 )
                                {
                                    $newHeight++;
                                }
                                $optStr = "-s " . $maxWidth . "x" . $newHeight;
                            }
                        }
                    }
                }
            }
            else
            {
                $optStr = $opt;
            }
            if ( trim( $optStr ) != '' )
            {
                $option .= ' ' . $optStr;
            }
        }
        $array_replace = array();
        $array_replace[] = $original_file;
        $array_replace[] = $bitrate;
        $array_replace[] = $option;
        $array_replace[] = $converted_file;

        $command = preg_replace( $array_search, $array_replace, $settings['program'] );
        return $command;
    }

    /**
     * Register the new source
     * @param string $fileName
     */
    public function registerFile( $filePath, SimpleXMLElement $root )
    {
        $source = $this->searchFile( $filePath, $root );
        $source['status'] = self::STATUS_CONVERSION_IN_PROGRESS;
        # save the xml
        $this->saveData();
        return $source;
    }

    /**
     * search a file within the audio / video tag
     * create a new entry if not found
     * @param string $filePath
     * @param string $root
     * @param string $addSource
     * @return SimpleXMLElement|boolean
     */
    public function searchFile( $filePath, SimpleXMLElement $root, $addSource = true )
    {
        $fileName = pathinfo( $filePath, PATHINFO_BASENAME );
        if ( count( $root->source ) > 0 )
        {
            $result = $root->xpath( "//source[@src='" . $fileName . "']" );
            if ( count( $result ) > 0 )
            {
                return $result[0];
            }
        }
        if ( $addSource )
        {
            $newSource = $root->addChild( 'source' );
            $newSource['original'] = 0;
            $newSource['src'] = $fileName;
            return $newSource;
        }
        return false;
    }

    /**
     * save the xml and the world
     */
    public function saveData()
    {
        $xml = $this->xml->saveXML();
        $this->attribute->setAttribute( 'data_text', $xml );
        # delete attribute cache
        $this->attribute->Content = null;
        $this->attribute->store();
    }

    /**
     * Update the status of the converted file
     * @param SimpleXMLElement $source
     * @param int $status
     */
    public function setStatus( SimpleXMLElement $source, $status )
    {
        $source['status'] = $status;
        if ( $status == self::STATUS_CONVERSION_FINISHED && $source['original'] == 0 )
        {
            $source['show'] = 1;
        }
        else
        {
            $source['show'] = 0;
        }
        $this->saveData();
    }
    
    /**
     * Update the Error-Counter of the converted file
     * @param SimpleXMLElement $source
     */
    public function setErrorCounter( SimpleXMLElement $source)
    {
        if(isset($source['errorcounter'])) {
            $count_temp = (integer) $source['errorcounter'];
            $source['errorcounter'] = (string) ++$count_temp;
        } else {
            $source['errorcounter'] = 1;
        }
        $this->saveData();
    }
    
    /**
     * Reset the Error-Counter of the converted file
     * @param SimpleXMLElement $source
     */
    public function resetErrorCounter( SimpleXMLElement $source)
    {
        $source['errorcounter'] = 0;
        $this->saveData();
    }
    /**
     * Limited the Error-Counter to 2
     * @param SimpleXMLElement $source
     */
    public function limitErrorCounter( SimpleXMLElement $source)
    {
        $source['errorcounter'] = 2;
        $this->saveData();
    }
    /**
     * Get the Error-Counter of the converted file
     * @param SimpleXMLElement $source
     * @return Value of Error-Counter
     */
    public function getErrorCounter( SimpleXMLElement $source)
    {
        return $source['errorcounter'];
    }

    /**
     * Update the codec of the converted file
     * Local copy of file needs to be deleted outside
     * @param SimpleXMLElement $source
     */
    public function updateFileInfo( SimpleXMLElement $source )
    {
        $content = $this->attribute->content();
        $binary = $content['binary'];
        if ( $binary instanceof eZBinaryFile )
        {
            # new object, get data...
            $dirName = pathinfo( $binary->filePath(), PATHINFO_DIRNAME );

            $docRoot = eZSys::rootDir();
            $filePath = $dirName . DIRECTORY_SEPARATOR . $source['src'];
            $file = eZClusterFileHandler::instance(  $binary->filePath() );
            if ( !file_exists( $binary->filePath() ) )
            {
                $file->fetch();
            }

            if ( $filePath{0} != '/' )
            {
                $mfilePath = $docRoot . DIRECTORY_SEPARATOR . $filePath;
                $mfilePath = str_replace( array( "/", "\\" ), array( DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ), $mfilePath );
            }
            $probe = FFMpeg\FFProbe::create();
            $collection = $probe->streams( $mfilePath );
            $stream = $collection->first();
            $format = $probe->format( $filePath );

            $source['codecs'] = $stream->get("codec_name");

            $file = eZClusterFileHandler::instance( $filePath );
            $stat = $file->stat();
            $source['filesize'] = $stat['size'];

            $oFileName = pathinfo( $binary->attribute( 'original_filename' ) );
            $nExt = pathinfo( $source['src'], PATHINFO_EXTENSION );

            $source['originalfilename'] = pathinfo( self::newFileName( $oFileName, $nExt ), PATHINFO_BASENAME );

            if ( isset( $this->xml->video ) )
            {
                $stream = $collection->videos()->first();
                $dimensions = $stream->getDimensions();
                $source['height'] = $dimensions->getHeight();
                $source['width'] = $dimensions->getWidth();
                //$source['aratio'] = $this->getAspectRatio( $source['width'], $source['height'] );
                /**
                 * @TODO: videos with different width / height settings are not possible at the moment
                 * ffmpeg cannot read size from webm format
                 */
                if ( (string) $source['height'] != '' and (string) $source['height'] > 0 )
                {
                    $this->xml->video['height'] = (string) $source['height'];
                }
                if ( (string) $source['width'] != '' and (string) $source['width'] > 0 )
                {
                    $this->xml->video['width'] = (string) $source['width'];
                }
                $duration = (float) $format->get("duration");
                if ( $duration > 0 )
                {
                    $this->xml->video['duration'] = $duration;
                }
            }
            $this->saveData();
        }
        else
        {
            eZDebug::writeError( 'xrowvideo: Object '.$this->attribute->ID . ', version' . $this->attribute->Version.', has no binary file' );
        }
    }

    function getAspectRatio( $width, $height )
    {
        $widthOrig = $width;
        $heightOrig = $height;
        while( $height != 0 )
        {
            $remainder = $width % $height;
            $width = $height;
            $height = $remainder;
        }
        $gcd = abs( $width );
        $widthOrig = $widthOrig / $gcd;
        $heightOrig = $heightOrig / $gcd;
        $ratio = $widthOrig . ":" . $heightOrig;
        return $ratio;
    }  

    /**
     * Last but not least - delete the stuff
     * @param unknown_type $version
     */
    function deleteConvertedFiles()
    {
        # get all converted files execpt the original
        $result = $this->xml->xpath( "//source[@original=0]" );

        $storageDir = eZSys::storageDirectory();
        $group = "audio";
        if ( isset( $this->xml->video ) )
        {
            $group = "video";
        }
        $dirName = $storageDir . '/original/' . $group;
        foreach( $result as $source )
        {
            $filePath = $dirName . DIRECTORY_SEPARATOR . $source['src'];
            $filePath = str_replace( array( "/", "\\" ), array( DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ), $filePath );
            $convertedFile = eZClusterFileHandler::instance( $filePath );

            if ( $convertedFile->exists() )
            {
                $convertedFile->delete();
                eZDebug::writeDebug( $filePath, 'xrowvideo file has been deleted.' );
            }
            else
            {
                eZDebug::writeDebug( $filePath . ' not exists.', __METHOD__ );
            }
            unset( $source );
        }
    }

    public function storedFileInfo( $fileName )
    {
        $result = $this->xml->xpath( "//source[@src='$fileName' and @original=0]" );
        if ( count( $result ) == 0 )
        {
            $result = $this->xml->xpath( "//source[@originalfilename='$fileName' and @original=0]" );
        }
        if ( count( $result ) == 0 )
        {
            $result = $this->xml->xpath( "//source[@src='$fileName' and @original=1]" );
        }
        if ( count( $result ) > 0 )
        {
            $content = $this->attribute->content();
            $binary = $content['binary'];
            if ( $binary instanceof eZBinaryFile )
            {
                $dirName = pathinfo( $binary->filePath(), PATHINFO_DIRNAME );

                $res = array( 'filename' => (string) $result[0]['src'],
                              'original_filename' => (string) $result[0]['originalfilename'],
                              'filepath' => $dirName . '/' . (string) $result[0]['src'],
                              'mime_type' => (string) $result[0]['mimetype'] );
                return $res;
            }
            else
            {
                eZDebug::writeError( 'xrowvideo: Object '.$this->attribute->ID . ', version' . $this->attribute->Version.', has no binary file' );
            }
        }
        else
        {
            eZDebug::writeError( $fileName . ' - file not found', 'xrowvideo' );
        }
        return false;
    }

    static public function updateGivenAttributesDataText( $givenAttributes, $mediaContent, $binary )
    {
        foreach( $givenAttributes as $givenAttribute )
        {
            $givenAttributeContent = $givenAttribute->content();
            $found = false;
            $givenAttributeContent['media']->xml->video->attributes()->status = self::STATUS_CONVERSION_FINISHED;
            if( isset( $givenAttributeContent['media']->xml->video->source ) )
            {
                if( isset( $givenAttributeContent['media']->xml->video->source[0] ) )
                {
                    if( isset( $givenAttributeContent['media']->xml->video->source[0]->attributes()->src ) && 
                        isset( $givenAttributeContent['media']->xml->video->source[0]->attributes()->src[0] ) )
                    {
                        if( (string) $givenAttributeContent['media']->xml->video->source[0]->attributes()->src == $binary->Filename )
                        {
                            $found = true;
                        } elseif ((string) $givenAttributeContent['binary']->Filename == $binary->Filename)
                        {
                            $found = true;
                        }
                    }
                    else
                    {
                        $found = true;
                    }
                }
                // check if the video is exist and has the same name or video is not exist
                unset( $givenAttributeContent['media']->xml->video->source );
            }
            else
            {
                $found = true;
            }
            if( $found )
            {
                foreach( $mediaContent['media']->xml->video->source as $item )
                {
                    $xml = $givenAttributeContent['media']->xml->video->addChild( $item->getName() );
                    foreach( $item->attributes() as $name => $value )
                    {
                        $xml->addAttribute( $name, $value );
                    }
                }
                $givenAttributeContent['media']->saveData();
            }
        }
    }

    const TYPE_VIDEO = 1;
    const TYPE_AUDIO = 2;
    const TYPE_UNKNOWN = 0;

    const STATUS_NEEDS_CONVERSION = 0;
    const STATUS_CONVERSION_IN_PROGRESS = 1;
    const STATUS_CONVERSION_FINISHED = 2;
    const STATUS_CONVERSION_ERROR = 3;

    public $xml;
    public $attribute;
    public $settings = array( 'autoplay' => 0,
                              'loop' => 0,
                              'controls' => 1,
                              'width' => '',
                              'height' => '',
                              'init_sub' => '' );
}
