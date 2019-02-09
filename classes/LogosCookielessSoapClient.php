<?php
	class LogosCookielessSoapClient extends SoapClient {
		
		public function __construct ($wsdl, $options = array("trace"=>1, "exceptions"=>0, 'cache_wsdl' => WSDL_CACHE_BOTH)) {
			parent::__construct($wsdl, $options);
		}
		
		public function __doRequest($request, $location, $action, $version, $one_way = 0) {
	        $response = parent::__doRequest($request, $location, $action, $version, $one_way);
	        $start=strpos($response,'<s:');
	        $end=strrpos($response,'>');    
	        $response_string=substr($response,$start,$end-$start+1);
	        return($response_string);
	    }		
	}
	
?>