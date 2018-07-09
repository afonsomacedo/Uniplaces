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
<?php

include('funcoes.php');


function TrataContactoMilAnuncios($url){
	$timeout=100;
	$result="";
	$cookie="cookie.txt"; 
	
	
	/*
	echo "<br>GetData<hr>";
	echo get_data($url);
	echo "<hr>";

	echo "<br>GetSimpleDom<hr>";
	echo getSimpleDOM($url);
	echo "<hr>";
	*/
	
	$ch = curl_init($url); // initialize curl with given url
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // max. seconds to execute
	curl_setopt($ch, CURLOPT_FAILONERROR, 1); // stop when it encounters an error
	
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); 
	//curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata); 
	//curl_setopt ($ch, CURLOPT_POST, 1); 
	
	$html=@curl_exec($ch);
	//echo "<hr>".$html."<hr>"
	$posi=strpos($html,"eval(unescape");
	//echo "<br>Posi:".$posi;
	if ($posi !== false)
	{
		$posf=strpos($html,"</script>",$posi);
		$result=substr($html,$posi,$posf-$posi);
		$result=str_replace("eval(unescape(",'',$result);
		$result=str_replace("document.write(",'',$result);
		$result=str_replace(")",'',$result);
		$result=str_replace("'",'',$result);
		$result=str_replace('"','',$result);
		$result=str_replace('%u',';&#x',$result);
		$result = preg_replace("/\\\\u([0-9a-f]{3,4})/i", "&#x\\1;", $result);
		$result = html_entity_decode($result, null, 'UTF-8');
		$result=urldecode($result);

		$result=str_replace('<br>','',$result);
		$result=str_replace('<div class="telefonos">','',$result);
		$result=str_replace('<div class=&#x0022;telefonos&#x0022;>','',$result);
		$result=str_replace('&#9742;','',$result);
		$result=str_replace('&nbsp;','',$result);
		$result=str_replace('</div>','',$result);
		$result=str_replace('</div&#x003e','',$result);
		$result=str_replace(';','',$result);
	}
	return $result;
}




	$cConfig = new config();

	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	$sql = "";
	$sql = "SELECT Top 10 id, phone_01_img ";
	$sql=$sql. " FROM 02_imports ";
	$sql=$sql. " where site_name='milanuncios' ";
	$sql=$sql. " and IFNULL(phone_01_img,'')<>'' and phone_01='' ";
	
	$results = $conn->query($sql);
	foreach($results as $row)
	{

		$Link=$row['phone_01_img'];
		$id=$row['id'];
		$contacto=TrataContactoMilAnuncios($Link);
		echo "<br>Link: ".$Link." | Contacto: ".$contacto;
		
		$sqlUpdate="Update 02_imports Set phone_01_img='', phone_01='".$contacto."' Where site_id='".$id."'";
		ExecutaSQL($sqlUpdate);
	}

?>
</body>
</html>