<?php

//Vytvoreni tabulek k VSM - hotovo vc TF/IDF, lze spustit pøed hlavním bìhem, ale netrvá dlouho
//dodelat TF-IDF
//TF = #techto slov / #celkovy pocet slov v dokumentu
//TF slozla je cca stejna pro vsechny
//IDF = log(#vsechny dokumenty / #dokumenty obsahujici slovo)
//Krok 2

class ObjectsAttributesBinarization{

private  $db_server="localhost"; //connect spider
 private $db_jmeno="root";
 private $db_heslo="";
 private $db_nazev_db="antikvariat";
 private $known_user_item_pairs=array();
 private $known_items=array();
 private $pageID_oid_rewrite = array();

/*     $db_server="127.0.0.1"; //connect spider
$db_jmeno="tatraturcz001";
 $db_heslo="dovolena50";
 $db_nazev_db="tatraturcz";*/
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
private $nextOid = 1;
private $addressToOidMap;


public function __construct($typ) {
    
    echo Date("H:i:s")." starting ObjectsAttributesBinarization\n<br/>";
    if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }
    @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
    @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());

    //nastaveni kodovani
   /* mysql_query("SET character_set_results=cp1250");
    mysql_query("SET character_set_connection=UTF8");
    mysql_query("SET character_set_client=UTF8");*/
    mysql_query("TRUNCATE objects_binary_attributes");
    
    $this->addressToOidMap = array();

    $query = "select `objects_table`.* from `objects_table` 
                where 1 ";
    $result = mysql_query($query);
 

    while ($row = mysql_fetch_array($result)) {  
        if($typ=="antikvariat"){
            $this->CreateBinnryAttributesAntikvariat($row);
        }else{
            $this->CreateBinnryAttributesSlantour($row);
        }                                                       
    } 
    $this->CalculateTF_IDF();
    
          
        
    echo Date("H:i:s")." finishing ObjectsAttributesBinarization\n<br/>";    
}
private function CalculateTF_IDF(){
    $feature_idf = array();
    $oid_doc_lenght = array();
    $oid_feature_value = array();
    $all_docs = 0;
    
    $query_all_docs = "select count(distinct `oid`) as `oids` from `objects_binary_attributes` where 1 "; 
    $result = mysql_query($query_all_docs);
    while ($row = mysql_fetch_array($result)) {  
        $all_docs = $row["oids"];
    } 
    
    $query_doc_lenght = "select `oid`, count(distinct `feature`) as `features` from `objects_binary_attributes` where 1 group by `oid`"; 
    $result = mysql_query($query_doc_lenght);
    while ($row = mysql_fetch_array($result)) {  
        $oid_doc_lenght[$row["oid"]] = $row["features"];
    } 
    
    $query_idf = "select `feature`, sum(`value`) as `values` from `objects_binary_attributes` where 1 group by `feature`"; 
    $result = mysql_query($query_idf);
    while ($row = mysql_fetch_array($result)) {  
        $feature_idf[$row["feature"]] = $row["values"];
    }    
   // print_r($feature_idf);
    
    $query_oid_feature_val = "select *  from `objects_binary_attributes` where 1 "; 
    $result = mysql_query($query_oid_feature_val);
    while ($row = mysql_fetch_array($result)) {  
        $oid_feature_value[$row["oid"]][$row["feature"]] = $row["value"];
    } 
    
    foreach ($oid_feature_value as $oid => $features) {
        foreach ($features as $feature => $value) {
            $tf = $value/$oid_doc_lenght[$oid];
            $idf = log($all_docs/$feature_idf[$feature]);
            $tf_idf = $tf*$idf;
            $update = "update `objects_binary_attributes` set `value`=$tf_idf where `oid`=$oid and `feature`=\"$feature\" limit 1";
            //echo $update."\n<br/>";
            mysql_query($update);
        }
    }
    
    
}    
private function CreateBinnryAttributesAntikvariat($row){
    //value = TF/IDF value - ta se doplni az nakonec hromadne, dodat on update set value = value +1
    // hned se spocte TF, IDF az na druhy pruchod
    $query_insert_features = "INSERT INTO `objects_binary_attributes`(`oid`, `feature`, `value`) VALUES ";
    $first = 1;
    $binTerms = array();
    $used_terms = array();         
    if(!isset($this->known_items[$row["oid"]])){
        $this->known_items[$row["oid"]] = 1;                                   
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["name"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["author"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["description"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["publisher"]));
                if(intval($row["issue"])>0){
                    $binTerms = array_merge($binTerms, array("vydani".intval($row["issue"])));
                }
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["translator"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["ilustrator"]));
                $binTerms = array_merge($binTerms, array("category".$row["category"]));
                if($row["price"]>0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["price"],array(0,20,50,100,200,350, 500, 1000, 2000, 10000), "price"));
                }
                if($row["pages_count"]>0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["pages_count"],array(0,50,100,200,300,400,500, 1000, 2000, 10000), "pages"));
                }
                if($row["publication_date"]>1700){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["publication_date"],array(1700,1800,1850,1900,1950,1990, 2000, 2010, 2050), "date"));
                }                        

               // print_r($binTerms);
        foreach ($binTerms as $key => $term) {
            if(strlen($term)>2 and $term!=""){
                if($first){
                    $first = 0;
                    $query_insert_features.="(".$row["oid"].",\"".$this->nazev_web($term)."\",1)\n"; 
                }else{
                    $query_insert_features.=",(".$row["oid"].",\"".$this->nazev_web($term)."\",1)\n"; 
                }

            }
                                         
        }
        $query_insert_features .= "on duplicate key update `value`=`value`+1";
       // echo nl2br($query_insert_features);                
        mysql_query($query_insert_features);
    }       
}


