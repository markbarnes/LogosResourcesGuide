<?php
	class LogosSoapClient extends SoapClient {
		
		public function __construct ($wsdl, $options = array("trace"=>1, "exceptions"=>0, 'cache_wsdl' => WSDL_CACHE_BOTH)) {
			parent::__construct($wsdl, $options);
			$cookies = $this->get_cookies();
			foreach ($cookies as $cookie) {
				if (strpos($wsdl, $cookie['domain'])) {
					$this->__setCookie($cookie['name'], $cookie['value']);
				}
			}
		}
		
		public function __doRequest($request, $location, $action, $version, $one_way = 0) {
	        $response = parent::__doRequest($request, $location, $action, $version, $one_way);
	        $start=strpos($response,'<s:');
	        $end=strrpos($response,'>');    
	        $response_string=substr($response,$start,$end-$start+1);
	        return($response_string);
	    }		

		function get_cookies($second_run = false) {
            if (!file_exists(COOKIEJAR)) {
                $this->sign_in();
                return $this->get_cookies(true);
            } else {
			    $cookies = file_get_contents(COOKIEJAR);
			    $cookies = explode ("\r\n", $cookies);
			    $new_cookie = array();
			    $now = time();
			    foreach ($cookies as $cookie) {
				    $c = explode ("\t", $cookie);
				    if (count($c) > 5 && ($c[4] > $now || $c[4] == 0)) {
					    $new_cookie [] = array ('domain' => str_replace('#HttpOnly_', '', $c[0]), 'name' => $c[5], 'value' => $c[6], 'expires' => $c[4], 'path' => $c[2]);
				    }
			    }
			    if (!$new_cookie && !$second_run) {
				    $this->sign_in();
				    return $this->get_cookies(true);
			    }
			    return $new_cookie;
            }
		}
	
		function sign_in() {
			if (!is_dir('cookies')) {
				mkdir ('cookies');
			}
			$url = 'https://services.logos.com/accounts/v1/users/signin?storefront=logos';
			$login_fields = json_encode(array ('accountType' => 'user', 'email' => array ('value' => LOGOS_EMAIL), 'password' => array ('value' => LOGOS_PASSWORD), 'userType' => array ('value' => 'User')));
			$options = array (CURLOPT_URL => $url,
							  CURLOPT_RETURNTRANSFER => true,
							  CURLOPT_USERAGENT => 'Logos/5.2 (5.2.1.19; en-GB; Win)',
							  CURLOPT_ENCODING => 'gzip, deflate', 
							  CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'x-logos-auth-version: 1.0', 'Content-Length: '.strlen($login_fields)),
							  CURLOPT_POST => true,
							  CURLOPT_POSTFIELDS => $login_fields,
							  CURLOPT_SSL_VERIFYHOST => 0,
        					  CURLOPT_SSL_VERIFYPEER => false,
        					  CURLOPT_HEADER => true,
        					  CURLOPT_COOKIEJAR => realpath(COOKIEJAR)
							 );
			$ch = curl_init();
			curl_setopt_array($ch, $options);
			$result = curl_exec($ch);
		}
	
	}
	
?>