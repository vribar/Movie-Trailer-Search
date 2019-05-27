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
  * Get movie records from The Movie Database
  *
  * getMovies.php checks cache for the requested records, if found
  * the cache records are returned, if not found the TMDB API and Youtube API's
  * are used to retrieve the data
  *
  * parameters are retrieved from $_GET
  *
  * @param string $searchtxt the search text for the requested records
  *
  * @param int $firstrec the index of the first movie-record (default: 0)
  *
  * @param int $count number of movie records to return
  *
  * @param int $trailers number of trailers per movie to return (default: 0, max: 25)
  *
  * @param boolean $cache use cache (default: true)
  *
  * @return json All movie/trailer records in an array
  *
  * @see https://www.vribar.nl/ogd/api.html
  */
require_once 'config.php';
require_once 'Movie.class.php';
require_once 'Response.class.php';


// Input parameters


$pSearchTxt = $_GET['searchtxt'];
$pFirstRec = $_GET['firstrec'];
$pCount = $_GET['count'];
$pCountTrailers = $_GET['trailers'];
$pCache = $_GET['cache'];


function mkInt(&$var) {
	if (is_numeric($var)) {
		$var = (int) $var;
		return TRUE;
	} else {
		return FALSE;
	}
}
function mkBool(&$var) {
	if (strtoupper($var) == "TRUE") {
		$var = TRUE;
		return TRUE;
	} else if (strtoupper($var) == "FALSE") {
		$var = FALSE;
		return TRUE;
	} else {
		return FALSE;
	}
}
function IsNullOrEmpty($s){
    return (!isset($s) || trim($s) === '');
}




 // global variables

$movieCacheFile = CACHEDIR . "/movie." . strtoupper($pSearchTxt) . ".cache"; // filename of the cachefile for movie info

$tmdbQueries = 0;	// counter for number of queries to "The Movie Database"
$ytQueries = 0;		// counter for number of queries to "Youtube.com"

// $responnse holds all the data retrieved from cache or DB

$response = new Response();

/**
  * function curl_get_contents is workaround for getting https data without configurinng
  * ssl-support for Apache
  *
  * @param string $url The URL for the data to be retrieved
  *
  * @return json the data
  */

function curl_get_contents($url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

	$err = curl_error($curl);

	if ($err) {
		die($err);
	}

	$data = curl_exec($curl);
	curl_close($curl);
	return $data;
}

function readMovieCache(&$response) {
/**
  * function readMovieCache reads the Movie cache. 
  * first check:
  *  - cachefile exists and is readable
  *  - user did not disable cachhe option
  *  - cachefile is not too old
  *
  * if check fails: set the lastpage less than the totalpages to force a tmdb-query
  * 
  * @param Response &$response
  *
  * @return bool $movieCacheFile is readable
  */

	global $movieCacheFile, $pCache;

		if (!is_readable($movieCacheFile) || !$pCache || (time() - filemtime($movieCacheFile) > CACHEAGE)) {
		$response->setHeader(0,1,0);
		return FALSE;
	}


	$jsonLines = file($movieCacheFile);

	$recNum = 0;
	foreach ($jsonLines as $jsonLine) {
		$line = json_decode($jsonLine);
		// put header and requested lines in $lines
		if ($recNum == 0 ) { // first record is the header
			$response->setHeader($line->lastPage, $line->totalPages, $line->totalResults);
		} else {
			$movie = new Movie($line->tmdbId, $line->title, $line->overview, $line->releaseDate, $line->image);
			$response->addMovie($movie);
		}
		$recNum++;
	}
	return TRUE;
}

function writeMovieCache(&$response) {
/**
  * function writeMovieCache writes the response to the $movieCacheFile
  * 
  * @param Response &$response
  */
	global $movieCacheFile;
	$fhandle = fopen($movieCacheFile,'w');
	fputs($fhandle,$response->jsonHeader().PHP_EOL);
	foreach($response->movies as $movie) {
		fputs($fhandle, $movie->jsonRecord().PHP_EOL);
	}
	fclose($fhandle);
}

function getMoviesPage(&$response, $page) {
/**
  * function getMoviesPage reads a page from TMDB, and fills the response
  * NOTICE: the $page parameter reflects the pagenumber as defined by TMDB and is not the pagenumber of the HTML page. 
  * a requests to TMDB goes per page of 20 movie records
  * 
  * @param Response &$response
  *
  * @param int $page the requested TMDB-page
  */
	global $pSearchTxt, $tmdbQueries;
	$tmdbQueries++;
	$url = "https://api.themoviedb.org/3/search/movie?include_adult=false&page=".$page."&query=".$pSearchTxt."&language=en-US&api_key=".TMDB_API_KEY;
	$contents = curl_get_contents($url);
	$data = json_decode($contents);

	if (isset($data->success) && !$data->success) { 
		$response->error($data->status_code, $data->status_message);
	} else {
		$response->setHeader($page, $data->total_pages, $data->total_results);
		$movies = [];
		foreach($data->results as $r) {
			$movie = new Movie($r->id,$r->title, $r->overview, $r->release_date, $r->poster_path);
			$response->addMovie($movie);
		}
	}
}

