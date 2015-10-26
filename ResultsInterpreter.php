<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of resultInterpreter
 *
 * @author peska
 */
class ResultsInterpreter {
    //put your code here

    private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
     private $alg_results;
/*     $db_server="127.0.0.1"; //connect spider
$db_jmeno="tatraturcz001";
 $db_heslo="dovolena50";
 $db_nazev_db="tatraturcz";*/
 
 /**
  *compute how many objects from each method had qualified into the each K of top-k
  * @param type $from minimak K we are interested
  * @param type $to  maximal K
  */
public function presenceAtTopKDsitribution($from, $to) {
    $result_array = array(array());
    for($i = $from; $i <= $to; $i++){
        $query = "select count(*) as `pocet`, `algorithm` from `results`            
            where `position` <= ".$i." group by `algorithm` order by `algorithm`";
        $result = mysql_query($query);
        $result_array[$row["algorithm"]][$i] = 0;
        while($row = mysql_fetch_array($result)){
            $result_array[$row["algorithm"]][$i] = $row["pocet"];
            
        }
    }
    return $result_array;        
}
 
/**
 *compute ideal DCG from the count of prefered objects
 * @param type $count
 * @return type 
 */
public function computeIDCG($count) {
    $result = 0;
    $preference = 1;
    for($i=1; $i <= $count; $i++){
        if($i==1){
            $result = $preference; 
        }else{
            $result += $preference/log($i, 2); 
        }        
    }
    return $result;        
}

/**
 *compute normalized DCG from the list of objects recommended
 * @param type $objectList contains array $oid -> position
 * @return type 
 */
public function computeNDCG($objectList) {
    $result = 0;
    $dcg = 0;
    $preference = 1;
    foreach ($objectList as $key => $value) {
        //value = position in recommended list
        if($value==1){
            $dcg += $preference; 
        }else{
            $dcg += $preference/log($value, 2); 
        } 
    }    
    $pocet = sizeof($objectList);
    $idcg = $this->computeIDCG($pocet);
    $result = $dcg/$idcg;
    return $result;   
         
}


 /**
  *compute how many objects from each method had qualified into the each K of top-k
  * @param type $from minimak K we are interested
  * @param type $to  maximal K
  */
public function computeSumRules($alg) {
    $query = "select sum(`rel_applied`) as `applied`, sum(`rel_correct`) as `correct`, sum(`rel_weak`) as `weak` from `results`            
        where `algorithm` = \"".$alg."\" group by `algorithm` ";
    $result = mysql_query($query);
    while($row = mysql_fetch_array($result)){
        return "<td>".$row["applied"]."<td>".$row["correct"]."<td>".$row["weak"]."<td>".($row["correct"]/($row["applied"]+$row["correct"]+$row["weak"]+1))."<td>".($row["applied"]/($row["applied"]+$row["correct"]+$row["weak"]+1))."";            
    }        
}


 
/**
 * compute ndcg for all users and aggregate them according to the algorithms
 * @return type 
 */
public function computeAverageNDCGPerAlgorithm() {         
    $result_array = array();
    $ndcg_array = array();
    $ndcg_all_array = array();
    $query = "SELECT  distinct `uid`, `algorithm` FROM `results`
                 WHERE 1 order by `algorithm` ";
    $result = mysql_query($query);
    $last_pocet = 1;
    while ($row = mysql_fetch_array($result)) {
        $recommendingList = array();

        $queryUser = "select oid, position from results where uid = ".$row["uid"]." and algorithm = \"".$row["algorithm"]."\"  " ;
        $resultUser = mysql_query($queryUser);
        while ($rowUser = mysql_fetch_array($resultUser)) {
            $recommendingList[$rowUser["oid"]] = $rowUser["position"];
        }

        $currNDCG = $this->computeNDCG($recommendingList);
        $ndcg_array[$row["algorithm"]][] = $currNDCG;            

        
        
    }
    //vyhodim nDCG pro vsechny algoritmy
   foreach ($ndcg_array as $type => $NDCGs) {
                if(sizeof($NDCGs)>0){
                    $avg_ndcg = array_sum($NDCGs)/sizeof($NDCGs);
                    $this->alg_results[$type] = $avg_ndcg;
                }
            } 
     
}


public function __construct($typ) {
        if($typ=="antikvariat"){
                $this->db_nazev_db="antikvariat";
            }else{
                $this->db_nazev_db="slantour";
            }    
    @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
    @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());


    $res2 = $this->computeAverageNDCGPerAlgorithm();
    echo "<table><tr><th>Algorithm<th>nDCG<th>App rules<th>Conf Rules<th>Weak Rules<th>Ordering correctness<th>Applied rate";
    foreach ($this->alg_results as $key => $value) {
        $res = $this->computeSumRules($key);
        echo "<tr><td>$key<td>$value $res";
    }
    echo "</table>";
    

    $res1 = $this->presenceAtTopKDsitribution(1, 50);
    echo "<table><tr><th>Algorithm";
    for ($index = 1; $index <= 50; $index++) {
        echo "<th>$index";
    }
    foreach ($res1 as $alg => $list) {        
        echo "<tr><td>$alg";
        foreach ($list as $value) {
            echo "<td>$value";            
        }
    }
    echo "</table>"; 
    

/*    
    foreach ($res2 as $key => $list) {
        echo $key.";";
        foreach ($list as $key2 => $value) {
            echo $value.";";            
        }
        echo "<br/>\n";
        
    }
    echo "<br/>\n<br/>\n"; 
    */
 //print_r($this->addressToOidMap);
}


}

//$m = new ResultsInterpreter("antikvariat");
$m2 = new ResultsInterpreter("slantour");
?>
