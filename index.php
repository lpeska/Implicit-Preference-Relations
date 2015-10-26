<?php
/*
 * Primary file initializing static files, database and evaluates each experimented confugiration
 */
gc_enable();
include 'getObjectVisibility.php';
include 'ObjectAttributesBinarization.php';
include 'trainTestSplit.php';
include 'IPR.php';
include 'EvaluateConfiguration.php';
include 'SimilarCategoryItems.php';
include 'staticData.php';
$typ = "slantour";
 //tuto cast lze vynechat pri opakovanem spousteni - data ulozena v DTB 
new getObjectVisibility($typ);
new ObjectsAttributesBinarization($typ);
new TrainTestSplit($typ);
new SimilarCategoryItems($typ);
/*konec vynechatelne casti*/
staticData::init($typ);
/*spustim konkretni konfiguraci*/       



new EvaluateConfiguration($typ, "RAND", 0, "", 0, 0, 0,"");      
new EvaluateConfiguration($typ, "SIMCAT", 0, "", 0, 0, 0,"");    
new EvaluateConfiguration($typ, "VSM", 0, "", 0, 0, 0,"");   
new EvaluateConfiguration($typ, "VSM_TF", 0, "", 0, 0, 0,"");
new EvaluateConfiguration($typ, "Popular", 0, "", 0, 0, 0,"");  
new EvaluateConfiguration($typ, "IPR", 0, "", 0, 0, 0,"");  
new EvaluateConfiguration($typ, "MF", 0, "", 0, 0, 0,"", 10);              
new EvaluateConfiguration($typ, "MF", 0, "", 0, 0, 0,"", 50);          
new EvaluateConfiguration($typ, "MF", 0, "", 0, 0, 0,"", 200);     


new EvaluateConfiguration($typ, "MF_IPR", 0.5, "VSM", 1, 0.1, 0.1,"top", 200);   
new EvaluateConfiguration($typ, "SIMCAT_IPR", 0.5, "VSM", 1, 0.1, 0.1,"bottom");    
new EvaluateConfiguration($typ, "VSM_IPR", 0.8, "VSM", 1, 0.1, 0.1,"top");
new EvaluateConfiguration($typ, "Popular_IPR", 0.1, "VSM", 1, 0.1, 0.1,"top");
new EvaluateConfiguration($typ, "VSM_TF_IPR", 0.5, "VSM", 1, 0.1, 0.1,"swap");




?>

