<?php /* #?ini charset="utf-8"?

[xrowVideoSettings]
Runtimes=html5
EnableTrackingwithGA=disabled
TrackingGAID=1234567-8
AsyncFileTransfer=disabled

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
defaultSubtitleLanguage=en
initSubtitle=false

ConvertVideoFiles[]
ConvertVideoFiles[]=mp4

ConvertAudioFiles[]
ConvertAudioFiles[]=mp3

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

[mp4]
Program=ffmpeg -y -i <original_file> <bitrate> <options> -profile:v baseline -f mp4 <converted_file>
# " -movflags faststart" for later
MimeType=video/mp4
Options[]
Options[]=-strict experimental -c:a aac
Options[]=-threads 0

[mp3]
Program=ffmpeg -y -i <original_file> <options> -f mp3 <converted_file>
MimeType=audio/mp3
Options[]
Options[]=-ab 128000 -ar 48000
Options[]=-threads 0

#[ErrorSettings]
#ReceiverArray[]
#ReceiverArray[]=test@example.com

*/?>
