/* do: set player options */
LBP.options = {
    //focusFirstOnInit: true, // focus first (video) player on initialization
    showSources: true, // if switch between available video qualities should be possible
    defaultTimerFormat: "PASSED_HOVER_REMAINING", // default timer format, could be "PASSED_DURATION" (default), "PASSED_REMAINING", "PASSED_HOVER_REMAINING"
    defaultLanguage: 'de',
    hideControls: false
};

/* flash aktivieren oder deaktivieren*/
$(function(){
    if(FlashDetect.installed){      	
        $(".download-info").remove() ;
    }else{
    	$(".flow-player-flash-fallback").remove();
    }
});