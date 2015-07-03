<?php /* #?ini charset="utf-8"?

[xrowVideoSettings]
Runtimes=html5,gears,flash,silverlight,browserplus
EnableTrackingwithGA=disabled
TrackingGAID=1234567-8

# enable and set this time, if your DB timeouts are smaller than 10000
#WaitingTimeOutTime=100000
#InteractiveTimeOutTime=100000

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

DefaultVideoForPlayer=720p

#KeepProportion=enabled

UseVideoBitrate=enabled
ConvertCommandReplace[]
ConvertCommandReplace[VideoBitrate]=-b:v
ConvertCommandReplace[AudioBitrate]=-ab
ConvertCommandReplace[FramesPerSecond]=-r
Bitrates[]
Bitrates[]=1080p
Bitrates[]=720p
Bitrates[]=360p
Bitrates[]=240p

[Bitrate_1080p]
Height=1080
# if KeepProportion is enabled Width will not be considered
Width=1920
VideoBitrate=7M
AudioBitrate=192K
FramesPerSecond=25

[Bitrate_720p]
Height=720
Width=1280
VideoBitrate=4M
AudioBitrate=128K
FramesPerSecond=25

[Bitrate_360p]
Height=360
Width=640
VideoBitrate=1.4M
AudioBitrate=128K
FramesPerSecond=25

[Bitrate_240p]
Height=240
Width=427
VideoBitrate=560K
AudioBitrate=80K
FramesPerSecond=25

[flv]
Program=ffmpeg -y -i <original_file> <bitrate> <options> -f flv <converted_file>
MimeType=video/x-flv
# override output files
Options[]
Options[]=-ar 44100

[mp4]
Program=ffmpeg -y -profile:v baseline -i <original_file> <bitrate> <options> -f mp4 <converted_file>
# " -movflags faststart" for later
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

*/?>
