<?php 

$json = file_get_contents('yasawa.json'); 
 // print_r($data);

 //reverses the date string and convert into unix time.
 function format_date($datestr){
    $date = date_create_from_format('j-m-Y', $datestr);
    return strtotime(date_format($date, 'Y-m-d'));
    
 }

 function days_between($timestamp1, $timestamp2) {
    $diff = abs($timestamp2 - $timestamp1);
    return floor($diff / (60 * 60 * 24));
}
 
 //checks wether the datefrom and dateto falls in specified seasons
 function check_range($df, $dt, $sdb, $sde){
    $df = format_date($df);
    $dt = format_date($dt);
    $sdb = strtotime($sdb);
    $sde = strtotime($sde);
    return ($df >= $sdb && $dt <= $sde)? true : false;
    }
 


//Will create an associative array that will be used for reaching plans to calculate
function routing_tree($wd,$df,$dt){

    

    $tree = array();
    foreach($wd as $room){
        $xplps = $room['allSeasons']['XPLProductSeason'];
        $tree[$room['name']] = array(
            'id' => $room['id'],
        );
        
        foreach($xplps as $plan){
            if(true == check_range($df, $dt, $plan['dateFrom'], $plan['dateTo'])){
            $tree[$room['name']][$plan['id']] = 
             array(
               "status" => check_range($df, $dt, $plan['dateFrom'], $plan['dateTo']),
               "name" => $plan['name'],
               "types" => $plan['allRateBands']['XPLProductRateBand'][0]['allRates']['XPLProductRate'],

            );
             
             }else{
                continue;
             }
        }
    }
   
    return $tree;

}


$date = date_create_from_format('j-m-Y', '15-10-2012');

//Driver function action in action 
function get_pricing($json,$dateFrom,$dateTo,$adults){

    $adults = $adults <= 2 ? 1 : 3;
    
    //decoded the json into an associative array.
    $data = json_decode($json,true);
    //pruned the data to working data.
    $wd = $data['allChildren']['XPLProduct'];

    
    
    $od = routing_tree($wd,$dateFrom,$dateTo);
    //print_r($od);


    $nights = days_between(format_date($dateFrom),format_date($dateTo));
   

    //It gets messy from here, now state updates will be stored in an array

    $presentation = array();

    
    foreach($od as $kamra){
       
        $presentation["Room ID - ".$kamra['id']." (".array_search ($kamra, $od).")"] = array();
       
        foreach($kamra as $scheme){
            if('id' !== array_search ($scheme, $kamra) ){
                if(!isset($scheme['types'][$adults-1]['value'])){
                    continue;
                }

               

             
                
                $calc = 
                (isset($scheme['types'][$adults-1]['value']))?
                $scheme['types'][$adults-1]['value']*$adults*$nights
                :"Not available";
    

                $presentation["Room ID - ".$kamra['id']." (".array_search ($kamra, $od).")"][$scheme['name']." => "] = 
                "".$calc."";
            }
        }
    }


    /*

    Code Bellow is the new presentation layer. 
    It shows only The prices for the available rooms.


    */
    echo "<h2>".$dateFrom." - ".$dateTo."</h2><br>";
    
    foreach($presentation as $child){
       if(count($child) == 0){
            continue;
       }
        echo array_search($child,$presentation)."<br>";
        foreach($child as $sub){
            echo array_search($sub, $child)."<span style=\"color:red;\">".$sub."</span>"."<br>";
        }
    
    }
}



//magic starts here
get_pricing($json,'01-11-2019','08-11-2019',2);










?>