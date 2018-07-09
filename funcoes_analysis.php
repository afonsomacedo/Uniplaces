<?php

/*
function ExecutaSQLAnalysis($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);
	
	$sql = $script;
	//echo "<br>".$sql;
	$results = $conn->query($sql);
	
	$results = null;
	$conn = null;
	
	$results = null;
	$conn = null;

}
*/

function DaHtmlCtxHttpsAnalysis($URL, $timeout=2, $useragent=""){

	$ch = curl_init();
	if ($useragent==""){
		$useragent=setUserAgent();
	}
	
	//echo "<br>UserAgent: ".$useragent." | Timeout: ".$timeout;

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

function daValorAtributoAnalysis($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=scraping_analysis", $cConfig->login, $cConfig->senha);
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




function GetTrovit($site_id, $site_name, $URL, $zona, $market, $offer_type, $max_page) {
	try
	{
		$site="Trovit";
		echo "<br>Entrou: $site";
		
		flush();
	
		
		$numi=0;
		$lista_ids=" ";
		$sqlfinal="";
		for ($x = 1; $x <= $max_page; $x++) {
			
			
			$link=$URL.'/page.'.$x;
			echo "<hr>Link:".$link;
			$htmlanuncio=DaHtmlCtxHttpsAnalysis($link);
			
			$divul=$htmlanuncio->find('ul[id=wrapper_listing]',0);
		
			//$divul=$divul->find('div',1);
			
			$page_pos=0;
			if (sizeof($divul)>0)
			{
				foreach($divul->find('li') as $li)
				{
				
					$page_pos=$page_pos+1;
					$title="";
					$location="";
					$price="";
					$sqm2="";
					$competitor_name="";
					$posted_at="";
					$sponsored="0";
					$room_link="";

					$divconteudo = $li;

					$divtitle=$divconteudo->find('h4',0);
					if (sizeof($divtitle)>0)
					{
						$title=$divtitle->plaintext;
					}
					
					$divsponsored=$divconteudo->find('span[class=isPremium]',0);
					if (sizeof($divsponsored)>0)
					{
						$sponsored="1";
					}

					$divpreco=$divconteudo->find('div[class=price]',0);
					if (sizeof($divpreco)>0)
					{
						$price=$divpreco->plaintext;
						$price=str_replace("€","",$price);
						$price=utf8_encode($price);
						$price=str_replace("&euro;","",$price);
					}

									
					$divlocation=$divconteudo->find('h5',0);
					if (sizeof($divlocation)>0)
					{
						$location=$divlocation->plaintext;
					}
					
					foreach($divconteudo->find('div[class=property]') as $blocos){
						$sqm2=$blocos->plaintext;
						if ($sqm2!="")
						{
							$posisqm=strpos($sqm2,"m²");
							if ($posisqm !== false)
							{
								$sqm2=str_replace("m²","",$sqm2);
							}
						}
						if ($sqm2!="")
						{
							$posisqm=strpos($sqm2,"m&sup2;");
							if ($posisqm !== false)
							{
								$sqm2=str_replace("m&sup2;","",$sqm2);
							}
						}
					}
					
					$divcompetitor=$divconteudo->find('small[class=source]',0);
					if (sizeof($divcompetitor)>0)
					{
						$competitor_name=$divcompetitor->plaintext;
					}
					
					$divdate=$divconteudo->find('small[class=date]',0);
					if (sizeof($divdate)>0){
						$posted_at=$divdate->plaintext;
					}
					
					$divlink=$divconteudo->find('a',0);
					if (sizeof($divlink)>0){
						$room_link=$divlink->href;
					}
					
					
					$title=trataplicasBD(tratatextoBD((trim($title))));
					$location=trataplicasBD(tratatextoBD((trim($location))));

					$price=limpa(str_replace("€","",$price));
					$price=limpa(str_replace("?","",$price));
					$price=limpa(str_replace("&euro;","",$price));
					$zona=limpa(limpa(trim($zona)));

					
					echo "<br/>Link: ".$link;
					echo "<br/>URL: ".$URL;
					echo "<br/>title: ".$title;
					echo "<br/>location: ".$location;
					echo "<br/>price: ".$price;
					echo "<br/>sqm2: ".$sqm2;
					echo "<br/>competitor_name: ".$competitor_name;
					echo "<br/>posted_at: ".$posted_at;
					echo "<br/>sponsored: ".$sponsored;
					echo "<br/>room_link: ".$room_link;
					echo "<br/>page_num: ".$x;
					echo "<br/>page_pos: ".$page_pos;
					

					if ($room_link!=""){

						$sql="INSERT INTO competitors_adposting ";
						$sql=$sql." (site_id, site_name, date, city, country, offer_type, title, location, price, sqm2, competitor_name, posted_at, premium, room_link, page_num, page_pos) ";
						$sql=$sql." VALUES  ";
						$sql=$sql." ('".$site_id."', '".$site_name."', NOW(),'".$zona."','".$market."','".$offer_type."','".$title."','".$location."','".$price."','".$sqm2."','".$competitor_name."','".$posted_at."','".$sponsored."','".$room_link."','".$x."','".$page_pos."'); ";
						//echo "<br/>".$sql;
						//ExecutaSQL($sql);
						
						$sqlfinal=$sqlfinal." ".$sql;
						
						//if ($numi==200) break;
						
						if ($numi==200)
						{
							$numi=0;
							echo "<br/>".$sqlfinal;
							ExecutaSQLAnalysis($sqlfinal);
							$sqlfinal="";
							echo "<hr>";
							flush();
						}
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}

		}
		
		echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}


function GetMilAnuncios($site_id, $site_name, $URL, $zona, $market, $offer_type, $max_page) {
	try
	{
		$site="MilAnuncios";
		echo "<br>Entrou: $site";
		
		flush();
	
		
		$numi=0;
		$lista_ids=" ";
		$sqlfinal="";
		for ($x = 1; $x <= $max_page; $x++) {
			
			
			$link=$URL.'&pagina=.'.$x;
			echo "<hr>Link:".$link;
			$htmlanuncio=DaHtmlCtxHttpsAnalysis($link);
			
			$divul=$htmlanuncio->find('div[id=cuerpo]',0);
		
			//$divul=$divul->find('div',1);
			
			$page_pos=0;
			if (sizeof($divul)>0)
			{
				foreach($htmlanuncio->find('div[class=aditem]') as $li)
				{
				
					$page_pos=$page_pos+1;
					$title="";
					$location="";
					$price="";
					$sqm2="";
					$competitor_name="";
					$posted_at="";
					$sponsored="0";
					$room_link="";
					$room_id="";

					$divconteudo = $li;
					
					$divsublink=$divconteudo->find('a',0);
					if (sizeof($divsublink)>0){
						$sublink=$divsublink->href;
					}

					$divtitle=$divconteudo->find('a[class=aditem-detail-title]',0);
					if (sizeof($divtitle)>0)
					{
						$title=$divtitle->plaintext;
						$room_link='https://www.milanuncios.com'.$sublink.$divtitle->href;
					}

					
					$divsponsored=$divconteudo->find('div[class*=pillSellerTypePro]',0);
					if (sizeof($divsponsored)>0)
					{
						$sponsored="1";
					}
					
					if (($room_link!="") && ($sponsored=="1"))
					{
						echo "<hr>";
						$htmlanunciointerno=DaHtmlCtxHttpsAnalysis($room_link);
						$divcompetitor=$htmlanunciointerno->find('div[class=pagAnuContactNombre]',0);
						if (sizeof($divcompetitor)>0)
						{
							$competitor_name=$divcompetitor->plaintext;
						}
						$htmlanunciointerno->clear();
						unset($htmlanunciointerno);
					}
					
					$divroomid=$divconteudo->find('div[class=x5]',0);
					if (sizeof($divroomid)>0)
					{
						$room_id=$divroomid->plaintext;
					}

					$divpreco=$divconteudo->find('div[class=aditem-price]',0);
					if (sizeof($divpreco)>0)
					{
						$price=$divpreco->plaintext;
						$price=str_replace("€","",$price);
						$price=utf8_encode($price);
						$price=str_replace("&euro;","",$price);
					}

									
					$divlocation=$divconteudo->find('h5',0);
					if (sizeof($divlocation)>0)
					{
						$location=$divlocation->plaintext;
					}
					
					foreach($divconteudo->find('div[class=m2]') as $blocos){
						$sqm2=$blocos->plaintext;
						$sqm2=str_replace("m2","",$sqm2);
						if ($sqm2!="")
						{
							$posisqm=strpos($sqm2,"m²");
							if ($posisqm !== false)
							{
								$sqm2=str_replace("m²","",$sqm2);
							}
						}
						if ($sqm2!="")
						{
							$posisqm=strpos($sqm2,"m&sup2;");
							if ($posisqm !== false)
							{
								$sqm2=str_replace("m&sup2;","",$sqm2);
							}
						}
					}
					
					/*
					$divcompetitor=$divconteudo->find('small[class=source]',0);
					if (sizeof($divcompetitor)>0)
					{
						$competitor_name=$divcompetitor->plaintext;
					}
					*/
					
					$divdate=$divconteudo->find('div[class=x6]',0);
					if (sizeof($divdate)>0){
						$posted_at=$divdate->plaintext;
					}
					
				
					
					$title=trataplicasBD(tratatextoBD((trim($title))));
					$location=trataplicasBD(tratatextoBD((trim($location))));

					$price=limpa(str_replace("€","",$price));
					$price=limpa(str_replace("?","",$price));
					$price=limpa(str_replace("&euro;","",$price));
					$zona=limpa(limpa(trim($zona)));

					if ($sponsored=="1")
					{
						echo "<br/>Link: ".$link;
						echo "<br/>URL: ".$URL;
						echo "<br/>title: ".$title;
						echo "<br/>location: ".$location;
						echo "<br/>price: ".$price;
						echo "<br/>sqm2: ".$sqm2;
						echo "<br/>competitor_name: ".$competitor_name;
						echo "<br/>posted_at: ".$posted_at;
						echo "<br/>sponsored: ".$sponsored;
						echo "<br/>room_link: ".$room_link;
						echo "<br/>page_num: ".$x;
						echo "<br/>page_pos: ".$page_pos;
					}
					

					if ($room_link!=""){

						$sql="INSERT INTO competitors_adposting ";
						$sql=$sql." (site_id, site_name, date, city, country, offer_type, title, location, price, sqm2, competitor_name, posted_at, premium, room_id, room_link, page_num, page_pos) ";
						$sql=$sql." VALUES  ";
						$sql=$sql." ('".$site_id."', '".$site_name."', NOW(),'".$zona."','".$market."','".$offer_type."','".$title."','".$location."','".$price."','".$sqm2."','".$competitor_name."','".$posted_at."','".$sponsored."','".$room_id."','".$room_link."','".$x."','".$page_pos."'); ";
						//echo "<br/>".$sql;
						//ExecutaSQL($sql);
						
						$sqlfinal=$sqlfinal." ".$sql;
						
						//if ($numi==200) break;
						
						if ($numi==200)
						{
							$numi=0;
							echo "<br/>".$sqlfinal;
							ExecutaSQLAnalysis($sqlfinal);
							$sqlfinal="";
							echo "<hr>";
							flush();
						}
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}

		}
		
		echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}


function GetImmobilienscout24($site_id, $site_name, $URL, $zona, $market, $offer_type, $max_page) {
	try
	{
		$site="Immobilienscout24";
		echo "<br>Entrou: $site";
		
		flush();
	
		
		$numi=0;
		$lista_ids=" ";
		$sqlfinal="";
		for ($x = 1; $x <= $max_page; $x++) {
			
			
			$link=str_replace("/Suche/S-T/","/Suche/S-T/P-".$x."/",$URL);
			echo "<hr>Link:".$link;
			$htmlanuncio=DaHtmlCtxHttpsAnalysis($link);
			
			$divul=$htmlanuncio->find('ul[id=resultListItems]');
		
			//$divul=$divul->find('div',1);
			
			$page_pos=0;
			if (sizeof($divul)>0)
			{
				foreach($htmlanuncio->find('li[class=result-list__listing]') as $li)
				{
				
					$page_pos=$page_pos+1;
					$title="";
					$location="";
					$price="";
					$sqm2="";
					$competitor_name="";
					$posted_at="";
					$sponsored="0";
					$room_link="";
					$room_id="";

					$divconteudo = $li;

					$divtitle=$divconteudo->find('h5[class=result-list-entry__brand-title]',0);
					if (sizeof($divtitle)>0)
					{
						$title=$divtitle->plaintext;
					}
					
					$sponsored="1";
					$divsponsored=$divconteudo->find('img[class=result-list-entry__brand-logo--private]',0);
					if (sizeof($divsponsored)>0)
					{
						$sponsored="0";
					}


					/*
					$divlocation=$divconteudo->find('h5',0);
					if (sizeof($divlocation)>0)
					{
						$location=$divlocation->plaintext;
					}
					*/
					
					foreach($divconteudo->find('dl[class=grid-item]') as $blocos){
						$bloco=$blocos->plaintext;
						if ($price=="")
						{
							$posiprice=strpos($bloco,"€");
							if ($posiprice !== false)
							{
								$price=$bloco;
								$price=str_replace("€","",$price);
								$price=str_replace("Kaltmiete","",$price);
							}
							if ($price=="")
							{
								$posiprice2=strpos($bloco,"Kaltmiete");
								if ($posiprice2 !== false)
								{
									$price=$bloco;
									$price=str_replace("€","",$price);
									$price=str_replace("Kaltmiete","",$price);
								}
							}
						}
						if ($sqm2=="")
						{
							$posisqm=strpos($bloco,"m²");
							if ($posisqm !== false)
							{
								$sqm2=$bloco;
								$sqm2=str_replace("m²","",$sqm2);
								$sqm2=str_replace("Wohnfläche","",$sqm2);
								$sqm2=str_replace("Zimmerfläche","",$sqm2);
							}
							if ($sqm2=="")
							{
								$posisqm2=strpos($bloco,"Wohnfläche");
								if ($posisqm2 !== false)
								{
									$sqm2=$bloco;
									$sqm2=str_replace("m²","",$sqm2);
									$sqm2=str_replace("Wohnfläche","",$sqm2);
									$sqm2=str_replace("Zimmerfläche","",$sqm2);
								}
							}
							if ($sqm2=="")
							{
								$posisqm3=strpos($bloco,"Zimmerfläche");
								if ($posisqm3 !== false)
								{
									$sqm2=$bloco;
									$sqm2=str_replace("m²","",$sqm2);
									$sqm2=str_replace("Wohnfläche","",$sqm2);
									$sqm2=str_replace("Zimmerfläche","",$sqm2);
								}
							}
						}
						if ($posted_at=="")
						{
							$posidate=strpos($bloco,"Frei ab");
							if ($posidate !== false)
							{
								$posted_at=$bloco;
								$posted_at=str_replace("Frei ab","",$posted_at);
							}
						}
					}
					
					/*
					$divdate=$divconteudo->find('div[class=x6]',0);
					if (sizeof($divdate)>0){
						$posted_at=$divdate->plaintext;
					}
					*/
					
					$divlink=$divconteudo->find('a',0);
					if (sizeof($divlink)>0){
						$room_link=$divlink->href;
						$room_id=$room_link;
						$room_id=str_replace("expose","",$room_id);
						$room_id=str_replace("/","",$room_id);
						$room_link="https://www.immobilienscout24.de".$room_link;
					}
					
					if (($room_link!="") && ($sponsored=="1"))
					{
						echo "<hr>";
						$htmlanunciointerno=DaHtmlCtxHttpsAnalysis($room_link);
						$divcompetitor=$htmlanunciointerno->find('span[data-qa=companyName]',0);
						if (sizeof($divcompetitor)>0)
						{
							$competitor_name=$divcompetitor->plaintext;
						}
						/*
						$posicompany=strpos($htmlanunciointerno,'<span class="font-semibold" data-qa="companyName">');
						echo "<br>posicompany".$posicompany;
						if ($posicompany !== false)
						{
							$posicompanyfim=strpos($htmlanunciointerno,"</span>",$posicompany);
							echo "<br>posicompanyfim".$posicompanyfim;
							$competitor_name=substr($htmlanunciointerno,$posicompany, $posicompanyfim-$posicompany);
							echo "<br>competitor_name".$competitor_name;
						}
						*/
						/*
						//$divcompetitor=$htmlanunciointerno->find('span[data-qa=companyName]',0);
						foreach($htmlanunciointerno->find('span[class=font-semibold]') as $blocosspan)
						{
							echo "<br>".$blocosspan->plaintext;
							if (isset($blocosspan->attr['data-qa']))
							{
								if ($blocosspan->attr['data-qa']=="companyName")
								{
									$competitor_name=$blocosspan->plaintext;
									break;
								}
							}
						}
						*/
						$htmlanunciointerno->clear();
						unset($htmlanunciointerno);
					}
					
					
					$title=trataplicasBD(tratatextoBD((trim($title))));
					$location=trataplicasBD(tratatextoBD((trim($location))));

					$price=limpa(str_replace("€","",$price));
					$price=limpa(str_replace("?","",$price));
					$price=limpa(str_replace("&euro;","",$price));
					$zona=limpa(limpa(trim($zona)));

					if ($sponsored=="1")
					{
						echo "<br/>Link: ".$link;
						echo "<br/>URL: ".$URL;
						echo "<br/>title: ".$title;
						echo "<br/>location: ".$location;
						echo "<br/>price: ".$price;
						echo "<br/>sqm2: ".$sqm2;
						echo "<br/>competitor_name: ".$competitor_name;
						echo "<br/>posted_at: ".$posted_at;
						echo "<br/>sponsored: ".$sponsored;
						echo "<br/>room_link: ".$room_link;
						echo "<br/>page_num: ".$x;
						echo "<br/>page_pos: ".$page_pos;
					}
					

					if ($room_link!=""){

						$sql="INSERT INTO competitors_adposting ";
						$sql=$sql." (site_id, site_name, date, city, country, offer_type, title, location, price, sqm2, competitor_name, posted_at, premium, room_id, room_link, page_num, page_pos) ";
						$sql=$sql." VALUES  ";
						$sql=$sql." ('".$site_id."', '".$site_name."', NOW(),'".$zona."','".$market."','".$offer_type."','".$title."','".$location."','".$price."','".$sqm2."','".$competitor_name."','".$posted_at."','".$sponsored."','".$room_id."','".$room_link."','".$x."','".$page_pos."'); ";
						//echo "<br/>".$sql;
						//ExecutaSQL($sql);
						
						$sqlfinal=$sqlfinal." ".$sql;
						
						//if ($numi==200) break;
						
						if ($numi==200)
						{
							$numi=0;
							echo "<br/>".$sqlfinal;
							ExecutaSQLAnalysis($sqlfinal);
							$sqlfinal="";
							echo "<hr>";
							flush();
						}
					}
				} // fim do for da listagem
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);
			}
			
			break;

		}// fim do for da paginação
		
		echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}


function GetTrustPilot($site_id, $site_name, $URL, $max_page) {
	try
	{
		$site="TrustPilot";
		echo "<br>Entrou: $site";
		echo "<br>max_page: $max_page";
		
		flush();
	
		
		$numi=0;
		$lista_ids=" ";
		$sqlfinal="";
		
		if ($max_page=="0")
			$max_page=100;
		
		$lista_ids="";
		for ($x = 1; $x <= $max_page; $x++) {
			
			
			$link=$URL.'&page='.$x;
			echo "<hr>Link:".$link;
			$htmlanuncio=DaHtmlCtxHttpsAnalysis($link);
			
			$divul=$htmlanuncio->find('div[id=reviews-container]');
		
			//$divul=$divul->find('div',1);
			
			$page_pos=0;
			if (sizeof($divul)>0)
			{
				foreach($htmlanuncio->find('div[class=review-stack]') as $li)
				{
				
					$page_pos=$page_pos+1;
					$title="";
					$rating="";
					$published_at="";
					$body="";
					$has_reply="0";
					$reply_published_at="";
					$reply_body="";

					$divconteudo = $li;

					$divreviewid=$divconteudo->find('article[class=review-card]',0);
					if (sizeof($divreviewid)>0)
					{
						$reviewid=$divreviewid->attr["data-reviewmid"];
					}
					
					$posid=strpos($lista_ids,$reviewid);
					if ($posid === false)
					{
						$lista_ids=$lista_ids.','.$reviewid;
						echo "<br>ReviewID:".$reviewid;
						
					
						$numi=$numi+1;
						
						$divtitle=$divconteudo->find('h2[class=review-info__body__title]',0);
						if (sizeof($divtitle)>0)
						{
							$title=$divtitle->plaintext;
						}
						
						$divrating=$divconteudo->find('div[class=star-rating]',0);
						if (sizeof($divrating)>0)
						{
							$rating=$divrating->attr["class"];
							$rating=limpa(str_replace("star-rating","",$rating));
							$rating=limpa(str_replace("clearfix","",$rating));
							$rating=limpa(str_replace("count","",$rating));
							$rating=limpa(str_replace("size-medium","",$rating));
							$rating=limpa(str_replace("medium","",$rating));
							$rating=limpa(str_replace("-","",$rating));
							//preg_match_all('!\d+!', $rating, $rating);
							//$rating = (int) filter_var($rating, FILTER_SANITIZE_NUMBER_INT);
							$rating = preg_replace('/\D/', '', $rating);
						}
						
						$divpublished_at=$divconteudo->find('time[class=ndate]',0);
						if (sizeof($divpublished_at)>0)
						{
							$published_at=$divpublished_at->attr["datetime"];
						}
						
						$divbody=$divconteudo->find('div[class=review-info__body]',0);
						if (sizeof($divbody)>0)
						{
							$body=$divbody->plaintext;
						}
						
						$divreview=$divconteudo->find('div[class=company-reply__content]',0);
						if (sizeof($divreview)>0)
						{
							$has_reply="1";
							$divreply_published_at=$divreview->find('time[class=ndate]',0);
							if (sizeof($divreply_published_at)>0)
							{
								$reply_published_at=$divreply_published_at->attr["datetime"];
							}
							
							$divreply_body=$divreview->find('div[class=company-reply__content__body]',0);
							if (sizeof($divreply_body)>0)
							{
								$reply_body=$divreply_body->plaintext;
							}
						}
						
						$title=trataplicasBD(tratatextoBD((trim($title))));
						$body=trataplicasBD(tratatextoBD((trim($body))));
						$reply_body=trataplicasBD(tratatextoBD((trim($reply_body))));

						
						
						echo "<br/>URL: ".$URL;
						echo "<br/>link: ".$link;
						echo "<br/>title: ".$title;
						echo "<br/>rating: ".$rating;
						echo "<br/>published_at: ".$published_at;
						echo "<br/>body: ".$body;
						echo "<br/>competitor_name: ".$site_name;
						echo "<br/>has_reply: ".$has_reply;
						echo "<br/>reply_published_at: ".$reply_published_at;
						echo "<br/>reply_body: ".$reply_body;
						echo "<br/>page_num: ".$x;
						echo "<br/>page_pos: ".$page_pos;
						
						
						echo "<br/>page_num: ".$x;
						

						if ($title!=""){

							$sql="INSERT INTO competitors_trustpilot ";
							$sql=$sql." (trustpilot_id, competitor_name, date, review_title, review_rating, review_published_at, review_body, has_reply, reply_published_at, reply_body, page_num, page_pos) ";
							$sql=$sql." VALUES  ";
							$sql=$sql." ('".$site_id."', '".$site_name."', NOW(),'".$title."','".$rating."','".$published_at."','".$body."','".$has_reply."','".$reply_published_at."','".$reply_body."','".$x."','".$page_pos."'); ";
							//echo "<br/>".$sql;
							//ExecutaSQL($sql);
							
							$sqlfinal=$sqlfinal." ".$sql;
							
							//if ($numi==200) break;
							
							if ($numi==200)
							{
								$numi=0;
								//echo "<br/>".$sqlfinal;
								ExecutaSQLAnalysis($sqlfinal);
								$sqlfinal="";
								echo "<hr>";
								flush();
							}
						}
					}
				}
				
				$htmlanuncio->clear(); 
				unset($htmlanuncio);

			}
			
			$numi=0;
			echo "<hr>";
			echo "PAGINA: ".$x;
			//echo "<br/>".$sqlfinal;
			ExecutaSQLAnalysis($sqlfinal);
			$sqlfinal="";
			echo "<hr>";

		}
		
		echo "<br/>".$sqlfinal;
		ExecutaSQLAnalysis($sqlfinal);
		$sqlfinal="";

	}
	catch(Exception $e) {
	//echo '<br>Erro: ' .$e->getMessage();
	}
}


?>