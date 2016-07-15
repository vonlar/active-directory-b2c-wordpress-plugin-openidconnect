<?php

// Decodes the data array and returns the value of "claim"
function getClaim($claim, $data) {
	
	$data_array = json_decode($data, true);
	return $data_array[$claim];
}

// A class to handle both fetching and sending data to the various endpoints
class EndpointHandler {
	
	public $metadata = "";
	
	public function __construct($policy_name) {
		$this->getMetadata($policy_name);
	}
	
	// Fetches the data at an endpoint using a HTTP GET request
	public function getEndpointData($uri) {

		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$resp = curl_exec($ch);
		curl_close($ch);
		return $resp;
	}
	
	// Given a B2C policy name, constructs the metadata endpoint 
	// and fetches the metadata from that endpoint
	public function getMetadata($policy_name) {
		require "settings.php";
		$metadata_endpoint = $metadata_endpoint_begin . $policy_name;
		$this->metadata = $this->getEndpointData($metadata_endpoint);
	}
	
	// Returns the value of the issuer claim from the metadata
	public function getIssuer() {
		$iss = getClaim("issuer", $this->metadata);
		return $iss;	
	}
	
	// Returns the value of the jwks_uri claim from the metadata
	public function getJwksUri() {
		$jwks_uri = getClaim("jwks_uri", $this->metadata);
		
		// Cast to array if not an array
		$jwks_uri = is_array($jwks_uri) ? $jwks_uri : array($jwks_uri);
		return $jwks_uri;	
	}
	
	// Returns the data at the jwks_uri page
	public function getJwksUriData() {
		$jwks_uri = $this->getJwksUri();
		
		$key_data = array();
		foreach ($jwks_uri as $uri) {
			array_push($key_data, $this->getEndpointData($uri));	
		}
		
		return $key_data;
	}
	
	// Obtains the authorization endpoint from the metadata
	// and adds the necessary query arguments
	public function getAuthorizationEndpoint() {
		require "settings.php";
		$authorization_endpoint = getClaim("authorization_endpoint", $this->metadata).
											'&response_type='.$response_type.
											'&client_id='.$clientID.
											'&redirect_uri='.$redirect_uri.'/b2c-token-verification'.
											'&response_mode='.$response_mode.
											'&scope='.$scope;
		return $authorization_endpoint;
	}
	
	// Obtains the end session endpoint from the metadata
	// and adds the necessary query arguments
	public function getEndSessionEndpoint() {
		require "settings.php";
		$end_session_endpoint = getClaim("end_session_endpoint", $this->metadata).
																'&redirect_uri='.$redirect_uri;
		return $end_session_endpoint;
	}
}

?>