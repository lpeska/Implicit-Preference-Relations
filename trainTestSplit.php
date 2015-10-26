<?php
/*Krok 3, take muze byt udelany dopredu, pokud bych chtel opakovat pokus vicekrat s CV, je treba:
 * - zmenit razeni dat na nahodne
 * - vykaslat se na is_recommendable
 * - nastavit pevnou velikost train_set i test_set
 * - z nekterych behu vyhodit uzivatele, kteri maji malo interakci
*/

class TrainTestSplit{

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
    private $nextOid = 1;
    private $addressToOidMap;


    public function __construct($typ) {

        echo Date("H:i:s")." starting TrainTestSplit\n<br/>";
        if($typ=="antikvariat"){
                $this->db_nazev_db="antikvariat";
            }else{
                $this->db_nazev_db="slantour";
            }

        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());

        //nastaveni kodovani
        mysql_query("SET character_set_results=cp1250");
        mysql_query("SET character_set_connection=UTF8");
        mysql_query("SET character_set_client=cp1250");
        mysql_query("TRUNCATE train_set");
        mysql_query("TRUNCATE test_set");

        $this->TTSplit();

        echo Date("H:i:s")." finishing TrainTestSplit\n<br/>";  
     //print_r($this->addressToOidMap);
    }

    private function TTSplit(){
        $queryOIDs = "select distinct oid from `objects_binary_attributes`               
             where 1 ";
        $resultOIDs = mysql_query($queryOIDs);
        $availableOIDs = array();
        while ($rowOID = mysql_fetch_array($resultOIDs)) {  
            $availableOIDs[$rowOID["oid"]] = 1;
        }
        $result = mysql_query($query);
        
        $query = "select distinct `new_implicit_events`.* from `new_implicit_events` 
             where timeOnPage>500 order by userID, startDatetime, endDatetime";
        $result = mysql_query($query);
        //zpracuje jednotlivé trace
        $lastUID = 0;
        $count = 0;
        $countRecommendable = 0;
        $storage = array();
        while ($row = mysql_fetch_array($result)) {           
            if($row["userID"]!=$lastUID){
                $trainCount = 0;
                $lastUID = $row["userID"];
                if($count ==2){
                    $trainCount = 1;
                }else if($count == 3){
                    $trainCount = 2;
                }else if($count > 3 ){
                    $trainCount = floor($count *3/4);
                    //$testCount = $count - $trainCount;
                }
                $max_recommendable_id = 0;
                foreach ($storage as $key => $value) {
                    if($value["is_recommendable"]==1){
                        $max_recommendable_id = $key;
                    }
                }  
                if($max_recommendable_id<($trainCount+1)){
                    $trainCount = $max_recommendable_id - 1;
                }
               // echo $count.", ".$trainCount.", ".$max_recommendable_id."<br/>\n";
                //use only applicable users
                if($trainCount >= 1 and $countRecommendable > 0){
                    //ulozime data do prislusnych tabulek
                    for($i=0; $i<=$trainCount; $i++){
                        $sql = "INSERT INTO `train_set`(`userID`,`objectID`, `visitID`, `is_recommendable`) VALUES (".$storage[$i]["userID"].",".$storage[$i]["objectID"].",".$storage[$i]["visitID"].",".$storage[$i]["is_recommendable"].")";
                        mysql_query($sql);                           
                    }
                    for($i=($trainCount+1); $i<$count; $i++){
                        //ukladam pouze ty ktere chci testovat                    
                        $sql = "INSERT INTO `test_set`(`userID`,`objectID`, `visitID`, `is_recommendable`) VALUES (".$storage[$i]["userID"].",".$storage[$i]["objectID"].",".$storage[$i]["visitID"].",".$storage[$i]["is_recommendable"].")";
                        mysql_query($sql);                                    
                    }
                }

                $count = 0;
                $countRecommendable = 0;
                $storage = array();
            }
            if($row["objectID"]>0 and $availableOIDs[$row["objectID"]]>0){
                $row["is_recommendable"]=1;
                $countRecommendable++;
            }else{
                $row["is_recommendable"]=0;
            }
            if($availableOIDs[$row["objectID"]]>0){
                $storage[$count] = $row;
                $count++;        
            }
         }
    }

}

//$m = new TrainTestSplit("antikvariat");

?>
