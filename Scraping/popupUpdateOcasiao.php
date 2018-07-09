<?php

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/


set_time_limit(0);
ini_set('max_execution_time', 3600);

include('simple_html_dom.php');
include('funcoes.php');
include('funcoes_scrapping.php');
include('funcoes_OCR.php');

try {
	TrataNumerosOcasiao();
}
catch(Exception $eOcasiao) {
	echo '<br>Erro: ' .$eOcasiao->getMessage();
}
echo "<br>TrataNumerosOcasiao";

// COMENTADO devido ao erro da imagem
/*
sleep(1);
try {
	TrataNumerosImmobiliare();
}
catch(Exception $eImmobiliare) {
	echo '<br>Erro: ' .$eImmobiliare->getMessage();
}
echo "<br>TrataNumerosImmobiliare";
*/

sleep(1);
try {
	TrataNumerosCustoJusto();
}
catch(Exception $eCustoJusto) {
	echo '<br>Erro: ' .$eCustoJusto->getMessage();
}
echo "<br>TrataNumerosCustoJusto";

//TrataNumerosStudenten();

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
<body onload="JavaScript: AutoRefresh(60*1000);" style="background-color:#d3d3d3;">
<div width="100%" height="100%">
	<p align="center">
	<b>Updating Scrapping Ocasiao/Immobiliare/CustoJusto</b><br/>

	
</div>
</body>
</html>