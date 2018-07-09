<?php

function ActualuzaScrappingLista($DataIniScrappingList, $idScrappingList){
	$sqlanchor="";
	$sqlanchor=$sqlanchor." Update scraping_site_list Set num_errors=0, last_active_at='".$DataIniScrappingList."' Where site_id='".$idScrappingList."'";
	ExecutaSQL($sqlanchor);
	
	$sqlanchor="";
	$sqlanchor=$sqlanchor." Update 01_sessions Set result='1' Where site_id='".$idScrappingList."' and import_type='scraping' and CONVERT(IFNULL(session_datetime,'1900-01-01'),CHAR(10)) = CONVERT(CURRENT_DATE,CHAR(10)) ";
	ExecutaSQL($sqlanchor);
}

function DaIdSessao($id, $import_type){
	//if (!isset($_SESSION['SessaoScrapping']) || $_SESSION['SessaoScrapping'] == '') {
		$cConfig = new config();
		$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
		
		$idsession=0;	   
		$sql="";
		$sql=$sql." INSERT INTO 01_sessions (import_type, team_id, staff_id, site_id, session_datetime)";
		$sql=$sql." SELECT * FROM (SELECT '".$import_type."', team_id,staff_id, site_id, NOW() as Data from scraping_site_list where site_id='".$id."') AS tmp";
		$sql=$sql." WHERE NOT EXISTS (";
        $sql=$sql." 	SELECT session_id as id FROM 01_sessions where site_id='".$id."' and import_type='".$import_type."' and date(IFNULL(session_datetime,'1900-01-01')) = date(NOW()) and hour(IFNULL(session_datetime,'1900-01-01')) = hour(NOW())";
		$sql=$sql." ) LIMIT 1;";
		//echo "<hr>".$sql;
		ExecutaSQL($sql);
		
		$sql="";
		$sql=$sql." SELECT session_id as id FROM 01_sessions where site_id='".$id."' and import_type='".$import_type."' and date(IFNULL(session_datetime,'1900-01-01')) = date(NOW()) and hour(IFNULL(session_datetime,'1900-01-01')) = hour(NOW());";
		//echo "<hr>".$sql;
		$results = $conn->query($sql);
		foreach($results as $row)
		{
			$idsession=$row['id'];
		}
		
		$results = null;
		$conn = null;
	//}
	return $idsession;
}

function InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome="", $floor="", $rooms="", $caracteristicas="", $obs="", $photos_available="0", $address_neighborhood="", $source_date="", $is_test="0")
{
	$import_type='scraping';
	$IdSessao=DaIdSessao($idScrappingList, $import_type);
	$is_test="0";
	
	//'".$idScrappingList."',
	$sql="INSERT INTO 02_imports ";
	$sql=$sql." (session_id, import_type, import_team_name, import_staff_id, source_type";
	$sql=$sql." , source_name, title, description, source_date, price, phone_01, phone_01_img";
	$sql=$sql." , link_url, city_code, rent_type, country";
	$sql=$sql." , first_name, address_floor, number_of_rooms, obs ";
	$sql=$sql." , photos_available, address_neighborhood, is_test)";
	$sql=$sql." VALUES ";
	$sql=$sql." ('".$IdSessao."', '".$import_type."', 'Central (Scraping)', '2', 'Automatic Scraping' ";
	$sql=$sql." ,'".$site."','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."', '".$caracteristicas."' ";
	$sql=$sql." ,'".$link."','".$zona."', '".$offer_type."', '".$country."'";
	$sql=$sql." , '".$nome."','".$floor."','".$rooms."','".$obs."' ";
	$sql=$sql." ,'".$photos_available."','".$address_neighborhood."',".$is_test.");";
	echo "<hr>".$sql;
	ExecutaSQL($sql);
}

function TrataNumerosOcasiao(){
	
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$sql = "select import_id as id, phone_01_img ";
	$sql = $sql . " from 02_imports";
	$sql = $sql . " where source_name='Ocasiao'";
	$sql = $sql . " and IFNULL(phone_01_img,'')<>''";
	$sql = $sql . " AND phone_01=''";
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$ID=$row['id'];
		$imgcontacto=$row['phone_01_img'];
		//echo "<br/>imgcontacto: ".$imgcontacto;
		$fixconf='char_inc_6.php';
		file_put_contents("imagem.png", file_get_contents($imgcontacto));
		$retmas = parse_image("imagem.png",$fixconf);
		$contacto=print_output_plain($retmas);

		$sqlUpdate="Update 02_imports Set phone_01_img='', phone_01='$contacto' Where import_id='$ID'";
		ExecutaSQL($sqlUpdate);
		sleep(1);
		
	}

}

function TrataNumerosImmobiliare(){
	
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$sql = "select import_id as id, phone_01_img ";
	$sql = $sql . " from 02_imports";
	$sql = $sql . " where source_name='Immobiliare'";
	$sql = $sql . " and IFNULL(phone_01_img,'')<>''";
	$sql = $sql . " AND phone_01=''";
	$sql = $sql . " LIMIT 10";
	echo "<hr>".$sql;
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$ID=$row['id'];
		$imgcontacto=$row['phone_01_img'];
		echo "<br/>imgcontacto: ".$imgcontacto;
		$fixconf='char_inc_6.php';
		$ficheiro="imagem.png";
		$ficheiro_bk="imagem_bk.png";
		/*
		file_put_contents("imagem.png", file_get_contents($imgcontacto));
		$retmas = parse_image("imagem.png",$fixconf);
		*/
		file_put_contents($ficheiro, file_get_contents($imgcontacto));
		if (filesize($ficheiro)>0) {
			$retmas = parse_image($ficheiro,$fixconf);
			$contacto=print_output_plain($retmas);
			echo "<hr><img src=\"".$imgcontacto."\">".$contacto;

			if (strlen($contacto)>=10)
			{
				if ((strlen($contacto)==13) && (substr($contacto,0,1)=="6"))
				{
					$contacto="+".substr($contacto,1);
				}
					
				$sqlUpdate="Update 02_imports Set phone_01='$contacto' Where import_id='$ID'";
				echo "<br>".$sqlUpdate;
				ExecutaSQL($sqlUpdate);
			}
		}
		else
			copy($ficheiro_bk,$ficheiro);
		sleep(2);
		
	}

}

function TrataNumerosStudenten(){
	
	$imgcontacto="http://www.fotocasa.es/Handlers/PhoneImageText.ashx?Text=ABED53E63BCCB36AD50BCE37EEDDD3D0&amp;Size=20&amp;TextColor=black";
	/*
	echo "<br/><img src=\"".$imgcontacto."\">";
	echo "<br/>imgcontacto: ".$imgcontacto;
	echo "<br/>";
	*/
	$fixconf='char_inc_6.php';
	file_put_contents("imagem.png", file_get_contents($imgcontacto));
	$retmas = parse_image("imagem.png",$fixconf);
	$contacto=print_output_plain($retmas);
	$sqlUpdate="Update 02_imports Set phone_01_img='', phone_01='$contacto' Where import_id='$ID'";
	//echo "<br>".$sqlUpdate;
	
	
	/*
	$cConfig = new config();
	$serverName = $cConfig->servidor;
	$connectionInfo = array("UID" => $cConfig->login, "PWD" => $cConfig->senha, "Database"=>$cConfig->db,"CharacterSet" =>"UTF-8");

	$conn = sqlsrv_connect( $serverName, $connectionInfo);
	if( $conn === false ) {
	die( print_r( sqlsrv_errors(), true));
	}
	$sql = "select Top 10 id, Caracteristicas ";
	$sql = $sql . " from Scrapping";
	$sql = $sql . " where Site='Studenten'";
	$sql = $sql . " and ISNULL(Caracteristicas,'')<>''";
	$sql = $sql . " AND Contacto=''";
	$result = sqlsrv_query($conn, $sql);
	while($row = sqlsrv_fetch_array($result))
	{
		$ID=$row['id'];
		$imgcontacto=$row['Caracteristicas'];
		$imgcontacto="http://www.fotocasa.es/Handlers/PhoneImageText.ashx?Text=ABED53E63BCCB36AD50BCE37EEDDD3D0";
		echo "<br/>imgcontacto: ".$imgcontacto;
		$fixconf='char_inc_6.php';
		file_put_contents("imagem.png", file_get_contents($imgcontacto));
		$retmas = parse_image("imagem.png",$fixconf);
		$contacto=print_output_plain($retmas);

		$sqlUpdate="Update Scrapping Set Caracteristicas='', Contacto='$contacto' Where id='$ID'";
		echo "<br>".$sqlUpdate;
		//ExecutaSQL($sqlUpdate);
		
		break;
		
	}
	*/
}

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
	//echo "<hr>".$html."<hr>";
	$posi=strpos($html,"eval(unescape");
	echo "<br>Posi:".$posi;
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

function ConverteTelefoneCustoJusto($dataphone){
	$result="";
	echo "<br>DataPhone: ".$dataphone;
	$arrayresult = str_split($dataphone);
	foreach ($arrayresult as $curChar) {
		if (((ord($curChar) >= 65) && (ord($curChar)<= 90)) || ((ord($curChar) >= 97) && (ord($curChar)<= 122)))
		{
			if ((ord($curChar) >= 65) && (ord($curChar)<= 90))
			{
				$num=ord($curChar)+13;
				if ($num>90)
					$num=ord($curChar)-13;
				$result=$result.chr($num);
			}
			else
			{
				if ((ord($curChar) >= 97) && (ord($curChar)<= 122))
				{
					$num=ord($curChar)+13;
					if ($num>122)
						$num=ord($curChar)-13;
					$result=$result.chr($num);
				}
			}
		}
		else
			$result=$result.$curChar;
	}
	echo "<br>Result1: ".$result;
	$result=urlencode($result);
	$URL="http://www.custojusto.pt/modal/phone?body=".$result;
	$result=$URL;
	echo "<br>URL: ".$URL;
	
	return $result;
}


function TrataNumerosCustoJusto_old(){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$sql = "select import_id as id, phone_01_img, obs ";
	$sql = $sql . " from 02_imports";
	$sql = $sql . " where source_name='CustoJusto'";
	$sql = $sql . " and IFNULL(phone_01_img,'')<>''";
	$sql = $sql . " AND phone_01=''";
	$sql = $sql . " LIMIT 10";
	echo "<br>Sql: ".$sql;
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$ID=$row['id'];
		$Caracteristicas=$row['phone_01_img'];
		$Caracteristicas=str_replace('http://www.custojusto.pt/modal/phone?body=','',$Caracteristicas);
		$Caracteristicas=urlencode($Caracteristicas);
		$Caracteristicas='http://www.custojusto.pt/modal/phone?body='.$Caracteristicas;
		$sqlUpdate="Update 02_imports Set phone_01_img='$Caracteristicas', phone_01='' Where import_id='$ID'";
		echo "<br>Sql: ".$sqlUpdate;
		ExecutaSQL($sqlUpdate);
		sleep(1);
		
	}
}

//http://178.62.109.124/supply_scripts/popupUpdateOcasiao.php
function TrataNumerosCustoJusto(){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

	$sql = "select import_id as id, phone_01_img, obs ";
	$sql = $sql . " from 02_imports";
	$sql = $sql . " where source_name='CustoJusto'";
	$sql = $sql . " and IFNULL(phone_01_img,'')<>''";
	$sql = $sql . " AND phone_01=''";
	//$sql = $sql . " AND IFNULL(obs,'')<>''";
	$sql = $sql . " LIMIT 1";
	echo "<br>Sql: ".$sql;
	echo "<hr>";
	$results = $conn->query($sql);
	foreach($results as $row)
	{
		$ID=$row['id'];
		$URL=$row['phone_01_img'];
		echo "<br>URL: ".$URL;
		$htmlanuncio = new simple_html_dom();
		$htmlanuncio->load_file($URL);
		
		
		
		echo "<hr>";
		echo $htmlanuncio;
		echo "<hr>";
		echo sizeof($htmlanuncio);
		
		if (sizeof($htmlanuncio)<10)
		{
			$htmlanuncio->clear(); 
			unset($htmlanuncio);
			$htmlanuncio = new simple_html_dom();
			$htmlanuncio=DaHtmlCtxSimples($URL);
			echo "<hr>";
			echo $htmlanuncio;
			echo "<hr>";
			echo sizeof($htmlanuncio);
		}
		
		if (sizeof($htmlanuncio)<10)
		{
			$htmlanuncio->clear(); 
			unset($htmlanuncio);
			$htmlanuncio = new simple_html_dom();
			$htmlanuncio=DaHtmlCtx($URL);
			echo "<hr>";
			echo $htmlanuncio;
			echo "<hr>";
			echo sizeof($htmlanuncio);
		}
		
		
		$divconteudo = $htmlanuncio;
		$contacto="00000000";
		$divtitulo=$divconteudo->find('h1',0);
		if (sizeof($divtitulo)>0)
		{
			$contacto=$divtitulo->plaintext;
		}
		$sqlUpdate="Update 02_imports Set phone_01='$contacto' Where import_id='$ID'";
		echo "<br>sqlUpdate: ".$sqlUpdate;
		ExecutaSQL($sqlUpdate);
		$htmlanuncio->clear(); 
		unset($htmlanuncio);
		sleep(10);
	}
}




function getSimpleDOM($url){
		$htmlanuncio = new simple_html_dom();
		$htmlanuncio->load_file($url);
		$result=$htmlanuncio;
		$htmlanuncio->clear(); 
		unset($htmlanuncio);
		return $result;
}

function get_data($url){
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}



function VerificaLead($connection,$phone){
	$query = "SELECT Id from Lead Where Phone='".$phone."'";
	$response = $connection->query($query);
	$queryResult = new QueryResult($response);
	$result="";

	for ($queryResult->rewind(); $queryResult->pointer < $queryResult->size; $queryResult->next()) {
		/*
		print "<pre>";
		print_r($queryResult->current());
		print "</pre>";
		*/
		$result=$queryResult->current()->Id;
	}
	
	return $result;
}

