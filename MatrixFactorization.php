<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MatrixFactorization
 *
 * @author peska
 */
class MatrixFactorization {
    private $name;
    public $usersMatrix;
    public $objectMatrix;
    private $userObjectTrueValues;

    
    private $learningRate;
    private $lambda;
    private $userObjectCompensator;
    
    private $overfittingParam;
    protected $badIterationsThreshold;
    protected $maxLoops;
    protected $convergenceThreshold;
    
    //put your code here
    
    /**
     * set all data
     * $usersMatrix je ve tvaru uid, latent factors
     * $objectMatrix je ve tvaru oid, latent factors
     * $knownTrueValues je ve tvaru uid, oid => value
     */
    public function __construct($name, $initUsersMatrix, $initObjectMatrix, $userObjectTrueValues, $learningRate = 0.01, 
            $lambda = 0.15, $userObjectCompensator=1, $overfittingParam=0.5, $maxLoops=150, $badIterationsThreshold=5){
        $this->name = $name;
        $this->usersMatrix = $initUsersMatrix;
        $this->objectMatrix = $initObjectMatrix;
        $this->userObjectTrueValues = $userObjectTrueValues;
     
        $this->learningRate = $learningRate;
        $this->lambda = $lambda;  
        $this->userObjectCompensator = $userObjectCompensator;   
        $this->overfittingParam = $overfittingParam;  
        $this->maxLoops = $maxLoops;  
        $this->badIterationsThreshold = $badIterationsThreshold;  
        

        
        

    }
    //shuffle array preserving the keys
    static function shuffle_assoc(&$array) {
        $keys = array_keys($array);

        shuffle($keys);

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;
        $new=null;
        
        return true;
    }
    /**
     * natr�nuje latent factors jak pro uzivatele, tak objekty
     */
    public function train(){
        $distance = $this->distance();
        echo "initialDistance: ".$distance."\n\n";
        $badIterationsCount = 0;
        $continue = 1;
        $iterationsCount = 0;
        
      while($continue){
         $iterationsCount++;
         //shuffle ordering
         MatrixFactorization::shuffle_assoc($this->userObjectTrueValues);
         
         //update both users and objects matrix         
         $this->iteration();
         $newDistance = $this->distance();
         
         echo "iteration: ".$iterationsCount."\n";
         echo "distance: ".$newDistance."\n\n";
         
         if($distance <= $newDistance)
         {
            $badIterationsCount++;
            // line search and backtrack for an appropriate learning rate here.
            
            if($badIterationsCount > $this->badIterationsThreshold)
               echo "Distance is increasing on iterations. You probably want to set a lower learning rate.";
         } else {
            $badIterationsCount = 0;   // reset bad iterations count on a good iteration.
         }
         //prekrocili jsme pocet cyklu
         if($this->maxLoops !== null and $iterationsCount >= $this->maxLoops){             
            $continue = 0;
         //rozdul hodnot je prilis maly, dokonvergovali jsme k cili driv   
         } else if(  abs(($distance - $newDistance)) < $this->overfittingParam  ) {  // convergence test.
            $continue = 0;
         }

         $distance = $newDistance;
      } 
      //store factors into the database
      /*$sql = "delete from `feedback_traces_user_latent_factors` where `type`=\"$this->name\"";
      mysql_query($sql);
      $sql = "delete from `feedback_traces_object_latent_factors` where `type`=\"$this->name\"";
      mysql_query($sql);
      $sql = null;*/
      
   // $f = fopen("query".$this->name.".php", "w");
   
      
      foreach ($this->objectMatrix as $key => $factors) {
          $sql = "
              insert into `feedback_traces_object_latent_factors` ( `oid`, `fid`, `value`,  `type`) VALUES ";
          $first = 1;
          foreach ($factors as $fid => $value) {
              if($first){
                  $first = 0;
                  $carka = "";
              }else{
                  $carka = ",";
              }
              $sql .= $carka."(".$key.",".$fid.",\"".$value."\",\"$this->name\")";
              $value = null;
          }
          //mysql_query($sql);
     //     fwrite($f, $sql);
          //echo $sql;
          $sql = null;
          $key = null;
          $factors = null;
      }
      
      foreach ($this->usersMatrix as $key => $factors) {
          $sql = "
              insert into `feedback_traces_user_latent_factors` ( `uid`, `fid`, `value`,  `type`) VALUES ";
          $first = 1;
          foreach ($factors as $fid => $value) {
              if($first){
                  $first = 0;
                  $carka = "";
              }else{
                  $carka = ",";
              }
              $sql .= $carka."(".$key.",".$fid.",\"".$value."\",\"$this->name\")";
              $value = null;
          }
          //mysql_query($sql);
     //     fwrite( $sql);
          //echo $sql;
          $sql = null;
          $key = null;
          $factors = null;
      } 
  //    fclose($f);
    }
    
