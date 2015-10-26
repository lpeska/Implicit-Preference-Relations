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
class CreateSingleCSV {
private  $db_server="127.0.0.1"; //connect spider
 private $db_jmeno="root";
 private $db_heslo="";
 private $db_nazev_db="ec-web2015";
    private $list_items;
    private $list_files;
    private $insert_query_start;
    private $insert_query_middle;

    public function __construct($files, $output, $dataset) {        
        $this->list_files = $files;
        $this->db_nazev_db = $dataset;
        $this->result_file = fopen($output.".csv", "w");
        $this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        $this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        mysql_query("Delete from `results` where 1");
        echo mysql_error();
        $this->insert_query_start = "INSERT INTO `results`(`algorithm`, `position`, `uid`, `oid`, `rel_applied`, `rel_correct`, `rel_weak`) VALUES"; 
        fwrite($this->result_file, "dataset;method;position;uid;oid;purchased;similarityThreshold;useVisibility;minVisibilityThreshold;minRelationThreshold;IPRConflictStrategy;AppliedRel;ConcRel;WeakRel\n");
        foreach ($this->list_files as $key => $file) {
            $f = fopen($file.".csv", "r");
            while (($buffer = fgets($f)) !== false) {
                $row = explode(";", $buffer);
                $this->processRow($row);                                
            }   
            $this->insert_query = $this->insert_query_start.substr($this->insert_query_middle, 0, -2)." ON DUPLICATE KEY UPDATE `algorithm` = `algorithm`";
            mysql_query($this->insert_query);
          //  echo $this->insert_query;
            $this->insert_query = "";
            echo mysql_error();
            
        }         

    }
    private function processRow($row){
        //dataset;method;position;uid;oid;visitID;purchased;similarityThreshold;simMethod;useVisibility;minVisibilityThreshold;minRelationThreshold;IPRConflictStrategy;IPRAppliedRelations
        if($row[0]!="dataset"){
            if($row[1]=="VSM" or $row[1]=="SIMCAT" or $row[1]=="RAND" or $row[1]=="MF_10" or $row[1]=="MF_20" or $row[1]=="MF_50" or $row[1]=="MF_100" or $row[1]=="MF_150" or $row[1]=="MF_200"){
                $row[7] = "";
                $row[8] = "";
                $row[9] = "";
                $row[10] = "";
                $row[11] = "";
                $row[12] = "";
                $row[13] = "";
                $row[14] = "";
                $row[15] = "\n";
                $name=$row[1];
            }else{
                $name=$row[1]."-".$row[7]."-".$row[9]."-".$row[10]."-".$row[11]."-".$row[12];
            }
            if($row[2]=="NaN"){
              $row[2]="20000";  
            }
            if(!isset($this->list_items[$row[0].$row[1].$row[3].$row[4].$row[6].$row[7].$row[9].$row[10].$row[11].$row[12].$row[13].$row[14].$row[15]])){
                //zapisu radek do outputu a databaze
                $this->insert_query_middle .= "(\"".$name."\",".$row[2].",".$row[3].",".$row[4].",".intval($row[13]).",".intval($row[14]).",".intval($row[15])."),\n";
                
                fwrite($this->result_file, $row[0].";".$row[1].";".$row[2].";".$row[3].";".$row[4].";".$row[6].";".$row[7].";".$row[9].";".$row[10].";".$row[11].";".$row[12].";".$row[13].";".$row[14].";".$row[15]."");
            }
            //vytvorim zaznam o radku s vynechanym visitID                
            $this->list_items[$row[0].$row[1].$row[3].$row[4].$row[6].$row[7].$row[9].$row[10].$row[11].$row[12].$row[13].$row[14].$row[15]] = 1;
        }
    }   
}

/*$ca = new CreateSingleCSV(array(
    "UMAP2015-antikvariat-RAND-VSM-swap-50-1-10-10",
    "UMAP2015-antikvariat-SIMCAT-VSM-swap-50-1-10-10",
    "UMAP2015-antikvariat-VSM_IPR-VSM-top-1-0-10-10",
    "UMAP2015-antikvariat-SIMCAT_IPR-VSM-top-1-0-10-10",
    "UMAP2015-antikvariat-VSM_IPR-VSM-top-20-1-50-50",
    "UMAP2015-antikvariat-SIMCAT_IPR-VSM-top-20-1-50-50",
    "UMAP2015-antikvariat-VSM_IPR-VSM-top-20-1-1-1",
    "UMAP2015-antikvariat-SIMCAT_IPR-VSM-top-20-1-1-1",
    "UMAP2015-antikvariat-VSM_IPR-VSM-top-20-0-10-10",
    "UMAP2015-antikvariat-SIMCAT_IPR-VSM-top-20-0-10-10",
   
            ), "results_antikvariat5", "antikvariat");
*/

$cs = new CreateSingleCSV(array(
  //  "UMAP2015-slantour-MF_10---0-1-0-0",
    "UMAP2015-slantour-MF_20---0-1-0-0",
    "UMAP2015-slantour-MF_50---0-1-0-0",
    "UMAP2015-slantour-MF_100---0-1-0-0",
    "UMAP2015-slantour-MF_150---0-1-0-0",
    "UMAP2015-slantour-MF_200---0-1-0-0",
    "UMAP2015-slantour-MF_IPR_200-VSM-swap-10-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-swap-20-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-swap-50-1-10-10",
 //   "UMAP2015-slantour-MF_IPR_200-VSM-top-5-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-top-10-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-top-20-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-top-50-1-10-10",
    "UMAP2015-slantour-RAND---50-1-0-0",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-swap-10-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-swap-20-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-swap-50-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-top-5-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-top-10-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-top-20-1-10-10",
    "UMAP2015-slantour-SIMCAT_IPR-VSM-top-50-1-10-10",
    
    "UMAP2015-slantour-SIMCAT---50-1-0-0",
    "UMAP2015-slantour-Popular-VSM-top-10-1-10-10",
    "UMAP2015-slantour-Popular_IPR-VSM-top-10-1-10-10",
    "UMAP2015-slantour-IPR-VSM-top-10-1-10-10",
    
    "UMAP2015-slantour-VSM_IPR-VSM-top-10-1-10-10",
//    "UMAP2015-slantour-VSM_IPR-VSM-top-20-1-10-10",
    "UMAP2015-slantour-VSM_IPR-VSM-top-50-1-10-10",
//    "UMAP2015-slantour-VSM_IPR-VSM-swap-10-1-10-10",
 //   "UMAP2015-slantour-VSM_IPR-VSM-swap-50-1-10-10",
    "UMAP2015-slantour-VSM-VSM-top-50-1-0-0" ,
    
    "UMAP2015-slantour-VSM_TF---0-1-0-0" ,
    "UMAP2015-slantour-SIMCAT_IPR-VSM-bottom-10-1-10-10",
 //   "UMAP2015-slantour-VSM_IPR-VSM-bottom-10-1-10-10",
    "UMAP2015-slantour-MF_IPR_200-VSM-bottom-10-1-10-10"
            ), "results_slantour", "slantour");