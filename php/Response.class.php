<?php
class Response {
/* class response contains the information gotten from "The Movie Database" */
	public $success;
	public $statusCode;
	public $lastPage;
	public $totalPages;
	public $totalResults;
	public $movies = array();

	public function setHeader($lastPage, $totalPages, $totalResults) {
		$this->success = TRUE;
		$this->statusCode = 0;
		$this->lastPage = $lastPage;
		$this->totalPages = $totalPages;
		$this->totalResults = $totalResults;
	}
	public function error($statusCode, $message) {
		$this->success = FALSE;
		$this->statusCode = $statusCode;
		$this->message = $message;
	}
	public function addMovie($movie) {
		$this->movies[] = $movie;
	}
	public function movieCount() {
		return count($this->movies);
	}
	public function jsonHeader() {
		if ($this->success) {
			$obj = (object)[
					'success'=>$this->success,
					'lastRecord'=>count($this->movies),
					'lastPage'=>$this->lastPage,
					'totalPages'=>$this->totalPages,
					'totalResults'=>$this->totalResults,
				];
		} else {
			$obj = (object)[
					'success'=>$this->success,
					'status_code'=>$this->statusCode,
					'message'=>$this->message
			];
		}
		return json_encode($obj);
	}
	public function toJson($from = "", $count = 0, $tmdbQueries = 0, $ytQueries = 0) {
		if ($this->success) {
			$obj = (object)[
					'success'=>$this->success,
					'totalResults'=>$this->totalResults,
					'tmdbQueries'=>$tmdbQueries,
					'ytQueries'=>$ytQueries,
					'movies'=>array_slice($this->movies, $from, $count)
				];
		} else {
			$obj = (object)[
					'success'=>$this->success,
					'status_code'=>$this->statusCode,
					'message'=>$this->message
			];
		}
		return json_encode($obj);
	}
}
?>