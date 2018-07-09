<?php
$FiltroSessao="";
if(isset($_GET['SessionID']))
{
	$FiltroSessao=$_GET['SessionID'];
	$FiltroSessao=str_replace("_"," ",$FiltroSessao);
	echo "SessionID: ".$FiltroSessao;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('max_execution_time', 3600);

$salesforce['username'] = 'marketingsalesforce@uniplaces.com';
$salesforce['password']= 'OgqJAr6JYQT9';
$salesforce['security_token'] = 'VX2Rzqx5kq2UelCuhRmjIZvzd';

require_once ('salesforce-toolkit/SforcePartnerClient.php');
$wsdl = 'salesforce-toolkit/partner.wsdl';
include('funcoes.php');


function ActualizaFirstExportType($salesforce_id, $unique_phone_id){
	$sql="";
	$sql=$sql." Update 05_unique_phone t5";
	$sql=$sql." INNER JOIN";
	$sql=$sql." 	(select t6.sf_lead_id, t6.unique_phone_id, t6.export_type";
	$sql=$sql." 	from 06_salesforce_ids t6";
	$sql=$sql." 	INNER JOIN";
	$sql=$sql." 		(select sf_lead_id, unique_phone_id, min(exported_at) as data";
	$sql=$sql." 		from 06_salesforce_ids";
	$sql=$sql." 		where sf_lead_id='".$salesforce_id."' AND unique_phone_id='".$unique_phone_id."'";
	$sql=$sql." 		group by sf_lead_id, unique_phone_id) as minimos";
	$sql=$sql." 		ON t6.sf_lead_id=minimos.sf_lead_id ";
	$sql=$sql." 		AND t6.unique_phone_id=minimos.unique_phone_id";
	$sql=$sql." 		AND t6.exported_at=minimos.data) as t6";
	$sql=$sql." 	ON t5.sf_lead_id=t6.sf_lead_id";
	$sql=$sql."     AND t5.unique_phone_id=t6.unique_phone_id";
	$sql=$sql." Set t5.first_export_type=t6.export_type";
	$sql=$sql." where IFNULL(t5.first_export_type,'')='' ";
	$sql=$sql." AND t5.sf_lead_id='".$salesforce_id."' AND t5.unique_phone_id='".$unique_phone_id."'";
	ExecutaSQL($sql);
}

$cConfig = new config();
$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);

$NumRegistos=50;
$NumTOTALCalls=85000;
$NumCalls=0;

$NumCalls=daValorAtributo("select count(*) as Result from log_sf_calls where date(create_date)>=date(current_date() - interval 24 hour) ");
$NumCallsHoje=daValorAtributo("select count(*) as Result from log_sf_calls where date(create_date)=date(current_date()) ");
$Faltam=daValorAtributo("select count(*) as Result  from v_export_to_sf ");


$AutoRefresh="AutoRefresh(60*1000);";
if ($Faltam>0)
{
	$AutoRefresh="AutoRefresh(500);";
	$NumRegistos=50;
}

?>

<html><head>

<?php if ($FiltroSessao=="") : ?>
	<script type="text/JavaScript">
		function AutoRefresh(interval) {
			setTimeout("location.reload(true);",interval);
		}
		<?php echo $AutoRefresh; ?>
	</script>
<?php endif; ?>
<body>
<?php

$dataInicio=date("Y-m-d H:i:s");

$hora=date("H");

//if ($FiltroSessao=="")
//	echo $hora;

if ($FiltroSessao=="")
	echo "<hr>NumCalls (Today): ".$NumCallsHoje." | NumCalls (Last24h): ".$NumCalls." | Maximum Calls: ".$NumTOTALCalls;

echo "<hr>Faltam Exportar: ".$Faltam;

$i=0;

