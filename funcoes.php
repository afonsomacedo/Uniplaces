<?php


define('CRAWLER_COOKIE_FILENAME', 'cookie.txt');

Class config {
        var $db;
        var $login;
        var $senha;
        var $odbc;
        var $driver;
        var $servidor;

		function __construct () {
                 
                 $this->db="scraping_xxxx";
                 $this->login="xxxx";
                 $this->senha="xxxx";
                 $this->servidor="xxxx";
        }

}


function tratatextoBD($texto){
	$texto=trim($texto);
	//
	$texto=htmlentities($texto,ENT_COMPAT,'UTF-8');
	//$texto=htmlspecialchars($texto,ENT_COMPAT,'UTF-8');
	return $texto;
}

function log_sf_calls($tipo, $unique_phone_id, $records){
	
	$call = print_r($records, true);
	
	$sqlanchor=" INSERT INTO log_sf_calls (unique_phone_id, call_type, call_made, create_date)";
	$sqlanchor=$sqlanchor." VALUES ('".$unique_phone_id."', '".$tipo."', '".$call."', NOW())";
	echo "<hr>".$sqlanchor;
	ExecutaSQL($sqlanchor);
}


function ExecutaSQLAnalysis($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);
	
	$sql = $script;
	//echo "<br>".$sql;
	$results = $conn->query($sql);
	
	$results = null;
	$conn = null;

}

function ExecutaSQL($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	
	$sql = $script;
	//echo "<br>".$sql;
	$results = $conn->query($sql);
	
	$results = null;
	$conn = null;

}

function ExecutaSQLTimeOut($script){
	$cConfig = new config();
	$conn = new PDO(
			"mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db
			, $cConfig->login
			, $cConfig->senha
			,array(
				PDO::ATTR_TIMEOUT => 300,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			));
	
	$sql = $script;
	//echo "<br>".$sql;
	
	
	
	try {
        $results = $conn->query($sql);
    } catch(Exception $e) {
        echo "<hr>";
        echo $e->getMessage();
    }
	
	$results = null;
	$conn = null;

}

function ExecuteNewSQL($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	//$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//$conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
	$sql = $script;
	//echo "<br>".$sql;
	
	$sql = htmlentities($sql, null, 'iso-8859-1');
	$conn->query($sql);
	/*
	try
	{
		if ($conn->query($sql) === TRUE) {
			echo "Record updated successfully";
		} else {
			echo "Error updating record: ";
			print_r("<pre>");
			print_r($conn);
			print_r("</pre>");
		}
	}
	catch(PDOException $e)
	{
		echo $e->getMessage();
	}
	*/
	
	$conn = null;
	
}

function daValorAtributo($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	$result="";
	$sql = $script;
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$result=$row['Result'];
	}
	
	$results = null;
	$conn = null;
	return $result;

}

function limpa($texto){
	$texto=trim($texto);
	$texto=str_replace("  "," ",$texto);
	$texto=addslashes($texto);
	return $texto;
}

function tratamoeda($texto){
	$texto=trim($texto);
	return $texto;
}

function tratatexto($texto){
	$texto=trim($texto);
	//
	$texto = preg_replace("[^A-Za-z0-9]", "", $texto);
	$texto=htmlentities($texto,ENT_COMPAT,'ISO-8859-1',true);
	$texto=RemoveAccents($texto);
	$texto=htmlspecialchars($texto,ENT_COMPAT,'ISO-8859-1');
	return $texto;
}

function RemoveAccents($string) {
    // From http://theserverpages.com/php/manual/en/function.str-replace.php
    //$string = htmlentities($string);
    return preg_replace("/&([a-z])[a-z]+;/i", "$1", $string);
}

function trataplicas($texto){
	$texto=trim($texto);
	$texto=str_replace("'",'"',$texto);
	return $texto;
}

function trataplicasBD($texto){
	$texto=trim($texto);
	$texto=str_replace("'",'',$texto);
	return $texto;
}


function apagaplicas($texto){
	$texto=trim($texto);
	$texto=str_replace("'","",$texto);
	return $texto;
}

function setUserAgent(){
	//list of browsers
	$agentBrowser = array(
			'Firefox',
			'Safari',
			'Opera',
			'Flock',
			'Internet Explorer',
			'Seamonkey',
			'Konqueror',
			'GoogleBot'
	);
	//list of operating systems
	$agentOS = array(
			'Windows 3.1',
			'Windows 95',
			'Windows 98',
			'Windows 2000',
			'Windows NT',
			'Windows XP',
			'Windows Vista',
			'Redhat Linux',
			'Ubuntu',
			'Fedora',
			'AmigaOS',
			'OS 10.5'
	);
	//randomly generate UserAgent
	return $agentBrowser[rand(0,7)].'/'.rand(1,8).'.'.rand(0,9).' (' .$agentOS[rand(0,11)].' '.rand(1,7).'.'.rand(0,9).'; en-US;)';
}

