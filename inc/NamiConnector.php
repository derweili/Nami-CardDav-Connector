<?php
class NamiConnector{ 
	private $url;
    private $https;
    private $Login;
    private $username;
    private $password;
	private $curlOptions;
	public $fields_string = '';
	public $gid;

	function __construct( $https, $url, $cookie_path ) {
            $this->https = $https;
			$this->url = $url;
			$this->Login = "API";
            $this->curlOptions = array("CURLOPT_COOKIEJAR" => $cookie_path ,"CURLOPT_COOKIEFILE" => $cookie_path);

			$this->gid = "070901";
    }
	
	function call_api($service,$fields = null) {

		if ($this->https == true) 	{
			$serviceurl = "https://" . $this->url . $service;
		} else 	{
			$serviceurl = "http://" . $this->url . $service;
		}

		$ch = curl_init();

		// setze die URL und andere Optionen
		curl_setopt($ch, CURLOPT_URL, $serviceurl);
		
		if (isset($fields)) {
			foreach ($fields as $key => $value)
			{
			  $post_data[$key] = urlencode($value);
			}
			
			//url-ify the data for the POST
			$fields_string = '';
			foreach($post_data as $key=>$value) { $fields_string .= $key.'='.$value.'&'; /*echo 'test';*/}
			rtrim($fields_string,'&');
		
			curl_setopt($ch, CURLOPT_POST, count($post_data));
			curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
		}

		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->curlOptions["CURLOPT_COOKIEFILE"]);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->curlOptions["CURLOPT_COOKIEJAR"]);

		//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: JSESSIONID=gFUp0xsJeOFSo5e49w-Ryn9n.undefined"));


		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if ($this->https == true) 	{
			// These options are for https!!!
			// Turns off certificate verification --- be carefull!
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			curl_setopt($ch, CURLOPT_PORT , 443); 
		}

		if(curl_exec($ch) === false)
		{
			$error ='Curl-Fehler: ' . curl_error($ch);
		}
		else
		{
			// führe die Aktion aus und gebe die Daten an den Browser weiter
			$result = curl_exec($ch);
			//var_dump($result);
			$result = json_decode($result , true);
			//print_r($result);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			//print_r($http_status);
			if ( isset($result["statusCode"] ) && $result["statusCode"] <> 0 ) {  $error = 'Api-Fehler: ' . $result["statusMessage"];}
		}
		//		var_dump($result);

		if (isset($error)) {echo $error; echo 'error';}
		else {return($result);}

		// schließe den cURL-Handle und gebe die Systemresourcen frei
		curl_close($ch);
	}
	
	function login($credentials) {
	
		// preparing the post data
		$fields = array(
			'Login'		=>	$this->Login, 
			'username'	=>	$credentials["username"], 
			'password'	=>	$credentials["password"],
			'redirectTo'	=>	'app.jsp',

		);
					
		return $this->call_api("/ica/rest/nami/auth/manual/sessionStartup",$fields);
	}

	function set_group_id( $group_id ) {

		$this->gid = $group_id;

	}
	
	function get_groups() {
		//$result = $this->call_api("/ica/rest/orgadmin/gruppierung/flist?_dc=1353416298860&page=1&start=0&limit=4000");
		$result = $this->call_api("/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root?_dc=1477418659231&sort=%5B%7B%22property%22%3A%22leaf%22%2C%22direction%22%3A%22ASC%22%7D%5D&node=root");
		//return $result;
		//var_dump($result);
		$response = $result;
		if ($response["responseType"] == "EXCEPTION") {echo $response["message"];} else {
			return $response;
		}
	}
	
	
	function get_groupid() {
		// Holt die Gruppierungs-ID des Stammes aus der Nami
		$response = $this->get_groups();
		$gruppierungen = $response["data"];
		
		print_r($gruppierungen);
			
		/*foreach($gruppierungen as $gruppierung) {
			if (substr_count($gruppierung["descriptor"],"Essen-Stoppenberg, St. Nikolaus") == 1) {
				return $gruppierung["id"];
			} else {
				echo "Fehler: Der Stamm konnte in der Nami nicht gefunden werden.";
			}
		}*/
	}
	
	function get_members( $filterString = '', $searchString = '' ) {
		$result = $this->call_api("/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/". $this->gid ."/flist?_dc=1477420805317&page=1&start=0&limit=500&filterString=" . $filterString . "&searchString=" . $searchString);
		//var_dump($result);
		$response = $result;
		var_dump($response);
		if ($response["responseType"] == "EXCEPTION") {echo $response["message"];} else {return $response["data"];}
	}
	
	function get_memberdata($name) {
		$members = $this->get_members();
			
		foreach ($members as $member) {
			if (substr_count($member["descriptor"],$name) == 1) {
				$data = $member["entries"];
				return $data;
			}
		}
	}

	
	function get_detailed_memberdata( $member_id = '' ) {
		$result = $this->call_api("/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/". $this->gid ."/" . $member_id );
		//var_dump($result);
		$response = $result;
		if ( $response["responseType"] == "EXCEPTION" ) { echo $response["message"]; } else { return $response["data"]; }
	}


	function get_membernumber($name) {
		$data = $this->get_memberdata($name);
		return $data["mitgliedsNummer"];	
	}
}
?>