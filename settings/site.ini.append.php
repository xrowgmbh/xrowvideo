<?php /* #?ini charset="utf-8"?

[TemplateSettings]
ExtensionAutoloadPath[]=xrowvideo

[SSLZoneSettings]
ModuleViewAccessMode[xrowvideo/*]=keep

[RegionalSettings]
TranslationExtensions[]=xrowvideo

[RoleSettings]
# permission check inside both modules canRead in download and canEdit in upload
PolicyOmitList[]=xrowvideo/download
PolicyOmitList[]=xrowvideo/upload
PolicyOmitList[]=xrowvideo/embed
[HTTPHeaderSettings]
Cache-Control[/xrowvideo/embed]=public, must-revalidate, max-age=600
Pragma[/xrowvideo/embed]=
Expires[/xrowvideo/embed]=+600

*/ ?>