function curl_exec_new($url) {

	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8118");
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	//curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
	
	
	$response = curl_exec($ch);

    return $response;
}


function dactx($URL){
			
	$cookie="cookie.txt"; 

	$postdata = "a_login_email=afonso_macedo@sapo.pt&password=YYnJJY88";
			
  	$username="afonso_macedo"; 
	$password="llFFFF11"; 
	$urllogin="http://www.bquarto.pt/bb_login/logi_entrada_jq.php4"; 
	$cookie="cookie.txt"; 

	//$postdata = "a_login_email=".$username."&a_login_pass=".$password."&a_login_entrar=Entrar"; 
	//$postdata="ponteiro=login&cok=YJYJYpYNosoptpupNvoEovop7pNxNB1437158827&ling=pt&login0=afonso_macedo@sapo.pt=YYnJJY88=YJYJYpYNosoptpupNvoEovop7pNxNB1437158827&remk=1&codigo=926046521";
	$postdata="ponteiro=login&cok=YJYJYpYNosoptpupNvoEovop7pNxNB1437158827&ling=pt&login0=afonso_macedo%40sapo.pt%3DllFFFF11%3DYJYJYpYNosoptpupNvoEovop7pNxNB1437158827&remk=1&codigo=926046521";

	$urlloginautomatico="http://www.bquarto.pt/?kod1=752293088&kod2=UUVVVVnnn&kod3=";
	$urlloginautomatico="http://www.bquarto.pt/?kod=MTQzNzI1NDA3My00MzE3MTkwOTctNzUyMjkzMDg4LXB0ZW50cmF0";

	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL,$urlloginautomatico); 
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie); 
	curl_setopt ($ch, CURLOPT_REFERER, $urllogin); 

	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata); 
	curl_setopt ($ch, CURLOPT_POST, 1); 
	$resultlogin = curl_exec ($ch); 
	

	curl_setopt($ch, CURLOPT_URL, $urlloginautomatico);
	//curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8118");
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie); 
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata); 
	curl_setopt ($ch, CURLOPT_POST, 1); 
	
	$response = curl_exec($ch);
	
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8118");
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie); 
	
	$response = curl_exec($ch);
	
	return $response;
}

function DaHtmlCtxBQUarto($URL){
	$html = new simple_html_dom();
	$content = dactx($URL); 
	$html->load($content,true,false);
	
	return $html;
}

function DaHtmlCtx($URL){
	$html = new simple_html_dom();
	$content = curl_exec_new($URL); 
	$html->load($content,true,false);
	
	return $html;
}

function DaHtml($URL,$string){
	try {
		$html = new simple_html_dom();
		$html->load_file($URL);
	}
	catch(Exception $e) {
		  $html="";
	}
	
	return $html;
}


function DaHtmlCtxIdealista($URL){


	$listurl1 = array (
				'https://webcache.googleusercontent.com/search?q=cache:BHJYk2kwjsAJ:',
				'https://webcache.googleusercontent.com/search?q=cache:zqnXVQid4FgJ:',
				'https://webcache.googleusercontent.com/search?q=cache:fONJDnvYPOoJ:'
				);
	$listurl2 = array (
				'+&cd=2&hl=pt-PT&ct=clnk&gl=pt',
				'+&cd=2&hl=pt-BR&ct=clnk&gl=pt',
				'+&cd=2&hl=en-US&ct=clnk&gl=en'
				);
	$proxies = array (
				'173.234.92.107',
				'173.234.93.94',
				'173.234.94.90:54253',
				'69.147.240.61:54253',
				'69.7.113.4'
				);
	$proxy = $proxies[array_rand($proxies)];    // Select a random proxy from the array and assign to $proxy variable

	$url1=$listurl1[array_rand($listurl1)];
	$url2=$listurl2[array_rand($listurl2)];
	//$URL=$url1.$URL.$url2;
	echo "<br>URL1: $url1";
	echo "<br>URL2: $url2";
	echo "<br>Proxy: $proxy";
	echo "<br>URLnovo: $URL";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, ‘HTTP’);
	//curl_setopt($ch, CURLOPT_PROXY, $proxy);    // Set CURLOPT_PROXY with proxy in $proxy variable
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}


