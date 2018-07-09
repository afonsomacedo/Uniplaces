<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html>
<body>
<?php

set_time_limit(0);
ini_set('max_execution_time', 3600);


include('simple_html_dom.php');
include('funcoes.php');
include('funcoes_analysis.php');


	$inicio = date("y/m/d h:i:s A"); 

	$result="";
	$result=$result."<br/>Inicio: ".$inicio;

	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);

	$FiltroSite="";
	if(isset($_GET['Site']))
	{
		$FiltroSite=$_GET['Site'];
		$FiltroSite=str_replace("_"," ",$FiltroSite);
		echo "Site: ".$FiltroSite;
	}
	
	$i=0;
	$faltam=0;
	$sql = "select *  ";
	$sql = $sql." from competitors_trustpilot_list  \r\n ";
	$sql = $sql." where active=1 AND date(IFNULL(last_active_at,'2017-01-01')) < date(current_date()) \r\n ";

	if($FiltroSite!=""){
		$sql = $sql." AND site_name='".$FiltroSite."' \r\n";
	}
	
	$sql = $sql." LIMIT 1";

	echo "<br>Sql: ".htmlentities($sql);
	
	/*
	id	Site	City	URL																LastDate	DataIni		Faltam
	1	Bakeca	Roma	http://www.bakeca.it/annunci/offro-camera/luogo/lazio/page/1/	NULL		2015-08-29	1190
	*/
	
	
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$id=$row['trustpilot_id'];
		$site=$row['competitor_name'];
		$URL=$row['base_url'];
		$max_page_num=$row['max_page_num'];
		
		//$max_page_num=2;
		
		//echo "<br>";
		echo "<br>ID: ".$id." | Site: ".$site." | URL: ".$URL;
		flush();
		
		$sqlanchor="";
		$sqlanchor=$sqlanchor." Update competitors_trustpilot_list Set last_active_at=current_date() Where trustpilot_id='".$id."'";
		ExecutaSQLAnalysis($sqlanchor);

		
		try {
			echo "<br>Entrou: $site";
			GetTrustPilot($id, $site, $URL, $max_page_num);
		   
			$sqlanchor="";
			$sqlanchor=$sqlanchor." Update competitors_trustpilot_list Set last_active_at=current_date() Where trustpilot_id='".$id."'";
			ExecutaSQLAnalysis($sqlanchor);
		}
		catch(Exception $eUpdate) {
			echo '<br>Erro: ' .$eUpdate->getMessage();
			$sqlanchor="";
			$sqlanchor=$sqlanchor." Update competitors_trustpilot_list Set last_active_at=current_date() Where trustpilot_id='".$id."'";
			ExecutaSQLAnalysis($sqlanchor);

		}

	}
	//sqlsrv_free_stmt( $results );

	$results = null;
	$conn = null;


?>

<script>
function redirect()
{
	location.href='UpdateTrustPilot.php';
}
setTimeout(redirect, 30*1000);
</script>
</body>
</html>