if ($NumCalls<$NumTOTALCalls)
{

	$sql ="";
	$sql = $sql . " select * from v_export_to_sf as t05";
	
	
	
	$sql = $sql . " Where 1=1";
	if ($FiltroSessao!="")
	{
		$sql = $sql . " AND (t05.first_touch_session_id='".$FiltroSessao."' OR t05.last_touch_session_id='".$FiltroSessao."')";
	}
	
	//$sql = $sql . " AND (t05.phone_01_final like '%393405510206%') ";
	//$sql = $sql . " AND (t05.sf_lead_id like '%00Q5800000FPlErEAL%') ";

	//$sql = $sql . " AND unique_phone_id='68741'";
	
	//$sql = $sql . " Order By first_touch_import_type DESC, CASE WHEN IFNULL(LeadId,'') = '' THEN 0 ELSE 1 END, unique_phone_id DESC ";
	$sql = $sql . " LIMIT 0, $NumRegistos";
	
	echo "<hr>".$sql;

	# Create Salesforce connection
	$mySforceConnection = new SforcePartnerClient();
	$mySforceConnection->createConnection($wsdl);
	/*
	$options = array(
            'soap_version'=>SOAP_1_1,
			'exceptions'=>true,
			'trace'=>1,
			'cache_wsdl'=>WSDL_CACHE_NONE,
			"stream_context"=>stream_context_create(
                array(
                    "ssl"=>array(
						"ciphers"=>'DHE-RSA-AES256-SHA:DHE-DSS-AES256-SHA:AES256-SHA:KRB5-DES-CBC3-MD5:KRB5-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:EDH-DSS-DES-CBC3-SHA:DES-CBC3-SHA:DES-CBC3-MD5:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:AES128-SHA:RC2-CBC-MD5:KRB5-RC4-MD5:KRB5-RC4-SHA:RC4-SHA:RC4-MD5:RC4-MD5:KRB5-DES-CBC-MD5:KRB5-DES-CBC-SHA:EDH-RSA-DES-CBC-SHA:EDH-DSS-DES-CBC-SHA:DES-CBC-SHA:DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5:EXP-KRB5-DES-CBC-MD5:EXP-KRB5-RC2-CBC-SHA:EXP-KRB5-DES-CBC-SHA:EXP-EDH-RSA-DES-CBC-SHA:EXP-EDH-DSS-DES-CBC-SHA:EXP-DES-CBC-SHA:EXP-RC2-CBC-MD5:EXP-RC2-CBC-MD5:EXP-KRB5-RC4-MD5:EXP-KRB5-RC4-SHA:EXP-RC4-MD5:EXP-RC4-MD5',
						"crypto_method" => 17 
						)
                )
            )
        );
	
	$mySforceConnection->createConnection($wsdl, null, array('ssl_method' => SOAP_SSL_METHOD_TLS));
	*/
	$mySforceConnection->login($salesforce['username'], $salesforce['password'].$salesforce['security_token']);

	/*
	$sqlcalls = "Exec dbo.IncrementaCalls  @Data=NULL, @CriaTabela=1";
	ExecutaSQL($sqlcalls);
	*/


	$results = $conn->query($sql);
	foreach($results as $row)
	{
		
		$contacto=$row["phone_01"];
		$contacto=str_replace("+","\+",$contacto);
		$LeadID=$row["LeadId"];
		
		if ($FiltroSessao=="")
			echo "<hr>LeadID: ".$LeadID;
		
		$AccountID=$row["sf_account_id"];
		$ExportID = $row['unique_phone_id'];
		
		$existeRegisto=false;
		
		if ($LeadID=="" || $LeadID=="0")
		{
			//$search = 'FIND {'.$contacto.'} IN PHONE FIELDS RETURNING LEAD(ID, CreatedDate)';
			$search = 'FIND {'.$contacto.'} IN PHONE FIELDS RETURNING LEAD(ID, CreatedDate, ConvertedAccountId, IsConverted), ACCOUNT(ID)';
			//, tracking_import_type__c, tracking_source_type__c, tracking_source_name__c

			if ($FiltroSessao=="")
				echo "<hr>".$search;

			// Vou procurar a Lead
			
			log_sf_calls("search",$ExportID, $search);
			$searchResult = $mySforceConnection->search($search);
			
			
			print "<pre>"; print_r($searchResult);print "</pre>";
					
			if (sizeof($searchResult->searchRecords)==0)
			{
				// Não existe a Lead
				# Salesforce Create New Lead 
				$records = array();
				$records[0] = new SObject();
				$records[0]->type = 'Lead';
				$records[0]->fields = array(
					//'lead_type__c' => 'Landlord',
					
					///'RecordType' => 'Sales Lead (Landlord)',	
					'RecordTypeId' => '012580000008cUC',	
				
					//'flow_stage__c' => 'Marketing Qualified Lead', 
					//'status' => 'Awaiting First Call Attempt',
					//'status__c' => 'Awaiting First Call Attempt',
					//'sales_status__c' => 'awaiting qualification call',
					
					//'db_export_id__c' => $row['export_id'],
					'status' => 'New',
					'Lead_Stage__c' => 'Awaiting First Call Attempt',
					'import_team__c' => 'Central (marketing)',
					'Lead_Source_Description__c' => tratatexto($row['first_touch_source_name']),
					///'Lead_Gen_Notes__c' => CorrigeLink($row['first_touch_link_url']),
					
					
					
					//'language_locale__c' => $row['language_locale'],
					//'country_code__c' => $row['country_code_complete'],
					//'country_code_2__c' => $row['country_code_complete'],
					
					'city' => $row['first_touch_city_code'],
					'city__c' => $row['first_touch_city_code'],
					'country' => $row['first_touch_country'],
					'email' => $row['first_touch_email'],
					'phone' => $row['first_touch_phone_01'],
					'phone_2__c' => $row['first_touch_phone_02'],
					'firstname' => tratatexto($row['first_touch_first_name']),
					'LastName' => tratatexto($row['first_touch_last_name']),
					///'company' => tratatexto($row['first_touch_last_name']),
					
					//'tracking_import_type__c' => $row['first_touch_import_type'],
					//'tracking_source_type__c' => $row['first_touch_source_type'], 
					//'tracking_source_name__c' => $row['first_touch_source_name'],
					
					'db_unique_phone_id__c' => $row['unique_phone_id'],
					'db_count_unique_link_url__c' => $row['count_unique_link_url'],
					
					//'db_first_touch_address_building__c' => $row['first_touch_address_building'],
					//'db_first_touch_address_floor__c' => $row['first_touch_address_floor'],
					//'db_first_touch_address_neighborhood__c' => $row['first_touch_address_neighborhood'],
					//'db_first_touch_address_postal_code__c' => $row['first_touch_address_postal_code'],
					'Street' => tratatexto($row['first_touch_address_street']),
					'db_first_touch_city_code__c' => $row['first_touch_city_code'],
					'db_first_touch_country__c' => $row['first_touch_country'],
					///'db_first_touch_currency__c' => tratamoeda($row['first_touch_currency']),
					//'db_first_touch_description__c' => limpaHTML($row['first_touch_description']),
					'db_first_touch_email__c' => $row['first_touch_email'],
					'db_first_touch_first_name__c' => tratatexto($row['first_touch_first_name']),
					///'db_first_touch_furnished__c' => $row['first_touch_furnished'],
					//'db_first_touch_geo_latitude__c' => $row['first_touch_geo_latitude'],
					//'db_first_touch_geo_longitude__c' => $row['first_touch_geo_longitude'],
					
					'db_first_touch_import_ext_staff_id__c' => $row['first_touch_import_ext_staff_id'],
					'db_first_touch_import_id__c' => $row['first_touch_import_id'],
					'db_first_touch_import_staff_id__c' => $row['first_touch_import_staff_id'],
					'db_first_touch_import_team_name__c' => $row['first_touch_import_team_name'], 
					'db_first_touch_import_type__c' => $row['first_touch_import_type'], 
					'db_first_touch_last_name__c' => tratatexto($row['first_touch_last_name']),
					'db_first_touch_link_url__c' => CorrigeLink($row['first_touch_link_url']),
					//'db_first_touch_number_of_properties__c' => $row['first_touch_number_of_properties'],
					//'db_first_touch_number_of_rooms__c' => $row['first_touch_number_of_rooms'],
					///'db_first_touch_rent_type__c' => $row['first_touch_rent_type'], 
					'db_first_touch_phone_01__c' => $row['first_touch_phone_01'],
					///'db_first_touch_phone_02__c' => $row['first_touch_phone_02'],
					//'db_first_touch_photos_available__c' => ($row['first_touch_photos_available'] =="" ? '0' : $row['first_touch_photos_available']) ,
					//'db_first_touch_photos_url__c' => $row['first_touch_photos_url'],
					///'db_first_touch_price__c' => (strlen(preg_replace('/\D/', '', $row['first_touch_price'])) == 0 ? '0' : preg_replace('/\D/', '', $row['first_touch_price'])),
					
					'db_first_touch_source_name__c' => tratatexto($row['first_touch_source_name']),
					'db_first_touch_source_type__c' => $row['first_touch_source_type'], 
					///'db_first_touch_title__c' => limpaHTML($row['first_touch_title']),
					
					//'db_last_touch_address_building__c' => $row['last_touch_address_building'],
					//'db_last_touch_address_floor__c' => $row['last_touch_address_floor'],
					//'db_last_touch_address_neighborhood__c' => $row['last_touch_address_neighborhood'],
					//'db_last_touch_address_postal_code__c' => $row['last_touch_address_postal_code'],
					//'db_last_touch_address_street__c' => $row['last_touch_address_street'],
					'db_last_touch_city_code__c' => $row['last_touch_city_code'],
					'db_last_touch_country__c' => $row['last_touch_country'],
					///'db_last_touch_currency__c' => tratamoeda($row['last_touch_currency']),
					//'db_last_touch_description__c' => limpaHTML($row['last_touch_description']),
					'db_last_touch_email__c' => $row['last_touch_email'],
					'db_last_touch_first_name__c' => tratatexto($row['last_touch_first_name']),
					///'db_last_touch_furnished__c' => $row['last_touch_furnished'],
					//'db_last_touch_geo_latitude__c' => $row['last_touch_geo_latitude'],
					//'db_last_touch_geo_longitude__c' => $row['last_touch_geo_longitude'],
					'db_last_touch_import_ext_staff_id__c' => $row['last_touch_import_ext_staff_id'],
					'db_last_touch_import_id__c' => ($row['last_touch_import_id'] == "" ? '0' : $row['last_touch_import_id']),
					'db_last_touch_import_staff_id__c' => $row['last_touch_import_staff_id'],
					'db_last_touch_import_team_name__c' => $row['last_touch_import_team_name'],
					'db_last_touch_import_type__c' => $row['last_touch_import_type'],
					'db_last_touch_last_name__c' => tratatexto($row['last_touch_last_name']),
					'db_last_touch_link_url__c' => CorrigeLink($row['last_touch_link_url']),
					//'db_last_touch_number_of_properties__c' => ($row['last_touch_number_of_properties'] =="" ? '0' : $row['last_touch_number_of_properties']),
					//'db_last_touch_number_of_rooms__c' => ($row['last_touch_number_of_rooms'] =="" ? '0' : $row['last_touch_number_of_rooms']),
					///'db_last_touch_rent_type__c' => $row['last_touch_rent_type'],
					'db_last_touch_phone_01__c' => $row['last_touch_phone_01'],
					///'db_last_touch_phone_02__c' => $row['last_touch_phone_02'],
					//'db_last_touch_photos_available__c' => ($row['last_touch_photos_available'] =="" ? '0' : $row['last_touch_photos_available']),
					//'db_last_touch_photos_url__c' => $row['last_touch_photos_url'],
					///'db_last_touch_price__c' => (strlen(preg_replace('/\D/', '', $row['last_touch_price'])) == 0 ? '0' : preg_replace('/\D/', '', $row['last_touch_price'])),
					'db_last_touch_source_name__c' => $row['last_touch_source_name'],
					'db_last_touch_source_type__c' => $row['last_touch_source_type'],
					///'db_last_touch_title__c' => limpaHTML($row['last_touch_title'])
					
					
				);
				
				///if ($row['first_touch_source_date']!=""){$records[0]->fields['db_first_touch_source_date__c'] = $row['first_touch_source_date'];}
				if ($row['first_touch_import_date']!=""){$records[0]->fields['db_first_touch_import_date__c'] = $row['first_touch_import_date'];}
				if ($row['last_touch_import_date']!="")	{$records[0]->fields['db_last_touch_import_date__c'] = $row['last_touch_import_date'];}
				
				if (($row['first_touch_source_type']=="Automatic Scraping") || ($row['first_touch_source_type']=="Manual Scraping"))
					$records[0]->fields['LeadSource'] = 'Automatic Scraping';
				else
					$records[0]->fields['LeadSource'] = 'Ambassadors Program';//$records[0]->fields['LeadSource'] = 'Referrals';
				
				
				if ($row['first_touch_import_type']=="scraping")
					$records[0]->fields['Import_Type__c'] = 'Scraping';
				else
					if ($row['first_touch_import_type']=="lead_by_lead")
						$records[0]->fields['Import_Type__c'] = 'Lead by Lead';
					else
						$records[0]->fields['Import_Type__c'] = 'Bulk Import';
				
				
				
				try
				{
					//$sqlcalls = "Exec dbo.IncrementaCalls  @Data=NULL"; ExecutaSQL($sqlcalls);
					$NumCalls=$NumCalls+1;
					
					/*
					if ($NumCalls>$NumTOTALCalls)
						break;
					*/
					if ($FiltroSessao=="")
						echo "<hr>Entrou 1";
					
					
					//print "<pre>"; print_r($records);print "</pre>";
					
					log_sf_calls("create",$ExportID, $records);
					$response = $mySforceConnection->create($records);
					
					print "<pre>"; print_r($response);print "</pre>";
					
					if ($response[0]->success==1)
					{
						$salesforce_id=$response[0]->id; 
						$sql="";
						$sql=$sql." INSERT INTO 06_salesforce_ids (`unique_phone_id`,`exported_at`,`phone_01_final`,`sf_lead_id`, `create_date`, export_type)";
						$sql=$sql." VALUES ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$salesforce_id."', NOW(), 'lead_creation')";
						if ($FiltroSessao=="")
						{
							echo "<p><b>New LeadID: ($salesforce_id)</b></p>";
							echo "<hr><b>SQL: </b>".$sql;
						}
						ExecutaSQL($sql);
						$sql="";
						$sql=$sql." Update 05_unique_phone set sf_lead_id='".$salesforce_id."', last_export=NOW() where unique_phone_id='".$ExportID."'";
						if ($FiltroSessao=="")
							echo "<hr><b>SQL: </b>".$sql;
						ExecutaSQL($sql);
						ActualizaFirstExportType($salesforce_id, $ExportID);
					}
					else
					{
						$salesforce_id=0; $salesforce_error="Erro:". $response[0]->errors[0]->message; 
						if ($FiltroSessao=="")
						{
							print_r("<pre>");
							print_r($records);
							print_r("</pre>");
							echo "<p><b>$salesforce_error</b></p>";
						}
						$erro=true;
						$sql="";
						$sql=$sql." INSERT INTO 06_salesforce_ids (`unique_phone_id`,`exported_at`,`phone_01_final`,`sf_lead_id`, `create_date`, error_message_01, export_type)";
						$sql=$sql." VALUES ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$salesforce_id."',  NOW(), '".$salesforce_error."', 'lead_creation')";
						
						if ($FiltroSessao=="")
							echo "<hr><b>SQL: </b>".$sql;
						ExecutaSQL($sql);
						$sql="";
						$sql=$sql." Update 05_unique_phone set sf_lead_id='".$salesforce_id."', last_export=NOW() where unique_phone_id='".$ExportID."'";
						
						if ($FiltroSessao=="")
							echo "<hr><b>SQL: </b>".$sql;
						ExecutaSQL($sql);
						ActualizaFirstExportType($salesforce_id, $ExportID);
					}
				} catch (Exception $e) {
					if ($FiltroSessao=="")
					{
						print "<pre>"; print_r($records[0]);print "</pre>";
						$errmessage =  "Exception ".$e->faultstring."<br/><br/>\n"; $errmessage .= "Last Request:<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastRequestHeaders(); $errmessage .= "<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastRequest(); $errmessage .= "<br/><br/>\n"; $errmessage .= "Last Response:<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastResponseHeaders(); $errmessage .= "<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastResponse(); 
						echo "<hr>Erro de Lead Creation<br>"; echo $errmessage; $salesforce_id=0; $salesforce_error="Erro de Create"; echo "<p><b>$salesforce_id</b></p>"; $erro=true;
					}
				}
				
				//Lead Não existia e foi feito o pedido de criação
				/*
				if ($salesforce_id!=0)
				{
					$sql="";
					$sql=$sql." INSERT INTO 06_salesforce_ids (`unique_phone_id`,`exported_at`,`phone_01_final`,`sf_lead_id`,`sf_lead_current_flow_stage`, `sf_lead_current_status`,`create_date`)";
					$sql=$sql." VALUES ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$salesforce_id."', 'Marketing Qualified Lead', NOW())";
					echo "<hr>".$sql;
					ExecutaSQL($sql);
				}
				*/
			}
			else
			{
				//Eu não tenho Lead, mas ela já existe no Sforce
				/*
				$salesforce_id=$searchResult->searchRecords[0]->Id;
				$tracking_import_type__c= $searchResult->searchRecords[0]->fields->tracking_import_type__c;
				$tracking_source_type__c= $searchResult->searchRecords[0]->fields->tracking_source_type__c;
				$tracking_source_name__c= $searchResult->searchRecords[0]->fields->tracking_source_name__c;
				$CreatedDate= $searchResult->searchRecords[0]->fields->CreatedDate;
				echo "<p><b>Eu não tenho Lead, mas ela já existe no Sforce</b>: $salesforce_id</p>";
				$sql="";
				$sql=$sql." INSERT INTO 06_salesforce_ids (`unique_phone_id`,`exported_at`,`phone_01_final`,`sf_lead_id`,`sf_lead_current_flow_stage`, `create_date`, `sf_current_import_type`, `sf_current_source_type`,`sf_current_source_name`, export_type, sf_lead_create_date)";
				$sql=$sql." VALUES ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$salesforce_id."', 'Marketing Qualified Lead', NOW(), '".$tracking_import_type__c."', '".$tracking_source_type__c."', '".$tracking_source_name__c."', 'lead_update', '".$CreatedDate."')";
				echo "<hr><b>SQL: </b>".$sql;
				ExecutaSQL($sql);
				$sql="";
				$sql=$sql." Update 05_unique_phone set sf_lead_id='".$salesforce_id."' where unique_phone_id='".$ExportID."'";
				echo "<hr><b>SQL: </b>".$sql;
				ExecutaSQL($sql);
				*/


				if ($searchResult->searchRecords[0]->type=="Account")
				{
					$AccountID=$searchResult->searchRecords[0]->Id;
					$CreatedDate=$searchResult->searchRecords[0]->CreatedDate;
					$existeRegisto=true;
				}
				else
				{
					$LeadID=$searchResult->searchRecords[0]->Id;
					$CreatedDate=$searchResult->searchRecords[0]->CreatedDate;										   
					$existeRegisto=true;
				}
///$LeadID=$searchResult->searchRecords[0]->Id;
///$CreatedDate=$searchResult->searchRecords[0]->CreatedDate;
///$existeRegisto=true;
				
			}
		}
		else
		{
			$existeRegisto=true;
		}
		
		if ($existeRegisto==true)
		{
			if ($FiltroSessao=="")
				echo "<hr>Entrou2 - Já tenho Lead:".$LeadID;
			
			// Vou procurar a Lead
			log_sf_calls("retrieve",$ExportID, "ID, CreatedDate, Status, db_unique_phone_id__c,Self_Service_Flow__c, LeadSource, Lead_Source_Description__c, OwnerID, City__c, Won_Time__c, Lost_Time__c, Lost_Reason__c, Lead_Stage__c, PPs__c, PPs_lost__c, PPs_published__c, flow_stage__c, ConvertedAccountId, ConvertedContactId, IsConverted, ConvertedDate");

			$searchResult = $mySforceConnection->retrieve("ID, CreatedDate, Status, db_unique_phone_id__c,Self_Service_Flow__c, LeadSource, Lead_Source_Description__c, OwnerID, City__c, Won_Time__c, Lost_Time__c, Lost_Reason__c, Lead_Stage__c, PPs__c, PPs_lost__c, PPs_published__c, flow_stage__c, ConvertedAccountId, ConvertedContactId, IsConverted, ConvertedDate, S_Service_Send_to_Sales__c, Ops_ID__c",'Lead',$LeadID);
		 ///$searchResult = $mySforceConnection->retrieve("ID, CreatedDate, Status, db_unique_phone_id__c,Self_Service_Flow__c, LeadSource, Lead_Source_Description__c, OwnerID, City__c, Won_Time__c, Lost_Time__c, Lost_Reason__c, Lead_Stage__c, PPs__c, PPs_lost__c, PPs_published__c",'Lead',$LeadID);
		 //tracking_import_type__c, tracking_source_type__c, tracking_source_name__c

			print "<pre>"; print_r($searchResult);print "</pre>";
			
			
			if (($AccountID=="") && ((sizeof($searchResult)==0) || (sizeof($searchResult[0]->fields)==0)))
			{
				/*
				$sql="";
				$sql=$sql." INSERT INTO 06_salesforce_ids ";
				$sql=$sql." (unique_phone_id,exported_at,phone_01_final,sf_lead_id, create_date, export_type, sf_lead_current_status)";
				$sql=$sql." VALUES ";
				$sql=$sql." ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$LeadID."', NOW(), 'lead_removed','REMOVED')";
				echo "<hr><b>SQL: </b>".$sql;
				ExecutaSQL($sql);
				*/
				
				$sql="";
				$sql=$sql." Update 05_unique_phone set sf_lead_id='', last_export=NULL where unique_phone_id='".$ExportID."'";
				if ($FiltroSessao=="")
					echo "<hr><b>SQL: </b>".$sql;
				ExecutaSQL($sql);
			}
			else
			{
				if (($AccountID=="") && ($searchResult[0]->fields->ConvertedAccountId!=""))
					$AccountID=$searchResult[0]->fields->ConvertedAccountId;

				if ($AccountID!="")
				{
					log_sf_calls("retrieveAccount",$ExportID, "ID, Status__c, Self_Service_Flow__c, S_Service_Send_to_Sales__c, Ongoing_Negotiation__c,First_Onboard_Time__c,First_Offer_Published_Date__c,Photography_Sessions_Completed__c,Photography_Sessions_Scheduled__c,Open_Opportunity_Offer__c,Open_Opportunity_Properties__c,Completed_Opportunity_Offer__c,Completed_Opportunity_Properties__c, Total_Opportunity_Offer__c, Total_Opportunity_Properties__c,Opps_while_dormant__c,  Dormant_Time__c, Dormant_Reason__c,Account_ID__c,Draft_ID__c,Draft_URL__c, ops_id__c, Ops_Sales_Closer__c, Ops_Account_Manager__c, OPS_Test__c,Out_Of_Platform__c, db_unique_phone_id__c");
					$searchResultAccount = $mySforceConnection->retrieve("ID, Status__c, Self_Service_Flow__c, S_Service_Send_to_Sales__c, Ongoing_Negotiation__c,First_Onboard_Time__c,First_Offer_Published_Date__c,Photography_Sessions_Completed__c,Photography_Sessions_Scheduled__c,Open_Opportunity_Offer__c,Open_Opportunity_Properties__c,Completed_Opportunity_Offer__c,Completed_Opportunity_Properties__c, Total_Opportunity_Offer__c, Total_Opportunity_Properties__c,Opps_while_dormant__c,  Dormant_Time__c, Dormant_Reason__c,Account_ID__c,Draft_ID__c,Draft_URL__c, ops_id__c, Ops_Sales_Closer__c, Ops_Account_Manager__c, OPS_Test__c,Out_Of_Platform__c, db_unique_phone_id__c, Portfolio_Offers__c, Portfolio_Properties__c",'Account',$AccountID);
					//flow_stage__c, account_published__c, account_published_date__c
					print "<pre>"; print_r($searchResultAccount);print "</pre>";
				}
				//LeadID já existente
				# Salesforce Create New Lead 
				$records = array();
				$records[0] = new SObject();
				$preenchefirst=false;
				if ($AccountID!="")
				{
					$records[0]->type = 'Account';
					$records[0]->Id = $AccountID;
					if (sizeof($searchResultAccount)>0)
						if ($searchResultAccount[0]->fields->db_unique_phone_id__c=="")
							$preenchefirst=true;
				}
				else
				{
					$records[0]->type = 'Lead';
					$records[0]->Id = $LeadID;
					if ($searchResult[0]->fields->db_unique_phone_id__c=="")
						$preenchefirst=true;
				}

				if ($FiltroSessao=="")
					echo "<br>preenchefirst - ".$preenchefirst;

				$records[0]->fields = array(

					'db_count_unique_link_url__c' => $row['count_unique_link_url'],
					//'db_last_touch_address_building__c' => $row['last_touch_address_building'],
					//'db_last_touch_address_floor__c' => $row['last_touch_address_floor'],
					//'db_last_touch_address_neighborhood__c' => $row['last_touch_address_neighborhood'],
					//'db_last_touch_address_postal_code__c' => $row['last_touch_address_postal_code'],
					//'db_last_touch_address_street__c' => $row['last_touch_address_street'],
					
					///'db_last_touch_currency__c' => tratamoeda($row['last_touch_currency']),
					//'db_last_touch_description__c' => limpaHTML($row['last_touch_description']),
					
					//'db_last_touch_furnished__c' => $row['last_touch_furnished'],
					//'db_last_touch_geo_latitude__c' => $row['last_touch_geo_latitude'],
					//'db_last_touch_geo_longitude__c' => $row['last_touch_geo_longitude'],
					'db_last_touch_import_ext_staff_id__c' => $row['last_touch_import_ext_staff_id'],
					'db_last_touch_import_id__c' => ($row['last_touch_import_id'] == "" ? '0' : $row['last_touch_import_id']),
					'db_last_touch_import_staff_id__c' => $row['last_touch_import_staff_id'],
					'db_last_touch_import_team_name__c' => $row['last_touch_import_team_name'],
					'db_last_touch_import_type__c' => $row['last_touch_import_type'],
					
					'db_last_touch_link_url__c' => CorrigeLink($row['last_touch_link_url']),
					//'db_last_touch_number_of_properties__c' => ($row['last_touch_number_of_properties'] =="" ? '0' : $row['last_touch_number_of_properties']),
					//'db_last_touch_number_of_rooms__c' => ($row['last_touch_number_of_rooms'] =="" ? '0' : $row['last_touch_number_of_rooms']),
					///'db_last_touch_rent_type__c' => $row['last_touch_rent_type'],
					
					///'db_last_touch_phone_02__c' => $row['last_touch_phone_02'],
					//'db_last_touch_photos_available__c' => ($row['last_touch_photos_available'] =="" ? '0' : $row['last_touch_photos_available']),
					//'db_last_touch_photos_url__c' => $row['last_touch_photos_url'],
					///'db_last_touch_price__c' => (strlen(preg_replace('/\D/', '', $row['last_touch_price'])) == 0 ? '0' : preg_replace('/\D/', '', $row['last_touch_price'])),
					'db_last_touch_source_name__c' => $row['last_touch_source_name'],
					'db_last_touch_source_type__c' => $row['last_touch_source_type']
					///'db_last_touch_title__c' => limpaHTML($row['last_touch_title']),

					//'db_num_updates__c' => $row['count_unique_link_url'],
					
				);
				
				if ($AccountID=="")
				{
					$records[0]->fields['db_last_touch_city_code__c'] = $row['last_touch_city_code'];
					$records[0]->fields['db_last_touch_country__c'] = $row['last_touch_country'];
					$records[0]->fields['db_last_touch_email__c'] = $row['last_touch_email'];
					$records[0]->fields['db_last_touch_first_name__c'] = tratatexto($row['last_touch_first_name']);
					$records[0]->fields['db_last_touch_last_name__c'] = tratatexto($row['last_touch_last_name']);
					$records[0]->fields['db_last_touch_phone_01__c'] = $row['last_touch_phone_01'];
				}
				
				
				
				
				if ($row['last_touch_import_date']!="") { $records[0]->fields['db_last_touch_import_date__c'] = $row['last_touch_import_date']; }
				if ($row['last_touch_import_id']!="") { $records[0]->fields['db_last_touch_import_id__c'] = $row['last_touch_import_id']; }
				//if ($row['last_update']=="") { $records[0]->fields['db_last_update__c'] =  date("Y-m-d").'T'.date("H:i:s").'Z';} //%Y-%m-%dT%H:%i:%sZ
				
				if (($AccountID=="") && ($preenchefirst==true))
				{
					$records[0]->fields = array(
						//'db_export_id__c' => $row['export_id'],
						'db_unique_phone_id__c' => $row['unique_phone_id'],
						'db_count_unique_link_url__c' => $row['count_unique_link_url'],
						//'db_first_touch_address_building__c' => $row['first_touch_address_building'],
						//'db_first_touch_address_floor__c' => $row['first_touch_address_floor'],
						//'db_first_touch_address_neighborhood__c' => $row['first_touch_address_neighborhood'],
						//'db_first_touch_address_postal_code__c' => $row['first_touch_address_postal_code'],
						'Street' => tratatexto($row['first_touch_address_street']),
						'db_first_touch_city_code__c' => $row['first_touch_city_code'],
						'db_first_touch_country__c' => $row['first_touch_country'],
						///'db_first_touch_currency__c' => tratamoeda($row['first_touch_currency']),
						//'db_first_touch_description__c' => limpaHTML($row['first_touch_description']),
						'db_first_touch_email__c' => $row['first_touch_email'],
						'db_first_touch_first_name__c' => tratatexto($row['first_touch_first_name']),
						///'db_first_touch_furnished__c' => $row['first_touch_furnished'],
						//'db_first_touch_geo_latitude__c' => $row['first_touch_geo_latitude'],
						//'db_first_touch_geo_longitude__c' => $row['first_touch_geo_longitude'],
						
						'db_first_touch_import_ext_staff_id__c' => $row['first_touch_import_ext_staff_id'],
						'db_first_touch_import_id__c' => $row['first_touch_import_id'],
						'db_first_touch_import_staff_id__c' => $row['first_touch_import_staff_id'],
						'db_first_touch_import_team_name__c' => $row['first_touch_import_team_name'], 
						'db_first_touch_import_type__c' => $row['first_touch_import_type'], 
						'db_first_touch_last_name__c' => tratatexto($row['first_touch_last_name']),
						'db_first_touch_link_url__c' => CorrigeLink($row['first_touch_link_url']),
						//'db_first_touch_number_of_properties__c' => $row['first_touch_number_of_properties'],
						//'db_first_touch_number_of_rooms__c' => $row['first_touch_number_of_rooms'],
						///'db_first_touch_rent_type__c' => $row['first_touch_rent_type'], 
						'db_first_touch_phone_01__c' => $row['first_touch_phone_01'],
						///'db_first_touch_phone_02__c' => $row['first_touch_phone_02'],
						//'db_first_touch_photos_available__c' => ($row['first_touch_photos_available'] =="" ? '0' : $row['first_touch_photos_available']) ,
						//'db_first_touch_photos_url__c' => $row['first_touch_photos_url'],
						///'db_first_touch_price__c' => (strlen(preg_replace('/\D/', '', $row['first_touch_price'])) == 0 ? '0' : preg_replace('/\D/', '', $row['first_touch_price'])),
						//'db_first_touch_source_date__c' => $row['first_touch_source_date'],
						'db_first_touch_source_name__c' => $row['first_touch_source_name'],
						'db_first_touch_source_type__c' => $row['first_touch_source_type']
						///'db_first_touch_title__c' => limpaHTML($row['first_touch_title'])
						
						
						
						//'db_num_updates__c' => $row['count_unique_link_url'],
						
					);
					
					///if ($row['first_touch_source_date']!=""){$records[0]->fields['db_first_touch_source_date__c'] = $row['first_touch_source_date'];}
					if ($row['first_touch_import_date']!="") { $records[0]->fields['db_first_touch_import_date__c'] = $row['first_touch_import_date']; }
					if ($row['first_touch_import_id']!="") { $records[0]->fields['db_first_touch_import_id__c'] = $row['first_touch_import_id']; }
					
					if ($row['first_touch_import_type']=="scraping")
						$records[0]->fields['Import_Type__c'] = 'Scraping';
					else
						if ($row['first_touch_import_type']=="lead_by_lead")
							$records[0]->fields['Import_Type__c'] = 'Lead by Lead';
						else
							$records[0]->fields['Import_Type__c'] = 'Bulk Import';
				}
				
				try
				{
					//$sqlcalls = "Exec dbo.IncrementaCalls  @Data=NULL"; ExecutaSQL($sqlcalls);
					$NumCalls=$NumCalls+1;
					/*
					if ($NumCalls>$NumTOTALCalls)
						break;
					*/
					
					
					log_sf_calls("update", $ExportID, $records);
					print_r("<pre>");
					print_r($records);
					print_r("</pre>");
					$response = $mySforceConnection->update($records);
					
						
					
					
					
					if ($response[0]->success!=1)
					{
						$salesforce_error=$response[0]->errors[0]->message;
						if (strpos($salesforce_error,'reopened')>0)
						{
							//É o erro reopened, vou prencher o campo e fazer update outra vez
							$records[0]->fields['Date_to_be_Reoepened__c'] = $row['TODAYDATE'];
							log_sf_calls("update", $ExportID, $records);
							print_r("<pre>");
							print_r($records);
							print_r("</pre>");
							$response = $mySforceConnection->update($records);
							
							
						}
					}
					
					
					if ($response[0]->success==1)
					{
						if (sizeof($searchResult)>0)
						{
							$salesforce_id=$searchResult[0]->Id;
							$Won_Time__c=$searchResult[0]->Won_Time__c;
							$Won_Time__c=str_replace("T"," ",$Won_Time__c);
							$Won_Time__c=str_replace("Z","",$Won_Time__c);
							if (strlen(trim($Won_Time__c))==0)
								$Won_Time__c="NULL";
							else
								$Won_Time__c="'".$Won_Time__c."'";
							$Lost_Time__c=$searchResult[0]->Lost_Time__c;
							$Lost_Time__c=str_replace("T"," ",$Lost_Time__c);
							$Lost_Time__c=str_replace("Z","",$Lost_Time__c);
							if (strlen(trim($Lost_Time__c))==0)
								$Lost_Time__c="NULL";
							else
								$Lost_Time__c="'".$Lost_Time__c."'";
							$CreatedDate=$searchResult[0]->CreatedDate;
							$CreatedDate=str_replace("T"," ",$CreatedDate);
							$CreatedDate=str_replace("Z","",$CreatedDate);
							$sql="";
							$sql=$sql." INSERT INTO 06_salesforce_ids ";
							$sql=$sql." (unique_phone_id,exported_at,phone_01_final,sf_lead_id, create_date, ";
							$sql=$sql." sf_lead_create_date, export_type, ";
							$sql=$sql." sf_lead_ssp_flow, ";
							$sql=$sql." sf_lead_source, ";
							$sql=$sql." sf_lead_source_name, ";
							//$sql=$sql." sf_OwnerID, ";
							$sql=$sql." sf_lead_city__c, ";
							$sql=$sql." sf_won_date, ";
							$sql=$sql." sf_lead_lost_date, ";
							$sql=$sql." sf_lead_lost_reason, ";
							$sql=$sql." sf_lead_current_stage, ";
							$sql=$sql." sf_PPs, ";
							$sql=$sql." sf_PPs_Lost, ";
							$sql=$sql." sf_PPs_Published, ";
							$sql=$sql." sf_lead_ssp_send_to_sales,";
							$sql=$sql." sf_lead_ops_id,";
							$sql=$sql." sf_lead_current_status";
							
							if (isset($searchResultAccount))
							{
								if ((sizeof($searchResultAccount)>0) && ($AccountID!=""))
								{
									$sql=$sql." ,sf_account_id, sf_account_contact_id, sf_account_current_status, sf_account_ssp_flow ";
									$sql=$sql." ,sf_account_ssp_send_to_sales, sf_account_ongoing_negotiation, sf_account_first_onboarding_time, sf_account_first_offer_published_date  ";
									$sql=$sql." ,sf_account_photo_sessions_completed, sf_account_photo_sessions_scheduled, sf_account_open_opp_offers, sf_account_open_opp_properties  ";
									$sql=$sql." ,sf_account_completed_opp_offers, sf_account_completed_opp_properties, sf_account_total_opp_offers, sf_account_total_opp_properties  ";
									$sql=$sql." ,sf_account_opps_while_dormant, sf_account_dormant_time, sf_account_dormant_reason, enricher_account_id  ";
									$sql=$sql." ,enricher_draft_id, enricher_draft_url, ops_id, ops_sales_closer  ";
									$sql=$sql." ,ops_account_manager, ops_is_test, ops_out_of_platform  ";
									$sql=$sql." ,sf_account_portfolio_offers_manual, sf_account_portfolio_properties_manual  ";
								}
							}
							
							$sql=$sql." )";
							$sql=$sql." VALUES ";
							$sql=$sql." ('".$ExportID."', '".$row["create_date"]."','".$contacto."', '".$salesforce_id."', NOW(), ";
							$sql=$sql." '".$CreatedDate."' , 'lead_update',";
							$sql=$sql." '".$searchResult[0]->Self_Service_Flow__c."', ";
							$sql=$sql." '".$searchResult[0]->LeadSource."', ";
							$sql=$sql." '".$searchResult[0]->Lead_Source_Description__c."', ";
							//$sql=$sql." '".$searchResult[0]->OwnerID."', ";
							$sql=$sql." '".$searchResult[0]->City__c."', ";
							$sql=$sql." ".$Won_Time__c.", ";
							$sql=$sql." ".$Lost_Time__c.", ";
							$sql=$sql." '".$searchResult[0]->Lost_Reason__c."', ";
							$sql=$sql." '".$searchResult[0]->Lead_Stage__c."', ";
							$sql=$sql." '".$searchResult[0]->PPs__c."', ";
							$sql=$sql." '".$searchResult[0]->PPs_lost__c."', ";
							$sql=$sql." '".$searchResult[0]->PPs_published__c."', ";
							$sql=$sql." '".$searchResult[0]->S_Service_Send_to_Sales__c."', ";
							$sql=$sql." '".$searchResult[0]->Ops_ID__c."', ";
							$sql=$sql." '".$searchResult[0]->Status."' ";
							
							if (isset($searchResultAccount))
							{
								if ((sizeof($searchResultAccount)>0) && ($AccountID!=""))
								{
									//$sql=$sql." ,'".$searchResult[0]->fields->ConvertedAccountId."', '".$searchResult[0]->fields->ConvertedContactId."', '".$searchResultAccount[0]->fields->Status__c."', '".$searchResultAccount[0]->fields->Self_Service_Flow__c."' ";
									$sql=$sql." ,'".$AccountID."', '".$searchResult[0]->fields->ConvertedContactId."', '".$searchResultAccount[0]->fields->Status__c."', '".$searchResultAccount[0]->fields->Self_Service_Flow__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->S_Service_Send_to_Sales__c."', '".$searchResultAccount[0]->fields->Ongoing_Negotiation__c."', ".TrataDataSF($searchResultAccount[0]->fields->First_Onboard_Time__c).", ".TrataDataSF($searchResultAccount[0]->fields->First_Offer_Published_Date__c)." ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Photography_Sessions_Completed__c."', '".$searchResultAccount[0]->fields->Photography_Sessions_Scheduled__c."', '".$searchResultAccount[0]->fields->Open_Opportunity_Offer__c."', '".$searchResultAccount[0]->fields->Open_Opportunity_Properties__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Completed_Opportunity_Offer__c."', '".$searchResultAccount[0]->fields->Completed_Opportunity_Properties__c."', '".$searchResultAccount[0]->fields->Total_Opportunity_Offer__c."', '".$searchResultAccount[0]->fields->Total_Opportunity_Properties__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Opps_while_dormant__c."', ".TrataDataSF($searchResultAccount[0]->fields->Dormant_Time__c).", '".$searchResultAccount[0]->fields->Dormant_Reason__c."', '".$searchResultAccount[0]->fields->Account_ID__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Draft_ID__c."', '".$searchResultAccount[0]->fields->Draft_URL__c."', '".$searchResultAccount[0]->fields->OPS_ID__c."', '".$searchResultAccount[0]->fields->Ops_Sales_Closer__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Ops_Account_Manager__c."', '".$searchResultAccount[0]->fields->OPS_Test__c."', '".$searchResultAccount[0]->fields->Out_Of_Platform__c."' ";
									$sql=$sql." ,'".$searchResultAccount[0]->fields->Portfolio_Offers__c."', '".$searchResultAccount[0]->fields->Portfolio_Properties__c."' ";
								}
							}
							
							$sql=$sql." )";
							if ($FiltroSessao=="")
								echo "<hr><b>SQL: </b>".$sql;
							ExecutaSQL($sql);
							
							$sql="";
							$sql=$sql." Update 05_unique_phone set sf_lead_id='".$salesforce_id."', last_export=NOW() ";
							if (isset($searchResultAccount))
							{
								if (sizeof($searchResultAccount)>0)
								{
									$sql=$sql." , sf_account_id='".$AccountID."'";
								}
							}
							$sql=$sql." where unique_phone_id='".$ExportID."'";
							if ($FiltroSessao=="")
								echo "<hr><b>SQL: </b>".$sql;
							ExecutaSQL($sql);
							ActualizaFirstExportType($salesforce_id, $ExportID);
						}
					}
					else
					{
						$salesforce_id=0; $salesforce_error="Erro:". $response[0]->errors[0]->message; 
						if ($FiltroSessao=="")
							echo "<p><b>$salesforce_error</b></p>"; 
						$erro=true;
					}
				} catch (Exception $e) {
					if ($FiltroSessao=="")
					{
						$errmessage =  "Exception ".$e->faultstring."<br/><br/>\n"; $errmessage .= "Last Request:<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastRequestHeaders(); $errmessage .= "<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastRequest(); $errmessage .= "<br/><br/>\n"; $errmessage .= "Last Response:<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastResponseHeaders(); $errmessage .= "<br/><br/>\n"; $errmessage .= $mySforceConnection->getLastResponse(); 
						echo "<hr>"; echo $errmessage; $salesforce_id=0; $salesforce_error="Erro de Create"; echo "<p><b>$salesforce_id</b></p>"; $erro=true;
					}
				}
			}
			
			//Lead Não existia e foi feito o pedido de criação
			//if ($salesforce_id!=0)
			
		}


		$i=$i+1;

		
	}

}

if ($FiltroSessao=="")
{
	$dataFim=date("Y-m-d H:i:s");
	echo "<br>";
	echo "<hr>";
	echo "<br>Início: ".$dataInicio;
	echo "<br>NumRegistos a Tratar: ".$NumRegistos;
	echo "<br>NumRegistos Tratados: ".$i;
	echo "<br>Fim: ".$dataFim;
	//$interval = ($dataFim-$dataInicio);
	//echo "<br>Tempo: ".$interval;
	echo "<hr>Kill Comands";

	$results = null;
	$conn = null;

	if ($i>0)
	{
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
	}
}

if (($Faltam==0) && ($hora>=18) && ($hora<=23))
{
	
	$sqlDelete="delete from log_sf_calls where create_date<DATE_ADD(Now(), INTERVAL -2 MONTH)";
	ExecutaSQL($sqlDelete);
}
?>
</body>
</html>