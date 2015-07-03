/* do: set player options */
LBP.options = {
    focusFirstOnInit: false, // focus first (video) player on initialization
    showSources: true, // if switch between available video qualities should be possible
    defaultTimerFormat: "PASSED_HOVER_REMAINING", // default timer format, could be "PASSED_DURATION" (default), "PASSED_REMAINING", "PASSED_HOVER_REMAINING"
    defaultLanguage: 'de',
    hideControls: false,
    triggerHTML5Events : [ "play", "pause" ],
};
if( $('input.hiddenleanbacktrackingGAID').length )
{
    var googleanalyticsid=$('input.hiddenleanbacktrackingGAID').val();
  //do: define Google Analytics Tracker extension option(s)
    LBP.gaTracker.options = {
            addJSCode: false, // true if extension should add
                                // Google Analytics async Javascript source code
            //profileID: "UA-64749560-1", // profile ID (web property ID, website ID)
            profileID: googleanalyticsid, // profile ID (web property ID, website ID)
                                            // events should be tracked for
            debug: true, // true if tracked events should be written to console
        }
        // do: define category to track for
        LBP.gaTracker.trackCategory = "Video-Events";
        // do: define events to be tracked
        LBP.gaTracker.trackEvents =
            ["VolumeChange", "RateChange", "Seeking", "Seeked", "Ended", "Play", "Pause"];


}

/*Feature #5636*/
$(document).ready(function(){
    function supports_video() { return !!document.createElement('video').canPlayType; }
    if (!supports_video()) 
    {
        $('.video_with_html5').hide();
        $('.video_with_nohtml5').css("display","block");
    }
   
    $('.video-download').bind('click', function()
    {
           $('.download-info').toggle();
           $('.download-info').css("float","left");
           $('.download-info').css("cursor","hand");
    });
    
    $('.flash-version').bind('click', function()
    {
          $('.leanback-player-video').remove();
          $('.extra-flash-video').css("display","block");
          $('.flash-version').remove();
          $('.separator-video').remove();
    });
    
    var bro=$.browser;
    var binfo="";
    if(navigator.appVersion.indexOf("MSIE") !== -1) {binfo="Microsoft Internet Explorer";}
    //if(bro.mozilla) {binfo="Mozilla Firefox";}
    //if(bro.safari) {binfo="Apple Safari";}
    //if(bro.opera) {binfo="Opera";}
    if(binfo == "Microsoft Internet Explorer")
    {
        $('.extra-flash-video').attr("classid","clsid:D27CDB6E-AE6D-11cf-96B8-444553540000");
    }
});
