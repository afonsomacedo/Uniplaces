<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

$hora=date("H");
//$hora=4;
?>


<html>
<head>
	<script type="text/JavaScript">
	function AutoRefresh(interval) {
		setTimeout("location.reload(true);",interval);
	}
	</script>
	<title>Uniplaces</title>
	<style type="text/css">
		.janela
		 {
			float:left;
			margin:2px;
		 }
	</style>
</head>
<body onload="JavaScript: AutoRefresh(30*1000);" style="background-color:#d3d3d3;">
<div width="100%" height="100%">
	<p align="center">
	<b>Updating Scrapping Uniplaces</b><br/>
	
	<?php
	
	include('funcoes.php');

	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	
	$sql = "select Distinct site_name from scraping_site_list where active='1' and IFNULL(num_errors,0) < 2";
	//$sql = $sql . " and site_name<>'pisocompartido'";
	$sql = $sql . "  order by site_name";
	
	$results = $conn->query($sql);
	foreach($results as $row)
	{

		?>
		<div class="janela">
			<iframe src="Update.php?Site=<?php echo $row['site_name'];?>" frameborder="1" width="200" height="100"></iframe> 
		</div>
		<?php
		
	}
	
	$results = null;
	$conn = null;
	//sqlsrv_free_stmt( $results );
	

	?>
	<div class="janela">
		<iframe src="popupUpdateOcasiao.php" frameborder="1" width="390" height="100"></iframe> 
	</div>
	<div class="janela">
		<iframe src="updateMilAnuncios.php" frameborder="1" width="390" height="100"></iframe> 
	</div>
	
	<!--
	<div class="janela">
		<iframe src="http://operations.uniplaces.com.ukwsp.com/icaltool/UpdateICal.php" frameborder="1" width="390" height="100"></iframe> 
	</div>
	-->
	
	
	<?php if (($hora>6) && ($hora<15)) : ?>
	<!--<div class="janela">
		<iframe src="automatismos.php?Passo=2" frameborder="1" width="390" height="100"></iframe> 
		<iframe src="automatismos.php?Passo=3" frameborder="1" width="390" height="100"></iframe> 
		<iframe src="automatismos.php?Passo=4" frameborder="1" width="390" height="100"></iframe> 
		<iframe src="automatismos.php?Passo=41" frameborder="1" width="390" height="100"></iframe> 
		<iframe src="automatismos.php?Passo=5" frameborder="1" width="390" height="100"></iframe>
	</div>
	-->
	<?php endif; ?>
	

<?php

	$dataFim=date("Y-m-d H:i:s");
	echo "<div style='clear: both; float:left; display: block;'>";
	echo "<br>";
	echo "<hr>Kill Comands";

	$results = null;
	$conn = null;


	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$sql=" select ID from information_schema.processlist where COMMAND='Sleep' and Time > 10 ";
	echo "<br>".$sql;
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$process_id=$row["ID"];
		$sqlanchor=" KILL ".$process_id;
		echo "<br>".$sqlanchor;
		ExecutaSQL($sqlanchor);
	}

	$results = null;
	$conn = null;
	
	echo "</div>";


?>	
</div>

</body>
</html>