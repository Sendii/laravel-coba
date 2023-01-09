<?php

namespace App\Http\Controllers;
use \Spatie\Crypto\Rsa\KeyPair;

use Illuminate\Http\Request;

class HomeController extends Controller
{
	public function __construct(){
		$this->timestamp = date('c');
		$this->credential = null;
		$this->private = config('app')['token'];
	}
	public function getToken(){
		$pathToPublicKey = public_path('public.pem');
		$pathToPrivateKey = public_path('private.pem');
		$stringSign = $this->private['x-client-key']."|".$this->timestamp;
		$signature = \Spatie\Crypto\Rsa\PrivateKey::fromFile($pathToPrivateKey)->sign($stringSign);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://devapi.btn.co.id/snap/v1/access-token/b2b',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
				"grantType": "client_credentials",
				"additionalInfo": {}
			}',
			CURLOPT_HTTPHEADER => array(
				"X-SIGNATURE: {$signature}",
				"X-TIMESTAMP: {$this->timestamp}",
				"X-CLIENT-KEY: {$this->private['x-client-key']}",
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$this->credential = json_decode($response);
	}	

	public function postToAPI($endpoint, $bodyParams, $method='POST'){
		dd($this->getToken());
		$token = $this->credential->accessToken;
		$reqBody = $bodyParams;
		$reqBody = json_encode(json_decode($reqBody));
		// echo $reqBody.'</br>';
		$reqBody = hash('sha256', $reqBody);
		// echo $reqBody.'</br>';
		$stringSign = "{$method}:{$endpoint}:{$token}:{$reqBody}:{$this->timestamp}";
		// echo $stringSign.'</br>';
		$stringSign = base64_encode(hash_hmac('sha512', $stringSign, $this->private['secret_key'], true));

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://devapi.btn.co.id${endpoint}",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "{$method}",
			CURLOPT_POSTFIELDS => $bodyParams,
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer {$token}",
				"X-EXTERNAL-ID: {$this->private['x-external-id']}",
				"ORIGIN: https://devapi.btn.co.id",
				"X-PARTNER-ID: {$this->private['x-partner-id']}",
				"X-SIGNATURE: {$stringSign}",
				"X-TIMESTAMP: {$this->timestamp}",
				"CHANNEL-ID: API",
				"Content-Type: application/json"
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}

	public function index(){
		echo $this->postToAPI('/btnproperti/snap/v1/b2b/search-branch-office',
			"{
				'App_Key':'tkW3WMcuVteEo5GGJbZy',
				'id':'00005',
				'pos':'',
				'i_prop':'',
				'i_kot':'',
				'jns': '1',
				'Sort': ''
			}"
		);
	}
}