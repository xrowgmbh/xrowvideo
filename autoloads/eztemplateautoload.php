<?php

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 
    'script' => 'extension/xrowvideo/autoloads/xrowvideooperator.php' , 
    'class' => 'xrowVideoOperator' , 
    'operator_names' => array( 
        'xrowvideo_get_filepath' 
    ) 
);
$eZTemplateOperatorArray[] = array(
    'script' => 'extension/xrowvideo/autoloads/xrowvideogetlanguagesoperator.php' ,
    'class' => 'xrowVideoGetLanguageOperator' ,
    'operator_names' => array(
        'get_language'
    )
);
?>