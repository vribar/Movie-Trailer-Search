<?php
/**
  * @copyright   2019 Vribar BV. All rights reserved
  * @author      Henk
  *
  * @api
  *
  * @link        https://www.vribar.nl/ogd
  *
  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
  *
  * Description
  *
  * Get the base URL for TMDB-posters. First checks the cache.
  *
  * @return json the base URL
  *
  * @see https://www.vribar.nl/ogd/api.html
  */

require_once 'config.php';

function returnImageBase($contents) {
	echo $contents->images->secure_base_url.$contents->images->poster_sizes[1];
}

function writeConfigCache($response) {
	$handle  = fopen(CONFIGFILE, "w");
	fwrite($handle,$response);
	fclose($handle);
}


// try cache first...
if (is_readable(CONFIGFILE)) {
	if  (time() - filemtime(CONFIGFILE) < CACHEAGE) {
		$contents = json_decode(file(CONFIGFILE)[0]);
		returnImageBase($contents);
		exit;
	}

}
// if cache file didn't work, read TMDB

$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_URL => "https://api.themoviedb.org/3/configuration?api_key=".TMDB_API_KEY,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_POSTFIELDS => "{}",
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err)  {
		$ret = "cURL Error #:" . $err;
} else {
	writeConfigCache($response);
	returnImageBase(json_decode($response));
}

?>