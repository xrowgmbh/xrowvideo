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
ConvertVideoFiles[]=ogv
ConvertVideoFiles[]=mp4
ConvertVideoFiles[]=flv
ConvertVideoFiles[]=webm

ConvertAudioFiles[]
ConvertAudioFiles[]=mp3
ConvertAudioFiles[]=oga

MaxVideoWidth=1920

[flv]
Program=ffmpeg
MimeType=video/x-flv
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# 1 = max, 31 = worst quality
Options[4]=-qscale 10
# set this parameter to resize the video to the max allowed width
Options[5]=-s
# sound
Options[6]=-ab 128000 -ar 44100
# output file
Options[7]=-f flv <converted_file>

[ogv]
Program=ffmpeg
MimeType=video/ogg
Options[]
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# 1 = max, 31 = worst quality
Options[4]=-qscale 10
# set this parameter to resize the video to the max allowed width
Options[5]=-s
# sound
Options[6]=-ab 128000 -ar 48000
# output file
Options[7]=-f ogg <converted_file>

[oga]
Program=ffmpeg
MimeType=audio/ogg
Options[]
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# drop audio
Options[3]=-vn -acodec libvorbis
# sound
Options[4]=-ab 128000 -ar 44100
# output file
Options[5]=-f ogg <converted_file>

[mp4]
Program=ffmpeg
MimeType=video/mp4
Options[]
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# 1 = max, 31 = worst quality
Options[4]=-qscale 10
# set this parameter to resize the video to the max allowed width
Options[5]=-s
# sound
Options[6]=-ab 128000 -ar 48000
# output file
Options[7]=-f mp4 <converted_file>

[mp3]
Program=ffmpeg
MimeType=audio/mp3
Options[]
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# sound
Options[3]=-ab 128000 -ar 48000
# output file
Options[4]=-f mp3 <converted_file>

[webm]
Program=ffmpeg
MimeType=video/webm
Options[]
# override output files
Options[1]=-y
# input file
Options[2]=-i <original_file>
# 1 = max, 31 = worst quality
Options[4]=-qscale 10
# set this parameter to resize the video to the max allowed width
Options[5]=-s
# sound
Options[6]=-ab 128000 -ar 48000
# output file
Options[7]=-f webm <converted_file>

