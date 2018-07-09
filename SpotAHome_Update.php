<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('max_execution_time', 3600);

date_default_timezone_set('UTC');

$hora=date("H");


?>
<html>
<head>
<body style="background-color:#d3d3d3;">
<?php


ini_set('display_errors', 1);

set_time_limit(0);
ini_set('max_execution_time', 6000);

include('simple_html_dom.php');
include('funcoes.php');
include('funcoes_analysis.php');





function GetSpotAHome($ID, $link) {
	try
	{
		$site="SpotAHome";
		//echo "<br>Entrou: $site";

		//echo "<hr>".$link;
		if (($link!=""))
		{
			
			$video="0";
			$floorplan="0";
			$price="";
			$room_id="";
			$type="";
			$neighborhood="";
			$availability="";
			$min_stay="";
			$max_stay="";
			$verified="0";
			$instant="0";
			$calendar_updated_at="";
			$bathrooms="";
			$floor_area_m2="";
			$maps_link="";
			$title="";

			$htmlanuncio = new simple_html_dom();
			//$htmlanuncio->load_file($link);
			$htmlanuncio=DaHtmlCtxHttpsAnalysis($link);
			$divconteudo = $htmlanuncio;

			$divavailability=$divconteudo->find('div[class*=room--availability]',0);
			if (sizeof($divavailability)>0)
			{
				$availability=$divavailability->plaintext;
				$availability=str_replace("Available","",$availability);
				$availability=str_replace(":","",$availability);
			}

			$divpreco=$divconteudo->find('span[class=rentable-unit-price]',0);
			if (sizeof($divpreco)>0)
			{
				$price=$divpreco->plaintext;
				$price=str_replace("€","",$price);
				$price=utf8_encode($price);
				$price=str_replace("&euro;","",$price);
			}

							
			$divtype=$divconteudo->find('div[class=room__contract-type]',0);
			if (sizeof($divtype)>0)
			{
				$type=$divtype->plaintext;
				$type=str_replace("Type of contract","",$type);
				$type=str_replace("Learn more","",$type);
				$type=str_replace(":","",$type);
				
			}
			
			$divvideo=$divconteudo->find('a[class*=slider--tab-video]',0);
			if (sizeof($divvideo)>0)
			{
				$video="1";
			}
			
			$divfloorplan=$divconteudo->find('a[class*=slider--tab-floorplan]',0);
			if (sizeof($divfloorplan)>0)
			{
				$floorplan="1";
			}
			
			//echo "<hr>".print_r($divconteudo->find('meta[title]',0));
			//echo "<hr>".print_r($divconteudo->find('title',0));
			
			//echo "<hr>".$divconteudo->find('meta[property=og:title]',0)->attr["content"];
			$divtitle=$divconteudo->find('meta[property=og:title]',0);
			if (sizeof($divtitle)>0)
			{
				$title=$divtitle->attr["content"];
			}
			
			foreach($divconteudo->find('div[class=property-info]') as $blocos)
			{
				$bloco=$blocos->plaintext;
				if ($neighborhood=="")
				{
					$posibairro=strpos($bloco,"Neighborhood information");
					if ($posibairro !== false)
					{
						$divneighborhood=$blocos->find('p',0);
						if (sizeof($divneighborhood)>0)
						{
							$neighborhood=$divneighborhood->plaintext;
							$neighborhood=str_replace(":","",$neighborhood);
						}
					}
				}
				if ($neighborhood=="")
				{
					$posibairro=strpos($bloco,"Información del barrio");
					if ($posibairro !== false)
					{
						$divneighborhood=$blocos->find('p',0);
						if (sizeof($divneighborhood)>0)
						{
							$neighborhood=$divneighborhood->plaintext;
							$neighborhood=str_replace(":","",$neighborhood);
						}
					}
				}
				
				if ($bathrooms=="")
				{
					$posibathroom=strpos($bloco,"Number of bathrooms:");
					if ($posibathroom !== false)
					{
						$posfbathroom=strpos($bloco,"</li>",$posibathroom);
						$bathrooms=substr($bloco,$posibathroom,$posfbathroom-$posibathroom);
						$bathrooms=str_replace("Number of bathrooms:","",$bathrooms);
					}
				}
				if ($bathrooms=="")
				{
					$posibathroom=strpos($bloco,"Número de baños:");
					if ($posibathroom !== false)
					{
						$posfbathroom=strpos($bloco,"</li>",$posibathroom);
						$bathrooms=substr($bloco,$posibathroom,$posfbathroom-$posibathroom);
						$bathrooms=str_replace("Número de bañoss:","",$bathrooms);
					}
				}
				
				if ($floor_area_m2=="")
				{
					$posifloor=strpos($bloco,"Floor area:");
					if ($posifloor !== false)
					{
						$posffloor=strpos($bloco,"</li>",$posifloor);
						$floor_area_m2=substr($bloco,$posifloor,$posffloor-$posifloor);
						$floor_area_m2=str_replace("Floor area:","",$floor_area_m2);
					}
				}
				
				if ($floor_area_m2=="")
				{
					$posifloor=strpos($bloco,"Superficie:");
					if ($posifloor !== false)
					{
						$posffloor=strpos($bloco,"</li>",$posifloor);
						$floor_area_m2=substr($bloco,$posifloor,$posffloor-$posifloor);
						$floor_area_m2=str_replace("Superficie:","",$floor_area_m2);
					}
				}
				
			}

			if ($bathrooms!="")
			{
				$bathrooms=trim(substr($bathrooms,0,10));
				$bathrooms=str_replace(chr(128),"",$bathrooms);
				$bathrooms=str_replace(chr(226),"",$bathrooms);
				$bathrooms=str_replace(chr(138),"",$bathrooms);
				$bathrooms = preg_replace("/[^0-9]/", "", $bathrooms);
			}
			
			
			if ($floor_area_m2!="")
			{
				$floor_area_m2=trim(substr($floor_area_m2,0,10));
				$floor_area_m2=str_replace(chr(128),"",$floor_area_m2);
				$floor_area_m2=str_replace(chr(226),"",$floor_area_m2);
				$floor_area_m2=str_replace(chr(138),"",$floor_area_m2);
				$floor_area_m2 = preg_replace("/[^0-9]/", "", $floor_area_m2);
			}
			
			$divminmax=$divconteudo->find('div[class=room--minmaxstay]',0);
			if (sizeof($divminmax)>0)
			{
				$divmin=$divminmax->find('div[class=apartment-info--item]',0);
				if (sizeof($divmin)>0)
				{
					$min_stay=$divmin->plaintext;
				}
				$divmax=$divminmax->find('div[class=apartment-info--item]',1);
				if (sizeof($divmax)>0)
				{
					$max_stay=$divmax->plaintext;
				}
			}
			
			$divcalendar=$divconteudo->find('div[class=availability-update-info]',0);
			if (sizeof($divcalendar)>0)
			{
				$calendar_updated_at=$divcalendar->plaintext;
			}
			
			$divverified=$divconteudo->find('div[class=booknow-card--footer]',0);
			if (sizeof($divverified)>0)
			{
				$textoverified=$divverified->plaintext;
				$posiverified=strpos($textoverified,"Checked by Spotahome");
				if ($posiverified !== false)
				{
					$verified="1";
				}
				
				$posiinstant=strpos($textoverified,"Instant Booking");
				if ($posiinstant !== false)
				{
					$instant="1";
				}
			}
		
			
			$divmaps=$divconteudo->find('img[class=static-map]',0);
			if (sizeof($divmaps)>0)
			{
				$maps_link=$divmaps->src;
			}
			
			

			$htmlanuncio->clear(); 
			unset($htmlanuncio);

			$price=limpa(str_replace("€","",$price));
			$price=limpa(str_replace("?","",$price));
			$price=limpa(str_replace("&euro;","",$price));
			$price=limpa(str_replace("ÃÂ£","",$price));
			$price=limpa(str_replace("AED","",$price));
			
			//$zona=limpa(limpa(trim($zona)));
			//$neighborhood=trataplicasBD(tratatextoBD((trim($neighborhood))));
			$neighborhood=trataplicasBD(((trim($neighborhood))));
			
			
			$pos = strrpos($link, "/");
			if ($pos !== false)
			{
				$room_id=substr($link,$pos);
				$room_id=limpa(str_replace("/","",$room_id));
			}
			
			if ($room_id=="")
				$room_id="0";
			
			
			if (strlen($neighborhood)>180)
				$neighborhood=substr($floor_area_m2,1,180);



			/*
			echo "<br/>Link: ".$link;
			echo "<br/>URL: ".$URL;
			echo "<br/>neighborhood: ".$neighborhood;
			echo "<br/>price: ".$price;
			echo "<br/>type: ".$type;
			echo "<br/>room_id: ".$room_id;
			echo "<br/>availability: ".$availability;
			echo "<br/>floorplan: ".$floorplan;
			echo "<br/>video: ".$video;
			echo "<br/>min_stay: ".$min_stay;
			echo "<br/>max_stay: ".$max_stay;
			echo "<br/>verified: ".$verified;
			echo "<br/>instant: ".$instant;
			echo "<br/>calendar_updated_at: ".$calendar_updated_at;
			echo "<br/>floor_area_m2: ".$floor_area_m2;
			echo "<br/>bathrooms: ".$bathrooms;
			*/
			
			//echo "<br/>Link: ".$link;
			echo "<br/>ID_Sah: ".$ID." - room_id: ".$room_id;

			if ($room_id!="")
			{
				$sql="Update competitors_spotahome ";
				$sql=$sql." set neighborhood='".$neighborhood."', type='".$type."', room_id='".$room_id."', price='".$price."', availability='".$availability."'";
				$sql=$sql." , floorplan='".$floorplan."', video='".$video."', min_stay='".$min_stay."', max_stay='".$max_stay."', verified='".$verified."' ";
				$sql=$sql." , calendar_updated_at='".$calendar_updated_at."', bathrooms='".$bathrooms."', floor_area_m2='".$floor_area_m2."' ";
				$sql=$sql." , google_maps_link='".$maps_link."', og_title='".$title."', instant='".$instant."' ";
				$sql=$sql." where id_sah='".$ID."' ";
				echo "<br/>".$sql;
				ExecutaSQLAnalysis($sql);
				
				/*
				$sqlfinal=$sqlfinal." ".$sql;
				
				//if ($numi==200) break;
				
				if ($numi==400)
				{
					$numi=0;
					//echo "<br/>".$sqlfinal;
					ExecutaSQLAnalysis($sqlfinal);
					$sqlfinal="";
					echo "<hr>Correu Sql";
				}
				
				*/
				
				
			}
			
			

		}
		
		/*
		//echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);
		echo "<hr>Correu Sql";
		*/

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

$num=0;
$id=0;

try
	{
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);
	$sql = "";
	$sql = $sql." SELECT id_sah, link_url FROM competitors_spotahome where IFNULL(link_url,'')<>'' AND IFNULL(room_id,'')=''";
	$sql = $sql." order by id_sah";
	$sql = $sql." Limit 20;";

	echo "<br>Sql:".$sql;

	$dataInicio=date("Y-m-d H:i:s");
	echo "<hr>Hora: ".$hora." | ID: ".$id;
	$results = $conn->query($sql);

	foreach($results as $row)
	{
		$num=$num+1;
		$id=$row['id_sah'];
		$URL=$row['link_url'];
		GetSpotAHome($id, $URL);
	}
	$results = null;
	$conn = null;
}
catch(Exception $e) {
//echo '<br>Erro: ' .$e->getMessage();
}

if ($num>0)
{
	?>
	<script>
	function redirect()
	{
		location.href='SpotAHome_Update.php';
	}
	setTimeout(redirect, 30*1000);
	</script>
	<hr>Activado o Redirect 30 seg
	<?php
}
else
{
	?>
	<script>
	function redirect()
	{
		location.href='SpotAHome_Update.php';
	}
	setTimeout(redirect, 60*60*1000);
	</script>
	<hr>Activado o Redirect 1 hora
	<?php
}



$dataFim=date("Y-m-d H:i:s");


echo "<hr><br>";
echo "<br>Data de Início: ".$dataInicio;
echo "<br>Data de Fim: ".$dataFim;
echo "<hr><br>";



?>

</body>
</html>