function GetBakeca($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	$site="Bakeca";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	//$html->load_file($URL);
	//$html=DaHtmlCtx($URL);
	$html=DaHtmlCtxSimples($URL);

	$items = $html->find('div[class=b-ann-item]');  
	 
	foreach($items as $post) {
		# remember comments count as nodes
		$itemdiv=str_get_html($post);
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		
		$divtitulo=$itemdiv->find('h3',0);
		$link=$itemdiv->find('a',0)->href;
		$divdescricao=$itemdiv->find('p[class=b-ann-desc]',0);
		$divpreco=$itemdiv->find('span[class=b-ann-prezzo]',0);
		$divdata=$itemdiv->find('div[class=b-ann-date]',0);
		if (sizeof($divtitulo)>0)
				$titulo=$divtitulo->plaintext;
		
		if (sizeof($divdescricao)>0)
				$descricao=$divdescricao->plaintext;
		
		if (sizeof($divpreco)>0)
				$preco=$divpreco->plaintext;
		
		
		if (sizeof($divdata)>0)
				$data=$divdata->innertext;
		
		//echo "<br>Link: $link";
		try {
			if ($link!="")
			{
				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divcontacto = $htmlanuncio->find('div[class=b-dett-contacts-telefoni]',0);
				if (sizeof($divcontacto)>0)
					$contacto=$divcontacto->plaintext;
				$contacto=str_replace("TEL","",$contacto);
				//echo "<br/>Contacto: ".$contacto;
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}
		}
		catch(Exception $ebakeca) {
		  echo '<br>Erro: ' .$ebakeca->getMessage();
		}
		
		
		$titulo=limpa($titulo);
		$link=limpa($link);
		$descricao=limpa($descricao);
		$descricao=str_replace("'",'',$descricao);
		$preco=limpa(str_replace("EURO","",$preco));
		$data=limpa($data);
		$contacto=limpa(trim($contacto));
		
		echo "<br/>Link: ".$link;
		echo "<br/>titulo: ".$titulo;
		echo "<br/>descricao: ".$descricao;
		echo "<br/>preco: ".$preco;
		echo "<br/>contacto: ".$contacto;
		
		InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
		
		ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		
		
	}
	$html->clear(); 
	unset($html);
}

