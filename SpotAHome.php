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





function GetSpotAHome($URL, $zona, $offer_type) {
	try
	{
		$site="SpotAHome";
		echo "<br>Entrou: $site";
		//$html = new simple_html_dom();
		//$html->load_file($URL);
		//$html=DaHtmlCtxHttpsSAH($URL);
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$html = curl_exec($ch);
		curl_close($ch);
		//$html = file_get_contents($URL);
		$posi=strpos($html,"<script>window['__CONTEXT__']");
		//echo "<br>Posi:".$posi;
		if ($posi !== false)
		{
			$posf=strpos($html,"</script>",$posi);
			$result=substr($html,$posi,$posf-$posi);
			$result=str_replace("<script>window['__CONTEXT__']","",$result);
			$result=str_replace("</script>","",$result);
		}
		
		$html = $result;
		
		$posi=strpos($html,',"initialSmallMarkers":[');
		if ($posi !== false)
		{
			$posf=strpos($html,',"experiment":"',$posi);
			$result=substr($html,$posi,$posf-$posi);
			$result=str_replace(',"initialSmallMarkers":[',"",$result);
			$result=str_replace(',"experiment":""}',"",$result);
		}
		
		
		
		
		$result=",".$result;
		
		
		//echo "<hr>";
		$resultados=explode(',{"id":',$result);
		
		print_r("<pre>");
		print_r($resultados);
		print_r("</pre>");
		
		
		echo "<hr>NumEntradas: ".count($resultados);
		
		$numi=0;
		$lista_ids=" ";
		$sqlfinal="";
		foreach($resultados as $resultado)
		{
			$link="";
			$id="";
			if ($resultado!="")
			{
				echo "<br>resultado:" .$resultado;
				$numi=$numi+1;
				$posid=strpos($resultado,',');
				if ($posid !== false)
				{
					$id=substr($resultado,0,$posid);
					$id=str_replace(',',"",$id);
					
					//echo "<br>ID:" .$id;
					//echo "<br>lista_ids:" .$lista_ids;
					
					$poslistaid=strpos($lista_ids,$id);
					if ($poslistaid === false)
					{
						$link=$URL."/".$id;
						$lista_ids=$lista_ids.",".$id;
					}
				}
				
				//echo "<br>-- NumID: ".$numi." | ID:".$id;
				
			}
			//echo "<hr>".$link;
			if (($link!=""))
			{
				
					$sql="INSERT INTO competitors_spotahome ";
					$sql=$sql." (date, link_url, city, offer_type) ";
					$sql=$sql." VALUES  ";
					$sql=$sql." (NOW(),'".$link."','".$zona."','".$offer_type."'); ";
					//echo "<br/>".$sql;
					//ExecutaSQL($sql);
					
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
				

			}
			
			
		}
		
		//echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);
		echo "<hr>Correu Sql";

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

$id=0;
$cConfig = new config();
$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);
$sql = "";
$sql = $sql." SELECT *";
$sql = $sql." FROM competitors_spotahome_list";
$sql = $sql." where active=1 ";
$sql = $sql." AND date(IFNULL(last_active_at,'2017-01-01')) < date(current_date())";
//$sql = $sql." AND weekday(current_date())=6"; //So correr ao domingo
$sql = $sql." Limit 1;";


echo "<br>Sql:".$sql;
$results = $conn->query($sql);
foreach($results as $row)
{
	$id=$row['site_id'];
	$site=$row['site_name'];
	$URL=$row['base_url'];
	$zona=$row['city_code'];
	$offer_type=$row['offer_type'];
}

$results = null;
$conn = null;


$dataInicio=date("Y-m-d H:i:s");

echo "<hr>Hora: ".$hora." | ID: ".$id;

//if (($id<=65) && ($id>0)){
if ($id!=0){
	$UrlFinal=$URL;
	echo "<br/>UrlFinal: ".$UrlFinal;
	echo "<br/>zona: ".$zona;
	echo "<br/>offer_type: ".$offer_type;
	$numregistos=0;
	try {
		$sqlanchor="Update competitors_spotahome_list Set last_active_at=current_date() Where site_id='".$id."'";
		ExecutaSQLAnalysis($sqlanchor);
		GetSpotAHome($UrlFinal,$zona,$offer_type);
		$sqlanchor="Update competitors_spotahome_list Set last_active_at=current_date() Where site_id='".$id."'";
		ExecutaSQLAnalysis($sqlanchor);
	}
	catch(Exception $e) {
		echo '<br>Erro: ' .$e->getMessage();
	}
	flush();

	
	?>
	<script>
	function redirect()
	{
		location.href='SpotAHome.php';
	}
	setTimeout(redirect, 60*1000);
	</script>
	<hr>Activado o Redirect 1 min
	<?php

}
else
{
	$sql="CALL AutomatismoSpotAHome();";
	//ExecutaSQLAnalysis($sql);
	?>
	<script>
	function redirect()
	{
		location.href='SpotAHome.php';
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