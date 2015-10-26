<?php
//prvni krok: zpracuje ktere objekty byly viditelne

class getObjectVisibility{
    private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
     
    public function __construct($typ) {    
        echo Date("H:i:s")." starting getObjectVisibility\n<br/>";
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }
        
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
        mysql_query("TRUNCATE object_visibility");
        
        
        $this->get_object_visibility();
        
        echo Date("H:i:s")." finishing getObjectVisibility\n<br/>";  
    }
    
    function get_object_visibility(){
        //bereme jen navstevy, ktere jsou aspon trochu zajimave
    $data = mysql_query("SELECT * FROM `new_implicit_events` where `timeOnPage` > 500 or`forwardingToLinkCount` > 0");
    while ($row = mysql_fetch_array($data)) {
    $dotaz_insert = "INSERT INTO `object_visibility`(`visitID`, `objectID`, `posX`, `posY`, `selected`,`visible`, `visible_time`, `visible_percentage`) VALUES";

        $windowSizeX = $row["windowSizeX"];
        $windowSizeY = $row["windowSizeY"];
        if($windowSizeX==0){
            $windowSizeX = 1280; //default width      
        }
        if($windowSizeY==0){
            $windowSizeY = 960; //default height      
        }
        $visitID = $row["visitID"];
        $logfile = $row["logFile"];
        $objectsListed = $row["objectsListed"];
        $total_visit_time = $row["timeOnPage"];
        $maxX = $windowSizeX;
        $maxY = $windowSizeY;
        //hledam vybrane objekty
        $selected_objects = array();
        if($row["pageType"]=="zobrazit" and $row["objectID"]!=""){
            @$selected_objects[$row["objectID"]]++;
        }
        $j=0;

        //get selected objects
        preg_match_all("/oid=[0-9]+/", $logfile, $oids, PREG_PATTERN_ORDER);
        foreach ($oids[0] as $oid_array) {
            $j++;
            $oid = explode("=", $oid_array);
            if(intval($oid[1])>0){
                @$selected_objects[intval($oid[1])]++;
            }                
        }
        //array [oid]=>{posx, posy, visible, visible_time}
        $all_objects = array();

        //get all objects listed
        $objects_array = explode(";", $objectsListed);
        foreach ($objects_array as $object_record) {
            $object_record_array = explode(":", $object_record);
            $oid = $object_record_array[0];
            if ($oid > 0) {
                $object_position_array = explode(",", $object_record_array[1]);
                $objPosX = $object_position_array[1];
                $objPosY = $object_position_array[0];
                $all_objects[$oid] = array($objPosX, $objPosY, 0, 0);
            }
        }    

        $last_event_datetime = $row["startDatetime"];    
       // print_r($selected_objects);
        $i = 0;
        $posX = 0;
        $posY = 0;

       // echo $logfile;
        $positions = array();
        preg_match_all("/([0-9]+-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+); Scroll, to:([0-9]+), ([0-9]+)/", $logfile, $positions, PREG_PATTERN_ORDER);
       // print_r($positions);

        foreach ($positions[0] as $key => $p) {
            $i++;
            $datetime = $positions[1][$key];
            $posX = $positions[2][$key];
            $posY = $positions[3][$key];       
            $diff = abs(strtotime($last_event_datetime) - strtotime($datetime));
            if($diff == 0){
                $diff = 0.1;
            }        
            $last_event_datetime = $datetime;
            //echo $diff."\n<br/>";
            //projdu vsechny objekty a pokud spadaji do spanu pozice, prictu jim cas a visibility
            foreach ($all_objects as $oid => $obj_array) {
                $objX = $obj_array[0];
                $objY = $obj_array[1];
                if($objX >= $posX and $objX <= ($posX+$windowSizeX) and $objY >= $posY and $objY <= ($posY+$windowSizeY)){
                    //objekt je v zornÃ©m poli
                    //tady by se dalo jinak prepocitavat "index viditelnosti" dle pozice objektu
                    //pridani casu zobrazeni
                    $all_objects[$oid][2] = 1; //objekt byl videt
                    $all_objects[$oid][3] += $diff; //pripocte se cas viditelnosti
                }
            }       
        }

        //jeste musim dopocitat cas od posledniho scrollovani do konce navstevy
        $diff = abs(strtotime($last_event_datetime) - strtotime($row["endDatetime"]));

        //projdu vsechny objekty a pokud spadaji do spanu pozice, prictu jim cas a visibility
        foreach ($all_objects as $oid => $obj_array) {
            $objX = $obj_array[0];
            $objY = $obj_array[1];
            if($objX >= $posX and $objX <= ($posX+$windowSizeX) and $objY >= $posY and $objY <= ($posY+$windowSizeY)){
                //objekt je v zornÃ©m poli
                //tady by se dalo jinak prepocitavat "index viditelnosti" dle pozice objektu
                //pridani casu zobrazeni
                $all_objects[$oid][2] = 1; //objekt byl videt
                $all_objects[$oid][3] += $diff; //pripocte se cas viditelnosti
            }
        }
        //echo "visitID:" . $visitID. " pocet scroll:" . $i."\n<br/>";

        //zapisu vysledky
        foreach ($all_objects as $oid => $obj_array) {
            $visible_percentage = round( ($obj_array[3]*1000/$total_visit_time) ,2);
            if($visible_percentage > 1){
                $visible_percentage = 1;
            }
            if(isset($selected_objects[$oid])){
                $selected = 1;
            }else{
                $selected = 0;
            }    
            if($obj_array[3] > 9999){
                $obj_array[3] = 10000;
            }
            //early heuristics on minimal feature values
            if(($obj_array[3] >1 and $visible_percentage > 0.01) or $selected==1){
                if ($dotaz_insert != "INSERT INTO `object_visibility`(`visitID`, `objectID`, `posX`, `posY`, `selected`,`visible`, `visible_time`, `visible_percentage`) VALUES") {
                    $dotaz_insert .= ",";
                }
                $dotaz_insert .= "(" . $visitID . "," . $oid . "," . $obj_array[0] . "," . $obj_array[1] . ",". $selected . "," . $obj_array[2] . "," . $obj_array[3] . "," . $visible_percentage . ")\n";    
            }
        }

        //pokud jsem mel prazdnou mnozinu objektu, nepisu dotaz
        if ($dotaz_insert != "INSERT INTO `object_visibility`(`visitID`, `objectID`, `posX`, `posY`, `selected`,`visible`, `visible_time`, `visible_percentage`) VALUES") {
       //     echo nl2br($dotaz_insert);
            $spravne = mysql_query($dotaz_insert);
            echo mysql_error();
        }    
    }

    }
}

?>
