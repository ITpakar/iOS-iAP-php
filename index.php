<?php

/*
this little script is intended for 
in-app purchasing on iOS devices

params 
id=[ID]
receipt-data=[receipt-data]
sandbox=[]
*/

error_reporting(55);

$rec = $_GET['receipt-data'];
$receipt = json_encode(array('receipt-data' => $rec));

// this is done lazy but there is no need to do it fancy
if (isset($_GET['sandbox'])) {
	$url = "https://sandbox.itunes.apple.com/verifyReceipt";
} else {
	$url = "https://buy.itunes.apple.com/verifyReceipt";
}

// curl options to make it all work correctly
$curl_handle=curl_init();
curl_setopt($curl_handle, CURLOPT_URL, $url);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_handle, CURLOPT_HEADER, 0);
curl_setopt($curl_handle, CURLOPT_POST, true);
curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $receipt); 
curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
$response_json = curl_exec($curl_handle);
$response = json_decode($response_json);
curl_close($curl_handle);

// if the receipt-data request returs 0, all went well and we can proceed
if ($response->{'status'} == "0") {
	
	// checks the USER_AGENT string to keep it simple
	$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
	$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
	
	if (($iphone == true) || ($ipod == true))  {
		
		// static folder where files are beind stored
		$folder = "some_secret_folder_or_path"; 
		
		// IDs that correspond to filenames
		$file[000001]= "some_video_file.m4v";
		// [...]
		$file[999999]= "some_other_video_file.m4v";
	
		$file_name = $file[intval($_GET['id'])];
		$file_path= $folder."/".$file_name;
		$file_type = filetype($file_path);
	
		$data = file_get_contents($file_path);
		$file_size = strlen($data);
		
		// set correct response headers
		header("Pragma: public");
		header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-type: Application/ $file_type");
		header("Content-Disposition: attachment; filename=$file_name");
		header("Content-Description: Download PHP");
		header("Content-Length: $file_size");
		header("Content-Transfer-Encoding: binary");
		
		// retrieve file and deliver it
		$file = @fopen($file_path,"r");
		if ($file) {
			while(!feof($file)) {
				$buffer = fread($file, 1024*8);
				echo $buffer;
	    	}
			@fclose($file);
		}
	}
	// 403 response, have it do whatever you like and how you've coded your app
	else {
		header('HTTP/1.1 403 Forbidden');
	}

}
// returns a 403 response if the receipt-data request retuned anything other than a 0
else {
	header('HTTP/1.1 403 Forbidden');
}

?>