function GetBQuarto($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	$site="BQuarto";
	echo "<br>Entrou: $site";
	$htmlok=0;
	try {
		$html = DaHtmlCtxBQUarto($URL);
		$htmlok=1;
	}
	catch(Exception $e) {
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	if ($htmlok==1)
	{

		$items = $html->find('div[id*=d_anuncio_]');  
		 
		foreach($items as $post) {
			# remember comments count as nodes
			$itemdiv=str_get_html($post);
			
			$nome="";
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			
			$divnome=$itemdiv->find('div[id*=nome_]',0);
			$divtitulo=$itemdiv->find('div[id*=titulo_]',0); 
			$divtitulo2=$itemdiv->find('div[id*=titulo2_]',0);
			$divcontacto=$itemdiv->find('div[id*=telef_]',0);
			$divpreco=$itemdiv->find('div[id*=renda_]',0);

			$divlink=$itemdiv->find('div[id*=infor_]',0);
			if (sizeof($divlink)>0)
			{
				$divlink=$divlink->find('a',0);
				if (sizeof($divlink)>0)
					$link=$divlink->href;
			}
			
			$divdescricao=$itemdiv->find('div[id*=descri_]',0);
		
			//$divdata=$itemdiv->find('div.bk-annuncio-date',0);
			if (sizeof($divnome)>0)
					$nome=strip_tags($divnome);
				
			if (sizeof($divtitulo)>0)
					$titulo=strip_tags($divtitulo);
			if (sizeof($divtitulo2)>0)
					$titulo=$titulo." ".strip_tags($divtitulo2);
			
			if (sizeof($divcontacto)>0)
					$contacto=strip_tags($divcontacto);
			
			if (sizeof($divdescricao)>0)
					$descricao=strip_tags($divdescricao);
			
			if (sizeof($divpreco)>0)
					$preco=strip_tags($divpreco);
				
			$preco=str_replace("&#8364;","",$preco);
			
			
			try {
				if ($link!="")
				{
					$htmlanuncio=DaHtml($link,"");


					$divdescricaolink = $htmlanuncio->find('div.desc',0);
					if (sizeof($divdescricaolink)>0)
						$descricao=$divdescricaolink->plaintext;

					if ($contacto=="")
					{
						$divcontacto = $htmlanuncio->find('div.dir_tex3u',0);
						//echo "<br/>divcontacto: (".$divcontacto.")";
						if (sizeof($divcontacto)>0)
							$contacto=strip_tags($divcontacto);
					}
					if ($contacto=="")
					{
						$divcontacto = $htmlanuncio->find('div.dir_tex3',0);
						//echo "<br/>divcontacto: (".$divcontacto.")";
						if (sizeof($divcontacto)>0)
							$contacto=strip_tags($divcontacto);
					}
					
					if ($contacto=="")
					{
						$contacto="Sem Acesso";
					}
					$htmlanuncio->clear(); 
					unset($htmlanuncio);
				}
			}
			catch(Exception $e) {
			  echo '<br>Erro: ' .$e->getMessage();
			}
			
			
			$titulo=limpa($titulo);
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("EURO","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			
			if ($link!="")
			{
				
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome);
				//echo "<br/>SQL: ".$sql;
				//ExecutaSQL($sql);
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
			
		}
		$html->clear(); 
		unset($html);
	}
}

function GetCraigslist($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	$site="Craigslist";
	echo "<br>Entrou: $site";
	$URLBase=substr($URL,0,strpos($URL,"/search"));
	
	$html = new simple_html_dom();
	$html=DaHtmlCtxSimples($URL);

	$items = $html->find('li[class=result-row]');
	echo "<br/>items: ".sizeof($items);
	foreach($items as $post) {
		# remember comments count as nodes
		$itemdiv=str_get_html($post);
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		
	
		$link=$itemdiv->find('a',0)->href;
		echo "<br/>Link: ".$link;
		
		if (($link!="") && ($URLBase!=""))
			$link=$URLBase.$link;
	

		if ($link!="")
		{
			$htmlanuncio = new simple_html_dom();
			$htmlanuncio=DaHtmlCtxSimples($link);
			$divconteudo = $htmlanuncio;
			
			$divtitulo=$divconteudo->find('span[class=postingtitletext]',0);
			if (sizeof($divtitulo)>0)
				$titulo=$divtitulo->plaintext;
			
			$divdata=$divconteudo->find('time',0);
			if (sizeof($divdata)>0)
			{
				$data=$divdata->datetime;
				$data=str_replace("T"," ",$data);
				$data=substr($data,0,strpos($data,"+"));
			}
			
			$divdescricao=$divconteudo->find('section[id=postingbody]',0);
			if (sizeof($divdescricao)>0)
				$descricao=$divdescricao->plaintext;
			
			$divpreco=$divconteudo->find('span[class=price]',0);
			if (sizeof($divpreco)>0)
				$preco=$divpreco->plaintext;
			
			$linkcontacto=$divconteudo->find('a[id=replylink]',0);
			if (sizeof($linkcontacto)>0)
				$linkcontacto=$linkcontacto->href;
			if ($linkcontacto!="")
			{
				$htmlcontacto = new simple_html_dom();
				$htmlcontacto->load_file($URLBase.$linkcontacto);
				echo "<br>URLContacto: ".$URLBase.$linkcontacto;
				$divcontacto=$htmlcontacto->find('p[class=reply-tel-number]',0);
				if (sizeof($divcontacto)>0)
					$contacto=$divcontacto->plaintext;
				if ($contacto!="")
				{
					$contacto=substr($contacto,0,strpos($contacto,"Webmail-Links"));
					$contacto=str_replace("Bevorzugte Kontaktform:E-Mail","",$contacto);
					$contacto=str_replace("Tel.","",$contacto);
					$contacto=str_replace("&#9742;","",$contacto);
				}
				unset($htmlcontacto);
				
			}
							
			$htmlanuncio->clear(); 
			unset($htmlanuncio);
		}

		
		
		$titulo=limpa(str_replace("&#x20AC;","",$titulo));
		$link=limpa($link);
		$descricao=limpa($descricao);
		$preco=limpa(str_replace("&#x20AC;","",$preco));
		$data=limpa($data);
		$contacto=limpa(trim($contacto));
		
		echo "<br/>Link: ".$link;
		echo "<br/>titulo: ".$titulo;
		echo "<br/>descricao: ".$descricao;
		echo "<br/>preco: ".$preco;
		echo "<br/>contacto: ".$contacto;

		if ($contacto!="")
		{
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
		
		//if ((strpos($titulo,"möbliert")>0) || (strpos($titulo,"m&#x00D6;bliert")>0) || (strpos($titulo,"moebliert")>0) || (strpos($titulo,"furnished")>0) || (strpos($titulo,"student")>0)
		//	|| (strpos($descricao,"möbliert")>0) || (strpos($descricao,"m&#x00D6;bliert")>0) || (strpos($descricao,"moebliert")>0) || (strpos($descricao,"furnished")>0) || //(strpos($descricao,"student")>0))
		//{
			//ExecutaSQL($sql);
		
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}
	}
	$html->clear(); 
	unset($html);
}

function GetIdealistaES($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList, $htmlcode) {
	
	$site="Idealista ES";
	echo "<br>Entrou: $site";
	echo "<br>URL: $URL";
	$html = new simple_html_dom();
	//$html=DaHtmlCtxHttps($URL);
	$html = str_get_html($htmlcode);

	$continua=true;
	try
	{
		$items = $html->find('div[class=item]');
		$continua=true;
	}
	catch(Exception $e) {
	  $continua=false;
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	if ($continua==true)
	{
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$tipoanuncio="";
		
			$divlink=$post->find('a',0);
			
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			$linkbase="http://www.idealista.com";
			$link=$linkbase.$link;
			echo "<br/>Link: ".$link;
		
			if (strpos($link,"pro/")>0)
				$link="";

			if ($link!="")
			{
				try {
					
					$continuaanuncio=true;
					try
					{
						$htmlanuncio=DaHtmlCtxHttps($link,"<span class=\"txt-bold\">");
						$continuaanuncio=true;
					}
					catch(Exception $e) {
					  $continuaanuncio=false;
					  echo '<br>Erro: ' .$e->getMessage();
					}
					
					if ($continuaanuncio==true)
					{
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('h1[class=txt-bold]',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						$divdata=$divconteudo->find('section[id=stats]',0);
						if (sizeof($divdata)>0)
						{
							$h2data=$divdata->find('h2[class="txt-medium txt-bold"]',0);
							if (sizeof($h2data)>0)
								$data=$h2data->plaintext;
						}
						
						$divdescricao=$divconteudo->find('span[class=commentsContainer]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						
						$divpreco=$divconteudo->find('div[class=info-data]',0);
						if (sizeof($divpreco)>0)
						{
							$spanpreco=$divpreco->find('span',0);
							if (sizeof($spanpreco)>0)
								$preco=$spanpreco->plaintext;
						}
						
						$divcaracteristicas=$divconteudo->find('section[id=details]',0);
						if (sizeof($divcaracteristicas)>0)
						{
							$divscaracteristicas=$divcaracteristicas->find('div',3);
							if (sizeof($divscaracteristicas)>0)
								$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
							$divscaracteristicas=$divcaracteristicas->find('div',4);
							if (sizeof($divscaracteristicas)>0)
								$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
						}
						
						$linkcontacto=$divconteudo->find('div[class="phone first-phone"]',0);
						if (sizeof($linkcontacto)>0)
							$contacto=$linkcontacto->plaintext;
						
						
						$divtipoanuncio=$divconteudo->find('div[class="advertiser-data txt-soft"]',0);
						if (sizeof($divtipoanuncio)>0)
							$tipoanuncio=$divtipoanuncio->plaintext;
										
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
				}
				catch(Exception $e) {
				  echo '<br>Erro: ' .$e->getMessage();
				}
			}

			
			$titulo=limpa($titulo);
			$link=limpa($link);
			$descricao=limpa($descricao);
			$replace= array("\\\"",);
			$descricao=str_replace($replace,"",$descricao);
			$preco=limpa($preco);
			$data=limpa($data);
			$data=str_replace("Annuncio aggiornato il ","",$data);
			$data=str_replace("listing updated on ","",$data);
			$contacto=limpa(trim($contacto));


			if (($link!="") && (strpos($tipoanuncio,"Particular")>0))
			{
				/*
				$sql="INSERT INTO [Scrapping] ";
				$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
				$sql=$sql." VALUES  ";
				$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."') ";
				*/
				//$caracteristicas=$contacto; $contacto="";
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','',''); //Caracteristicas
				
				//echo "<br/>SQL: ".$sql;
				//ExecutaSQL($sql);
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}


		}
	} //if ($continua==true)
	
	$html->clear(); 
	unset($html);

}

function GetIdealistaPT($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList, $htmlcode) {
	
	$site="Idealista PT";
	echo "<br>Entrou: $site";
	echo "<br>URL: $URL";
	$html = new simple_html_dom();
	//$html->load_file($URL);
	//$html=DaHtmlCtxHttps($URL);
	$html = str_get_html($htmlcode);

	$continua=true;
	try
	{
		$items = $html->find('div[class=item]');
		$continua=true;
	}
	catch(Exception $e) {
	  $continua=false;
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	//echo "<br>Continua: ".$continua;
	
	if ($continua==true)
	{
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$tipoanuncio="";
		
			$divlink=$post->find('a',0);
			
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			$linkbase="http://www.idealista.pt";
			$link=$linkbase.$link;
			//echo "<br/>Link: ".$link;
		
			if (strpos($link,"pro/")>0)
				$link="";

			if ($link!="")
			{
				try {
					
					$continuaanuncio=true;
					try
					{
						$htmlanuncio=DaHtmlCtxHttps($link,"<span class=\"txt-bold\">");
						$continuaanuncio=true;
					}
					catch(Exception $e) {
					  $continuaanuncio=false;
					  echo '<br>Erro: ' .$e->getMessage();
					}
					
					if ($continuaanuncio==true)
					{
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('h1[class=txt-bold]',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						$divdata=$divconteudo->find('section[id=stats]',0);
						if (sizeof($divdata)>0)
						{
							$h2data=$divdata->find('h2[class="txt-medium txt-bold"]',0);
							if (sizeof($h2data)>0)
								$data=$h2data->plaintext;
						}
						
						$divdescricao=$divconteudo->find('span[class=commentsContainer]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						
						$divpreco=$divconteudo->find('div[class=info-data]',0);
						if (sizeof($divpreco)>0)
						{
							$spanpreco=$divpreco->find('span',0);
							if (sizeof($spanpreco)>0)
								$preco=$spanpreco->plaintext;
						}
						
						$divcaracteristicas=$divconteudo->find('section[id=details]',0);
						if (sizeof($divcaracteristicas)>0)
						{
							$divscaracteristicas=$divcaracteristicas->find('div',3);
							if (sizeof($divscaracteristicas)>0)
								$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
							$divscaracteristicas=$divcaracteristicas->find('div',4);
							if (sizeof($divscaracteristicas)>0)
								$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
						}
						
						$linkcontacto=$divconteudo->find('div[class="phone first-phone"]',0);
						if (sizeof($linkcontacto)>0)
							$contacto=$linkcontacto->plaintext;
						
						
						$divtipoanuncio=$divconteudo->find('div[class="advertiser-data txt-soft"]',0);
						if (sizeof($divtipoanuncio)>0)
							$tipoanuncio=$divtipoanuncio->plaintext;
										
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
				}
				catch(Exception $e) {
				  echo '<br>Erro: ' .$e->getMessage();
				}
			}

			
			$titulo=limpa($titulo);
			$link=limpa($link);
			$descricao=limpa($descricao);
			$replace= array("\\\"",);
			$descricao=str_replace($replace,"",$descricao);
			$preco=limpa($preco);
			$data=limpa($data);
			$data=str_replace("Anúncio atualizado no dia","",$data);
			$contacto=limpa(trim($contacto));


			if (($link!="") && (strpos($tipoanuncio,"Particular")>0))
			{
				/*
				$sql="INSERT INTO [Scrapping] ";
				$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
				$sql=$sql." VALUES  ";
				$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."') ";
				*/
				//$caracteristicas=$contacto; $contacto="";
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','',''); //Caracteristicas
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}


		}
	} //if ($continua==true)
	
	$html->clear(); 
	unset($html);

}

function GetIdealistaIT($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList, $htmlcode) {
	
	$site="Idealista IT";
	echo "<br>Entrou: $site";
	echo "<br>URL: $URL";
	$html = new simple_html_dom();
	//$html->load_file($URL);
	//$html=DaHtmlCtxHttps($URL);
	//$html=DaHtmlCtxIdealista($URL);
	$html = str_get_html($htmlcode);
	
	$continua=true;
	try
	{
		$items = $html->find('div[class=item]');
		$continua=true;
	}
	catch(Exception $e) {
	  $continua=false;
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	echo "<br>Continua: ".$continua;
	
	if ($continua==true)
	{
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$tipoanuncio="";
		
			$divlink=$post->find('a',0);
			
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			$linkbase="http://www.idealista.it";
			$link=$linkbase.$link;
			echo "<br/>Link: ".$link;
		
			if (strpos($link,"pro/")>0)
				$link="";

			if ($link!="")
			{
				
				//$htmlanuncio=DaHtmlCtxHttps($link,"<span class=\"txt-bold\">");
				
				$htmlanuncio=DaHtmlCtxIdealista($link);
				//echo "<hr>".$htmlanuncio;
				$continuaanuncio=true;

				$divconteudo = $htmlanuncio;
				
				$divtitulo=$divconteudo->find('h1[class=txt-bold]',0);
				if (sizeof($divtitulo)>0)
					$titulo=$divtitulo->plaintext;
				
				$divdata=$divconteudo->find('section[id=stats]',0);
				if (sizeof($divdata)>0)
				{
					$h2data=$divdata->find('h2[class="txt-medium txt-bold"]',0);
					if (sizeof($h2data)>0)
						$data=$h2data->plaintext;
				}
				
				$divdescricao=$divconteudo->find('span[class=commentsContainer]',0);
				if (sizeof($divdescricao)>0)
					$descricao=$divdescricao->plaintext;
				
				$divpreco=$divconteudo->find('div[class=info-data]',0);
				if (sizeof($divpreco)>0)
				{
					$spanpreco=$divpreco->find('span',0);
					if (sizeof($spanpreco)>0)
						$preco=$spanpreco->plaintext;
				}
				
				$divcaracteristicas=$divconteudo->find('section[id=details]',0);
				if (sizeof($divcaracteristicas)>0)
				{
					$divscaracteristicas=$divcaracteristicas->find('div',3);
					if (sizeof($divscaracteristicas)>0)
						$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
					$divscaracteristicas=$divcaracteristicas->find('div',4);
					if (sizeof($divscaracteristicas)>0)
						$caracteristicas=$caracteristicas.$divscaracteristicas->plaintext;
				}
				
				$linkcontacto=$divconteudo->find('div[class="phone first-phone"]',0);
				if (sizeof($linkcontacto)>0)
					$contacto=$linkcontacto->plaintext;
				
				
				$divtipoanuncio=$divconteudo->find('div[class="advertiser-data txt-soft"]',0);
				if (sizeof($divtipoanuncio)>0)
					$tipoanuncio=$divtipoanuncio->plaintext;
								
				$htmlanuncio->clear(); 
				unset($htmlanuncio);

			}

			
			$titulo=limpa($titulo);
			$link=limpa($link);
			$descricao=limpa($descricao);
			$replace= array("\\\"",);
			$descricao=str_replace($replace,"",$descricao);
			$preco=limpa($preco);
			$data=limpa($data);
			$data=str_replace("Annuncio aggiornato il ","",$data);
			$data=str_replace("listing updated on ","",$data);
			$contacto=limpa(trim($contacto));

			/*
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>contacto: ".$contacto;
			*/

			if (($link!="") && (strpos($tipoanuncio,"Privato")>0))
			{
				/*
				$sql="INSERT INTO [Scrapping] ";
				$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
				$sql=$sql." VALUES  ";
				$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."') ";
				*/
				//$caracteristicas=$contacto; $contacto="";
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','');
				//echo "<br/>SQL: ".$sql;
				//ExecutaSQL($sql);
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}

		}
	} //if ($continua==true)
	
	$html->clear(); 
	unset($html);

}

function GetImmobiliare($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="Immobiliare";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	//$html->load_file($URL);
	$html=DaHtmlCtxSimples($URL);
	
	$items = $html->find('ul[id=listing-container]');
	
	foreach($items as $post) {
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		$caracteristicas="";
		$tipoanuncio="Privato";
	
		$divlink=$post->find('a',0);
		if (sizeof($divlink)>0)
			$link=$divlink->href;
		
		//echo "<br/>Link: ".$link;

		if ($link!="")
		{

				
				$continuaanuncio=true;

					$htmlanuncio=DaHtml($link,"<span class=\"txt-bold\">");
					$continuaanuncio=true;

				

					$divconteudo = $htmlanuncio;
					
					$divtitulo=$divconteudo->find('div[class=title-detail]',0);
					if (sizeof($divtitulo)>0)
						$titulo=$divtitulo->plaintext;
					
					/*
					$divdata=$divconteudo->find('section[id=stats]',0);
					if (sizeof($divdata)>0)
					{
						$h2data=$divdata->find('h2[class="txt-medium txt-bold"]',0);
						if (sizeof($h2data)>0)
							$data=$h2data->plaintext;
					}
					*/
					$data="";
					
					$divdescricao=$divconteudo->find('div[class=description-text]',0);
					if (sizeof($divdescricao)>0)
						$descricao=$divdescricao->plaintext;
					
					$divpreco=$divconteudo->find('div[class=detail-features]',0);
					if (sizeof($divpreco)>0)
					{
						$ulpreco=$divpreco->find('ul',0);
						if (sizeof($ulpreco)>0)
							$strongpreco=$ulpreco->find('strong',0);
							if (sizeof($strongpreco)>0)
								$preco=$strongpreco->plaintext;
					}
					
					/*
					$divcaracteristicas=$divconteudo->find('div[class=tabs_container]',0);
					if (sizeof($divcaracteristicas)>0)
					{
						if (sizeof($divcaracteristicas->find('div[id=tab_div_1]',0))>0)
							$caracteristicas=$caracteristicas.$divcaracteristicas->find('div[id=tab_div_1]',0)->plaintext;
						if (sizeof($divcaracteristicas->find('div[id=tab_div_2]',0))>0)
							$caracteristicas=$caracteristicas.$divcaracteristicas->find('div[id=tab_div_2]',0)->plaintext;
						if (sizeof($divcaracteristicas->find('div[id=tab_div_3]',0))>0)
							$caracteristicas=$caracteristicas.$divcaracteristicas->find('div[id=tab_div_3]',0)->plaintext;

					}
					*/
					
					$divcontacto=$divconteudo->find('p[class="contact"]',0);
					if (sizeof($divcontacto)>0)
					{
						$imgcontacto=$divcontacto->find('img',0);
						if (sizeof($imgcontacto)>0)
							$contacto=$imgcontacto->src;
					}
									
					$htmlanuncio->clear(); 
					unset($htmlanuncio);


		}

		
		$titulo=html_entity_decode(limpa($titulo));
		$link=limpa($link);
		$descricao=html_entity_decode(limpa($descricao));
		$replace= array("\\\"",);
		$descricao=str_replace($replace,"",$descricao);
		$descricao=str_replace("'",'',$descricao);
		$preco=html_entity_decode(limpa($preco));
		$data=limpa($data);
		$data=str_replace("Annuncio aggiornato il ","",$data);
		$data=str_replace("listing updated on ","",$data);
		$contacto=limpa(trim($contacto));
		$caracteristicas=html_entity_decode(trataplicas($caracteristicas));
		$caracteristicas=str_replace("'",'',$caracteristicas);
		$caracteristicas="";

		
		echo "<br/>Link: ".$link;
		echo "<br/>titulo: ".$titulo;
		echo "<br/>descricao: ".$descricao;
		echo "<br/>preco: ".$preco;
		echo "<br/>contacto: ".$contacto;
		

		if (($contacto!=""))
		{
			/*
			$sql="INSERT INTO [Scrapping] ";
			$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
			$sql=$sql." VALUES  ";
			$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','','".$link."','".$zona."','".$URL."','".$contacto."') ";
			*/
			$caracteristicas=$contacto; $contacto="";
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','',$caracteristicas);
				
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}

	}
	
	$html->clear(); 
	unset($html);

}

function GetNullProvision($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="NullProvision";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	try
	{
		$html->load_file($URL);
		$items = $html->find('a[class=resultlist-item]');  
		 
		foreach($items as $post) {
			# remember comments count as nodes
			//$itemdiv=str_get_html($post);
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$floor="";
			$space="";
			$vacant="";
			$rooms="";
			$bathrooms="";
			$pets="";
			$garage="";
			$numgarage="";
			
		
			$link=$post->href;
			echo "<br/>Link: ".$link;
			
			$pos=strpos($link,"attr1");
			if ($pos!==false)
			{
				$link=substr($link,$pos);
				$link=str_replace("attr1",'',$link);
				$link=str_replace("=",'',$link);
				$link="https://www.immobilienscout24.de/expose/".$link;
			}

		
			try {
				if ($link!="")
				{
					$htmlanuncio = new simple_html_dom();
					try {
						$htmlanuncio->load_file($link);
						$divconteudo = $htmlanuncio;
						
						$titulo=$divconteudo->find('h1[id=expose-title]',0)->plaintext;
						
						$divdescricao=$divconteudo->find('span[data-qa=is24-expose-address]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;

						$divpreco=$divconteudo->find('div[class=is24qa-kaltmiete]',0);
						if (sizeof($divpreco)>0)
							$preco=$divpreco->plaintext;
						
						$linkcontacto=$divconteudo->find('div[class=is24-phone-number hide]',0);
						if (sizeof($linkcontacto)>0)
							$contacto=$linkcontacto->plaintext;
						
						/* Campos Extra NullProvision */
						$divfloor=$divconteudo->find('dd[class=is24qa-etage]',0);
						if (sizeof($divfloor)>0)
							$floor=$divfloor->plaintext;
						
						$divspace=$divconteudo->find('dd[class=is24qa-wohnflaeche-ca]',0);
						if (sizeof($divspace)>0)
							$space=$divspace->plaintext;
						
						$divvacant=$divconteudo->find('dd[class=is24qa-bezugsfrei-ab]',0);
						if (sizeof($divvacant)>0)
							$vacant=$divvacant->plaintext;
						
						$divrooms=$divconteudo->find('dd[class=is24qa-zimmer]',0);
						if (sizeof($divrooms)>0)
							$rooms=$divrooms->plaintext;
						
						$divbathrooms=$divconteudo->find('dd[class=is24qa-badezimmer]',0);
						if (sizeof($divbathrooms)>0)
							$bathrooms=$divbathrooms->plaintext;
						
						$divpets=$divconteudo->find('dd[class=is24qa-haustiere]',0);
						if (sizeof($divpets)>0)
							$pets=$divpets->plaintext;
						
						$divgarage=$divconteudo->find('dd[class=is24qa-garage-stellplatz]',0);
						if (sizeof($divgarage)>0)
							$garage=$divgarage->plaintext;
						
						$divnumgarage=$divconteudo->find('dd[class=is24qa-anzahl-garage-stellplatz]',0);
						if (sizeof($divnumgarage)>0)
							$numgarage=$divnumgarage->plaintext;
						
						/* Campos Extra NullProvision */
										
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
					catch(Exception $e) {
					  //echo '<br>Erro: ' .$e->getMessage();
					}
				}
			}
			catch(Exception $e) {
			  //echo '<br>Erro: ' .$e->getMessage();
			}
			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("&#x20AC;","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));

			/*
			$sql="INSERT INTO [Scrapping] ";
			$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase] ";
			$sql=$sql." , [Floor], [Space], [Vacant], [Rooms], [Bathrooms], [Pets], [Garage], [NumGarage])";
			$sql=$sql." VALUES  ";
			$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."' ";
			$sql=$sql." ,'".$floor."','".$space."','".$vacant."','".$rooms."','".$bathrooms."','".$pets."','".$garage."','".$numgarage."')";
			*/
			
			
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, "", $floor, $rooms);
			//echo "<br/>SQL: ".$sql;
			//ExecutaSQL($sql);
		
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetOLX($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="OLX";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	$html->load_file($URL);

	$items = $html->find('td[class*=offer]');  
	 
	foreach($items as $post) {
		# remember comments count as nodes
		$itemdiv=str_get_html($post);
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		
	
		$divlink=$itemdiv->find('a[class*=detailsLink]',0);
		if (sizeof($divlink)>0)
			$link=$divlink->href;
		//echo "<br/>Link: ".$link;
		
	
		try {
			if ($link!="")
			{
				$htmlanuncio = new simple_html_dom();
				$htmlanuncio->load_file($link);
				$divconteudo = $htmlanuncio->find('div[class*=offerbody]',0);
				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
					$titulo=$divtitulo->plaintext;
				
				$divdata=$divconteudo->find('span[class="pdingleft10 brlefte5"]',0);
				if (sizeof($divdata)>0)
					$data=$divdata->plaintext;
				
				if (strpos($data,",")>0)
				{
					$data=substr($data, strpos($data,",")+1);
					if (strpos($data,",")>0)
					{
						$data=substr($data, 0,strpos($data,","));
					}
				}
				
				
				$divdescricao=$divconteudo->find('div[id=textContent]',0);
				if (sizeof($divdescricao)>0)
					$descricao=$divdescricao->plaintext;
				
				$divpreco=$divconteudo->find('div[class*=pricelabel]',0);
				if (sizeof($divpreco)>0)
					$preco=$divpreco->plaintext;
				$preco=limpa(str_replace("Preço","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				
				$divcontactos = $divconteudo->find('ul[id=contact_methods]',0);
				if (sizeof($divcontactos)>0)
				{
					$divcontacto=$divcontactos->find('div[class*=contact-button]',0);
					if (sizeof($divcontacto)>0)
					{
						$classcontacto=$divcontacto->class;

						$phonetemp=explode("'id':'",$classcontacto);
						if (sizeof($phonetemp)>1)
						{
							$phonenew=explode("',",$phonetemp[1]);
							$phoneid=$phonenew[0];

							//https://www.olx.pt/ajax/misc/contact/phone/zQYop/
							//$phonenum=file_get_contents("http://olx.pt/ajax/misc/contact/phone/".$phoneid."/");
							$phonenum=DaHtmlCtxHttps("https://www.olx.pt/ajax/misc/contact/phone/".$phoneid."/");

							$phonehalf=explode('":"',$phonenum);
							$phonenum=explode('"}',$phonehalf[1]);
							$contacto=$phonenum[0];
						}
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}
		}
		catch(Exception $e) {
		  //echo '<br>Erro: ' .$e->getMessage();
		}
		
		
		$titulo=limpa($titulo);
		$link=limpa($link);
		$descricao=limpa($descricao);
		$preco=limpa(str_replace("EURO","",$preco));
		$data=limpa($data);
		$contacto=limpa(trim($contacto));

		if ($contacto!="")
		{
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
		
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}
	
		
	}
	$html->clear(); 
	unset($html);
}

function GetOLXPL($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="OLX";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	$html->load_file($URL);

	$items = $html->find('td[class*=offer]');  
	 
	foreach($items as $post) {
		# remember comments count as nodes
		$itemdiv=str_get_html($post);
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		
	
		$divlink=$itemdiv->find('a[class*=detailsLink]',0);
		if (sizeof($divlink)>0)
			$link=$divlink->href;
		//echo "<br/>Link: ".$link;
		
	
		try {
			if ($link!="")
			{
				$htmlanuncio = new simple_html_dom();
				$htmlanuncio->load_file($link);
				$divconteudo = $htmlanuncio->find('div[class*=offerbody]',0);
				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
					$titulo=$divtitulo->plaintext;
				
				$divdata=$divconteudo->find('span[class="pdingleft10 brlefte5"]',0);
				if (sizeof($divdata)>0)
					$data=$divdata->plaintext;
				
				if (strpos($data,",")>0)
				{
					$data=substr($data, strpos($data,",")+1);
					if (strpos($data,",")>0)
					{
						$data=substr($data, 0,strpos($data,","));
					}
				}
				
				
				$divdescricao=$divconteudo->find('div[id=textContent]',0);
				if (sizeof($divdescricao)>0)
					$descricao=$divdescricao->plaintext;
				
				$divpreco=$divconteudo->find('div[class*=pricelabel]',0);
				if (sizeof($divpreco)>0)
					$preco=$divpreco->plaintext;
				$preco=limpa(str_replace("Preço","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				
				$divcontactos = $divconteudo->find('ul[id=contact_methods]',0);
				if (sizeof($divcontactos)>0)
				{
					$divcontacto=$divcontactos->find('div[class*=contact-button]',0);
					if (sizeof($divcontacto)>0)
					{
						$classcontacto=$divcontacto->class;

						$phonetemp=explode("'id':'",$classcontacto);
						if (sizeof($phonetemp)>1)
						{
							$phonenew=explode("',",$phonetemp[1]);
							$phoneid=$phonenew[0];

							//https://www.olx.pt/ajax/misc/contact/phone/zQYop/
							//$phonenum=file_get_contents("http://olx.pt/ajax/misc/contact/phone/".$phoneid."/");
							$phonenum=DaHtmlCtxHttps("https://www.olx.pt/ajax/misc/contact/phone/".$phoneid."/");

							$phonehalf=explode('":"',$phonenum);
							$phonenum=explode('"}',$phonehalf[1]);
							$contacto=$phonenum[0];
						}
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}
		}
		catch(Exception $e) {
		  //echo '<br>Erro: ' .$e->getMessage();
		}
		
		
		$titulo=limpa($titulo);
		$link=limpa($link);
		$descricao=limpa($descricao);
		$preco=limpa(str_replace("EURO","",$preco));
		$data=limpa($data);
		$contacto=limpa(trim($contacto));

		if ($contacto!="")
		{
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
		
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}
	
		
	}
	$html->clear(); 
	unset($html);
}


function GetPAP($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="PAP";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	$html->load_file($URL);
	
	$continua=true;
	try
	{
		$items = $html->find('li[class=annonce]');
		$continua=true;
	}
	catch(Exception $e) {
	  $continua=false;
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	if ($continua==true)
	{
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$tipoanuncio="Privato";
		
			$divlink=$post->find('a',0);
			if (sizeof($divlink)>0)
				$link=$divlink->href;

			$linkbase="http://www.pap.fr";
			$link=$linkbase.$link;
			//echo "<br/>Link: ".$link;

			if ($link!="")
			{
				try {
					
					$continuaanuncio=true;
					try
					{
						$htmlanuncio=DaHtml($link,"<span class=\"txt-bold\">");
						$continuaanuncio=true;
					}
					catch(Exception $e) {
					  $continuaanuncio=false;
					  echo '<br>Erro: ' .$e->getMessage();
					}
					
					if ($continuaanuncio==true)
					{
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('span[class=title]',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						$divdata=$divconteudo->find('span[class=date]',0);
						if (sizeof($divdata)>0)
							$data=$divdata->plaintext;
						
						$divdescricao=$divconteudo->find('div[class=text-annonce]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						
						$divpreco=$divconteudo->find('span[class=prix]',0);
						if (sizeof($divpreco)>0)
							$preco=$divpreco->plaintext;

						
						$divcaracteristicas=$divconteudo->find('div[class=footer-descriptif clearfix]',0);
						if (sizeof($divcaracteristicas)>0)
						{
							if (sizeof($divcaracteristicas->find('ul',0))>0)
								$caracteristicas=$caracteristicas.$divcaracteristicas->find('ul',0)->plaintext;
						}
						
						$divcontacto=$divconteudo->find('span[class="telephone hide-tel"]',0);
						if (sizeof($divcontacto)>0)
							$contacto=$divcontacto->plaintext;
										
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
				}
				catch(Exception $e) {
				  echo '<br>Erro: ' .$e->getMessage();
				}
			}

			
			//$titulo=html_entity_decode(trataplicas(limpa($titulo)));
			$titulo = htmlentities($titulo, null, 'UTF-8');
			//$titulo = html_entity_decode($titulo, null, 'UTF-8');
			$link=limpa($link);
			$descricao=html_entity_decode(trataplicas(limpa($descricao)));
			$replace= array("\\\"",);
			$descricao=str_replace($replace,"",$descricao);
			$descricao = htmlentities($descricao, null, 'UTF-8');
			$preco=html_entity_decode(limpa($preco));
			$preco=str_replace("&euro;","",$preco);
			$preco=str_replace(".","",$preco);
			$preco=str_replace(" ","",$preco);
			$data=limpa($data);
			//$data="";
			$contacto=limpa(trim($contacto));
			$contacto=str_replace(".","",$contacto);
			$caracteristicas=html_entity_decode(trataplicas($caracteristicas));
			//$caracteristicas="";


			if (($contacto!=""))
			{
				/*
				$sql="INSERT INTO [Scrapping] ";
				$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
				$sql=$sql." VALUES  ";
				$sql=$sql." ('".$site."','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."'); ";
				*/

				$caracteristicas=$contacto; $contacto="";
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','',$caracteristicas); //Caracteristicas
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
			
		}
	} //if ($continua==true)
	
	$html->clear(); 
	unset($html);

}

function GetPisoCompartido($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="PisoCompartido";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	try
	{
		$html->load_file($URL);
		$items = $html->find('div[class=resultado-parrilla]');  
		 
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$nome="";
		
			$divlinkh2=$post->find('h2[class*=titulo]',0);
			if (sizeof($divlinkh2)>0)
			{
				$divlink=$divlinkh2->find('a',0);
				if (sizeof($divlink)>0)
					$link=$divlink->href;
			}
			
			$link=htmlspecialchars_decode($link);
			//$linnk='http://www.pisocompartido.com/habitacion/490701/madrid-madrid_capital_zona_urbana/parrilla?f=all';
			//echo "<br/>Link: ".$link;

				if ($link!="")
				{
					$htmlanuncio = new simple_html_dom();

						$htmlanuncio->load_file($link);
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('h1',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;

						
						$divdescricao=$divconteudo->find('span[class=txt-descripcion]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						
						$divcaracteristicas=$divconteudo->find('div[class=contenedor-icon-descripcion-long]',0);
						if (sizeof($divcaracteristicas)>0)
						{
							$tablecaracteristicas=$divcaracteristicas->plaintext;
							$divcaracteristicas=$divconteudo->find('div[class*=contenedor-icon-descripcion-piso-long]',0);
							if (sizeof($divcaracteristicas)>0)
							{
								$tablecaracteristicas=$tablecaracteristicas.$divcaracteristicas->plaintext;
							}
						}
						
						$divpreco=$divconteudo->find('span[class=txt-precio]',0);
						if (sizeof($divpreco)>0)
							$preco=$divpreco->plaintext;
						
						$linkcontacto=$divconteudo->find('span[class=load-telefono]',0);
						if (sizeof($linkcontacto)>0)
							$contacto=$linkcontacto->plaintext;
						
						
						$divAnuncio=$divconteudo->find('input[id=idAnuncio]',0);
						if (sizeof($divAnuncio)>0)
							$idAnuncio=$divAnuncio->value;
						
						$divTipo=$divconteudo->find('input[id=tipoAnuncio]',0);
						if (sizeof($divTipo)>0)
							$Tipo=$divTipo->value;
						
						/*
						$divanuncio_key=$divconteudo->find('input[id=anuncio_key]',0);
						if (sizeof($divanuncio_key)>0)
							$anuncio_key=$divanuncio_key->value;
						
						$dividOrigen=$divconteudo->find('input[id=idOrigen]',0);
						if (sizeof($dividOrigen)>0)
							$idOrigen=$dividOrigen->value;
						
						$divid_hab=$divconteudo->find('input[name=id_hab]',0);
						if (sizeof($divid_hab)>0)
							$id_hab=$divid_hab->value;
						
						$divid_pisoscom=$divconteudo->find('input[name=id_pisoscom]',0);
						if (sizeof($divid_pisoscom)>0)
							$id_pisoscom=$divid_pisoscom->value;
						*/
						
						$query = http_build_query(array(
								'id_anuncio' => $idAnuncio,
								'type' => $Tipo,
								/*
								'anuncio_key' => $anuncio_key,
								'origenanuncio' => $idOrigen,
								'id_hab' => $id_hab,
								'id_pisoscom' => $id_pisoscom,
								'nombre' => '',
								'idexterno' => '',
								'text_mensaje' => 'Estoy interesad@ en este anuncio, me gustaría tener más información.'
								*/
							));
						$request = array(
						'http' => array(
								'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
							"Content-Length: ".strlen($query)."\r\n".
							"User-Agent:MyAgent/1.0\r\n",
							'method' => 'POST',
							'content' => $query,
						)
						);

						$context = stream_context_create($request);
						//echo "<hr>";
						//echo $query;
						
						$htmlcontacto = file_get_html('https://www.pisocompartido.com/ajax/datosAnuncianteAjax/', false, $context);
						
						//echo $htmlcontacto;
						//echo "<hr>";
						//?{"nombre":"Anunciante","prefijo":"34","telefono":"934673519","parrilla":false,"status":"OK"}
						$postelefone=strpos($htmlcontacto, "telefono");
						//echo "(".$postelefone.")";
						if ($postelefone !== false)
						{
							$posparrilla=strpos($htmlcontacto,"parrilla",$postelefone);
							//echo "(".$posparrilla.")";
							$contacto=substr($htmlcontacto,$postelefone, $posparrilla-$postelefone);
							$contacto=str_replace(":","",$contacto);
							$contacto=str_replace(",","",$contacto);
							$contacto=str_replace('"',"",$contacto);
							$contacto=str_replace("telefono","",$contacto);
							
						}
						/*
						$arrcontacto = json_decode($htmlcontacto);
						
						//print_r((array) json_decode($htmlcontacto));
						print_r("<pre>");
						print_r($arrcontacto);
						print_r("</pre>");
						$contacto=$arrcontacto->telefono;
						
						$htmlcontacto->clear(); 
						unset($htmlcontacto);
						*/
						/*
						if (limpa($contacto)=="")
						{
							$linkcontacto=$divconteudo->find('span[class=load-telefono]',0);
							//echo "<hr>".$linkcontacto;
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
							if ($contacto!="")
							{
								$contacto=str_replace("javascript","",$contacto);
								$contacto=str_replace(":","",$contacto);
								$contacto=str_replace(";","",$contacto);
								$contacto=str_replace("llamar_ficha","",$contacto);
								$contacto=str_replace("(","",$contacto);
								$contacto=str_replace(")","",$contacto);
								$contacto=str_replace("'","",$contacto);
								//echo "<br>Contacto: (".$contacto.")";
							}
						}
						*/
						
						$divnome=$divconteudo->find('span[class=txt-nombre]',0);
						if (sizeof($divnome)>0)
							$nome=$divnome->plaintext;
										
						$htmlanuncio->clear(); 
						unset($htmlanuncio);

				}

			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$caracteristicas=limpa($caracteristicas);
			$preco=limpa(str_replace("&#x20AC;","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			
			
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>contacto: ".$contacto;
			

			if ($contacto!="")
			{
				/*
				$sql="INSERT INTO [Scrapping] ";
				$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas], [Nome]) ";
				$sql=$sql." VALUES  ";
				$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."', '".$nome."') ";
				*/

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome);
				//echo "<br/>SQL: ".$sql;
				//ExecutaSQL($sql);
			
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
			

		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetSecondamano($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	
	$site="Secondamano";
	echo "<br>Entrou: $site";
	$html = new simple_html_dom();
	//$html->load_file($URL);
	$html=DaHtmlCtxHttps($URL);
	
	//$items = $html->find('li[class=item-vetrina]');
	//Correcção do código (dia 2017-06-11)
	//$items = $html->find('div[class=div-annuncio]');
	//Correcção do código (dia 2017-07-04)
	$items = $html->find('div[class*=caption-ricerca]');

	//echo "<hr>Sizeof: ".sizeof($items);
	foreach($items as $post) {
		
		$titulo="";
		$link="";
		$descricao="";
		$preco="";
		$data="";
		$contacto="";
		$caracteristicas="";
		$nome="";
	
		$divlink=$post->find('a',0);
		if (sizeof($divlink)>0)
			$link=$divlink->href;
		
		//echo "<br/>Link: ".$link;

		if ($link!="")
		{

				
				$continuaanuncio=true;

					$htmlanuncio=DaHtml($link,"");
					$continuaanuncio=true;

				
				if ($continuaanuncio==true)
				{
					$divconteudo = $htmlanuncio;
					
					$divtitulo=$divconteudo->find('span[id=cphMainPage_lblTitolo]',0);
					if (sizeof($divtitulo)>0)
						$titulo=$divtitulo->plaintext;
					
					$divdata=$divconteudo->find('span[id=cphMainPage_lblDataInizioVisibilita]',0);
					if (sizeof($divdata)>0)
					{
						$data=$divdata->plaintext;
						$data=str_replace("Ultima modifica:","",$data);
					}
					
					$divdescricao=$divconteudo->find('span[id=cphMainPage_lblTesto]',0);
					if (sizeof($divdescricao)>0)
						$descricao=$divdescricao->plaintext;
					
					$divpreco=$divconteudo->find('span[id=cphMainPage_lblPrezzo]',0);
					if (sizeof($divpreco)>0)
					{
						$preco=$divpreco->plaintext;
					}
					
					/*
					if (sizeof($divconteudo->find('div[class=detail_info_left]',0))>0)
						$caracteristicas=$caracteristicas.$divconteudo->find('div[class=detail_info_left]',0)->plaintext;
					if (sizeof($divconteudo->find('div[class=detail_info_up]',0))>0)
						$caracteristicas=$caracteristicas.$divconteudo->find('div[class=detail_info_up]',0)->plaintext;
					*/
					
					/*
					$divnome=$divconteudo->find('div[class=name_surname]',0);
					if (sizeof($divnome)>0)
						$nome=$divnome->plaintext;
					*/
					
					$divcontacto=$divconteudo->find('span[id=cphMainPage_phonefull]',0);
					if (sizeof($divcontacto)>0)
					{
						$contacto=$divcontacto->plaintext;
					}
									
					$htmlanuncio->clear(); 
					unset($htmlanuncio);
				}

		}

		
		$titulo=html_entity_decode(limpa($titulo));
		$link=limpa($link);
		$descricao=html_entity_decode(limpa($descricao));
		$replace= array("\\\"",);
		$descricao=str_replace($replace,"",$descricao);
		$descricao=str_replace("'","",$descricao);
		$preco=html_entity_decode(limpa($preco));
		$data=limpa($data);
		$data=str_replace("Annuncio aggiornato il ","",$data);
		$data=str_replace("listing updated on ","",$data);
		$contacto=limpa(trim($contacto));
		$caracteristicas=html_entity_decode(trataplicas($caracteristicas));
		$caracteristicas=str_replace("'","",$caracteristicas);

		
		/*
		echo "<br/>Link: ".$link;
		echo "<br/>titulo: ".$titulo;
		echo "<br/>descricao: ".$descricao;
		echo "<br/>preco: ".$preco;
		echo "<br/>contacto: ".$contacto;
		*/

		if (($contacto!=""))
		{
			/*
			$sql="INSERT INTO [Scrapping] ";
			$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase], [Caracteristicas]) ";
			$sql=$sql." VALUES  ";
			$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."','".$caracteristicas."') ";
			*/

			//$caracteristicas=$contacto; $contacto="";
			InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','',''); //Caracteristicas
	
			ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
		}
		

	}
	
	$html->clear(); 
	unset($html);

}

function GetSubito($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="Subito";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=items_listing]',0);


			foreach($divul->find('li') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
				$cidade="";
			
		
				$divlink=$li->find('a',0);
				if (sizeof($divlink)>0)
					$link=$divlink->href;
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							$htmlanuncio->load_file($link);
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('time',1);
							if (sizeof($divdata)>0)
								$data=$divdata->attr['datetime'];
							
							$divdescricao=$divconteudo->find('div[class=description]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;
							
							$divpreco=$divconteudo->find('td[class=details_value price]',0);
							if (sizeof($divpreco)>0)
								$preco=$divpreco->plaintext;
							
							$divcidade=$divconteudo->find('td[class="details_value"]',1);
							if (sizeof($divcidade)>0)
								$cidade=$divcidade->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=adv_phone_full]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa($titulo);
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$cidade=ltrim(limpa($cidade));
				
				
				/*
				echo "<hr>";
				echo "<br>titulo: $titulo";
				echo "<br>descricao: $descricao";
				echo "<br>Preco: $preco";
				echo "<br>data: $data";
				echo "<br>contacto: $contacto";
				echo "<br>cidade: $cidade";
				*/

				if ($contacto!="")
				{

					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyPiso($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyPiso";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.easypiso.com";
				
				$link=$linkbase.$li->attr['data-url'];
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							//$htmlanuncio->load_file($link);
							$htmlanuncio = dactxEasyPiso("EasyPiso",$link,"https://www.easypiso.com/m/login");
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('span[class=detail__footertext]',1);
							if (sizeof($divdata)>0)
							{
								$divdata->plaintext;
								$data=str_replace("Publicado el ","",$data);
							}
							
							$divdescricao=$divconteudo->find('section[class=detail]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;
							
							if ($titulo=="")
							{
								$divtitulo=$divconteudo->find('section[class=detail]',0);
								if (sizeof($divtitulo)>0)
									$divtitulo=$divtitulo->find('h4',0);
								if (sizeof($divtitulo)>0)
									$titulo=$divtitulo->plaintext;
							}
							
							$preco=$divconteudo->find('p[class=price__cost]',0)->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyQuarto($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyQuarto";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.easyquarto.com.pt/";
				
				$link=$linkbase.$li->attr['data-url'];
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							//$htmlanuncio->load_file($link);
							$htmlanuncio = dactxEasyPiso("EasyStanza",$link,"https://www.easyquarto.com.pt//m/login");
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('span[class=detail__footertext]',1);
							if (sizeof($divdata)>0)
							{
								$data=$divdata->plaintext;
								$data=str_replace("Publicado el ","",$data);
							}
							
							$divdescricao=$divconteudo->find('section[class=detail]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;
							
							if ($titulo=="")
							{
								$divtitulo=$divconteudo->find('section[class=detail]',0);
								if (sizeof($divtitulo)>0)
									$divtitulo=$divtitulo->find('h4',0);
								if (sizeof($divtitulo)>0)
									$titulo=$divtitulo->plaintext;
							}
							
							$divpreco=$divconteudo->find('p[class=price__cost]',0);
							if (sizeof($divpreco)>0)
								$preco=$divpreco->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyRoomMate($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyRoomMate";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://uk.easyroommate.com";
				
				$link=$linkbase.$li->attr['data-url'];
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							//$htmlanuncio->load_file($link);
							$htmlanuncio = dactxEasyPiso("EasyStanza",$link,"https://uk.easyroommate.com/m/login");
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('span[class=detail__footertext]',1);
							if (sizeof($divdata)>0)
							{
								$data=$divdata->plaintext;
								$data=str_replace("Publicado el ","",$data);
							}
							
							$divdescricao=$divconteudo->find('section[class=detail]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;
							
							if ($titulo=="")
							{
								$divtitulo=$divconteudo->find('section[class=detail]',0);
								if (sizeof($divtitulo)>0)
									$divtitulo=$divtitulo->find('h4',0);
								if (sizeof($divtitulo)>0)
									$titulo=$divtitulo->plaintext;
							}
							
							$divpreco=$divconteudo->find('p[class=price__cost]',0);
							if (sizeof($divpreco)>0)
								$preco=$divpreco->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyStanza($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyStanza";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.easystanza.it";
				
				$link=$linkbase.$li->attr['data-url'];
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							//$htmlanuncio->load_file($link);
							$htmlanuncio = dactxEasyPiso("EasyStanza",$link,"https://www.easystanza.it/m/login");
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('span[class=detail__footertext]',1);
							if (sizeof($divdata)>0)
							{
								$divdata->plaintext;
								$data=str_replace("Publicado el ","",$data);
							}
							
							$divdescricao=$divconteudo->find('section[class=detail]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;
							
							if ($titulo=="")
							{
								$divtitulo=$divconteudo->find('section[class=detail]',0);
								if (sizeof($divtitulo)>0)
									$divtitulo=$divtitulo->find('h4',0);
								if (sizeof($divtitulo)>0)
									$titulo=$divtitulo->plaintext;
							}
							
							$preco=$divconteudo->find('p[class=price__cost]',0)->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyWg($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyWg";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.easywg.de/";
				
				$link=$linkbase.$li->attr['data-url'];
				echo "<br/>Link: ".$link;


				if ($link!="")
				{
					$htmlanuncio = new simple_html_dom();
				
					//$htmlanuncio->load_file($link);
					$htmlanuncio = dactxEasyPiso("EasyStanza",$link,"https://www.easywg.de/m/login");
					$divconteudo = $htmlanuncio;
					
					$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
					if (sizeof($divtitulo)>0)
						$titulo=$divtitulo->plaintext;
					
					$divdata=$divconteudo->find('span[class=detail__footertext]',1);
					if (sizeof($divdata)>0)
					{
						$data=$divdata->plaintext;
						$data=str_replace("Publicado el ","",$data);
					}
					
					$divdescricao=$divconteudo->find('section[class=detail]',0);
					if (sizeof($divdescricao)>0)
						$descricao=$divdescricao->plaintext;
					
					if ($titulo=="")
					{
						$divtitulo=$divconteudo->find('section[class=detail]',0);
						if (sizeof($divtitulo)>0)
							$divtitulo=$divtitulo->find('h4',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
					}
					
					$divpreco=$divconteudo->find('p[class=price__cost]',0);
					if (sizeof($divpreco)>0)
						$preco=$divpreco->plaintext;
					
					$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
					if (sizeof($linkcontacto)>0)
						$contacto=$linkcontacto->plaintext;
									
					$htmlanuncio->clear(); 
					unset($htmlanuncio);
				
				}

				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				
				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetEasyAppartager($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EasyAppartager";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[class=search-results]',0);


			foreach($divul->find('li[class=listing__row]') as $li)
			{

				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.appartager.com/";
				
				$link=$linkbase.$li->attr['data-url'];
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							//$htmlanuncio->load_file($link);
							$htmlanuncio = dactxEasyPiso("EasyStanza",$link,"https://www.appartager.com/m/login");
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h1[class=breadcrumb__link]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$data=$divconteudo->find('span[class=detail__footertext]',1)->plaintext;
							$data=str_replace("Publicado el ","",$data);
							
							$descricao=$divconteudo->find('section[class=detail]',0)->plaintext;
							
							if ($titulo=="")
							{
								$divtitulo=$divconteudo->find('section[class=detail]',0);
								$divtitulo=$divtitulo->find('h4',0);
								$titulo=$divtitulo->plaintext;
							}
							
							$preco=$divconteudo->find('p[class=price__cost]',0)->plaintext;
							
							$linkcontacto=$divconteudo->find('span[id=phoneNumber]',0);
							if (sizeof($linkcontacto)>0)
								$contacto=$linkcontacto->plaintext;
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetKijiji($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="Kijiji";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('ul[id=search-result]',0);


			foreach($divul->find('li') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
				$nome="";
			
				$linkbase="http://www.kijiji.it";
				
				$divlink=$li->find('a',0);
				if (sizeof($divlink)>0)
					$link=$divlink->href;
				
				//echo "<br/>Link: ".$link;


				if ($link!="")
				{
					$htmlanuncio = new simple_html_dom();

					$htmlanuncio->load_file($link);
					$divconteudo = $htmlanuncio;
					
					$divtitulo=$divconteudo->find('h1',0);
					if (sizeof($divtitulo)>0)
						$titulo=$divtitulo->plaintext;
					

					$divdata=$divconteudo->find('span[class=vip__informations__value]',1);
					if (sizeof($divdata)>0)
					{
						$divdata->plaintext;
						$data=str_replace("Pubblicato ","",$data);
					}
					
					$divnome=$divconteudo->find('div[class=media__body]',0);
					if (sizeof($divnome)>0)
						$divnome=$divnome->find('div[class=title]',0);
						if (sizeof($divnome)>0)
							$nome=$divnome->plaintext;

					//$zona=$divconteudo->find('div[class=where]',0)->plaintext;
					
					$divdescricao=$divconteudo->find('div[class=vip__summary]',0);
					if (sizeof($divdescricao)>0)
						$descricao=$divdescricao->plaintext;

					$divpreco=$divconteudo->find('h2[class=vip-price]',0);
					if (sizeof($divpreco)>0)
						$preco=$divpreco->plaintext;
					
					$linkcontacto=$divconteudo->find('h3[class=modal-phone__text]',0);
					if (sizeof($linkcontacto)>0)
					{
						//$contacto=$linkcontacto->attr['data-inverted'];
						//$contacto=strrev($contacto);
						$contacto=$linkcontacto->plaintext;
					}
									
					$htmlanuncio->clear(); 
					unset($htmlanuncio);

				}

				
				
				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("&#x20AC;","",$preco));
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$nome=limpa(limpa(trim($nome)));
				$zona=limpa(limpa(trim($zona)));
				
				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				
				if ($contacto!="")
				{

					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome);
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				
				}

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetPortaPortese($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="PortaPortese";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('div[class=risultati-wrapper]',0);


			foreach($divul->find('div[class=risultato mod]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.portaportese.it";
				
				$divlink=$li->find('a',0);
				if (sizeof($divlink)>0)
					$link=$linkbase.$divlink->href;
				
				$divpreco=$li->find('span[class=attr-prezzo]',0);
				if (sizeof($divpreco)>0)
					$preco=$divpreco->plaintext;
				
				$divdata=$li->find('span[class=tipo-data]',0);
				if (sizeof($divdata)>0)
					$data=$divdata->plaintext;
				
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							$htmlanuncio->load_file($link);
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('h2[class=ins-title]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							/*
							$data=$divconteudo->find('div[class=date]',0)->plaintext;
							$data=str_replace("Pubblicato","",$data);
							*/
							
							//$nome=$divconteudo->find('div[class=seller]',0)->plaintext;

							//$zona=$divconteudo->find('div[class=where]',0)->plaintext;
							
							$divdescricao=$divconteudo->find('p[class=descIns]',0);
							if (sizeof($divdescricao)>0)
									$descricao=$divdescricao->plaintext;
							$divdescricao=$divconteudo->find('p[class=descIns]',1);
							if (sizeof($divdescricao)>0)
								$descricao=$descricao." ".$divdescricao->plaintext;

							/*
							$divpreco=$divconteudo->find('div[class=vip-price]',0);
							if (sizeof($divpreco)>0)
								$preco=$divpreco->plaintext;
							*/
							
							$linkcontacto=$divconteudo->find('a[title=Telefono]',0);

							if (sizeof($linkcontacto)>0)
							{
								$contacto=$linkcontacto->plaintext;
							}
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=utf8_encode(tirabarras(trataplicas(limpa($titulo))));
				$link=limpa($link);
				$descricao=utf8_encode(tirabarras(trataplicas(limpa($descricao))));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=utf8_encode(limpa($data));
				
				$contacto=limpa(trim($contacto));
				//$nome=limpa(limpa(trim($nome)));
				$zona=limpa(limpa(trim($zona)));
								
				if ($contacto!="")
				{

					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetPhosphoro($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="Phosphoro";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		$divul=$html->find('ul[class=lista_annunci]',0);


			foreach($divul->find('li[class=elemento_lista_annunci]') as $li)
			{
				
				$titulo="";
				$link="";
				$descricao="";
				$preco="";
				$data="";
				$contacto="";
			
				$linkbase="http://www.phosphoro.com";
				
				$divlink=$li->find('a',0);
				if (sizeof($divlink)>0)
					$link=$linkbase.$divlink->href;
				
				//echo "<br/>Link: ".$link;

				try {
					if ($link!="")
					{
						$htmlanuncio = new simple_html_dom();
						try {
							$htmlanuncio->load_file($link);
							$divconteudo = $htmlanuncio;
							
							$divtitulo=$divconteudo->find('div[class=sopra_titolo]',0);
							if (sizeof($divtitulo)>0)
								$titulo=$divtitulo->plaintext;
							
							$divdata=$divconteudo->find('div[class=row_periodo]',0);
							if (sizeof($divdata)>0)
								$data=$divdata->plaintext;
							
							$divdescricao=$divconteudo->find('div[class=text_box_note_dettagli]',0);
							if (sizeof($divdescricao)>0)
								$descricao=$divdescricao->plaintext;

							$divpreco=$divconteudo->find('div[class=tipo_singola]',0);
							if (sizeof($divpreco)>0)
								$preco=$divpreco->plaintext;
							
							$linkcontacto=$divconteudo->find('div[class=box_contatto_free]',0);
							if (sizeof($linkcontacto)>0)
							{
								$contacto=$linkcontacto->plaintext;
								$contacto=str_replace(" ","  ",$contacto);
							}
											
							$htmlanuncio->clear(); 
							unset($htmlanuncio);
						}
						catch(Exception $e) {

						}
					}
				}
				catch(Exception $e) {

				}
				
				
				$titulo=utf8_encode(tirabarras(trataplicas(limpa($titulo))));
				$link=limpa($link);
				$descricao=utf8_encode(tirabarras(trataplicas(limpa($descricao))));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=utf8_encode(limpa($data));
				
				$contacto=limpa(trim($contacto));
				//$nome=limpa(limpa(trim($nome)));
				$zona=limpa(limpa(trim($zona)));
							
				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
		//}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetCasaIT($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

		$site="CasaIT";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		//$divul=$html->find('div[id=searchResultsTbl]',0);
		$divul=$html->find('ul[class=listing-list]',0);


		//foreach($divul->find('div[class=resultBody]') as $li)
		foreach($divul->find('li') as $li)
		{
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
		
			$linkbase="http://www.casa.it";
			
			$divlink=$li->find('a',0);
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			if($link!="")
				$link=$linkbase.$link;
			
			//echo "<br/>Link: ".$link;


				if (($link!=""))
				{
					$htmlanuncio = new simple_html_dom();

						//$htmlanuncio->load_file($link);
						$htmlanuncio=DaHtmlCtxSimples($link);
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('h1',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						//$data=$divconteudo->find('div[class=date]',0)->plaintext;
						//$data=str_replace("Pubblicato","",$data);
						
						//$nome=$divconteudo->find('div[class=seller]',0)->plaintext;

						//$zona=$divconteudo->find('div[class=where]',0)->plaintext;
						
						$divdescricao=$divconteudo->find('div[id=description]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						

						$divpreco=$divconteudo->find('li[class=price]',0);
						if (sizeof($divpreco)>0)
							$preco=$divpreco->plaintext;
						
						$imgcontacto="";
						$contacto="";
						
						/*
						$licontacto=$divconteudo->find('li[class=phone]',0);
						if (sizeof($licontacto)>0)
						{
							$tagAcontacto=$licontacto->find('a',0);
							if (sizeof($tagAcontacto)>0)
								$contacto=$tagAcontacto->attr['data-value'];
						}
						*/
						
						$licontacto=$divconteudo->find('label[class=cut-number]',0);
						if (sizeof($licontacto)>0)
						{
							$contacto=$licontacto->plaintext;
						}
						$postelefone=strpos($contacto, "..");
						if (!($postelefone === false))
						{
							$posphone=strpos($divconteudo,'"phoneNumber":',0);
							$poslocal=strpos($divconteudo,'"type":',$posphone);
							$contacto=substr($divconteudo,$posphone, $poslocal-$posphone);
							$contacto=str_replace('phoneNumber',"",$contacto);
							$contacto=str_replace('"',"",$contacto);
							$contacto=str_replace(':',"",$contacto);
							$contacto=str_replace(',',"",$contacto);
						}
						
						$htmlanuncio->clear(); 
						unset($htmlanuncio);

				}

			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("€","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			$zona=limpa(limpa(trim($zona)));
			
			/*
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>data: ".$data;
			echo "<br/>contacto: ".$contacto;
			*/
			
			if ($contacto!="")
			{

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
		}
		
		$html->clear(); 
		unset($html);

}

function GetEMES($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="EMES";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('div[id=dnn_ctr1334_ModuleContent]',0);
		
		//$divul=$divul->find('div',1);

		foreach($divul->find('div[class=bordeoscuro]') as $li)
		{
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
		
			$linkbase="http://www.emes.es";
			
			$divlink=$li->find('a',0);
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			//echo "<br/>Link: ".$link;

			try {
				if (($link!=""))
				{
					$htmlanuncio = new simple_html_dom();
					try {
						$htmlanuncio->load_file($link);
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('span[id=dnn_ctr1338_dnnTITLE_lblTitle]',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						$divconteudo=$divconteudo->find('div[id=dnn_ctr1338_ModuleContent]',0);
						if (sizeof($divconteudo)>0)
						{
						
							$divdata=$divconteudo->find('div[class=fondoclaro]',0);
							
							if (sizeof($divdata)>0)
							{
								$data=$divdata->plaintext;
								$data=str_replace("Datos de contacto","",$data);
								$data=str_replace("Fecha Publicaci&oacute;n","",$data);
								$data=str_replace(" ","",$data);
								$data=str_replace(":","",$data);
							}
							
							$posnome=0;
							$divnome=$divconteudo->find('div[id=dnn_ctr1338_FichaAlojamiento_rptOfertaViviendas_ctl00_NombretitularDIV]',0);
							if (sizeof($divnome)>0)
							{
								$nome=$divnome->plaintext;
								$nome=str_replace("Nombre","",$nome);
								$nome=str_replace(" ","",$nome);
								$nome=str_replace(":","",$nome);
								$posnome=strpos($divconteudo->plaintext, $nome);
							}
							
							$postelefone=strpos($divconteudo->plaintext, "Tel", $posnome);
							$posemail=strpos($divconteudo->plaintext,"Correo",$posnome);
							$poslocal=strpos($divconteudo->plaintext,"Local",$posnome);
							if ($postelefone === false)
							{
								$email=substr($divconteudo->plaintext,$posemail, $poslocal-$posemail);
								$email=str_replace("Correo Electr&oacute;nico:","",$email);
							}
							else
							{
								$contacto=substr($divconteudo->plaintext,$postelefone, $posemail-$postelefone);
								$contacto=str_replace("Tel&eacute;fono de contacto:","",$contacto);
								$email=substr($divconteudo->plaintext,$posemail, $poslocal-$posemail);
								$email=str_replace("Correo Electr&oacute;nico:","",$email);
							}
							
							if ($contacto=="")
								$contacto=$email;
							
							
							$divlocalizacao=$divconteudo->find('div[id=dnn_ctr1338_FichaAlojamiento_rptOfertaViviendas_ctl00_Div1]',0);
							if (sizeof($divlocalizacao)>0)
							{
								$localizacao=$divlocalizacao->plaintext;
								$localizacao=str_replace("Localizaci&oacute;n:","",$localizacao);
							}

							
							$divdescricao=$divconteudo->find('div[id=dnn_ctr1338_FichaAlojamiento_rptOfertaViviendas_ctl00_DivComentarios]',0);
							if (sizeof($divdescricao)>0)
							{
								$descricao=$divdescricao->plaintext;
								$descricao=str_replace("Comentarios","",$descricao);
							}
							
							$descricao=$localizacao."|".$descricao;
							
							$postipo=0;
							$divtipo=$divconteudo->find('h3',0);
							if (sizeof($divtipo)>0)
							{
								$tipo=$divtipo->plaintext;
								$postipo=strpos($divconteudo->plaintext, $tipo);
							}
							
							$pospreco=strpos($divconteudo->plaintext, "Precio", $postipo);
							$poscarac=strpos($divconteudo->plaintext,"Caracter",$pospreco);
							$preco=substr($divconteudo->plaintext,$pospreco, $poscarac-$pospreco);
							$preco=str_replace("Precio:","",$preco);
							
							
						}
						
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
					catch(Exception $e) {

					}
				}
			}
			catch(Exception $e) {

			}
			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("€","",$preco));
			$preco=limpa(str_replace("&euro;","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			$zona=limpa(limpa(trim($zona)));
			
			
			/*
			
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>nome: ".$nome;
			echo "<br/>tipo: ".$tipo;
			echo "<br/>data: ".$data;
			echo "<br/>contacto: ".$contacto;
			echo "<br/>email: ".$email;
			*/
			
			if (($contacto!="") || ($email!=""))
			{

				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
		}
		
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetOcasiao($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="Ocasiao";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('dl[class=search-results]',0);


		foreach($divul->find('div[class=search_wrapper]') as $li)
		{
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
		
			$linkbase="http://www.ocasiao.pt";
			
			$divlink=$li->find('a',0);
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			$divdata=$li->find('div[class=search-info]',0);
			if (sizeof($divdata)>0)
				$data=$divdata->href;
			
			//echo "<br/>Link: ".$link;

			try {
				if (($link!="") && (strpos($link,"www.ocasiao.pt")>0))
				{
					$htmlanuncio = new simple_html_dom();
					try {
						$htmlanuncio->load_file($link);
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$divconteudo->find('h1',0);
						if (sizeof($divtitulo)>0)
							$titulo=$divtitulo->plaintext;
						
						//$data=$divconteudo->find('div[class=date]',0)->plaintext;
						//$data=str_replace("Pubblicato","",$data);
						
						//$nome=$divconteudo->find('div[class=seller]',0)->plaintext;

						//$zona=$divconteudo->find('div[class=where]',0)->plaintext;
						
						$divdescricao=$divconteudo->find('div[id=description]',0);
						if (sizeof($divdescricao)>0)
							$descricao=$divdescricao->plaintext;
						

						$divpreco=$divconteudo->find('div[class=item_price]',0);
						if (sizeof($divpreco)>0)
							$preco=$divpreco->plaintext;
						
						$imgcontacto="";
						$contacto="";
						
						$linkcontacto=$divconteudo->find('div[id=adv_contact]',0);
						if (sizeof($linkcontacto)>0)
						{
							$tagimgcontacto=$linkcontacto->find('img',0);
							if (sizeof($tagimgcontacto)>0)
								$contacto=$tagimgcontacto->attr['src'];
						}
						
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					}
					catch(Exception $e) {

					}
				}
			}
			catch(Exception $e) {

			}
			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("€","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			$zona=limpa(limpa(trim($zona)));
			
			/*
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>data: ".$data;
			echo "<br/>contacto: ".$contacto;
			*/
			
			if ($contacto!="")
			{
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
		}

		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetCustoJusto($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {

	try
	{
		$site="CustoJusto";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		$divul=$html->find('div[id=dalist]',0);
		
		//$divul=$divul->find('div',1);

		foreach($divul->find('a') as $li)
		{
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
		
			$linkbase="http://www.custojusto.pt";
			
			$link=$li->href;
		
			//echo "<br/>xLink: ".$link;

			//try {
				if (($link!=""))
				{
					
					$htmlanuncio = new simple_html_dom();
					//try {
						$htmlanuncio->load_file($link);
						$divconteudo = $htmlanuncio;
						
						$divtitulo=$htmlanuncio->find('h1[class=words]',0);
						if (sizeof($divtitulo)>0)
						{
							$titulo=$divtitulo->plaintext;
						}
						
						$divtitulo=$divconteudo->find('h1[class=words]',0);
						if (sizeof($divtitulo)>0)
						{
							$titulo=$divtitulo->plaintext;
						}
						
						$divpreco=$divconteudo->find('span[class=real-price]',0);
						if (sizeof($divpreco)>0)
						{
							$preco=$divpreco->plaintext;
							$preco=str_replace("€","",$preco);
						}
									
						$posnome=0;
						$divnome=$divconteudo->find('h3[class=user words]',0);
						if (sizeof($divnome)>0)
						{
							$nome=$divnome->plaintext;
						}
						
						$divcontacto=$divconteudo->find('button[id=phone-button]',0);
						if (sizeof($divcontacto)>0)
						{
							$dataphone=$divcontacto->attr['data-phone'];
							$contacto=$dataphone;
							$contacto=ConverteTelefoneCustoJusto($contacto);
							// O número é obtido na aplicação Windows
						}
						
						$divdescricao=$divconteudo->find('p[class=lead words]',0);
						if (sizeof($divdescricao)>0)
						{
							$descricao=$divdescricao->plaintext;
						}
						
						$divdescricao=$divconteudo->find('p[class="lead words"]',0);
						if (sizeof($divdescricao)>0)
						{
							$descricao=$divdescricao->plaintext;
						}
						$htmlanuncio->clear(); 
						unset($htmlanuncio);
					//} catch(Exception $e) { }
				}
			//} catch(Exception $e) {	}
			
			
			$titulo=limpa(str_replace("&#x20AC;","",$titulo));
			$link=limpa($link);
			$descricao=limpa($descricao);
			$preco=limpa(str_replace("€","",$preco));
			$preco=limpa(str_replace("&euro;","",$preco));
			$data=limpa($data);
			$contacto=limpa(trim($contacto));
			$zona=limpa(limpa(trim($zona)));
			
			/*
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>contacto: ".$contacto;
			*/
			
			if ($contacto!="")
			{
				InsereScrapping($site, $titulo, $descricao, $data, $preco, "", $link, $zona, $idScrappingList, $offer_type, $country, "", "", "", $contacto, $dataphone);
				
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}
			
		}
		
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	  //echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetMilAnuncios($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="MilAnuncios";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		
		$URL=str_replace("http://","https://",$URL);
		
		echo "<br>URL: ".$URL;
		//$html=DaHtmlCtxHttpsBot($URL,'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
		$html=DaHtmlCtxHttpsBot($URL,'Googlebot/2.1 (+http://www.google.com/bot.html)');
		//$html=get_web_page($URL);
		
		
		//var_dump($html);
		
		echo "<hr>".$html;

		$items = $html->find('div[class=aditem]');
		print_r("<pre>");
		print_r($items);
		print_r("</pre>");
		echo "<br/>items: ".sizeof($items);
		foreach($items as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="https://www.milanuncios.com";

			
			$sublink="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}

			$linkfinal="";
			$divlink=$li->find('a[class=aditem-detail-title]',0);
			if (sizeof($divlink)>0)
			{
				$linkfinal=$divlink->href;
			}
			
			$link="";

			if (($linkfinal!=""))
			{
				$link=$linkbase."/".$sublink."/".$linkfinal;
			}
			
			
			//$link=$li->find('a',0)->href;
			
			
			//aditem-detail-title

			//echo "<br/>sublink: ".$sublink;
			//echo "<br/>linkfinal: ".$linkfinal;
			echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				$htmlanuncio=DaHtmlCtxHttps($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('div[class=pagAnuTituloBox]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=pagAnuPrecioTexto]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}

				$contacto="";
				$caracteristicas="";
				$divcontacto=$divconteudo->find('button[id=pagAnuShowContactForm]',0);
				if (sizeof($divcontacto)>0)
				{
					$dataphone=$divcontacto->attr['onclick'];
					$dataphone=str_replace("od('","",$dataphone);
					$dataphone=str_replace("')","",$dataphone);
					
					if ($dataphone!="")
					{
						$LinkPaginaContacto='http://www.milanuncios.com/datos-contacto/?id='.$dataphone;
						echo "<br/>LinkPaginaContacto: ".$LinkPaginaContacto;
						$contacto=TrataContactoMilAnuncios($LinkPaginaContacto);
						if ($contacto=="")
						{
							$contacto="";
							$caracteristicas=$LinkPaginaContacto;
						}
					}
				}

				$divdescricao=$divconteudo->find('div[class=pagAnuCuerpoAnu]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				echo "<br/>caracteristicas: ".$caracteristicas;
				
				
				if ($contacto!="")
				{

					//$caracteristicas=$contacto; $contacto="";
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','',$caracteristicas); //Caracteristicas
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetMilAnunciosHTML($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList, $htmlcode) {
	try
	{
		$site="MilAnuncios HTML";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html = str_get_html($htmlcode);
		

		$items = $html->find('div[class=aditem]');
		echo "<br/>items: ".sizeof($items);
		foreach($items as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="https://www.milanuncios.com";

			
			$sublink="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}

			$linkfinal="";
			$divlink=$li->find('a[class=aditem-detail-title]',0);
			if (sizeof($divlink)>0)
			{
				$linkfinal=$divlink->href;
			}
			
			$link="";

			if (($linkfinal!=""))
			{
				$link=$linkbase."/".$sublink."/".$linkfinal;
			}
			
			
			//$link=$li->find('a',0)->href;
			
			
			//aditem-detail-title

			//echo "<br/>sublink: ".$sublink;
			//echo "<br/>linkfinal: ".$linkfinal;
			echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio=DaHtmlCtxHttps($link);
				//$htmlanuncio=DaHtmlCtxHttpsBot($link,'Googlebot/2.1 (+http://www.google.com/bot.html)');
				ini_set('user_agent', 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0.1) Gecko/20100101 Firefox/10.0.1');
				$htmlanuncio=DaHtmlCtxHttps($link);
				//$htmlanuncio=GOOGLE_GET($link);
				
				$divconteudo = $htmlanuncio;
				
				echo "<hr>".$divconteudo;

				$divtitulo=$divconteudo->find('div[class=pagAnuTituloBox]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=pagAnuPrecioTexto]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}

				$contacto="";
				$caracteristicas="";
				$divcontacto=$divconteudo->find('button[id=pagAnuShowContactForm]',0);
				if (sizeof($divcontacto)>0)
				{
					$dataphone=$divcontacto->attr['onclick'];
					$dataphone=str_replace("od('","",$dataphone);
					$dataphone=str_replace("')","",$dataphone);
					
					if ($dataphone!="")
					{
						$LinkPaginaContacto='http://www.milanuncios.com/datos-contacto/?id='.$dataphone;
						echo "<br/>LinkPaginaContacto: ".$LinkPaginaContacto;
						$contacto=TrataContactoMilAnuncios($LinkPaginaContacto);
						if ($contacto=="")
						{
							$contacto="";
							$caracteristicas=$LinkPaginaContacto;
						}
					}
				}

				$divdescricao=$divconteudo->find('div[class=pagAnuCuerpoAnu]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				echo "<br/>caracteristicas: ".$caracteristicas;
				
				
				if ($contacto!="")
				{

					//$caracteristicas=$contacto; $contacto="";
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','',$caracteristicas); //Caracteristicas
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
	
		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetImmobilienscout24($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		
		$site="Immobilienscout24";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxHttps($URL);

		//echo "<hr>";
		$items=$html->find('li[class=result-list__listing]');
		

		foreach($items as $li)
		{
		
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.immobilienscout24.de";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			
			//Tem de ter uma imagem destas no post "https://www.static-immobilienscout24.de/statpic/resultlist/6f685833c34693e2a10288befa23d75a_private_badge_orange.png"
			$divlogo=$li->find('img[class=result-list-entry__brand-logo--private]',0);
			$poslogo=0;
			if (sizeof($divlogo)>0)
			{
				$logo=$divlogo->src;
				//$logo=$divlogo->attr['lazy-src'];
				$poslogo=strpos("private_badge_orange",$logo);
				$poslogo=1;
			}

			//echo "<br/>Link: ".$link;
			//echo "<br/>poslogo: ".$poslogo;
			//try {
			if (($link!=""))// && ($poslogo!=0))
			{
				
				echo "<br/>Link: ".$link;

				$htmlanuncio = new simple_html_dom();
				$htmlanuncio->load_file($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1[id=expose-title]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=is24-value]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
				
				$divcontacto=$divconteudo->find('div[class=is24-phone-number]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
					$contacto=str_replace("Mobil","",$contacto);
					$contacto=str_replace("Telefon","",$contacto);
					$contacto=str_replace("-","",$contacto);
					$contacto=str_replace("-","",$contacto);
					$contacto=str_replace("-","",$contacto);
					$contacto=str_replace("-","",$contacto);
					
					$contacto=str_replace("","",$contacto);
					$contacto=utf8_encode($contacto);
					$contacto=str_replace(":","",$contacto);
				}
				
				if ($contacto=="")
				{
					$postelefone=strpos($divconteudo, '<div class="is24-phone-number');
					//echo "(".$postelefone.")";
					if ($postelefone !== false)
					{
						$posdiv=strpos($divconteudo,"</div>",$postelefone);
						//echo "(".$posdiv.")";
						$contacto=substr($divconteudo,$postelefone, $posdiv-$postelefone);
						//echo "(".$contacto.")";
						$contacto=strip_tags($contacto);
						//echo "(".$contacto.")";
						$contacto=str_replace("Telefon","",$contacto);
						$contacto=str_replace(":","",$contacto);
						$contacto=str_replace(" ","",$contacto);
					}
				}
				
				if ($contacto=="")
				{
					$postelefone=strpos($divconteudo, 'contactNumber');
					//echo "(".$postelefone.")";
					if ($postelefone !== false)
					{
						$posdiv=strpos($divconteudo,'"}',$postelefone);
						//echo "(".$posdiv.")";
						$contacto=substr($divconteudo,$postelefone, $posdiv-$postelefone);
						//echo "(".$contacto.")";
						$contacto=strip_tags($contacto);
						//echo "(".$contacto.")";
						$contacto=str_replace('contactNumber','',$contacto);
						$contacto=str_replace('"','',$contacto);
						$contacto=str_replace(":","",$contacto);
						$contacto=str_replace(" ","",$contacto);
						$contacto=str_replace("{","",$contacto);
						$contacto=str_replace("}","",$contacto);
					}
				}
				

				$divdescricao=$divconteudo->find('pre[class=is24qa-objektbeschreibung]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}

		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetStudenten($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Studenten";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		foreach($html->find('div[class=ltitel]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.studenten-wg.de";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				$htmlanuncio->load_file($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('div[class=wg-anzdiv1b]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=pr]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}

				$contacto="";
				$IDRegisto=str_replace($linkbase,"",str_replace(".html","",str_replace("mietangebot_","",$link)));
				$LinkPaginaContacto="http://static.studenten-wg.info/images/?t=h&id".$IDRegisto;
				//$contacto=TrataNumerosStudenten($LinkPaginaContacto);
				echo "<hr>".$LinkPaginaContacto."<hr>";

				$divdescricao=$divconteudo->find('div[class=wg-detailinfoblockcont]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				if ($LinkPaginaContacto!="")
				{
					$caracteristicas=$LinkPaginaContacto; $contacto="";
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','','',$caracteristicas); //Caracteristicas - $LinkPaginaContacto
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);

				}


			}

		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetPisosCom($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Pisos.Com";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		$html->load_file($URL);

		foreach($html->find('div[class="titlePrice clearfix"]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.pisos.com";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('span[class=jsPrecioH1]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
				
				$divcontacto=$divconteudo->find('div[class=phone]',0);
						if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
					$posi=strpos($contacto,"Ver");
					if ($posi !== false)
						$contacto="";
					$posi=strpos($contacto,"de");
					if ($posi !== false)
						$contacto="";
				}
				else
				{
					$divcontacto=$divconteudo->find('span[class=number]',0);
					if (sizeof($divcontacto)>0)
					{
						$contacto=$divcontacto->plaintext;
						$posi=strpos($contacto,"Ver");
						if ($posi !== false)
							$contacto="";
						$posi=strpos($contacto,"de");
						if ($posi !== false)
							$contacto="";
					}
				}

				$divdescricao=$divconteudo->find('div[class=description]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$preco=limpa(str_replace("/mes","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}
			}

		}
		$html->clear(); 
		unset($html);
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetVibbo($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Vibbo";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('div[class=basicList]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.vibbo.com";
			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$link="http:".$divsublink->href;
			}

			if (($link!=""))
			{
				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('span[class=price]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
				
				$divcontacto=$divconteudo->find('div[class=tel]',0);
				if (sizeof($divcontacto)>0)
				{
					$img1=$divcontacto->find('img[alt=Tel]',0);
					$img2=$divcontacto->find('img[alt=Tel]',1);
					$img3=$divcontacto->find('img[alt=Tel]',2);
					$contacto="";
					$imagem1="";
					if (sizeof($img1)>0)
						$imagem1=$img1->attr['src'];
					if (sizeof($img2)>0)
						$imagem2=$img2->attr['src'];
					if (sizeof($img3)>0)
						$imagem3=$img3->attr['src'];
					$imagem1=str_replace('.gif','',str_replace('//images.vibbo.com/numbers/55/','',$imagem1));
					//echo "<br/>imagem1: ".$imagem1;
					$imagem2=str_replace('.gif','',str_replace('//images.vibbo.com/numbers/55/','',$imagem2));
					//echo "<br/>imagem2: ".$imagem2;
					$imagem3=str_replace('.gif','',str_replace('//images.vibbo.com/numbers/55/','',$imagem3));
					//echo "<br/>imagem3: ".$imagem3;
					$sqlcontacto="SELECT CONCAT((SELECT numero FROM formatting_phone_img_vibbo WHERE imagem_site='".$imagem1."'),(SELECT numero FROM formatting_phone_img_vibbo WHERE imagem_site='".$imagem2."'), (SELECT numero FROM formatting_phone_img_vibbo WHERE imagem_site='".$imagem3."'))  as Result";
					//echo "<br/>sqlcontacto: ".$sqlcontacto;
					$contacto=daValorAtributo($sqlcontacto);
				}

				$divdescricao=$divconteudo->find('p[id=descriptionText]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));
				
				$link=limpa(str_replace("?ca=28_s&amp;st=a&amp;c=64","",$link));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				if (($contacto!=""))
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}
			}
		}
		$html->clear(); 
		unset($html);
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetCasascm($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Casascm";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('div[class=anuncio]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.casascm.pt";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$link=$divsublink->href;
			}
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h2[id=AdTitle]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('span[id=PriceSpan]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
								
				$divcontacto=$divconteudo->find('h5[class=show-for-medium-up]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
				}
				
				$divdescricao=$divconteudo->find('h5[class=descricao]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetCasasSapo($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="CasaSapo";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('div[class=searchResultProperty]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://casa.sapo.pt";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('p[class=detailPropertyTitle]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('p[class=detailPropertyPrice]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
								
				$divcontacto=$divconteudo->find('div[class=contactDetails]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
					$contacto=str_replace("Telefone","",$contacto);
					$contacto=str_replace("Telemóvel","",$contacto);
					$contacto=str_replace("Telem&oacute;vel","",$contacto);
					$contacto=str_replace("TelemÃ³vel","",$contacto);
					$contacto=str_replace(":","",$contacto);
				}
				
				$divdescricao=$divconteudo->find('div[id=pDescription]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/
				

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetAffitto($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Affitto";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('article[class=box_annuncio]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('p[class=prezzo]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
					$preco=str_replace("Prezzo","",$preco);
					$preco=str_replace(":","",$preco);
					$preco=str_replace("Spese incluse","",$preco);
				}
								
				$divcontacto=$divconteudo->find('span[class=phone_full]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
				}
				
				$divdescricao=$divconteudo->find('div[class=carr_dettagli]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetVivaStreet($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="VivaStreet";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('tr[class*=classified]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="";

			$link="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[id=title_price]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
								
				$divcontacto=$divconteudo->find('div[id=contact_phone_right_wrapper]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->attr['data-phone-number'];
				}
				
				$divdescricao=$divconteudo->find('div[class=shortdescription]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetGabinoHome($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="GabinoHome";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('div[class="list_advert"]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="";

			$link="";
			$divsublink=$li->find('a[class=external]',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			

			//echo "<br/>xLink: ".$link;
			$link=str_replace("gabinohome.com","gabinohome.mobi",$link);

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=price]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
								
				$divcontacto=$divconteudo->find('a[id=call]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->href;
					if ($contacto=="")
						$contacto=$divcontacto->plaintext;
					$contacto=limpa(str_replace("tel:","",$contacto));
				}
				
				$divdescricao=$divconteudo->find('div[class=block]',1);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				

				if ($contacto!="")
				{
					/*
					$sql="INSERT INTO [Scrapping] ";
					$sql=$sql." ([Site],[Titulo],[Descricao],[Data],[Preco],[Contacto],[Link], [Zona], [URLBase]) ";
					$sql=$sql." VALUES  ";
					$sql=$sql." ('$site','".$titulo."','".$descricao."','".$data."','".$preco."','".$contacto."','".$link."','".$zona."','".$URL."') ";
					//echo "<br/>SQL: ".$sql;
					//ExecutaSQL($sql);
					*/
					
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto,$link, $zona, $idScrappingList, $offer_type, $country);
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetWohnungsboerse($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Wohnungsboerse";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxSimples($URL);

		foreach($html->find('td[class="headline"]') as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.wohnungsboerse.net";

			$link="";
			$divsublink=$li->find('a[class=nounderline]',0);
			if (sizeof($divsublink)>0)
			{
				$sublink=$divsublink->href;
			}
			
			if (($sublink!=""))
			{
				$link=$linkbase.$sublink;
			}
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxSimples($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h2',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('span[class=kaltmiete-value]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
				}
								
				$divcontacto=$divconteudo->find('div[class=contact-phone]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->plaintext;
				}
				
				$divdescricao=$divconteudo->find('table[class=estate_detail_table]',0);
				if (sizeof($divdescricao)>0)
				{
					$divdescricaofinal=$divdescricao->find('tr',-1);
					if (sizeof($divdescricaofinal)>0)
					{
						$descricao=$divdescricaofinal->plaintext;
					}
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$titulo=substr($titulo,0,190);
				$link=limpa($link);
				$descricao=limpa($descricao);
				$descricao=substr($descricao,0,1000);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$preco=limpa(str_replace("&nbsp;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				/*
				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
				*/
				

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}


			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetKalaydo($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Kalaydo";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxTempo($URL);

		$items = $html->find('li[class=result-list-item]');
		//echo "<br/>items: ".sizeof($items);
		foreach($items as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="http://www.kalaydo.de";

			$sublink="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$link=$divsublink->href;
			}
			
			

			//echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxTempo($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divtabpreco=$divconteudo->find('table[class=general-info]',0);
				if (sizeof($divtabpreco)>0)
				{
					$divpreco=$divtabpreco->find('td[class=big]',0);
					if (sizeof($divpreco)>0)
					{
						$preco=$divpreco->plaintext;
						$preco=str_replace("€","",$preco);
						$preco=utf8_encode($preco);
						$preco=str_replace("&euro;","",$preco);
					}
				}
				
				/*
				$divcontacto=$divconteudo->find('div[class=phone]',0);
				if (sizeof($divcontacto)>0)
				{
					/*
					$codigohtml=$divcontacto;
					echo $codigohtml;
					echo "<hr>";
					$posi_func=strpos($codigohtml,'<span');
					print_r($posi_func);
					echo "<hr>";
					//$posi_link=strpos($codigohtml,"<br>",$posi_func);
					//print_r($posi_link);
					//echo "<hr>";
					$contacto=substr($codigohtml,$posi_func);
					$contacto=strip_tags($contacto);
					
					$contacto=$divcontacto->plaintext;
					
				}
				*/
				if ($contacto=="")
				{
					$divcontacto=$divconteudo->find('div[class=phone]',0);
					if (sizeof($divcontacto)>0)
					{
						$contacto="";
						foreach($divcontacto->find('span') as $span)
						{
							$classe=$span->attr['class'];
							$classe=str_replace("markt_expose_attrPhone_char","",$classe);
							$classe=str_replace(" ","",$classe);
							$classe=str_replace("markt_char_","",$classe);
							switch (strtoupper($classe)) {
								case "ZERO":
									$contacto=$contacto."0";
									break;
								case "ONE":
									$contacto=$contacto."1";
									break;
								case "TWO":
									$contacto=$contacto."2";
									break;
								case "THREE":
									$contacto=$contacto."3";
									break;
								case "four":
									$contacto=$contacto."4";
									break;
								case "FIVE":
									$contacto=$contacto."5";
									break;
								case "SIX":
									$contacto=$contacto."6";
									break;
								case "SEVEN":
									$contacto=$contacto."7";
									break;
								case "EIGHT":
									$contacto=$contacto."8";
									break;
								case "NINE":
									$contacto=$contacto."9";
									break;
							} 
						}
					}
				}
				
				$divdescricao=$divconteudo->find('p[class=filtered]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;
			

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetIdealistaHTML($site, $offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList, $htmlcode) {
	
	echo "<br>EntrouHTML: $site";
	echo "<br>URL: $URL";
	$html = new simple_html_dom();
	$html = str_get_html($htmlcode);

	$continua=true;
	try
	{
		$items = $html->find('div[class=item]');
		$continua=true;
	}
	catch(Exception $e) {
	  $continua=false;
	  echo '<br>Erro: ' .$e->getMessage();
	}
	
	//echo "<br>Continua: ".$continua;
	
	$i=0;
	
	if ($continua==true)
	{
		foreach($items as $post) {
			
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";
			$caracteristicas="";
			$maps_link="";
			$photos=0;
			$rooms="";
			$neighborhood="";
			$source_date="";
			
			$tipoanuncio=" Particular";
			
			$divconteudo=$post;
			$i=$i+1;
		
			$divlink=$post->find('a',0);
			
			if (sizeof($divlink)>0)
				$link=$divlink->href;
			
			switch (strtoupper($site)) {
				case "IDEALISTA ES":
					$linkbase="http://www.idealista.com";
					break;
				case "IDEALISTA IT":
					$linkbase="http://www.idealista.it";
					break;
				case "IDEALISTA PT":
					$linkbase="http://www.idealista.pt";
					break;				
			} 
			$link=$linkbase.$link;
			echo "<br/>Link: ".$link;
		
			if (strpos($link,"pro/")>0)
				$link="";

			if ($link!="")
			{
					
				$divtitulo=$divconteudo->find('a',0);
				if (sizeof($divtitulo)>0)
					$titulo=$divtitulo->plaintext;
				
			
				$divdescricao=$divconteudo->find('div[class=item-description]',0);
				if (sizeof($divdescricao)>0)
					$descricao=$divdescricao->plaintext;
				
				$divpreco=$divconteudo->find('div[class=price-row]',0);
				if (sizeof($divpreco)>0)
				{
					$spanpreco=$divpreco->find('span',0);
					if (sizeof($spanpreco)>0)
						$preco=$spanpreco->plaintext;
				}
				
				if ($preco=="")
				{
					$divpreco=$divconteudo->find('p[class=price]',0);
					if (sizeof($divpreco)>0)
					{
						$preco=$divpreco->plaintext;
					}
				}
				
				$linkcontacto=$divconteudo->find('div[class="item-toolbar-contact]',0);
				if (sizeof($linkcontacto)>0)
					$contacto=$linkcontacto->plaintext;
				
				if ($contacto=="")
				{
					$linkcontacto=$divconteudo->find('div[class="first-phone]',0);
					if (sizeof($linkcontacto)>0)
						$contacto=$linkcontacto->plaintext;
				}
				
				/*
				$divmaps=$divconteudo->find('img[id="sMap"]',0);
				if (sizeof($divmaps)>0)
				{
					$maps_link=$divmaps->attr['src'];
				}
				
				$divphotos=$divconteudo->find('div[class=placeholder-multimedia image]');
				if (sizeof($divphotos)>=3)
				{
					$photos=1;
				}
				
				
				$divdate=$divconteudo->find('section[id=stats]',0);
				if (sizeof($divdate)>0)
				{
					$divsourcedate=$divdate->find('p',0);
					if (sizeof($divsourcedate)>0)
					{
						$source_date=$divsourcedate->plaintext;
					}
				}
				
				
				$divneighborhood=$divconteudo->find('div[id=addressPromo]',0);
				if (sizeof($divneighborhood)>0)
				{
					$divulneighborhood=$divneighborhood->find('ul',0);
					if (sizeof($divulneighborhood)>0)
						$neighborhood=$divulneighborhood->plaintext;
					else
						$neighborhood=$divneighborhood->plaintext;
				}
				*/
				
				$divrooms=$divconteudo->find('div[class=info-data]',0);
				if (sizeof($divrooms)>0)
				{
					$divspanrooms=$divrooms->find('span[class=txt-big]',1);
					if (sizeof($divspanrooms)>0)
						$rooms=$divspanrooms->plaintext;
				}
				
				
				/* Já saquei a info da listagem, agora vou ver na página */
				$htmlanuncio = new simple_html_dom();
				$htmlanuncio=GOOGLE_GET($link);
				$divconteudo = $htmlanuncio;
				
				//if ($i==1) echo "<hr>".$htmlanuncio;

				if ($titulo=="")
				{
					$divtitulo=$divconteudo->find('h1',0);
					if (sizeof($divtitulo)>0)
						$titulo=$divtitulo->plaintext;
				}
				
				if ($preco=="")
				{
				
					$divpreco=$divconteudo->find('p[class=price]',0);
					if (sizeof($divpreco)>0)
					{
						$preco=$divpreco->plaintext;
					}
				}
				
				if ($contacto=="")
				{
					$linkcontacto=$divconteudo->find('div[class="first-phone]',0);
					if (sizeof($linkcontacto)>0)
						$contacto=$linkcontacto->plaintext;
				}
				
				if ($maps_link=="")
				{
					$divmaps=$divconteudo->find('img[id="sMap"]',0);
					if (sizeof($divmaps)>0)
					{
						$maps_link=$divmaps->attr['src'];
					}
				}
				
				if ($photos==0)
				{
					$divphotos=$divconteudo->find('div[class=placeholder-multimedia image]');
					if (sizeof($divphotos)>=3)
					{
						$photos=1;
					}
				}
				
				if ($source_date=="")
				{
					$divdate=$divconteudo->find('section[id=stats]',0);
					if (sizeof($divdate)>0)
					{
						$divsourcedate=$divdate->find('p',0);
						if (sizeof($divsourcedate)>0)
						{
							$source_date=$divsourcedate->plaintext;
						}
					}
				}
				
				if ($neighborhood=="")
				{
					$divneighborhood=$divconteudo->find('div[id=addressPromo]',0);
					if (sizeof($divneighborhood)>0)
					{
						$divulneighborhood=$divneighborhood->find('ul',0);
						if (sizeof($divulneighborhood)>0)
							$neighborhood=$divulneighborhood->plaintext;
						else
							$neighborhood=$divneighborhood->plaintext;
					}
				}
				
				if ($rooms=="")
				{
					$divrooms=$divconteudo->find('div[class=info-data]',0);
					if (sizeof($divrooms)>0)
					{
						$divspanrooms=$divrooms->find('span[class=txt-big]',1);
						if (sizeof($divspanrooms)>0)
							$rooms=$divspanrooms->plaintext;
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
				
				
									
			}

			
			$titulo=limpa($titulo);
			$link=limpa($link);
			$descricao=limpa($descricao);
			$replace= array("\\\"",);
			$descricao=str_replace($replace,"",$descricao);
			$preco=limpa($preco);
			$data=limpa($data);
			$data=str_replace("Anúncio atualizado no dia","",$data);
			$contacto=limpa(trim($contacto));
			$neighborhood=limpa(trim($neighborhood));
			
			$source_date=str_replace("Anúncio atualizado no dia","",$source_date);
			$source_date=str_replace("Annuncio aggiornato il","",$source_date);
			$source_date=limpa(trim($source_date));
			
			$maps_link=limpa(trim($maps_link));
			$rooms=limpa(trim($rooms));
			
			
			/*
			echo "<br/>Link: ".$link;
			echo "<br/>titulo: ".$titulo;
			echo "<br/>descricao: ".$descricao;
			echo "<br/>preco: ".$preco;
			echo "<br/>contacto: ".$contacto;
			echo "<br/>source_date: ".$source_date;
			echo "<br/>neighborhood: ".$neighborhood;
			echo "<br/>maps_link: ".$maps_link;
			echo "<br/>rooms: ".$rooms;
			*/

			
			if (($link!=""))
			{
			  //InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country, $nome="", $floor="", $rooms="", $caracteristicas="", $obs="", $photos_available="", $address_neighborhood="", $source_date="")
				InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country,'','',$rooms, '', $maps_link, $photos, $neighborhood, $source_date, '1');
		
				ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
			}


		}
	} //if ($continua==true)
	
	$html->clear(); 
	unset($html);

}

function GetQuoka($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="Quoka";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxHttps($URL);

		$items = $html->find('li[class=qx_search-result__list-item]');
		//echo "<br/>items: ".sizeof($items);
		foreach($items as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="https://m.quoka.de";

			$sublink="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$link=$divsublink->href;
				$link=$linkbase.$link;
			}
			
			

			echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxHttps($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('div[class=prctyp]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
				}
				
				
				foreach($divconteudo->find('a[class*=qbtn-prime]') as $linksphone)
				{
					$atributocontacto=$linksphone->href;
					$posi=strpos($atributocontacto,"tel://");
					if ($posi !== false)
					{
						$contacto=str_replace("tel://",'',$atributocontacto);
					}
				}
				
				
				$divdescricao=$divconteudo->find('div[class=dtl-dsc]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));

				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

function GetFotoCasa($offer_type, $country, $URL, $zona, $DataIniScrappingList, $idScrappingList) {
	try
	{
		$site="FotoCasa";
		echo "<br>Entrou: $site";
		$html = new simple_html_dom();
		//$html->load_file($URL);
		$html=DaHtmlCtxHttps($URL);

		$items = $html->find('div[class=re-Searchresult-item]');
		//echo "<br/>items: ".sizeof($items);
		foreach($items as $li)
		{
			$titulo="";
			$link="";
			$descricao="";
			$preco="";
			$data="";
			$contacto="";

			$linkbase="https://www.fotocasa.es";

			$sublink="";
			$divsublink=$li->find('a',0);
			if (sizeof($divsublink)>0)
			{
				$link=$divsublink->href;
				$link=$linkbase.$link;
			}
			
			
			//Vamos procurar a ver se existe o re-Card-promotionLogo, se exitir, passa à frente
			$divpromotionLogo==$li->find('div[class=re-Card-promotionLogo]',0);
			if (sizeof($divpromotionLogo)>0)
			{
				$link="";
			}
			

			echo "<br/>xLink: ".$link;

			//try {
			if (($link!=""))
			{

				$htmlanuncio = new simple_html_dom();
				//$htmlanuncio->load_file($link);
				$htmlanuncio=DaHtmlCtxHttps($link);
				$divconteudo = $htmlanuncio;

				$divtitulo=$divconteudo->find('h1[class=property-title]',0);
				if (sizeof($divtitulo)>0)
				{
					$titulo=$divtitulo->plaintext;
				}

				$divpreco=$divconteudo->find('span[id=priceContainer]',0);
				if (sizeof($divpreco)>0)
				{
					$preco=$divpreco->plaintext;
					$preco=str_replace("€","",$preco);
					$preco=utf8_encode($preco);
					$preco=str_replace("&euro;","",$preco);
					$preco=str_replace("/","",$preco);
					$preco=str_replace("mes","",$preco);
				}

				$contacto="";
				$divcontacto=$divconteudo->find('input[id=hid_AdPhone]',0);
				if (sizeof($divcontacto)>0)
				{
					$contacto=$divcontacto->attr['value'];
					$contacto=str_replace("tel:","",$contacto);
				}


				$divdescricao=$divconteudo->find('div[class=detail-section-content]',0);
				if (sizeof($divdescricao)>0)
				{
					$descricao=$divdescricao->plaintext;
				}

				$htmlanuncio->clear(); 
				unset($htmlanuncio);

				$titulo=limpa(str_replace("&#x20AC;","",$titulo));
				$link=limpa($link);
				$descricao=limpa($descricao);
				$preco=limpa(str_replace("€","",$preco));
				$preco=limpa(str_replace("?","",$preco));
				$preco=limpa(str_replace("&euro;","",$preco));
				$data=limpa($data);
				$contacto=limpa(trim($contacto));
				$zona=limpa(limpa(trim($zona)));


				echo "<br/>Link: ".$link;
				echo "<br/>titulo: ".$titulo;
				echo "<br/>descricao: ".$descricao;
				echo "<br/>preco: ".$preco;
				echo "<br/>contacto: ".$contacto;

				if ($contacto!="")
				{
					InsereScrapping($site, $titulo, $descricao, $data, $preco, $contacto, $link, $zona, $idScrappingList, $offer_type, $country);
					
					ActualuzaScrappingLista($DataIniScrappingList,$idScrappingList);
				}

			}
			
		}
		$html->clear(); 
		unset($html);
		
		
	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}

?>