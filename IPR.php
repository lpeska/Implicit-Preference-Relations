<?php

/*
* IPR vzniknou pro kazdeho uzivatele, po pouziti se zahodi. Probehnou nasledovne
 * v constructoru bude userID, minSimilarity a simMethod, useVisibility stahnu si vsechna data z trainSetu
 * pokud obsahuje nejaka navsteva forward_toLink, spoctu directPreferenceRelations a inferredPreferenceRelations
 * ty se pak nekde pouzijou dal
 *  */
class IPR{

    private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
    /*     $db_server="127.0.0.1"; //connect spider
    $db_jmeno="tatraturcz001";
     $db_heslo="dovolena50";
     $db_nazev_db="tatraturcz";*/
    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */
     private $similar_objects ;
    private $nextOid = 1;
    private $addressToOidMap;
    public $inferredPreferenceRelations;


    public function __construct($typ, $uid, $similarityThreshold, $simMethod, $useVisibility, $minVisibilityThreshold, $minRelationThreshold) {
        
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }
                $this->log = fopen("log.txt", "a");
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());




        $data = mysql_query("SELECT train_set.userID, object_visibility.* FROM `train_set` join object_visibility on (train_set.visitID = object_visibility.visitID)
        where train_set.userID=$uid order by visitID, selected desc");

        $lastVisitID = 0;
        $directPreferenceRelations = array();
        $this->inferredPreferenceRelations = array();
        $selectedObjects = array();
        $notSelectedObjects = array();

        while ($row = mysql_fetch_array($data)) {
           // print_r($row);
            if($row["visitID"]!=$lastVisitID){
                //vytvorim vsechny dvojce selected vs not selected
                //pripocitam relace z inferredPreferenceRelations
                foreach ($selectedObjects as $oid1 => $value1) {
                    foreach ($notSelectedObjects as $oid2 => $value2) {
                        //visibility vybraneho je mensi -> byl zkoumany mene ale stacilo to ->necham relaci na 1
                        if($value1 <= $value2){
                            $relation = 1;
                        }else{
                            $relation = $value2/($value1+0.0000001);
                        }                
                        $directPreferenceRelations[] = array("oid1"=>$oid1, "oid2"=>$oid2, "relation"=>$relation);
                        $sim_oid1 = $this->GetSimilarObjects($oid1, $similarityThreshold, $simMethod);
                        $sim_oid2 = $this->GetSimilarObjects($oid2, $similarityThreshold, $simMethod);
                        foreach ($sim_oid1 as $key1 => $value1) {
                            foreach ($sim_oid2 as $key2 => $value2) {
                                if(($value1*$value2)>$similarityThreshold){
                                    $inferred_relation = $value1*$value2 * $relation;
                                    if(isset($this->inferredPreferenceRelations[$key1][$key2])){
                                        //shodna relace, intenzity sectu
                                        $this->inferredPreferenceRelations[$key1][$key2] += $inferred_relation;

                                    }else if(isset($this->inferredPreferenceRelations[$key2][$key1])){
                                        //relace v opacnem smeru, odectu a necham jen tu "vetsi"
                                        if($this->inferredPreferenceRelations[$key2][$key1] > $inferred_relation){
                                           $this->inferredPreferenceRelations[$key2][$key1] = $this->inferredPreferenceRelations[$key2][$key1] - $inferred_relation;
                                        }else{
                                           $this->inferredPreferenceRelations[$key1][$key2] = $inferred_relation - $this->inferredPreferenceRelations[$key2][$key1]; 
                                           unset($this->inferredPreferenceRelations[$key2][$key1]);
                                        }

                                    }else{
                                        $this->inferredPreferenceRelations[$key1][$key2] = $inferred_relation;
                                    }
                                }
                            }
                        }

                    }
                }
                $lastVisitID = $row["visitID"];
                $selectedObjects = array();
                $notSelectedObjects = array();
            }
            $visibility = self::GetVisibility($row["visible_percentage"], $row["visible_time"], $useVisibility, $minVisibilityThreshold);
            //normalizace dat 0.98 a 23 jsou mediany hodnot u selected atributu
            //visibility je brana jako produktova S-norma


            if($row["selected"]==1){
                $selectedObjects[$row["objectID"]] = $visibility;
            }else{
                $notSelectedObjects[$row["objectID"]] = $visibility;
            }

        }

        //projdu vsechny relace a vyhodim ty, ktere jsou mensi nez threshold
        foreach ($this->inferredPreferenceRelations as $oid1 => $oids2) {
            foreach ($oids2 as $oid2 => $value) {
                if($value < $minRelationThreshold){
                    unset($this->inferredPreferenceRelations[$oid1][$oid2]);
                }
            }
        }
        //jeste je treba relace seradit a pole linearizovat do 1D
        $resultSet = array();
        foreach ($this->inferredPreferenceRelations as $oid1 => $val) {
            foreach ($val as $oid2 => $relation) {
               $resultSet[$oid1."_".$oid2] = $relation; 
            }
        }
        asort($resultSet);
        $this->inferredPreferenceRelations =  $resultSet;
        unset($resultSet);

    }
    function getRelations(){
        return $this->inferredPreferenceRelations;
    }
    //pocita podobnost k dalsim objektum, return array oid=>similarity, rozdeluje metody
    function GetSimilarObjects($oid1, $similarityThreshold, $simMethod){
        if(isset($this->similar_objects[$oid1])){
            return $this->similar_objects[$oid1];
        }else{
            if($simMethod == "VSM"){
                $this->similar_objects[$oid1] = $this->GetSimilarVSM($oid1,$similarityThreshold);
                return $this->similar_objects[$oid1];
            }else if($simMethod == "Attributes"){
                $this->similar_objects[$oid1] = $this->GetSimilarAttributes($oid1,$similarityThreshold);
                return $this->similar_objects[$oid1];
            }
        }
    }
    
    //spocita podobnosti vektoru objektu dle VSM
    function GetSimilarVSM($oid1, $similarityThreshold){
        //init values
        //toto chce vymyslet nejak lepe ulozene, napø. v nadrazenem objektu, nebo jako static
        $return_vector = array();
        
        $current_object = staticData::$object_features[$oid1];
        if(!is_array($current_object)){
            echo "chyba: neexistujici data pro objekt $oid1";
            fwrite($this->log, "chyba: neexistujici data pro objekt $oid1");
        }
        foreach (staticData::$object_features as $oid2 => $sim_object) {
            //kontrola zda uz podobnost nemame ulozenou
            if(isset(staticData::$object_similarities[$oid1][$oid2])){
                $sim = staticData::$object_similarities[$oid1][$oid2];
            }else if(isset(staticData::$object_similarities[$oid2][$oid1])){
                $sim = staticData::$object_similarities[$oid2][$oid1];
            }else{
                if(!is_array($current_object) or !is_array($sim_object)){
                    if(!is_array($sim_object)){
                        echo "chyba: neexistujici data pro objekt $oid2";
                        fwrite($this->log, "chyba: neexistujici data pro objekt $oid2");
                    }                    
                    $sim=0;
                }else{
                    $sim = self::CosineSimilarity($current_object, $sim_object);
                }                
               // staticData::$object_similarities[$oid1][$oid2] = $sim;
            }
            if($sim > $similarityThreshold){
               $return_vector[$oid2] =  $sim ;
            }
        }
        return $return_vector;
    }    

    //spocita COS sim
   static function CosineSimilarity($objectFeatures1, $objectFeatures2){
         $sumOF1 = 0.000000001; //nenulovy jmenovatel
         $sumOF2 = 0.000000001;
         $sumOF1_x_OF2 = 0;
         $features = array();
         //stanovuju globalni seznam vlastnosti         
         foreach ($objectFeatures1 as $key => $value) {
             $features[$key] = 1;
         }
         foreach ($objectFeatures2 as $key => $value) {
             $features[$key] = 1;
         }
         //projdu seznam vlastnosti, spoctu podobnost
         foreach ($features as $key => $val) {
             if(!isset($objectFeatures1[$key])){
                 $objectFeatures1[$key]=0;
             }
             if(!isset($objectFeatures2[$key])){
                 $objectFeatures2[$key]=0;
             }
             $sumOF1 += $objectFeatures1[$key]*$objectFeatures1[$key];
             $sumOF2 += $objectFeatures2[$key]*$objectFeatures2[$key];
             $sumOF1_x_OF2 += $objectFeatures1[$key]*$objectFeatures2[$key];
         }
         $objectFeatures1 = "";
         $objectFeatures2 = "";
         $similarity = $sumOF1_x_OF2 /(sqrt($sumOF1)*sqrt($sumOF2));
      
         return $similarity;
    }  
    
    //spocita podobnosti vektoru objektu dle moji metody zalozene na atributech
    function GetSimilarAttributes($oid1, $similarityThreshold){
        
    }    
    
    
    //pocita zda a na kolik byl objekt viditelny
    static function GetVisibility($visibility_percentage, $visibility_time, $useVisibility, $minVisibilityThreshold){
        if($useVisibility){
            //tady by to chtelo hodnoty napr. 90% kvantilu
            $visibility_percentage = min(array($visibility_percentage/0.98, 1) );
            $visibility_time = min(array($visibility_time/23, 1) );    
            $visibility = $visibility_time + $visibility_percentage - ($visibility_time * $visibility_percentage);
            if($visibility > $minVisibilityThreshold){
                return $visibility;
            }
        }else if($visibility_percentage>0 or $visibility_time >0){
            return 1;
        } 
        return 0;
    }

}

//$m = new IPR("antikvariat", 192574, 0.3, "VSM", 1, 0.1, 0.1);
//$m = new IPR("antikvariat", 292650, 0.1, "VSM", 1, 0.1, 0.1);
//$m = new IPR("antikvariat", 251307, 0.3, "VSM", 1, 0.1, 0.1);
//$m = new IPR("antikvariat", 228529, 0.3, "VSM", 1, 0.1, 0.1);
//$m = new IPR("antikvariat", 228537, 0.3, "VSM", 1, 0.1, 0.1);
?>
