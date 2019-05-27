<?php
class Movie {
/* class Movie contains a movie-object */
	public $tmdbId;
	public $title;
	public $overview;
	public $releaseDate;
	public $image;
	public $trailers;

	function __construct($tmdbId, $title, $overview, $releaseDate, $image) {
		$this->tmdbId = $tmdbId;
		$this->title = $title;
		$this->overview = $overview;
		$this->releaseDate = $releaseDate;
		$this->image = $image;
	}
	public function jsonRecord() {
		$obj = (object)[
			'tmdbId' => $this->tmdbId,
			'title' => $this->title,
			'overview' => $this->overview,
			'releaseDate' => $this->releaseDate,
			'image' => $this->image,
			'trailers' => $this->trailers
		];
		return json_encode($obj);
	}
	public function setTrailers($trailers) {
		$this->trailers = $trailers;
	}

}
?>