    /**one iteration of MF
     * 
     * @param type $pairs pole typu [uid,oid]->true rating, uz by melo byt shuffled
     */
    public function iteration(){
        //create temporal matrix for users and objects       
        foreach ($this->userObjectTrueValues as $key => $rating) {
            $keyParts = explode(",", $key);
            //ziskam latentni faktory uzivatele i objektu
            $userFactors = $this->usersMatrix[$keyParts[0]];
            $objectFactors = $this->objectMatrix[$keyParts[1]];
            $countParam = sizeof($userFactors);
            //provedu odhad, porovnam se skutecnym ratingem, upravim latentFactors
            $difference = $rating - $this->hypothesis($userFactors,$objectFactors);   
                        
            for($i=0; $i<$countParam; $i++) {
                $userFactors[$i] = $userFactors[$i] + $this->learningRate * ($difference * $objectFactors[$i] - $this->lambda*$userFactors[$i]);
                $objectFactors[$i] = $objectFactors[$i] + $this->learningRate * ($difference * $userFactors[$i] - $this->lambda*$this->userObjectCompensator*$objectFactors[$i]);
            }
            //latentni faktory updatuju v hlavni matici
            $this->usersMatrix[$keyParts[0]] = $userFactors;
            $this->objectMatrix[$keyParts[1]] = $objectFactors;
            
            $keyParts = null;
            $userFactors = null;
            $objectFactors = null;
           // unset($keyParts);
           // unset($userFactors);
           // unset($objectFactors);
        }
         
    }

    
    /**
 * compute score for each object and user
 * @param type $objectFactors object latent factors    
 * @param type $userFactors user latent factors
 * @return type preference score of the object for particular user
 */
function computeScore($oid, $uid){
    $score = 0;
            $userFactors = $this->usersMatrix[$uid];
            $objectFactors = $this->objectMatrix[$oid];
            $score = $this->hypothesis($userFactors,$objectFactors); 
    return $score;
}

    
    //jen spocte sumu latentnich faktoru - ocekavane hodnoceni
    public function hypothesis($userFactors, $objectFactors){
        //print_r($userFactors);
        $result = 0;
        foreach ($userFactors as $key => $value) {
            $result += $value * $objectFactors[$key];
            $key = null;
            $value = null;
           // unset($key);
           // unset($value);
        }
        return $result;
    }
    
    //pocita vzdalenost mezi idealnima a skutecnyma hodnotama
    public function distance(){
        $result = 0;
        foreach ($this->userObjectTrueValues as $key => $rating) {
            $keyParts = explode(",", $key);
            //ziskam latentni faktory uzivatele i objektu
            $userFactors = $this->usersMatrix[$keyParts[0]];
            $objectFactors = $this->objectMatrix[$keyParts[1]];
            $difference = $rating - $this->hypothesis($userFactors,$objectFactors); 
            $result += pow($difference, 2);                        
        }
        return $result;
        
    }
}

?>
