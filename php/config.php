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
  * Connfiguration file for the getMovies API
  *
  * 
  * getMovies.php checks cache for the requested records, if found
  * the cache records are returned, if not found the TMDB API and Youtube API's
  * are used to retrieve the data
  *
  */

/**
  * Please modify to suit your environment. You should obtain keys from The Movie dataase
  * and You tube to get this working
  *
  * @link  https://developers.themoviedb.org/3/
  * @link. https://developers.google.com/youtube/v3/
  */

define ("TMDB_API_KEY", "[YOUR TMDB API KEY HERE]");
define ("YOUTUBE_API_KEY", "[YOUR YOUTUBE API KEY HERE]");

// the location of your cache files
define ("CACHEDIR", "../cache");

// the maximum age of cache file (in seconds)
define ("CACHEAGE", 2 * 24 * 60 * 60); // 2 days max age of config file

// configuration data from TMDB
define ("CONFIGFILE", CACHEDIR . "/config.cache");




?>