private function CreateBinnryAttributesSlantour($row){
    //value = TF/IDF value - ta se doplni az nakonec hromadne, dodat on update set value = value +1
    // hned se spocte TF, IDF az na druhy pruchod
    $query_insert_features = "INSERT INTO `objects_binary_attributes`(`oid`, `feature`, `value`) VALUES ";
    $first = 1;
    $binTerms = array();
    $used_terms = array();         
    if(!isset($this->known_items[$row["oid"]])){
        $this->known_items[$row["oid"]] = 1;                                   
                $binTerms = array_merge($binTerms, $this->getTermsFromText($row["nazev"]));
                $fields = explode(":",$row["zeme"].":".$row["informace_list"].":".$row["destinace"]);
                
                foreach ($fields as $value) {
                    $binTerms = array_merge($binTerms, $this->getTermsFromText($value));
                }

                $binTerms = array_merge($binTerms, $this->getTermsFromText("typ_".$row["id_typ"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText("str_".$row["strava"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText("dopr_".$row["doprava"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText("ubyt_".$row["ubytovani"]));
                $binTerms = array_merge($binTerms, $this->getTermsFromText("ubytkat_".$row["ubytovani_kategorie"]));
                if($row["delka"]>0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["delka"],array(0,3,6,9,15,10000), "delka"));
                }
                if($row["sleva"]>=0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["sleva"],array(-1,1,6,11,21,10000), "sleva"));
                }
                if($row["prumerna_cena"]>0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["prumerna_cena"],array(0,500,1200,2500,5000,10000,20000,100000), "avgprice"));
                }
                if($row["prumerna_cena_noc"]>0){
                    $binTerms = array_merge($binTerms, 
                        $this->divideToIntervals($row["prumerna_cena_noc"],array(0,100,300,600,1200,2500,5000,10000,100000), "avgpricenight"));
                }
                if($row["od"]>0){
                    $od_array = explode("-", $row["od"]);
                    $od = "termin".$od_array[0].$od_array[1];
                    $binTerms = array_merge($binTerms, array($od));
                }                     


        foreach ($binTerms as $key => $term) {
                    if(!isset($used_terms[$term])){
                        $used_terms[$term] = 1;
                        if(strlen($term)>2){
                            if($first){
                                $first = 0;
                                $query_insert_features.="(".$row["oid"].",\"".$term."\",1)\n"; 
                            }else{
                                $query_insert_features.=",(".$row["oid"].",\"".$term."\",1)\n"; 
                            }

                        }
                    }                          
        }
        $query_insert_features .= "on duplicate key update `value`=`value`+1";        
       // echo nl2br($query_insert_features);                
        mysql_query($query_insert_features);
    }       
}

     

 //print_r($this->addressToOidMap);

     private function divideToIntervals($value, $intervals, $name){
         foreach ($intervals as $key => $valueInt) {
             if($key >0){
                if($value <= $valueInt and $value > $intervals[$key-1]){
                 //current interval                 
                 return array($name.$valueInt);
                }
             }
         }
         return array();
     }

     private function getTermsFromText($text){
         $text_array = preg_split("/[\s,\.]+/", $text);
         $result = array(); 
         foreach ($text_array as $key => $text_item) {   
            $text_item = trim($this->nazev_web(strtolower($text_item)));
                //zde by se mohly vyhazovat stop slova
                if($text_item!=""){
                    $result[] = $text_item;
                }
                 
         }
         return $result;
     }
     
     private function nazev_web($nazev){
		$nazev_web = Str_Replace(
						Array("?","ë","ç","ß","ä","á","è","ï","é","ì","í","¾","ò","ó","ö","ø","š","","ú","ù","ü","ý", "?","ž","Á","È","Ï","É","Ì","Í","¼","Ò","Ó","Ø","Š","","Ú","Ù","Ý","Ž") ,
						Array("e","e","c","ss","á","a","c","d","e","e","i","l","n","o","o","r","s","t","u","u","u","y", "y","z","A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z") ,
						$nazev);
		$nazev_web = Str_Replace(Array(" ", "_"), "-", $nazev_web); //nahradí mezery a podtržítka pomlèkami
		$nazev_web = Str_Replace(Array("(",")","/",";",":","+",".","!",",","\"","-","'","[","]","{","}"), "", $nazev_web); //odstraní ().!,"'
		$nazev_web = StrToLower($nazev_web); //velká písmena nahradí malými.
		return $nazev_web;
    }
     
}

//$m = new ObjectsAttributesBinarization("antikvariat");

?>
