<!DOCTYPE html>
<html>
	<head>
		<title>{$module_result.name|wash()}</title>
 
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		
		<link href="/extension/xrowvideo/design/standard/stylesheets/leanbackPlayer.modified.css" type="text/css" rel="stylesheet">
		<link href="/extension/xrowvideo/design/standard/stylesheets/leanbackPlayer.default.css" type="text/css" rel="stylesheet">
		<script charset="utf-8" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.js" type="text/javascript"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.en.js"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.de.js"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.fr.js"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.nl.js"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.es.js"></script>
		<script type="text/javascript" src="/extension/xrowvideo/design/standard/javascript/leanbackPlayer.ru.js"></script>
		{literal}
			<script type="text/javascript">
				/* do: set player options */
				LBP.options = {
				    //focusFirstOnInit: true, // focus first (video) player on initialization
				    showSources: true, // if switch between available video qualities should be possible
				    defaultTimerFormat: "PASSED_HOVER_REMAINING", // default timer format, could be "PASSED_DURATION" (default), "PASSED_REMAINING", "PASSED_HOVER_REMAINING"
				    defaultLanguage: 'de',
				    hideControls: false
				};
			</script>
		{/literal}

		{literal}
		<style type="text/css">
			body {
				position: absolute; border: 0; margin: 0; padding: 0;
			}
		</style>
		{/literal}
	</head>
	<body>
		{$module_result.content}
		<!--DEBUG_REPORT-->
	</body>
</html>