function getTrailers(&$movie) {
/**
  * function getTrailers gets the trailer info from cache or youtube and adds it
  * to the &$movie 
  * 
  * @param Movie &$movie
  */
	global $ytQueries, $pCountTrailers, $pCache;

	if ($pCountTrailers == 0) return; // no trailers needed! get out of here

	$trailers = [];

	$trailerCacheFile = CACHEDIR . "/trailer." . $movie->tmdbId . ".cache"; // filename of the cachefile for trailer info

	if (is_readable($trailerCacheFile) && $pCache && (time() - filemtime($trailerCacheFile) < CACHEAGE)) {
		$trailers = json_decode(file_get_contents($trailerCacheFile));
	}

	if (count($trailers) >= $pCountTrailers) { // cache found, append the trailers to $movie
		$movie->setTrailers(array_slice($trailers,0,$pCountTrailers));
	} else {
		//
		// here we do a Youtube search for the combiantion of the movie title annd "Trailer"
		//
		$searchTxt = urlencode($movie->title." trailer"); 

		$api_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=".$pCountTrailers."&q=" . $searchTxt . "&key=".YOUTUBE_API_KEY;
		$result = json_decode(curl_get_contents($api_url));
		$ytQueries++;
		$trailers=[];
		if (isset($result->error)) {
			$trailers[0] = (object)['success'=>FALSE, 'message'=>$result->error->errors[0]->message];
			$movie->setTrailers($trailers);
			return;
		}
		foreach($result->items as $trailer) {
			$trailers[] = (object)['success'=>TRUE, 'videoId'=> $trailer->id->videoId, 'title'=>urldecode($trailer->snippet->title), 'image'=>$trailer->snippet->thumbnails->default->url]; 
		}
		//
		// write the search result to query
		//
		$fhandle = fopen($trailerCacheFile,'w');
		fputs($fhandle,json_encode($trailers));
		fclose($fhandle);
		$movie->setTrailers($trailers);
	}
}

function main() {
/**
  * function main is as expected the main function. It all happens here.
  *
  * as long as the requested last-record is higher as the last cached record, and 
  * TMDB has more pages, we keep querying TMDB for new records.
  * When we have enough records in cache, we return the requested records
  */

	global $pSearchTxt, $pFirstRec, $pCount, $response, $tmdbQueries, $ytQueries, $pCache;

// check input
	if ($pSearchTxt == "") {
		$response->error(1001,"No search text given");
		echo $response->toJson();
		return;
	}

	$pFirstRec = (IsNullOrEmpty($pFirstRec) ? "0" : $pFirstRec);
	if (!mkInt($pFirstRec)) {
		$response->error(1002,"Can't make sense of firstrec [".$pFirstRec."] must be integer!");
		echo $response->toJson();
		return;
	}

	$pCount = (IsNullOrEmpty($pCount) ? "20" : $pCount);
	if (!mkInt($pCount)) {
		$response->error(1003,"Can't make sense of count [".$pCount)."] must be integer!";
		echo $response->toJson();
		return;
	}

	$pCountTrailers = (IsNullOrEmpty($pCountTrailers) ? "0" : $pCountTrailers);
	if (!mkInt($pCountTrailers)) {
		$response->error(1004,"Can't make sense of trailers [".$pCountTrailers."] must be integer!");
		echo $response->toJson();
		return;
	}

	$pCache = (IsNullOrEmpty($pCache) ? "TRUE" : $pCache);
	if (!mkBool($pCache)) {
		$response->error(1005,"Can't make sense of cache [".$pCache."] must be either True or False");
		echo $response->toJson();
		return;
	}

	$r = readMovieCache($response);

// while we don't have enough movies in our #response, and we haven't received the lastpage...

	while ($response->movieCount() < ($pFirstRec + $pCount) && $response->lastPage < $response->totalPages) {
		// get nextPage
		$nextPage = $response->lastPage + 1;
		getMoviesPage($response, $nextPage);
		if (!$response->success) {
			echo $response->toJson();
			return;
		}
		// write response to cache
		// this could be outside the while-loop but then concurrent requests will slow down
		writeMovieCache($response);
	}
	// get the trailers
	foreach(array_slice($response->movies,$pFirstRec,$pCount) as $requestedMovie) {
		getTrailers($requestedMovie);
	}
	// output requested recs
	echo $response->toJson($pFirstRec, $pCount, $tmdbQueries, $ytQueries);
}
main();
?>