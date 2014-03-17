<?php 
/*
 * Author: Gabriel Letarte
 * 
*/
class EloquentPaginator{

	const PAGE_REPLACE 	= '@page@';
	const URL_NEXT 		= 'Next >';
	const URL_PREVIOUS 	= '< Previous';

	//To be initialize after query
	public $results = array();	//Results on the currents page
	public $navigation = '';	//Urls to navigate other pages
	public $navNext = '';			//Url to navigate to the next page
	public $navPrevious = '';		//Url to navigate to the previous
	
	public $pageAt = 1;			//Page at
	public $nbPages;			//Number of pages total
	
	//Parameters
	private $query = null;		//Eloquent or Fluent query
	private $perPage = 20;		//Number per page
	private $maxNavigationLinks = 25;	//Maximum number of pages links displayed
	private $linkFormat = null;

	/**********************************************************************************************
	 * Public API
	**********************************************************************************************/
	
	/**
	 * @param query:     Eloquent query or Fluent query.
	 * @param pageAt:    The current page you want to visualize.
	 * @param navFormat: The link to the next page with @page@ being the page number.
	*/
	public static function paginate($query, $pageAt = 1, $navFormat = null){
		if (!is_numeric($pageAt)){
			$pageAt = 1;
		}
		$paginator = new EloquentPaginator();
		$paginator->doPaginate($query, $pageAt, $navFormat = null);
		return $paginator;
	}

	/**
	 * @return: a JSon representation of this.
	*/
	public function toJson(){
		$a = array(
			'results' => $this->results->toJson(),
			'navigation' => $this->navigation,
			'pageAt' => $this->pageAt,
			'nbPages' => $this->nbPages,
			'perPage' => $this->perPage,
		);
		return json_encode($a);
	}
	
	/**********************************************************************************************
	 * Private methods
	**********************************************************************************************/
	
	private function doPaginate($query, $pageAt = 1, $linkFormat = null){

		//sanitize pageAt parameter
		if (!is_numeric($pageAt)){
			$pageAt = intval($pageAt);
		}
		
		//Init
		if ($linkFormat === null || !is_string($linkFormat)){
			$this->linkFormat = self::getDefaultUrlFormat();
		}
		$this->pageAt = $pageAt;
		$this->query = $query;
		
		//Get the total number of pages
		//If pageAt is bigger than the number of page, set it to the last page
		$this->nbPages = $this->getNumberOfPages();
		if ($this->pageAt > $this->nbPages){
			$this->pageAt = $this->nbPages - 1;
			if ($this->pageAt == 0){
				$this->pageAt = 1;
			}
		}
		
		//Pagination
		$this->results = $this->getOneDataPage();
		$this->navigation = $this->createNavigation();
		$this->navPrevious = $this->createUrlPrevious();
		$this->navNext = $this->createUrlNext();
	}
	
	/**
	 * Get the total number of pages from this query
	*/
	private function getNumberOfPages(){
		return ceil($this->query->count() / $this->perPage);
	}
	
	/**
	 * Get one page of data from this query
	*/
	private function getOneDataPage(){
		return $this->query
			->skip(($this->pageAt - 1) * $this->perPage)
			->take($this->perPage)
			->get();
	}
	
	/**
	 * @return: a HTML div containing the URLs to navigate this pagination
	*/
	private function createNavigation(){
		$links = '<nav class="capsulePagination">';
		
		//Determine where we start if there are more links than we can display
		// if ($this->nbPages > $this->maxNavigationLinks){
			// $offset = $this->pageAt - floor($this->maxNavigationLinks / 2);
			// if ($offset - 
		// }
		
		$i=1;
		while ($i <= $this->nbPages && $i <= $this->maxNavigationLinks){
			if ($i == $this->pageAt){
				$links .= '<b>'.$i.'</b>';
			}
			else{
				$links .= '<a href="'.str_replace(self::PAGE_REPLACE, $i, $this->linkFormat).'">'.$i.'</a>';
			}
			
			//Happend '-' between each page links
			if ($i != $this->nbPages && $i <= $this->maxNavigationLinks - 1){
				$links .= ' - ';
			}
			$i++;
		}
		
		$links .= '</nav>';
		
		return $links;
	}
	
	/**
	 * Return a url to navigate to the previous page.
	 * Will return '' if at page 1
	*/
	private function createUrlPrevious(){
		$url = '';
		if ($this->pageAt != 1){
			$url .= '<a href="'.
				str_replace(self::PAGE_REPLACE, $this->pageAt - 1, $this->linkFormat).
				'">'.self::URL_PREVIOUS.'</a>';
		}
		return $url;
	}
	
	/**
	 * Return a url to navigate to the previous page.
	 * Will return '' if at last page
	*/
	private function createUrlNext(){
		$url = '';
		if ($this->pageAt != $this->nbPages){
			$url .= '<a href="'.
				str_replace(self::PAGE_REPLACE, $this->pageAt + 1, $this->linkFormat).
				'">'.self::URL_NEXT.'</a>';
		}
		return $url;
	}
	
	/**
	 * @return: the current url + '/PAGE_REPLACE'
	 * http://webcheatsheet.com/php/get_current_page_url.php
	*/
	private static function getDefaultUrlFormat() {
	
		$pageURL = 'http';
		
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"){
			$pageURL .= "s";
		}
		
		$pageURL .= "://";
		
		if ($_SERVER["SERVER_PORT"] != "80"){
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} 
		else{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		$pageURL = rtrim($pageURL, '/');
		//Remove the last occurence of page/{number}
		if (preg_match('/\/page\/[0-9]{1,}/', $pageURL, $match) !== 0){
			$pageURL = substr($pageURL, 0 ,strrpos($pageURL, $match[0]));
		}
		$pageURL = $pageURL.'/page/'.self::PAGE_REPLACE;
		
		return $pageURL;
	}
}
?>