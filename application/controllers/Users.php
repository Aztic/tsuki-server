<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->model('user');
	}

	/**
	 * Check if all input properties are present
	 * @param $elements
	 * @param string $method
	 * @param array $extraArray
	 * @return bool
	 */
	private function checkElements($elements, $method='post', $extraArray=[]) {
		foreach ($elements as $element) {
			if($method == 'post' && !$this->input->post($element)) {
				return false;
			}
			else if($method == 'put' && !array_key_exists($element, $extraArray)){
				return false;
			}
		}
		return true;
	}

	/**
	 * Simple function to avoid repeating that code again and again
	 * @param $statusCode
	 * @param $content
	 * @param string $type
	 * @return mixed
	 */
	private function returnHelper($statusCode, $content, $type='application/json') {
		return $this->output
			->set_content_type($type)
			->set_status_header($statusCode)
			->set_output( $type == 'application/json'? json_encode($content, JSON_UNESCAPED_SLASHES) : $content);
	}

	/**
	 * Try to register an user
	 * @return mixed
	 */
	public function register(){
		if($this->input->method() != 'post'){
			return $this->returnHelper(400, ['error'=>'invalid method']);

		}
		$enabled = $this->config->item('enable_register');
		if(!$enabled) {
			return $this->returnHelper(401, ['error'=>'Currently disabled']);
		}
		if(!$this->checkElements(['username', 'password', 'email'])) {
			return $this->returnHelper(400, ['error'=>'invalid form']);
		}
		$userData = [
			'username' => trim(strip_tags($this->input->post('username'))),
			'password'=>password_hash(base64_encode(hash('sha256',$this->input->post('password'),true)),PASSWORD_DEFAULT),
			'email'=> strip_tags($this->input->post('email')),
		];
		if(strlen($userData['username']) == 0) {
			return $this->returnHelper(400, ['error'=>'invalid form']);
		}
		if($this->user->userExists($userData)) {
			return $this->returnHelper(400, ['error'=>'User already exists']);
		}
		$token = $this->user->register($userData);
		if(gettype($token) == 'boolean') {
			// Invalid response
			return $this->returnHelper(500, ['error'=>'Error during register']);
		}
		else {
			return $this->returnHelper(200, $token);
		}
	}

	/**
	 * Try to log in an user
	 * @return mixed
	 */
	public function login() {
		if($this->input->method() != 'post'){
			return $this->returnHelper(400, ['error'=>'Invalid method']);
		}
		if(!$this->checkElements(['username', 'password'])) {
			return $this->returnHelper(400, ['error'=>'Invalid form content']);
		}
		$loginData = $this->user->manageLogin(
			['username'=>$this->input->post('username'), 'password'=>$this->input->post('password')]
		);
		$data = array();
		$status = 200;
		if(gettype($loginData) == 'boolean') {
			$data = ['error' => 'invalid credentials'];
			$status = 400;
		}
		else {
			$data = $loginData;
		}
		return $this->returnHelper($status,$data);
	}

	/**
	 * Get general user info from a token
	 * @return mixed
	 */
	public function userInfo() {
		$headers = $this->input->request_headers();
		$data = array('error' => 'invalid token');
		$status = 400;
		$toCheck = null;
		if(array_key_exists('x-token', $headers)) {
			$toCheck = 'x-token';
		}
		else if(array_key_exists('X-Token', $headers)) {
			$toCheck = 'X-Token';
		}
		if($toCheck != null) {
			$verification = $this->checkToken($headers[$toCheck]);
			if($verification['status']) {
				$data = $verification['data'];
				$status = 200;
			}
		}
		return $this->returnHelper($status,$data);
	}


	/**
	 * Return the current status of registrations
	 * @return mixed
	 */
	public function isEnabledRegister() {
		$enabled = $this->config->item('enable_register');
		return $this->returnHelper(200,['enabled' => $enabled]);
	}
}

