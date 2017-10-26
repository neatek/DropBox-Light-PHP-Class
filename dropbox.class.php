<?php
/**
	Working with entries:
	$data = $dropb->request('files', 'list_folder/continue', $params);
	var_dump($dropb->entry(1));  // very important
	var_dump($dropb->entry_filepath());
	var_dump($dropb->entry_name());
	var_dump($dropb->entry_is_file());
	var_dump($dropb->entry_deleted());

	foreach ($dropb->get_entries() as $key => $value) {
		var_dump($dropb->entry($key)); // very important
		var_dump($dropb->entry_filepath());
		var_dump($dropb->entry_name());
		var_dump($dropb->entry_is_file());
		var_dump($dropb->entry_deleted());
	}
**/
Class dropbox_neatek_class {

	const DROPBOX_API_URL = 'https://api.dropboxapi.com/2/';
	const DROPBOX_CONTENT_API_URL = 'https://content.dropboxapi.com/2/';
	const DROPBOX_CONTENT_METHODS = array(
		'get_shared_link_file'
	);

	private static $instance;
	private $token = '';
	private $last_answer = '';
	private $last_data = NULL;
	private $entry = NULL;
	private $shared_object = NULL;
	private $latest_params = NULL;
	//private $shared_list = NULL;
	/**
		require_once 'dropbox.class.php';
		$dropb = new dropbox_neatek_class('HERE_IS_YOUR_TOKEN');
	**/
	function __construct($token = '') {
		if(empty($token)) echo 'Provide please a token for Dropbox requests.';
		$this->token = $token;
	}

	/** only for this class **/
	function accept_params($array, $params) {
		if(!empty($params)) {
			foreach ($params as $key => $value) {
				if(strpos($key, 'sdk_dp_') !== false) {
					continue;
				}
				if(!empty($params[$key])) {
					$array[$key] = $value;
				}
			}
		}

		return $array;
	}

	/** only for this class **/
	function data_request($category = '', $method = '', $params = array()) {
		$method = str_replace('/', '_', $method);
		$filepath = dirname(__FILE__).'/default_params/'.$category.'_'.$method;
		if(file_exists($filepath)) {
			$data = (array) json_decode( file_get_contents($filepath) );
			$data = $this->accept_params($data, $params);
			return $data;
		}
		return '';
	}

	function show_lastest_params() {
		$this->show_struct('/* LASTEST_PARAMS_OF_REQUEST:::'."\r\n".print_r($this->latest_params,true));
	}

	/** only for this class **/
	function send_request($category = '', $method = '', $params = array()) {
		if(in_array($method, self::DROPBOX_CONTENT_METHODS)) {
			$request = self::DROPBOX_CONTENT_API_URL .'/'.$category.'/'.$method;
		}
		else {
			$request = self::DROPBOX_API_URL .'/'.$category.'/'.$method;
		}

		$data = $this->data_request($category, $method, $params);
		if(!function_exists('curl_init')) die('Please install cURL for php');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		$this->latest_params = $data;
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				'Authorization: Bearer '.$this->token,
				'Content-Type: application/json; charset=utf-8',
			)
		);
		
		$x = curl_exec($ch);
		if(!empty($x)) {
			$this->last_data = $this->json_or_null($x);
		}
		else {
			$this->last_data = NULL;
		}
		
		if(isset($params['sdk_dp_save']) && !empty($params['sdk_dp_save'])) {
			$this->last_answer = $x;
		}
		curl_close($ch);
		return $x;
	}

	function get_shared_links() {
		if(isset($this->last_data) && !empty($this->last_data)) {
			return $this->last_data->links;
		}

		return NULL;
	}

	/** get last data of last request **/
	function get_data() {
	 	return $this->last_data;
	}

	/** get entries of last request **/
	function  get_entries() {
		if(isset($this->last_data->entries))
			return $this->last_data->entries;

		return NULL;
	}

	/** get entry keys by Index **/
	function get_entry_keys_values($index = 0, $keys = false) {
		$entries = $this->get_entries()[$index];
		$values = NULL;
		if(isset($entries) && !empty($entries)) {
			$values = array();
			foreach ($entries as $k => $v) {
				if($keys == false)
					$values[] = $v;
				else
					$values[] = $k;
			}
		}
		return $values;
	}

	/** Example: 
		$data = $dropb->request('files', 'list_folder/continue', $params);
		foreach ($dropb->get_entries() as $key => $value) {
			$dropb->entry($key);
			if($dropb->entry_is_file() && !$dropb->entry_deleted()) {
				var_dump($dropb->entry_filepath());
				var_dump($dropb->heavy_share_path($dropb->entry_filepath(),true));
			}
		}
	**/
	function heavy_share_path($path = '', $download = false) {
		if(empty($path)) return NULL;
		$params = array('path' => $path);
		$this->share_path_or_file($params);
		if(!empty($this->get_shared_link()))
			return $this->get_shared_link($download);
		$data = $this->request('sharing', 'list_shared_links', $params);
		return $this->get_shared_link($download);
	}

	/**
		Example ( or USE - heavy_share_path($path) ):
			$data = $dropb->request('files', 'list_folder/continue', $params);
			foreach ($dropb->get_entries() as $key => $value) {
				
				$dropb->entry($key);
				
				if($dropb->entry_is_file() && !$dropb->entry_deleted()) {
					
					var_dump($dropb->entry_filepath());
					
					$dropb->share_path_or_file(
						array(
							'sdk_dp_save' => true, // support next - show_last()
							'path' => $dropb->entry_filepath()
						)
					);
					
					var_dump($dropb->get_shared_link());
					$dropb->show_last(); // file can be already shared, so we can see errors here.

					$params = array(
						'sdk_dp_save' => true,
						'path' => $dropb->entry_filepath()
					);

					var_dump($params);
					
					$data = $dropb->request('sharing', 'list_shared_links', $params);
					//$dropb->show_last();
					//$dropb->show_lastest_params();
					var_dump($dropb->get_shared_links());
					var_dump($dropb->get_shared_link(true));
				}
			}
	**/
	function share_path_or_file($params = array()) {
		if(!empty($params['path'])) {
			$this->data_request('sharing', 'create_shared_link_with_settings', $params);
			$this->shared_object = $this->request('sharing', 'create_shared_link_with_settings', $params);
			return $this->shared_object;
		}
		return NULL;
	}

	function get_shared() {
		return $this->shared_object;
	}

	/** using after - share_path_or_file **/
	function get_shared_link($download = false, $index = 0) {
		$url = '';
		/** support for get_shared_links() **/
		if(!empty($this->last_data) && isset($this->last_data->links) && isset($this->last_data->links[$index]->url)) {
			$url = $this->last_data->links[$index]->url;
		}
		/** support for - share_path_or_file() **/
		if(!empty($this->shared_object) && isset($this->shared_object->url)) {
			$url = $this->shared_object->url;
		}

		if(!empty($url)) {
			if(!empty($download)) {
				$url = str_replace('?dl=0', '?dl=1', $url);
			}

			return $url;
		}

		return NULL;
	}

	/** check if entry has tag, you can see all tags by 'show_entry($index = 0)' **/
	function entry_has_tag($tag = '') {
		if(!empty($this->entry)) {
			$entry = (array) $this->entry;
			if(isset($entry['.tag'])) {
				if(strpos($entry['.tag'], $tag) !== false) {
					return true;
				}
			}
		}

		return false;
	}

	/** is this entry is deleted? **/
	function entry_deleted() {
		return $this->entry_has_tag('deleted');
	}

	/** is this entry is file? **/
	function entry_is_file() {
		return $this->entry_has_tag('file');
	}

	/** get entry name **/
	function entry_name() {
		if(!empty($this->entry)) {
			if(isset($this->entry->name)) {
				return $this->entry->name;	
			}
		}

		return NULL;
	}

	/** get entry filepath **/
	function entry_filepath() {
		if(!empty($this->entry)) {
			if(isset($this->entry->path_display)) {
				return $this->entry->path_display;	
			}
		}

		return NULL;
	}

	/** get entry for using other functions in this class **/
	function entry($index = 0) {
		if(isset($this->get_entries()[$index])) {
			$this->entry = $this->get_entries()[$index];
			return $this->entry;
		}

		$this->entry = NULL;
		return NULL;
	}

	/** advanced - show entry object **/
	function show_entry($index = 0) {
		$this->show_entry_keys($index);
		$this->show_entry_values($index);
	}

	/** show entry keys by Index **/
	function show_entry_keys($index = 0) {
		$this->show_struct('/* ENTRY_KEYS_INDEX#'.$index.':::'."\r\n".print_r($this->get_entry_keys_values($index, true),true));
	}

	/** show entry values by Index **/
	function show_entry_values($index = 0) {
		$this->show_struct('/* ENTRY_VALUES_INDEX#'.$index.':::'."\r\n".print_r($this->get_entry_keys_values($index, false),true));
	}

	/** return string if not json **/
	function json_dec($string) {
		$x = json_decode($string);
		if(json_last_error() == JSON_ERROR_NONE) {
			return $x;
		}
		
		return $string;
	}

	/** return NULL if not json **/
	function json_or_null($json) {
		$data = $this->json_dec($json);
		if(!is_string($data)) {
			return $data;
		}

		return NULL;
	}

	/** only for this class **/
	function show_struct($text) {
		echo '<pre>'.print_r($text,true).'</pre>';
	}

	/** show last response from dropbox **/
	function show_last() {
		$this->show_struct('/* LATEST_REQUEST:::'."\r\n".print_r($this->json_dec($this->last_answer),true));
	}

	/** use it for request to dropbox. 
		Example - request('files', 'list_folder/continue', $params) 
		$params = {
			$params = array('cursor'=>'...', 'sdk_dp_save'=>true);
		}
		use - 'sdk_dp_save = true' for using show_last();
	**/
	function request($category = '', $method = '', $params = array()) {
		return json_decode($this->send_request($category, $method, $params));
	}

	/** if we have defaults parameters for request, get it. you can fill it in ./default_params/ 
		for 'files', 'list_folder/continue' it will be filename - files_list_folder_get_latest_cursor
	**/
	function get_params($category, $method) {
		$this->show_struct('/* PARAMS::: '.$category.'/'.$method."\r\n".print_r($this->data_request($category, $method),true));
	}

	/** get lastest cursor for folder **/
	function get_lastest_cursor($folder = '', $params = array()) {
		//$data = 
		$this->data_request('files', 'list_folder/get_latest_cursor', $params);
		return $this->request('files', 'list_folder/get_latest_cursor', $params);
	}
}