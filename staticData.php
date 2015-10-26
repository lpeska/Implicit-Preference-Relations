<?php
/**
 * Description of staticData
 * Data potøebné nìkolika rùznými tøídami - optimalizace dotazù do DTB
 * @author peska
 */
class staticData {
    //put your code here
    public static $object_features;
    public static $object_featuresTF;
    public static $object_category;
    public static $object_similarities;
    public static $object_popularity;
    private static $db_server="127.0.0.1"; //connect spider
    private static $db_jmeno="root";
    private static $db_heslo="";
    private static $db_nazev_db="antikvariat";
    //inicializace a zpracování SQL dotazù
    static function init($typ){
        echo Date("H:i:s")." starting  staticData\n<br/>";  
        
        
        //init object features
        if($typ=="antikvariat"){
            self::$db_nazev_db="antikvariat";
        }else{
            self::$db_nazev_db="slantour";
        }
        @$db_spojeni = mysql_connect(self::$db_server, self::$db_jmeno, self::$db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$db_vysledek = mysql_select_db($typ, $db_spojeni) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());

        $query_val = "SELECT * FROM `objects_binary_attributes` WHERE 1";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_features[$row_val["oid"]][$row_val["feature"]] = $row_val["value"];
        } 
        
        $query_val = "SELECT * FROM `objects_binary_attributes_tf` WHERE 1";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_featuresTF[$row_val["oid"]][$row_val["feature"]] = $row_val["value"];
        } 
        
        $query_val = "SELECT objectID, count(*) as popularity FROM `train_set` WHERE 1 group by objectID";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_popularity[$row_val["objectID"]] = log($row_val["popularity"]+2.72);
        } 

        
        
       // print_r(self::$object_features);
        
        $query_val = "SELECT oid, category FROM `objects_table` WHERE 1";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_category[$row_val["oid"]] = $row_val["category"];
        } 

        echo Date("H:i:s")." finish  staticData\n<br/>"; 
    }
}
