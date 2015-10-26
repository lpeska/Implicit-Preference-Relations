<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EvaluateConfiguration
 *
 * @author peska
 */
class EvaluateConfiguration {

    private $recommending_alg;
    private $similarityThreshold;
    private $simMethod;
    private $useVisibility;
    private $minVisibilityThreshold;
    private $minRelationThreshold;
    private $IPRConflictStrategy;
    
    private $IPRAppliedRelations =0;
    private $ConcordantRelations =0;
    private $WeakRelations =0;
    private $user_objects_vector;
    private $user_profile_vector;
    private $user_relations;
    private $result_file;
    private $log;
    
     private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
    //put your code here
    public function __construct($typ, $recommending_alg, $similarityThreshold, $simMethod, $useVisibility, $minVisibilityThreshold, $minRelationThreshold, $IPRConflictStrategy, $latentFactors=10) {        
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }     
        $this->log = fopen("log.txt", "a");
        $this->latentFactors = $latentFactors;
        if($recommending_alg=="MF" or $recommending_alg=="CBMF" or $recommending_alg=="MF_IPR"){
            $this->recommending_alg = $recommending_alg."_".$this->latentFactors;
        }else{
            $this->recommending_alg = $recommending_alg;
        }
        
        
        $this->result_file = fopen("UMAP2015-$typ-$this->recommending_alg"."-".$simMethod."-".$IPRConflictStrategy."-".intval($similarityThreshold*100)."-".$useVisibility."-".intval($minVisibilityThreshold*100)."-".intval($minRelationThreshold*100).".csv", "w");
        $this->recommending_alg = $recommending_alg;
        $this->similarityThreshold = $similarityThreshold;
        $this->simMethod = $simMethod;
        $this->useVisibility = $useVisibility;
        $this->minVisibilityThreshold = $minVisibilityThreshold;
        $this->minRelationThreshold = $minRelationThreshold;
        $this->IPRConflictStrategy = $IPRConflictStrategy;
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());                

        fwrite($this->result_file , "dataset;method;position;uid;oid;visitID;purchased;similarityThreshold;simMethod;useVisibility;minVisibilityThreshold;minRelationThreshold;IPRConflictStrategy;IPRAppliedRelations;ConcordantRelations;WeakRelations\n") ;  
        $this->train();  

    }
    
     
    //natrenuje prislusny doporucovaci algoritmus (je li to nutne) - jen obecna metoda, ktera odkazuje na konkretni rec. alg
    public function train(){
       echo Date("H:i:s")." starting  train()\n<br/>";  
       
        if($this->recommending_alg=="VSM"){
            $this->trainVSM();
        }
        if($this->recommending_alg=="VSM_TF"){
            $this->trainVSM();
        }
        if($this->recommending_alg=="VSM_TF_IPR"){
            $this->trainVSM_IPR();
        }
        if($this->recommending_alg=="VSM_IPR"){
            $this->trainVSM_IPR();
        }
        if($this->recommending_alg=="SIMCAT"){
            $this->trainSimCat();
        }
        if($this->recommending_alg=="Popular"){
            $this->trainSimCat();
        }
        if($this->recommending_alg=="Popular_IPR"){
            $this->trainSimCat_IPR();
        }
        if($this->recommending_alg=="SIMCAT_IPR"){
            $this->trainSimCat_IPR();
        }
        if($this->recommending_alg=="RAND"){
            $this->trainRand();
        }
        if($this->recommending_alg=="MF"){
            $this->trainMF();
        }
        if($this->recommending_alg=="MF_IPR"){
            $this->trainMF_IPR();
        }
        if($this->recommending_alg=="CBMF"){
            $this->trainCBMF();
        }
        if($this->recommending_alg=="IPR"){
            $this->trainIPRrank();
        }
        echo Date("H:i:s")." finishing  train()\n<br/>";  
    }
       //natrénuje VSM model a výsledky uloží do user_profile_vector
    public function trainVSM(){
        $this->getUserObjects();

        $this->user_profile_vector = array();
        foreach ($this->user_objects_vector as $uid => $objects) {
   //         echo "finished train user  ".$uid."\n<br/>";
            foreach ($objects as $oid => $value) {
                if($this->recommending_alg=="VSM_TF"){
                    foreach (staticData::$object_featuresTF[$oid] as $feature => $tf_idf) {
                        if(!isset($this->user_profile_vector[$uid][$feature])){
                            $this->user_profile_vector[$uid][$feature] = $value*$tf_idf;
                        }else{
                            $this->user_profile_vector[$uid][$feature] += $value*$tf_idf;
                        }
                    }
                }else{
                    foreach (staticData::$object_features[$oid] as $feature => $tf_idf) {
                        if(!isset($this->user_profile_vector[$uid][$feature])){
                            $this->user_profile_vector[$uid][$feature] = $value*$tf_idf;
                        }else{
                            $this->user_profile_vector[$uid][$feature] += $value*$tf_idf;
                        }
                    }   
                }
                
            }      
            if(!$this->user_printed){
                print_r($this->user_profile_vector[$uid]);
            }
            
            
            $this->test($uid);
            $this->user_printed=true;
            $this->user_top_k[$uid] = "";
            $this->user_profile_vector[$uid] = "";
            //echo Date("H:i:s")."finished user ".$uid."\n<br/>";
        }
        
    }  
       
         
      public function trainVSM_IPR(){
        $this->getUserObjects();

        $this->user_profile_vector = array();
        foreach ($this->user_objects_vector as $uid => $objects) {
            foreach ($objects as $oid => $value) {
                if($this->recommending_alg=="VSM_TF_IPR"){
                    foreach (staticData::$object_featuresTF[$oid] as $feature => $tf_idf) {
                        if(!isset($this->user_profile_vector[$uid][$feature])){
                            $this->user_profile_vector[$uid][$feature] = $value*$tf_idf;
                        }else{
                            $this->user_profile_vector[$uid][$feature] += $value*$tf_idf;
                        }
                    }
                }else{
                    foreach (staticData::$object_features[$oid] as $feature => $tf_idf) {
                        if(!isset($this->user_profile_vector[$uid][$feature])){
                            $this->user_profile_vector[$uid][$feature] = $value*$tf_idf;
                        }else{
                            $this->user_profile_vector[$uid][$feature] += $value*$tf_idf;
                        }
                    }   
                }
            }   
            
            $rel = new IPR($this->db_nazev_db, $uid, $this->similarityThreshold, $this->simMethod, $this->useVisibility, $this->minVisibilityThreshold, $this->minRelationThreshold) ;
            $this->user_relations = $rel->getRelations();//zaroven preusporada seznam
            
            $this->test($uid);
        }        
    }  
    
    public function trainRand(){
        $sql = "select distinct userID from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID "; 
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            $this->test_rand($row["userID"]);            
        }          
    }     
         
  /**
     * prida uvozovky pred a za textovy retezec
     * @param type $array pole
     */
    private function add_quotes($array){
        $new = array();
        foreach ($array as $key => $value) {
            $new[] = "\"".$value."\"";
        } 
        return $new;
    }
    
     //natrénuje CBMF model a výsledky ulo?í do user_profile_vector
    public function trainCBMF(){
        require_once "MatrixFactorizationWithAttributes.php";
        $latentFactors = $this->latentFactors;   
        $use_object_predictors = 1; 
     
       //get avg ratings     
       $average_rating = 0;
       $total_users = 1;
       $total_objects = 1;
       $sql_global_avg = "select count(*) as all_rows, count(distinct userID) as uids, count(distinct objectID) as oids  from `train_set` ";
       $result = mysql_query($sql_global_avg);
       while ($row = mysql_fetch_array($result)){
           $average_rating = $row["all_rows"] / ($row["uids"] * $row["oids"]);   
           $total_users = $row["uids"];
           $total_objects = $row["oids"];
       }
       $user_baseline_predictors = array();
       $sql_uid_avg = "select userID, count(distinct objectID) as oids from `train_set` group by userID";
       $result = mysql_query($sql_uid_avg);
       while ($row = mysql_fetch_array($result)){
           $user_baseline_predictors[$row["userID"]] = ($row["oids"]/$total_objects) - $average_rating;    
       }
       $object_baseline_predictors = array();
       $sql_oid_avg = "select objectID, count(distinct userID) as uids from `train_set` group by objectID";
       $result = mysql_query($sql_oid_avg);
       while ($row = mysql_fetch_array($result)){
           $object_baseline_predictors[$row["objectID"]] = ($row["uids"]/$total_users) - $average_rating;    
       }
                   
    $oid_array = array();
    $features_array = array();
    $features_matrix = array();
    $bMatrix = array();
    $featureNameIdMap = array();
    $fid=0;
    $query =  "select oid, feature, value from `objects_binary_attributes` where 1 "; 
    echo $query;
    $data_bmatrix = mysql_query($query);
    echo mysql_error();
    while ($row = mysql_fetch_array($data_bmatrix)) {

        if(!in_array($row["feature"], $features_array)){
            $features_array[] = $row["feature"];                
            $featureNameIdMap[$row["feature"]] = $fid;            
            $i=0;
            while($i < $latentFactors){
                $bMatrix[$i][$fid] = (mt_rand(0, 10)/10000);           
                $i++;
            }
            $fid++;                            
        }
        $features_matrix[ $row["oid"] ][ $featureNameIdMap[$row["feature"] ] ] = 1;//$row["value"];
    }
    
    $this->getUserObjects();
    $user_matrix = array();
    $trueRatings = array();
    $objectFactors = array();
    foreach($this->user_objects_vector as $uid => $objects){
        $i=0;
        while($i<$latentFactors){
            $user_matrix[$uid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }
        foreach ($objects as $oid => $value) {
            if($value>0){
                $key = $uid.",".$oid;
                $trueRatings[$key] = 1;//$value;
            }
        }
       }
  //  print_r($trueRatings);
  //  echo "<br/><br/><br/><br/>";
  //  print_r($features_matrix);
  //  echo "<br/><br/><br/><br/>";
    
    $MF = new MatrixFactorizationWithAttributes("CBMF", $user_matrix, $features_matrix, $bMatrix, $trueRatings, $fid, $latentFactors,
         $average_rating, $user_baseline_predictors, $object_baseline_predictors, "");
    $MF->$use_object_predictors =   $use_object_predictors;       
    $MF->train();  
    
    foreach (staticData::$object_features as $oid => $object_features) {
        $objectFeatures = $features_matrix[ $oid ];
            if(sizeof($objectFeatures)>0){
                $objectFactors[$oid] = $MF->computeObjectFactors( $objectFeatures, $latentFactors);
            }             
    }
 /*   echo "true ratings";
    print_r($trueRatings);
    echo "object factors";   
    print_r($objectFactors);
    echo "user_object vector";
    print_r($this->user_objects_vector);
    echo "user factors";
    print_r($MF->usersMatrix);*/

    $this->user_profile_vector = array();
    foreach ($this->user_objects_vector as $uid => $objects) {
        foreach (staticData::$object_features as $oid => $object_features) {
           // echo "object_ $oid _factors";
           // print_r($objectFactors[$oid]);
            
            $base_score = $MF->computeScore($objectFactors[$oid], $uid);
           // $score = $base_score + $average_rating + $user_baseline_predictors[$uid] + $object_baseline_predictors[$oid] ;
            $score = $base_score;
            $this->user_top_k[$uid][$oid] = $score;
        }
        arsort($this->user_top_k[$uid]);
      /*  if($uid == "202816"){
        echo "user_ $uid _topk";
        print_r($this->user_top_k);}*/
        $this->evaluate($uid);
        
        $this->user_top_k[$uid] = "";
    }        
}     
   

 
     //natrénuje CBMF model a výsledky ulo?í do user_profile_vector
    public function trainMF(){
        require_once "MatrixFactorization.php";
        $latentFactors = $this->latentFactors;           
        $use_object_predictors = 1; 
     
       //get avg ratings     
       $average_rating = 0;
       $total_users = 1;
       $total_objects = 1;
       $sql_global_avg = "select count(*) as all_rows, count(distinct userID) as uids, count(distinct objectID) as oids  from `train_set` ";
       $result = mysql_query($sql_global_avg);
       while ($row = mysql_fetch_array($result)){
           $average_rating = $row["all_rows"] / ($row["uids"] * $row["oids"]);   
           $total_users = $row["uids"];
           $total_objects = $row["oids"];
       }
       $user_baseline_predictors = array();
       $sql_uid_avg = "select userID, count(distinct objectID) as oids from `train_set` group by userID";
       $result = mysql_query($sql_uid_avg);
       while ($row = mysql_fetch_array($result)){
           $user_baseline_predictors[$row["userID"]] = ($row["oids"]/$total_objects) - $average_rating;    
       }
       $object_baseline_predictors = array();
       $sql_oid_avg = "select objectID, count(distinct userID) as uids from `train_set` group by objectID";
       $result = mysql_query($sql_oid_avg);
       while ($row = mysql_fetch_array($result)){
           $object_baseline_predictors[$row["objectID"]] = ($row["uids"]/$total_users) - $average_rating;    
       }
                   
    $oid_array = array();
    $object_matrix = array();

    $query =  "select distinct oid from `objects_binary_attributes` where 1 "; 
    echo $query;
    $data_bmatrix = mysql_query($query);
    echo mysql_error();
    while ($row = mysql_fetch_array($data_bmatrix)) {
        $i=0;
        $oid = $row["oid"];
        while($i < $latentFactors){
            $object_matrix[$oid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }

    }
    
    $this->getUserObjects();
    $user_matrix = array();
    $trueRatings = array();
    $objectFactors = array();
    foreach($this->user_objects_vector as $uid => $objects){
        $i=0;
        while($i<$latentFactors){
            $user_matrix[$uid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }
        foreach ($objects as $oid => $value) {
            if($value>0){
                $key = $uid.",".$oid;
                $trueRatings[$key] = 1;//$value;
            }
        }
     }
  //  print_r($trueRatings);
    echo "<br/><br/><br/><br/>";

    
    $MF = new MatrixFactorization("MF_".$this->latentFactors, $user_matrix, $object_matrix, $trueRatings);     
    $MF->train();  
    
    $this->user_profile_vector = array();
    foreach ($this->user_objects_vector as $uid => $objects) {
        foreach (staticData::$object_features as $oid => $object_features) {
           // echo "object_ $oid _factors";
           // print_r($objectFactors[$oid]);
            
            $base_score = $MF->computeScore($oid, $uid);
           // $score = $base_score + $average_rating + $user_baseline_predictors[$uid] + $object_baseline_predictors[$oid] ;
            $score = $base_score;
            $this->user_top_k[$uid][$oid] = $score;
        }
        arsort($this->user_top_k[$uid]);
        if($uid == "202816"){
        echo "user_ $uid _topk";
        print_r($this->user_top_k);}
        $this->evaluate($uid);
        
        $this->user_top_k[$uid] = "";
    }       
}  


    
    //natrénuje pro u?ivatele preferenci kategorie - v první ?ad? p?ímé, následn? odvozené
    public function trainSimCat(){
        $sql = "select distinct userID from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID "; 
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            //direct categories                
            $categoryList = $this->getUserCategories($row["userID"]);        
            //výpo?et odvozených kategorií
            foreach ($categoryList as $catID => $value) {
                $catSimList = $this->getCategorySimilarity($catID);
                foreach ($catSimList as $catID2 => $similarity) {
                    if(isset($categoryList[$catID2])){
                        $categoryList[$catID2] = $categoryList[$catID2] + ($value*$similarity);
                    }else{
                        $categoryList[$catID2] = ($value*$similarity);
                    }
                }
            }
            if($this->recommending_alg=="Popular"){
                $this->test_Popular($row["userID"], $categoryList);
            }else{
                $this->test_simCat($row["userID"], $categoryList);
            }            
            
        }          
    }    
  

    //vezme v?echny nav?tívené kategorie pro daného u?ivatele
    private function getUserCategories($uid) {
        $catList = array();
        $sql = "select * from train_set "
                . ""
                . "join objects_table on (train_set.objectID = objects_table.oid)"
                . " where train_set.userID=$uid ";
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            if(isset($catList[$row["category"]])){
                $catList[$row["category"]] ++;
            }else{
                $catList[$row["category"]] = 1;
            }
        }
        return $catList;
    }        
     //vezme v?echny podobné kategorie k sou?asné
    private function getCategorySimilarity($catID) {
        $catList = array();
        if($this->db_nazev_db=="antikvariat"){
            $sql = "SELECT `cat_id1` as `ref`, `cat_id2` as `category` , `similarity` FROM `category_similarity` WHERE `cat_id1`=$catID
                union all "
             . "SELECT `cat_id2` as `ref`, `cat_id1` as `category`, `similarity` FROM `category_similarity` WHERE `cat_id2`=$catID";
            
        }else{
            $sql = "SELECT `cat_id1` as `ref`, `cat_id2` as `category` , `similarity` FROM `category_similarity` WHERE `cat_id1`=\"$catID\"
                union all "
             . "SELECT `cat_id2` as `ref`, `cat_id1` as `category`, `similarity` FROM `category_similarity` WHERE `cat_id2`=\"$catID\"";
            
        }
         $query = mysql_query($sql);
       // echo $sql;
        while ($row = mysql_fetch_array($query)) {
            if(isset($catList[$row["category"]])){
                $catList[$row["category"]] += $row["similarity"];
            }else{
                $catList[$row["category"]] = $row["similarity"];
            }
        }
        return $catList;
    }
    
    
     //natrénuje CBMF model a výsledky ulo?í do user_profile_vector
    public function trainMF_IPR(){
        require_once "MatrixFactorization.php";
        $latentFactors = $this->latentFactors;           
        $use_object_predictors = 1; 
     
       //get avg ratings     
       $average_rating = 0;
       $total_users = 1;
       $total_objects = 1;
       $sql_global_avg = "select count(*) as all_rows, count(distinct userID) as uids, count(distinct objectID) as oids  from `train_set` ";
       $result = mysql_query($sql_global_avg);
       while ($row = mysql_fetch_array($result)){
           $average_rating = $row["all_rows"] / ($row["uids"] * $row["oids"]);   
           $total_users = $row["uids"];
           $total_objects = $row["oids"];
       }
       $user_baseline_predictors = array();
       $sql_uid_avg = "select userID, count(distinct objectID) as oids from `train_set` group by userID";
       $result = mysql_query($sql_uid_avg);
       while ($row = mysql_fetch_array($result)){
           $user_baseline_predictors[$row["userID"]] = ($row["oids"]/$total_objects) - $average_rating;    
       }
       $object_baseline_predictors = array();
       $sql_oid_avg = "select objectID, count(distinct userID) as uids from `train_set` group by objectID";
       $result = mysql_query($sql_oid_avg);
       while ($row = mysql_fetch_array($result)){
           $object_baseline_predictors[$row["objectID"]] = ($row["uids"]/$total_users) - $average_rating;    
       }
                   
    $oid_array = array();
    $object_matrix = array();

    $query =  "select distinct oid from `objects_binary_attributes` where 1 "; 
    echo $query;
    $data_bmatrix = mysql_query($query);
    echo mysql_error();
    while ($row = mysql_fetch_array($data_bmatrix)) {
        $i=0;
        $oid = $row["oid"];
        while($i < $latentFactors){
            $object_matrix[$oid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }

    }
    
    $this->getUserObjects();
    $user_matrix = array();
    $trueRatings = array();
    $objectFactors = array();
    foreach($this->user_objects_vector as $uid => $objects){
        $i=0;
        while($i<$latentFactors){
            $user_matrix[$uid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }
        foreach ($objects as $oid => $value) {
            if($value>0){
                $key = $uid.",".$oid;
                $trueRatings[$key] = 1;//$value;
            }
        }
     }
  //  print_r($trueRatings);
    echo "<br/><br/><br/><br/>";

    
    $MF = new MatrixFactorization("MF_".$this->latentFactors, $user_matrix, $object_matrix, $trueRatings);     
    $MF->train();  
    echo "finished training MF";
    fwrite($this->log, "finished training MF \n") ;
    $this->user_profile_vector = array();
    foreach ($this->user_objects_vector as $uid => $objects) {
        foreach (staticData::$object_features as $oid => $object_features) {
           // echo "object_ $oid _factors";
           // print_r($objectFactors[$oid]);
            
            $base_score = $MF->computeScore($oid, $uid);
           // $score = $base_score + $average_rating + $user_baseline_predictors[$uid] + $object_baseline_predictors[$oid] ;
            $score = $base_score;
            $this->user_top_k[$uid][$oid] = $score;
        }
        arsort($this->user_top_k[$uid]);
        fwrite($this->log, "About to start merging MF and IPR for user $uid \n") ;
        $rel = new IPR($this->db_nazev_db, $uid, $this->similarityThreshold, $this->simMethod, $this->useVisibility, $this->minVisibilityThreshold, $this->minRelationThreshold) ;
        $this->user_relations = $rel->getRelations();//zaroven preusporada seznam        
        echo "merge IPR with UID $uid \n";
        fwrite($this->log, "Received IPRs for user $uid \n") ;
        $this->test_MF_IPR($uid);
        fwrite($this->log, "Evaluated MF_IPR for user $uid \n") ;
    }       
}  
    
    
    //natrénuje pro u?ivatele preferenci kategorie - v první ?ad? p?ímé, následn? odvozené
    public function trainSimCat_IPR(){
        $sql = "select distinct userID from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID "; 
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            //direct categories                
            $categoryList = $this->getUserCategories($row["userID"]);        
            //výpo?et odvozených kategorií
            foreach ($categoryList as $catID => $value) {
                $catSimList = $this->getCategorySimilarity($catID);
                foreach ($catSimList as $catID2 => $similarity) {
                    if(isset($categoryList[$catID2])){
                        $categoryList[$catID2] = $categoryList[$catID2] + ($value*$similarity);
                    }else{
                        $categoryList[$catID2] = ($value*$similarity);
                    }
                }
            }
            

            $rel = new IPR($this->db_nazev_db, $row["userID"], $this->similarityThreshold, $this->simMethod, $this->useVisibility, $this->minVisibilityThreshold, $this->minRelationThreshold) ;
            $this->user_relations = $rel->getRelations();

            if($this->recommending_alg=="Popular_IPR"){
                $this->test_Popular_IPR($row["userID"], $categoryList);
            }else{
                $this->test_simCat_IPR($row["userID"], $categoryList);
            }                                     
        }          
    } 
    
  
    
    
    
    
private function getUserObjects(){
        $this->user_objects_vector = array();
        //p?ed produk?ní verzí odstranit limit
        $sql = "select * from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID ";
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            if($row["objectID"]>0){
                //stránka je o jednom objektu => má plnou podporu
                if(!isset($this->user_objects_vector[$row["userID"]][$row["objectID"]])){
                    $this->user_objects_vector[$row["userID"]][$row["objectID"]] = 1;
                }else{
                    $this->user_objects_vector[$row["userID"]][$row["objectID"]] += 1;
                }
      //          echo "finished getUserObjects - object ".$row["objectID"]."\n<br/>";
            }else{
                //category page => stránka je o více objektech a tím pádem je do user profile p?idám s pat?i?ným zmen?ením
                //zde lze update: p?idám dle visibility
                $sumVisibility = 0;
                $count = 0;
                $vis_array = array();
                $sql_objects = "select * from object_visibility  where visitID=".$row["visitID"]."";
                $query_objects = mysql_query($sql_objects);
                while ($row_objects = mysql_fetch_array($query_objects)) {
                    $vis = IPR::GetVisibility($row_objects["visible_percentage"], $row_objects["visible_time"], $this->useVisibility, $this->minVisibilityThreshold);
                    $sumVisibility += $vis;
                    $count++;
                    $vis_array[$row_objects["objectID"]] = $vis;                    
                }
                foreach ($vis_array as $oid => $value) {
                    if($sumVisibility > 0){
                        if(!isset($this->user_objects_vector[$row["userID"]][$oid])){
                            $this->user_objects_vector[$row["userID"]][$oid] = $value/$sumVisibility;
                        }else{
                            $this->user_objects_vector[$row["userID"]][$oid] += $value/$sumVisibility;
                        } 
                    }else if($count > 0){
                        //uzivatel se poradne nepodival na zadnou cast category page, vykaslem se na sumVis
                        if(!isset($this->user_objects_vector[$row["userID"]][$oid])){
                            $this->user_objects_vector[$row["userID"]][$oid] = 1/$count;
                        }else{
                            $this->user_objects_vector[$row["userID"]][$oid] += 1/$count;
                        } 
                    }
                }                
            }
        }
    }

    
    //natrénuje pro u?ivatele preferenci kategorie - v první ?ad? p?ímé, následn? odvozené
    public function trainIPRrank(){
        $sql = "select distinct userID from train_set where userID in ("
                . "select distinct test_set.userID from test_set "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID "; 
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            //direct categories                           
            $rel = new IPR($this->db_nazev_db, $row["userID"], $this->similarityThreshold, $this->simMethod, $this->useVisibility, $this->minVisibilityThreshold, $this->minRelationThreshold) ;
            $this->user_relations = $rel->getRelations();            
            $this->test_IPR($row["userID"]);            
        }          
    } 
    
  
    public function test($uid){
  
        if($this->recommending_alg=="VSM" or $this->recommending_alg=="VSM_TF"){
            $this->test_vsm($uid);
        }
        if($this->recommending_alg=="VSM_IPR" or $this->recommending_alg=="VSM_TF_IPR"){
            //vytvorim prvni sadu a zaroven otestuju
            $this->test_vsm_ipr($uid);
        }
    }
    public function test_vsm($uid){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
        $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
        $this->evaluate($uid);        
        echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
                
    }   
    
    public function test_rand($uid){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        $this->user_top_k = array();
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            $rating = (mt_rand(0, 1000000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->evaluate($uid);
    }      
    
    public function test_simCat($uid, $catList){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        //print_r($catList);
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] + (mt_rand(0, 10000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
       // print_r($this->user_top_k[$uid]);
        $this->evaluate($uid);
    }  
    
    public function test_Popular($uid, $catList){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        //print_r($catList);
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
       // $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] * staticData::$object_popularity[$oid];
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
       // print_r($this->user_top_k[$uid]);
        $this->evaluate($uid);
    }      
    
    public function test_IPR($uid){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();

        $resultList = array();
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
        
    
    public function test_Popular_IPR($uid, $catList){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] * staticData::$object_popularity[$oid];
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
    
    public function test_simCat_IPR($uid, $catList){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] + (mt_rand(0, 10000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
    
        
    public function test_MF_IPR($uid){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->createIPRList($uid); //pretridi seznam dle IPR
        fwrite($this->log, "Merged MF and IPR for user $uid \n") ;
        $this->evaluate($uid);
    } 
    
    
    
    public function test_vsm_ipr($uid){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;      
        
        $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);        
        $this->createIPRList($uid);
        $this->evaluate($uid);       
       
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
      
    }     
    public function test_vsm_uid($uid, $user_features){
        if($this->recommending_alg=="VSM_TF" or $this->recommending_alg=="VSM_TF_IPR" ){
            foreach (staticData::$object_features as $oid => $object_features) {                
                //pouziju pouze vlastnosti, ktere jsou v danem datasource
               /* $object_features_reduced = array();
                foreach ($object_features as $feature => $value) {                    
                        $object_features_reduced[$feature] = $value;                                        
                }  */              
                $similarity = IPR::CosineSimilarity($user_features, $object_features);
                $this->user_top_k[$uid][$oid] = $similarity;
            }
        }else{
            foreach (staticData::$object_featuresTF as $oid => $object_features) {                
                //pouziju pouze vlastnosti, ktere jsou v danem datasource
              /*  $object_features_reduced = array();
                foreach ($object_features as $feature => $value) {                    
                        $object_features_reduced[$feature] = $value;                                        
                }      */          
                $similarity = IPR::CosineSimilarity($user_features, $object_features);
                $this->user_top_k[$uid][$oid] = $similarity;
            }
        }    
        arsort($this->user_top_k[$uid]);
        
      /*  if(!$this->user_printed){
            print_r($this->user_top_k[$uid]);
        }*/
    }      
    
    //prebira logiku v pripade, ze jsou OID1 a OID2 v konfliktnim postaveni
    private function resolveConflict($oid1, $oid2, $pos1, $pos2, $relation, $uid){
        
        //zde je aplikovana ConflictStrategy:
        //- top: pridam prvni tesne pred druhy
        //- bottom: presunu druhy tesne za prvni
        //- swap: prohodim pozice
        //push oid1 just before oid2
        
        //nova cast algoritmu, relace musi byt dostatecne silna, aby fungovala
        $relative_distance = abs($pos1 - $pos2)/sizeof($this->user_top_k[$uid] );
       // echo "\n<br/> relative_distance:".$relative_distance ;
        if($this->db_nazev_db=="slantour"){            
            $relative_distance = $relative_distance*3;
        }
        if($relation >= $relative_distance){
            $this->IPRAppliedRelations++;
            if($this->IPRConflictStrategy == "top"){
                $newSet = array();
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   if($key == $oid2) {
                       //add first object
                       $newSet[$oid1] = 1; 
                       $newSet[$oid2] = 1; 
                   }else if($key == $oid1){
                       //do nothing
                   }else{
                       //copy value
                       $newSet[$key] = $value; 
                   }
                }
                //free memory
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   $this->user_top_k[$uid][$key] = null;
                   unset($this->user_top_k[$uid][$key]);
                }
                $this->user_top_k[$uid] = $newSet;



            }else if ($this->IPRConflictStrategy == "bottom") {
                $newSet = array();
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   if($key == $oid2) {
                       //do nothing

                   }else if($key == $oid1){
                       //add second object
                       $newSet[$oid1] = 1; 
                       $newSet[$oid2] = 1;  
                   }else{
                       //copy value
                       $newSet[$key] = $value; 
                   }
                }
                //free memory
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   $this->user_top_k[$uid][$key] = null;
                   unset($this->user_top_k[$uid][$key]);
                }
                $this->user_top_k[$uid] = $newSet;

            }else if ($this->IPRConflictStrategy == "swap") {
                $newSet = array();
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   if($key == $oid2) {
                       //swap first
                       $newSet[$oid1] = 1; 
                   }else if($key == $oid1){
                       //swap second
                       $newSet[$oid2] = 1;  
                   }else{
                       //copy value
                       $newSet[$key] = $value; 
                   }
                }
                //free memory
                foreach ($this->user_top_k[$uid] as $key => $value) {
                   $this->user_top_k[$uid][$key] = null;
                   unset($this->user_top_k[$uid][$key]);
                }
                $this->user_top_k[$uid] = $newSet;

            }  
           // echo "\n<br/>konflikt\n<br/>";
           // print_r(array_slice($this->user_top_k[$uid], 0, 10, true));
        }else{
            $this->WeakRelations++;
        }
    }
    
    public function createIPRList($uid){
    if(!isset($this->user_top_k[$uid])){
        $this->user_top_k[$uid] = array();
    }
  //  print_r(array_slice($this->user_top_k[$uid],0,10));
    
    $this->sum_relations = 0;
    $this->count_relations = 0.00001;
   //     print_r(array_slice($this->user_top_k[$uid], 0, 10, true));
    foreach($this->user_relations as $oids => $relation){       
        $oid_array = explode("_", $oids);
        $oid1 = $oid_array[0];
        $oid2 = $oid_array[1];
            $this->count_relations++;
            $this->sum_relations += $relation;
          
        $in_array1 = isset($this->user_top_k[$uid][$oid1]);
        $in_array2 =  isset($this->user_top_k[$uid][$oid2]);
        if(!$in_array1 and !$in_array2){
            //oba objekty nove, dam je co nejdal od sebe
            $this->IPRAppliedRelations++;
            $this->user_top_k[$uid] = array($oid1=>1)+ $this->user_top_k[$uid]+ array($oid2=>1);
           // echo "\n<br/>oba nove\n<br/>";
           // print_r(array_slice($this->user_top_k[$uid], 0, 10, true));
            
        }else if(!$in_array1 and $in_array2){
            //prvy objekt novy, dam ho na zacatek, s druhym nehybu
            $this->IPRAppliedRelations++;
            $this->user_top_k[$uid] = array($oid1=>1)+ $this->user_top_k[$uid];
           // echo "\n<br/>první nový\n<br/>";
           // print_r(array_slice($this->user_top_k[$uid], 0, 10, true));
            
        }else if($in_array1 and !$in_array2){
            //druhy objekt novy, dam ho na konec, s prvym nehybu
            $this->IPRAppliedRelations++;
            $this->user_top_k[$uid] = $this->user_top_k[$uid] + array($oid2=>1);
           // echo "\n<br/>druhý nový\n<br/>";
           // print_r(array_slice($this->user_top_k[$uid], 0, 10, true));
            
        }else if($in_array1 and $in_array2){
            //oba dva objekty jsou uz v poli, pokud jsou serazene opacne, pridam prvy tesne pred druhy
            $pos1 = array_search($oid1, array_keys($this->user_top_k[$uid]));
            $pos2 = array_search($oid2, array_keys($this->user_top_k[$uid]));
            
            if($pos1 > $pos2){
              //  echo "$oid1, $oid2, $pos1, $pos2, $relation \n<br/>";
                $this->resolveConflict($oid1, $oid2, $pos1, $pos2, $relation, $uid);
            }else{
                $this->ConcordantRelations++;
            }
            $oid1 = null;
            $oid2= null;
            $in_array1 = null;
            $in_array2 = null;
            unset($in_array1);
            unset($in_array2);            
        }

    }
    fwrite($this->log, "uzivatel: $uid, prumerna sila relace:".($this->sum_relations/$this->count_relations)." \n") ;
   // echo "uzivatel: $uid, prumerna sila relace:".($this->sum_relations/$this->count_relations)." \n<br/>";
   // print_r(array_slice($this->user_top_k[$uid], 0, 100, true));
    $this->user_relations[$uid] = NULL;
    unset($this->user_relations[$uid]);
    
}

    
    
    
    public function evaluate($uid){
        $i=0;
        $objects_array = array();   
        foreach ($this->user_top_k[$uid] as $object => $value) {
            $i++;
            $objects_array[$object] = $i;                
        }    
       // print_r($objects_array);
       // echo "<br/>\n".sizeof($objects_array)."<br/>\n";
        //print_r(array_slice($objects_array,0,100,true));
        $sql = "select * from test_set  "
                . "where test_set.is_recommendable=1  and test_set.userID = $uid";
        $query = mysql_query($sql);
        $method = $this->recommending_alg;
        if($method=="MF" or $method=="CBMF"){
            $method .= "_".$this->latentFactors;
        }
        $dataset = $this->db_nazev_db;
 
        //pro kazdy objekt z test setu zjistime jeho umisteni - ostatni lze udelat v ramci post zpracovani
        while ($row = mysql_fetch_array($query)) {
            $oid = $row["objectID"];
            $visitID = $row["visitID"];
            $purchased = $row["clickOnPurchaseCount"];                                 
            if(isset($this->user_top_k[$uid])){
                if(isset($objects_array[$oid])){
                    $position = $objects_array[$oid];
                }else{
                    $position = "NaN";
                    fwrite($this->log, "Nenalezen objekt ".$oid.", delka recList je".  sizeof($objects_array)." seznam objektu je \n") ;
                    fwrite($this->log, implode(",", array_keys($objects_array))) ;                    
                }
    
                 fwrite($this->result_file , "$dataset;$method;$position;$uid;$oid;$visitID;$purchased;$this->similarityThreshold;$this->simMethod;$this->useVisibility;$this->minVisibilityThreshold;$this->minRelationThreshold;$this->IPRConflictStrategy;$this->IPRAppliedRelations;$this->ConcordantRelations;$this->WeakRelations\n");
          
            }else{
                 fwrite($this->log, "Nenalezen uzivatel ".$uid." v user_top_k \n") ;
               
            }
        }
        
        unset($objects_array );
    }    
}
