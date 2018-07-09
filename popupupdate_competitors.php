
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$hora=date("H");
echo $hora;

$horaRecolheGoogle=0;

if ($hora<9)
	$Refresh=10;
else
	$Refresh=60;
$Refresh=10;
if ($hora==$horaRecolheGoogle)
	$Refresh=30*60;

?>

<html>
<head>
	<script type="text/JavaScript">
	function AutoRefresh(interval) {
		setTimeout("location.reload(true);",interval);
	}
	AutoRefresh(<?php echo $Refresh;?>*1000);
	</script>
<body style="background-color:#d3d3d3;">
<?php


ini_set('display_errors', 1);

set_time_limit(0);
ini_set('max_execution_time', 3600);

include('simple_html_dom.php');
include('funcoes.php');

function DaHtmlCtxHttpsCompetitor($URL, $timeout=2, $useragent=""){

	$ch = curl_init();
	if ($useragent==""){
		$useragent=setUserAgent();
	}
	
	echo "<br>UserAgent: ".$useragent." | Timeout: ".$timeout;

	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	$content = curl_exec($ch);

	if ($timeout!=2)
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	else
		$httpcode=200;
	$html = new simple_html_dom();
	if($httpcode==200)
	{
		$html->load($content,true,false);
	}

	curl_close($ch);

	return $html;
}


$cConfig = new config();
$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);

$NumRegistos=10;



	$sql ="";
	$sql = $sql . " select pp.*";
	$sql = $sql . " from z_competitors pp";
	$sql = $sql . " left join z_competitors_results ppr";
	$sql = $sql . " ON pp.competitor_list_id=ppr.competitor_list_id AND date(ppr.date)=date(current_date())";
	$sql = $sql . " where IFNULL(pp.final_class_name,'')<>'' AND  ppr.results is null";
	//$sql = $sql . " and pp.competitor='nestpick.com'";
	$sql = $sql . " LIMIT 0, $NumRegistos";

	echo "<hr>SQL: ".$sql;

	$i=0;

	$results = $conn->query($sql);
	foreach($results as $row)
	{
		
		$url=$row["url"];
		//echo "<hr>URL: ".$url;
		$url=str_replace("[YYYY]",date("Y"),$url);
		$url=str_replace("[YYYY+1]",date("Y")+1,$url);
		$url=str_replace("[MM]",date("m"),$url);
		$url=str_replace("[DD]",date("d"),$url);
		$id=$row["competitor_list_id"];
		$class=$row["final_class_name"];
		$competitor=trim($row["competitor"]);
		
		echo "<br>URL: ".$url;
		echo "<br>Class: ".$class;
		echo "<br>Competitor: (".$competitor.")";
		
		$numresult="0";
		$sql="";
		$sql=$sql." INSERT INTO z_competitors_results (competitor_list_id,date,results)";
		$sql=$sql." VALUES ('".$id."', NOW(), '".$numresult."')";
		
		echo "<hr>SQL: ".$sql;
		ExecutaSQLAnalysis($sql);
		
		try {
			
			
			$x = 1;
			$numresult=0;

			do {
				$useragent=setUserAgent();
				if (($competitor=="spotahome.com"))// || ($competitor=="nestpick.com"))
					$pos = strpos($useragent, "GoogleBot");
					if ($pos !== false)
						$useragent=setUserAgent();
				//	$useragent="GoogleBot/1.0 (Windows 2000 6.0; en-US;)";
				
								
				$timeout=10*$x*2;
				
				if (($x % 2) == 0)
					$useragent=setUserAgent();
				
				$html = new simple_html_dom();
				if (($competitor=="nestpick.com") && (($x % 2) == 0)){
					$html=DaHtmlCtxCompetitorHttps($url);
				}
				else {
					$html=DaHtmlCtxHttpsCompetitor($url,$timeout,$useragent);
				}
				
				//echo "<hr>".$html."<hr>";
				
				$divresultado = $html->find($class,0);

				echo "<br>Size(".(sizeof($divresultado)).")";
				if (sizeof($divresultado)>0)
				{
					echo $divresultado->plaintext;
					$numresult=$divresultado->plaintext;
					$x=5;
				}
				$x++;
			} while ($x <= 5);
			
			
		
		}
		catch(Exception $e) {
		  echo '<br>Erro: ' .$e->getMessage();
		}

		
		
		/*
		$sql="";
		$sql=$sql." INSERT INTO pp_competitors_results (competitor_list_id,date,results)";
		$sql=$sql." VALUES ('".$id."', NOW(), '".$numresult."')";
		*/
		
		$sql = "Update z_competitors_results ";
		$sql = $sql . " Set results='".$numresult."', useragent='".$useragent."', timeout='".$timeout."' ";
		$sql = $sql . " where competitor_list_id = '".$id."' and date(date)=date(current_date());";
		
		echo "<hr>SQL: ".$sql;
		ExecutaSQLAnalysis($sql);

		$i=$i+1;

		
	}
	
$results = null;
$conn = null;


$sql=" call AutomatismoCompetitors;";
echo "<hr>SQL: ".$sql;
ExecutaSQLAnalysis($sql);



?>

	<?php if ($hora==$horaRecolheGoogle) :?>
	<div class="janela">
		<iframe src="funcoesgoogle.php" frameborder="1" width="600" height="400"></iframe> 
	</div>
	<?php endif;?>

</body>
</html>