function DaHtmlCtxCompetitorHttps($URL){


	$proxies = array (
				'173.234.92.107',
				'173.234.93.94',
				'173.234.94.90:54253',
				'69.147.240.61:54253',
				'69.7.113.4'
				);
	$proxy = $proxies[array_rand($proxies)];    // Select a random proxy from the array and assign to $proxy variable

	$URL=$URL;
	echo "<br>URLnovo: $URL";
	echo "<br>Proxy: $proxy";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, ‘HTTP’);
	curl_setopt($ch, CURLOPT_PROXY, $proxy);    // Set CURLOPT_PROXY with proxy in $proxy variable
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}


function GOOGLE_GET($url){
		$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt($curl, CURLOPT_FAILONERROR, true); 
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50 );
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 		
        $content = curl_exec( $curl );
	    curl_close( $curl);
		$html = new simple_html_dom();
		$html->load($content,true,false);
		//$html = str_get_html($content);
		
		return $html;

}

function DaHtmlCtxHttpsBot($URL, $agent){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_TIMEOUT, 200);
	curl_setopt( $ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}


function DaHtmlCtxHttps($URL){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt( $ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}

function DaHtmlCtxSimples($URL){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt( $ch, CURLOPT_ENCODING, '');
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}

function DaHtmlCtxTempo($URL){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, setUserAgent());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt( $ch, CURLOPT_ENCODING, '');
	$content = curl_exec($ch);
	curl_close($ch);
	
	$html = new simple_html_dom();
	$html->load($content,true,false);
	//$html = str_get_html($content);
	
	return $html;
}

function dactxEasyPiso($Site, $URL, $UrlDeLogin){
	$cookie="cookie.txt"; 

	$postdata = "DestinationUrl=&Email=afonso.macedo@gmail.com&Password=a1a2a3";
			
	$username="afonso.macedo@gmail.com"; 
	$password="a1a2a3"; 
	$urllogin=$UrlDeLogin;
	$urlpostlogin=$URL;

	$UserAgent=setUserAgent();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_NOBODY, false);
	curl_setopt($ch, CURLOPT_URL, $urllogin);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	//set the cookie the site has for certain features, this is optional
	curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
	curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_exec($ch);

	//page with the content I want to grab
	curl_setopt($ch, CURLOPT_URL, $urlpostlogin);
	//do stuff with the info with DomDocument() etc
	$html = curl_exec($ch);
	curl_close($ch);
	
	
	$output = new simple_html_dom();   
	$output = str_get_html($html);

	return $output;
}

function daIDPortaPortese(){
	$URL='http://www.portaportese.it/rubriche/Immobiliare/Affitto_-_Subaffitto/m-pX000001500?tipologia=f04&zoomstart=10&latstart=41.8966&lngstart=12.494';
	
	$html = new simple_html_dom();
	$html->load_file($URL);

	$divul=$html->find('select[name=numero]',0);
	$divresult=$divul->find('option',1);
	if (sizeof($divresult)>0)
		$id=$divresult->attr['value'];;

	$html->clear(); 
	unset($html);
	
	/*
	$cConfig = new config();
	$conn = new PDO("sqlsrv:Server=".$cConfig->servidor.";Database=".$cConfig->db, $cConfig->login, $cConfig->senha);
	
	$serverName = "(local)";
	$connectionInfo = array("UID" => "sa", "PWD" => "sa", "Database"=>"Uniplaces","CharacterSet" =>"UTF-8");
	$conn = sqlsrv_connect( $serverName, $connectionInfo);
	
	$sql = "";
	$sql = $sql." select top 1 id ";
	$sql = $sql." from scrapping";
	$sql = $sql." where Site='PortaPortese' and UrlBase like '%usC$id%'";
	
	$results = sqlsrv_query($conn, $sql);
	if(sqlsrv_has_rows($results))
	{
		$id="";
	}

	sqlsrv_free_stmt( $results );
	*/
	
	
	return $id;
}

function tirabarras($texto){
	$texto=trim($texto);
	$texto=str_replace("\\","",$texto);
	return $texto;
}

function limpaHTML($texto){
	$texto=str_replace(chr(13),'',$texto);
	$texto=str_replace(chr(10),'',$texto);
	$texto=strip_tags($texto);
	$texto=preg_replace("/&#?[a-z0-9]{2,8};/i","",$texto);
	$texto=htmlspecialchars($texto);
	$texto=trim($texto);
	return $texto;
}

function CorrigeLink($link){
	$link=limpaHTML($link);
	$link=str_replace("&amp;","&",$link);
	$link=str_replace("&","&amp;",$link);
	return $link;
}


function TrataDataSF($Data){
	$Data=str_replace("T"," ",$Data);
	$Data=str_replace("Z","",$Data);
	if ($Data=="")
		$Data='NULL';
	else
		$Data="'".$Data."'";
	return $Data;
}

?>