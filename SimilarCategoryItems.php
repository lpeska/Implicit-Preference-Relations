<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SimilarCategoryItems
 *
 * @author peska
 */
class SimilarCategoryItems {
    //put your code here
     private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
     
    public function __construct($typ) {
    
        echo Date("H:i:s")." starting SimilarCategoryItems\n<br/>";
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());                
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_connection=UTF8");
        mysql_query("SET character_set_client=UTF8");
        
        mysql_query("TRUNCATE category_similarity");
        if($typ=="antikvariat"){
            $sql = "select * from train_set "
                . "join new_implicit_events on (train_set.visitID = new_implicit_events.visitID)"
                . "join objects_table on (train_set.objectID = objects_table.oid)"
                . " where timeOnPage > 500 order by train_set.userID ";
        }else{
            $sql = "select distinct train_set.objectID, train_set.userID, category, id_typ, zeme, destinace  from train_set                 
                join objects_table on (train_set.objectID = objects_table.oid)
                 where 1 order by train_set.userID ";
        }


        $query = mysql_query($sql);
        $items_visited = array();
        $items_covisited = array();
        $user_visited = array();
        $last_uid = -1;
        while ($row = mysql_fetch_array($query)) {
            if($last_uid != $row["userID"]){
                foreach ($user_visited as $catID => $value) {
                    //priprava na jaccard Sim (A, B)
                    if(!isset($items_visited[$catID])){
                        $items_visited[$catID] = 1;
                    }else{
                        $items_visited[$catID]++;
                    } 
                    
                    //priprava na Jaccard Sim (A prunik B)
                    foreach ($user_visited as $catID2 => $value2) {
                        if($catID < $catID2){
                            if(!isset($items_covisited[$catID][$catID2])){
                                $items_covisited[$catID][$catID2] = 1;
                            }else{
                                $items_covisited[$catID][$catID2] ++;
                            }                            
                        }
                    }                    
                } 
                
                
               $user_visited = array(); 
               $last_uid = $row["userID"];
            }
            //zajímají nás pouze návštěvy objektu s nějakou kategorií

            if($row["category"]>0){
                $user_visited[$row["category"]] = 1;
            }
            
        }
        foreach ($user_visited as $catID => $value) {
            //priprava na jaccard Sim (A, B)
            if(!isset($items_visited[$catID])){
                $items_visited[$catID] = 1;
            }else{
                $items_visited[$catID]++;
            } 

            //priprava na Jaccard Sim (A prunik B)
            foreach ($user_visited as $catID2 => $value2) {
                if($catID < $catID2){
                    if(!isset($items_covisited[$catID][$catID2])){
                        $items_covisited[$catID][$catID2] = 1;
                    }else{
                        $items_covisited[$catID][$catID2] ++;
                    }                            
                }
            }                    
        }
        $sql = "INSERT INTO `category_similarity`(`cat_id1`, `cat_id2`, `similarity`) VALUES\n";
        $first = 1;
        foreach ($items_covisited as $catID1 => $array) {
            foreach ($array as $catID2 => $prunik) {
                $similarity = $prunik / ($items_visited[$catID1] + $items_visited[$catID2] - $prunik);
                if($first){
                    $first = 0;
                }else{
                    $sql .= ",";
                }
                if($typ=="antikvariat"){
                    $sql .= "($catID1,$catID2,$similarity)\n";
                }else{
                    $sql .= "(\"$catID1\",\"$catID2\",$similarity)\n";
                }
                                    
            }            
        }
        
       // echo nl2br($sql);
        mysql_query($sql);
    }

}
//new SimilarCategoryItems("antikvariat");