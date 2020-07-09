<?php


/* desarrollo */


$apiKey = "bvnsrgkdxwmt9aapu7pq5xhc";

$Secret = "M7hrXyprYv";

$service_url ="https://api.test.hotelbeds.com/activity-api/3.0/";


print_r("<pre>");
print_r("apiKey =".$apiKey);
print_r("<pre>");

print_r("<pre>");
print_r("Secret =".$Secret);
print_r("<pre>");

print_r("<pre>");
print_r($service_url);
print_r("<pre>");






/* produccion */

/*
$service_url ="https://api.hotelbeds.com/activity-api/3.0/";

$apiKey = "acy5eu5ajqarvn8sxxq4k9pt";

$Secret = "dxJs54khuF";

*/


/* parametros para el metodo activities*/

$iata_destino='MIA';

$latitude="25.855773";
$longitude="-80.13915700000001";
$language="es";
$pages=1;
$itemsPerPage=20;


/******************************/

/* parametros para details/full */

$checkin="2020-09-12";
$checkout="2020-09-20";
$edades_adultos=array();
$edades_menores=array();
$edades_adultos[]=23;
$edades_adultos[]=33;

//$edades_menores[]=23;
//$edades_menores[]=33;

/***************************/


$service_activities =$service_url."activities";

$cantidad_actividades=0;

for ($pages=1;$pages<=1;$pages++) // paginacion

{


$signature = hash("sha256", $apiKey.$Secret.time());

$curl_header_data[] = "Api-Key: ".$apiKey;

$curl_header_data[] = "X-Signature: ".$signature;

$curl_header_data[] = "Accept: application/json";

$curl_header_data[] = "Accept-Encoding: gzip";

$curl_header_data[] = "Content-Type: application/json";


$data_string='{
  "filters": [
        {
            "searchFilterItems": [ 
                {"type": "gps", "latitude":'.$latitude.', "longitude":'.$longitude.'}
            ]
        }
  ],
  "from": "'.$checkin.'",
  "to": "'.$checkout.'",
  "language": "'.$language.'",
  "pagination": {
    "itemsPerPage":'.$itemsPerPage.',
    "page": '.$pages.'
  },
  "order": "DEFAULT"
}';



$curl = curl_init();

curl_setopt($curl, CURLOPT_URL,$service_activities);

curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header_data);

curl_setopt($curl, CURLOPT_ENCODING, 'GZIP');

curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_TIMEOUT, (60 * 15));

curl_setopt($curl, CURLOPT_VERBOSE, true);

curl_setopt($curl, CURLOPT_STDERR, $verbose);

$curl_response = curl_exec($curl);

curl_close($curl);

// obtengo todas las actividades para un rango de fecha y una ubicacion
$results_activities = json_decode($curl_response);


print_r("<pre>");
print_r($results_activities->activities);
print_r("<pre>");
die;


//armo los pasajeros con sus edades

unset($paxes);
	
foreach($edades_adultos as $pax) {

	$paxes[]='{"age": '.$pax.'}';

}

foreach($edades_menores as $pax) {

	$paxes[]='{"age": '.$pax.'}';

}

$service_details =$service_url."activities/details/full";

// recorro todas las actividades y obtengo el detalle de cada actividad

//$activities[] = $results_activities->activities[2]; // sacar el id 0 para recorrer todas las actividades


$activities = $results_activities->activities; // descomentar para recorrer todas las actividades


foreach($activities as $activity) 
{
    
 //codigo para obtener el detalle
 $codigo=(string) $activity->code;


 $data_string='{

  "code": "'.$codigo.'",

  "from": "'.$checkin.'",

  "to": "'.$checkout.'",

  "language": "'.$language.'",

  "paxes": ['.implode(",",$paxes).'

   ]

}';

//echo "<pre>";echo htmlentities($data_string);echo "</pre>";


$curl = curl_init();

curl_setopt($curl, CURLOPT_URL,$service_details);

curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header_data);

curl_setopt($curl, CURLOPT_ENCODING, 'GZIP');

curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_TIMEOUT, (60 * 15));

curl_setopt($curl, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');

curl_setopt($curl, CURLOPT_STDERR, $verbose);

$curl_response = curl_exec($curl);

curl_close($curl);

$results = json_decode($curl_response);

/*
print_r("<pre>");
print_r($results);
print_r("<pre>");
die;
*/


$actividad=array();
$arr_vigencia=array();
$activity=$results->activity;


$actividad["name"]=utf8_decode($activity->name);
$actividad["code"]=(string) $activity->code;
$actividad["type"]=utf8_decode($activity->type);
$actividad["country"]["code"]=$activity->country->code;
$actividad["country"]["name"]=$activity->country->name;

$dest = $activity->country->destinations;

foreach ($dest as $clave => $valor)
{     
    
  $destino['code']=utf8_decode($valor->code);
  $destino['name']=utf8_decode($valor->name);
  
  $actividad["country"]['destinations'][]=$destino; 
}  


$actividad["currency"]=$activity->currency;
$actividad["description"]=$activity->content->description;

$segmentationGroups=$activity->content->segmentationGroups;

foreach ($segmentationGroups as $key => $value) {
    
    $actividad['segmentationGroups'][$key]['code']=$value->code;
    $actividad['segmentationGroups'][$key]['name']=$value->name;
    $segments=$value->segments;
    foreach ($segments as $keySeg => $valueSeg) {
        
        $actividad['segmentationGroups'][$key]['segments'][$keySeg]['code']=$valueSeg->code;
        $actividad['segmentationGroups'][$key]['segments'][$keySeg]['name']=$valueSeg->name;
        
    }
}
 
$images=$activity->content->media->images;

foreach ($images as $key => $value) {
       
    $urls=$value->urls[1];
    $actividad['images'][] = $urls->resource; 
           
}


$geolocation=$activity->content->geolocation;
$actividad['latitude'] = $geolocation->latitude;
$actividad['longitude'] = $geolocation->longitude;

$arr_tarifas=array();
$rs_modalities=$results->activity->modalities;

//fechas tarifas

foreach($rs_modalities as $opcion) {
    
    
   $actividad_opcion['name']=$opcion->name;
   $actividad_opcion['duration']=$opcion->duration;   
   //$actividad_opcion['comments']=$opcion->comments;

   if(isset($opcion->questions))
   {
       foreach ($opcion->questions as $keyQuestions => $question) {
           
          $questions['code']= $question->code;
          $questions['text']= $question->text;
          $questions['required']= $question->required;
          $actividad_opcion['questions'][]=$questions;
       } 
           
       unset($questions);
   } 
   
    
   foreach($opcion->rates as $rate) { 
       
      $actividad_opcion['rateCode']=$rate->rateCode; 
       
      foreach($rate->rateDetails as $rd) { 
          
          unset($arr_tarifas);
          
          $arr_tarifas['rateKey']=(string) $rd->rateKey;
          $arr_tarifas['amount']=$rd->totalAmount->amount; // Cotizacion de todos los pax
          $arr_tarifas["currency"]=$activity->currency;
          foreach($rd->operationDates as $od) {
              
             unset($arr_vigencia); 

	    $arr_vigencia["from"]=$od->from;

	    $arr_vigencia["to"]=$od->to;

	    $arr_vigencia["cancellationPolicies"]=$od->cancellationPolicies;
	
            $arr_tarifas['operationDates'][]=$arr_vigencia;
	   }
                           
          $actividad_opcion['tarifas']=$arr_tarifas;
                            
      } 
                
   }

   
   $opciones[]=$actividad_opcion;
   
   unset($actividad_opcion);
}


$actividad['opciones']=$opciones;

unset($opciones);

$actividades[]=$actividad;

unset($actividad);

$cantidad_actividades++;

}
} // fin paginacion


print_r("cantidad de actividades");
print_r("<pre>");
print_r($cantidad_actividades);
print_r("<pre>");


print_r("<pre>");
print_r($actividades);
print_r("<pre>");




