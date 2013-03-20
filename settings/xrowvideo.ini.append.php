<?php /* #?ini charset="utf-8"?

[xrowVideoSettings]
Runtimes=html5,gears,flash,silverlight,browserplus

# info: http://www.fileinfo.com/filetypes/video
VideoExtensions[]
VideoExtensions[]=mp4
VideoExtensions[]=mp3
VideoExtensions[]=avi
VideoExtensions[]=flv
VideoExtensions[]=mpg
VideoExtensions[]=mov
VideoExtensions[]=wmv
VideoExtensions[]=webm
VideoExtensions[]=wav

Language=en

ConvertVideoFiles[]
ConvertVideoFiles[]=mp4
ConvertVideoFiles[]=webm
ConvertVideoFiles[]=flv

ConvertAudioFiles[]
ConvertAudioFiles[]=mp3
ConvertAudioFiles[]=oga

MaxVideoWidth=1920

DefaultVideoForPlayer=360p

[VideoBitrateSettings]
UseVideoBitrate=enabled
Bitrates[]
# 1.920 × 1.080 Pixel, 7 MB/s Video, 192 Kbit/s Audio, 25fps - FULLHD
Bitrates[1080p]=-s 1920x1080 -b:v 7M -r 25 -ab 192K
# 1.280 × 720 Pixel, 4 MB/s Video, 128 Kbit/s Audio, 25fps - HALF HD
Bitrates[720p]=-s 1280x720 -b:v 4M -r 25
# 1.024 x 576 Pixel, 2.5 MB/s Video, 128 Kbit/s Audio, 25fps
#Bitrates[576p]=-s 1024x576 -b:v 2.5M -r 25
# 640 x 360 Pixel, 1.4 MB/s Video, 128 Kbit/s Audio, 25fps
Bitrates[360p]=-s 640x360 -b:v 1.4M -r 25
# 416 x 234 Pixel, 560 Kbit/s Video, 80 Kbit/s Audio, 25fps
Bitrates[234p]=-s 416x234 -b:v 560K -r 25 -ab 80K
# 384 x 216 Pixel, 360 Kbit/s Video, 80 Kbit/s Audio, 25fps
Bitrates[216p]=-s 384x216 -b:v 360K -r 25 -ab 80K

[flv]
Program=ffmpeg -y -i <original_file> <bitrate> <options> -f flv <converted_file>
MimeType=video/x-flv
# override output files
Options[]
Options[]=-ar 44100

[mp4]
Program=ffmpeg -y -i <original_file> <bitrate> <options> -f mp4 <converted_file>
MimeType=video/mp4
Options[]
Options[]=-acodec libvo_aacenc -threads 0

[webm]
Program=ffmpeg -y -i <original_file> <bitrate> <options> -f webm <converted_file>
MimeType=video/webm
Options[]

[mp3]
Program=ffmpeg -y -i <original_file> <options> -f mp3 <converted_file>
MimeType=audio/mp3
Options[]
Options[]=-ab 128000 -ar 48000

[oga]
Program=ffmpeg -y -i <original_file> <options> -f ogg <converted_file>
MimeType=audio/ogg
Options[]
Options[]=-ab 128000 -ar 48000
Options[]=-vn -acodec libvorbis

 */
?>