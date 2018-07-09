<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html>
<style>
body{font-size: 10px;}
</style>
<body>
<?php

set_time_limit(0);
ini_set('max_execution_time', 3600);


include('simple_html_dom.php');
include('funcoes.php');
include('funcoes_scrapping.php');
include('funcoes_OCR.php');





	$inicio = date("y/m/d h:i:s A"); 

	$result="";
	$result=$result."<br/>Inicio: ".$inicio;

	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$FiltroSite="";
	if(isset($_GET['Site']))
	{
		$FiltroSite=$_GET['Site'];
		$FiltroSite=str_replace("_"," ",$FiltroSite);
		echo "Site: ".$FiltroSite;
	}
	
	$i=0;
	$faltam=0;
	/*
	$sql = "select * ,CONVERT(CURRENT_DATE,CHAR(10)) as DataIni \r\n ";
	$sql = $sql." , (select COUNT(*) from scraping_site_list where 1=1  \r\n ";
	if($FiltroSite!=""){
		$sql = $sql." AND site_name='".$FiltroSite."' \r\n";
	}
	$sql = $sql." AND Active='1' AND IFNULL(num_errors,0) < '2' AND date(IFNULL(last_active_at,'1900-01-01')) < date(CURRENT_DATE)) as Faltam \r\n ";
	$sql = $sql." from scraping_site_list  \r\n ";
	$sql = $sql." where Active='1' AND IFNULL(num_errors,0) < '2' AND date(IFNULL(last_active_at,'1900-01-01')) < date(CURRENT_DATE) \r\n ";
	$sql = $sql." AND (   \r\n ";
	$sql = $sql." (site_name like '%idealista%' AND htmlcode<>'' and date(IFNULL(html_lastdate,'1900-01-01')) = date(current_date()))  \r\n";
	//$sql = $sql." (site_name like '%idealista%' AND htmlcode<>'' and (date(IFNULL(html_lastdate,'1900-01-01')) = date(current_date())) OR (TIMESTAMPDIFF(HOUR, IFNULL(html_lastdate,'1900-01-01'), NOW())<4))   \r\n";
	$sql = $sql." OR  \r\n";
	$sql = $sql." (site_name not like '%idealista%' AND 1=1) \r\n";
	$sql = $sql." )  \r\n ";
	if($FiltroSite!=""){
		$sql = $sql." AND site_name='".$FiltroSite."' \r\n";
	}
	*/
	
	$sql = "select * from v_sitetoupdate ";
	$sql = $sql." where 1=1";
	if($FiltroSite!=""){
		$sql = $sql." AND site_name='".$FiltroSite."' \r\n";
	}
	$sql = $sql." LIMIT 1";

	//echo "<br>Sql: ".htmlentities($sql);
	
	/*
	id	Site	City	URL																LastDate	DataIni		Faltam
	1	Bakeca	Roma	http://www.bakeca.it/annunci/offro-camera/luogo/lazio/page/1/	NULL		2015-08-29	1190
	*/
	
	
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$id=$row['site_id'];
		$site=$row['site_name'];
		$URL=$row['base_url'];
		$zona=$row['city_code'];
		//$faltam=$row['Faltam'];
		$DataIni=$row['DataIni'];
		$country=$row['country'];
		$offer_type=$row['rent_type'];
		$htmlcode=$row['htmlcode'];
		
		//echo "<br>";
		//echo "<br>Faltam: ".$faltam;
		echo "<br>ID: ".$id." | Site: ".$site." | Zona: ".$zona." | URL: ".$URL." | DataIni: ".$DataIni;
		
		$sqlanchor="Update scraping_site_list Set num_errors=IFNULL(num_errors,0)+1, last_errors_date=NOW(), last_active_at=NOW() Where site_id='".$id."'";
		//echo "<br>sqlanchor: $sqlanchor";
		ExecutaSQL($sqlanchor);
		
		$sqlanchor=" Update 01_sessions Set result='0' Where site_id='".$id."' and import_type='scraping' and DAY(IFNULL(session_datetime,'1900-01-01')) = DAY(NOW()) and HOUR(IFNULL(session_datetime,'1900-01-01')) = HOUR(NOW()) ";
		ExecutaSQL($sqlanchor);
		
		$sqlanchor="Insert Into log_sites (site_id, date, msg, site_name, url) Values ('".$id."', NOW(), 'Correu', '".$site."', '".$URL."')";
		ExecutaSQL($sqlanchor);
		
		try {
			echo "<br>Entrou: $site";
			switch (strtoupper($site)) {
				case "BAKECA":
					GetBakeca($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "BQUARTO":
					GetBQuarto($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "CRAIGSLIST":
					GetCraigslist($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "IDEALISTA ES":
					//GetIdealistaES($offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					GetIdealistaHTML($site, $offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					break;
				case "IDEALISTA IT":
					//GetIdealistaIT($offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					GetIdealistaHTML($site, $offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					break;
				case "IDEALISTA PT":
					//GetIdealistaPT($offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					GetIdealistaHTML($site, $offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					break;
				case "IMMOBILIARE":
					GetImmobiliare($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "NULLPROVISION":
					GetNullProvision($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "OLX":
					GetOLX($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "PAP":
					GetPAP($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "PISOCOMPARTIDO":
					GetPisoCompartido($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "SUBITO":
					GetSubito($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				
				case "EASYPISO":
					GetEasyPiso($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "EASYQUARTO":
					GetEasyQuarto($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "EASYROOMMATE":
					GetEasyRoomMate($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "EASYSTANZA":
					GetEasyStanza($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "EASYWG":
					GetEasyWg($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "EASYAPPARTAGER":
					GetEasyAppartager($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
					
				case "KIJIJI":
					GetKijiji($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
					
				case "PORTAPORTESE":
					$idPorta="";
					$idPorta=daIDPortaPortese();
					if ($idPorta!="")
					{
						$URL=str_replace("[ID]",$idPorta,$URL);
						GetPortaPortese($offer_type, $country, $URL, $zona, $DataIni, $id);
					}
					break;
				
				case "PHOSPHORO":
					GetPhosphoro($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "CASAIT":
					GetCasaIT($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "OCASIAO":
					GetOcasiao($offer_type, $country, $URL, $zona, $DataIni, $id);
					/*TrataNumerosOcasiao();
					TrataNumerosOcasiao();
					TrataNumerosOcasiao();*/
					break;
				case "EMES":
					GetEMES($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "CUSTOJUSTO":
					GetCustoJusto($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "MILANUNCIOS":
					//GetMilAnuncios($offer_type, $country, $URL, $zona, $DataIni, $id);
					GetMilAnunciosHTML($offer_type, $country, $URL, $zona, $DataIni, $id, $htmlcode);
					break;
				case "IMMOBILIENSCOUT24":
					GetImmobilienscout24($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "STUDENTEN":
					GetStudenten($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
					
				case "PISOS.COM":
					GetPisosCom($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "VIBBO":
					GetVibbo($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "SECONDAMANO":
					GetSecondamano($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "CASASCM":
					GetCasascm($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
					
				case "CASASAPO":
					GetCasasSapo($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "AFFITTO":
					GetAffitto($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "VIVASTREET":
					GetVivaStreet($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				case "GABINOHOME":
					GetGabinoHome($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
					
				case "WOHNUNGSBOERSE":
					GetWohnungsboerse($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;
				
				case "KALAYDO":
					GetKalaydo($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;

				case "QUOKA":
					GetQuoka($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;

				case "FOTOCASA":
					GetFotoCasa($offer_type, $country, $URL, $zona, $DataIni, $id);
					break;	
					
					
					
					
				

			} 
		   
			$sqlanchor="";
			$sqlanchor=$sqlanchor." Update scraping_site_list Set num_errors=0, last_active_at=NOW(), last_errors_date=NOW()  Where site_id='".$id."'";
			ExecutaSQL($sqlanchor);
		}
		catch(Exception $eUpdate) {
			echo '<br>Erro: ' .$eUpdate->getMessage();
			$sqlanchor="";
			$sqlanchor=$sqlanchor." Update scraping_site_list Set last_active_at=NOW(), last_errors_date=NOW()  Where site_id='".$id."'";
			ExecutaSQL($sqlanchor);
			
			$sqlanchor=" Update 01_sessions Set result='0' Where site_id='".$id."' and import_type='scraping' and DAY(IFNULL(session_datetime,'1900-01-01')) = DAY(NOW()) and HOUR(IFNULL(session_datetime,'1900-01-01')) = HOUR(NOW()) ";
			ExecutaSQL($sqlanchor);
		}

	}
	//sqlsrv_free_stmt( $results );

	$results = null;
	$conn = null;


?>
</body>
</html>