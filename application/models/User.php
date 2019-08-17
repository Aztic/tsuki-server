<?php

use \Firebase\JWT\JWT;

class User extends CI_Model {
	public $username;
	public $createdAt;
	public $updatedAt;
	private $table;

	function __construct(){
		$this->table = 'users';
		$this->load->database();
		$this->config->load('jwt_config');
	}

	function getRows($params = array()){
		$this->db->select('*');
		$this->db->from($this->table);

		if(array_key_exists('conditions', $params)){
			foreach ($params['conditions'] as $key => $value) {
				$this->db->where($key,$value);
			}
		}

		if(array_key_exists('id', $params)){
			$this->db->where('id',$params['id']);
			$query = $this->db->get();
			$result = $query->row_array();
		}
		else{
			if(array_key_exists("start",$params) && array_key_exists("limit",$params)){
				$this->db->limit($params['limit'],$params['start']);
			}
			elseif(!array_key_exists("start",$params) && array_key_exists("limit",$params)){
				$this->db->limit($params['limit']);
			}
			$query = $this->db->get();
			if(array_key_exists("returnType",$params) && $params['returnType'] == 'count'){
				$result = $query->num_rows();
			}
			elseif(array_key_exists("returnType",$params) && $params['returnType'] == 'single'){
				$result = ($query->num_rows() > 0)?$query->row_array():FALSE;
			}
			else{
				$result = ($query->num_rows() > 0)?$query->result_array():FALSE;
			}
		}
		return $result;
	}

	public function insert($data = array()){
		$insert = $this->db->insert($this->table,$data);

		if($insert){
			return $this->db->insert_id();
		}
		else{
			return false;
		}

	}

	/**
	 * Generates a new JWT token with the given data
	 * @param $data
	 * @return string
	 */
	private function generateToken($data) {
		return JWT::encode($data, $this->config->item('jwt_key'));
	}

	/**
	 * Handles the login procedure
	 * @param array $data
	 * @return array|bool
	 */
	public function manageLogin($data = array()){
		$temp = array('conditions'=>array('username'=>$data['username']),'returnType'=>'single');
		$get_user = $this->getRows($temp);
		if($get_user){
			if(password_verify(base64_encode(hash('sha256', $data['password'],true)),$get_user['password'])){
				$token = $this->generateToken([
					'id' => $get_user['id'],
					'expiresAt' => time() + $this->config->item('jwt_expiration')
				]);
				return ['token'=>$token, 'expiresIn'=>$this->config->item('jwt_expiration')];
			}
		}
		return false;
	}

	/**
	 * Check if an username or email is being used
	 * @param $data
	 * @return bool
	 */
	public function userExists($data) {
		$sql = "SELECT * FROM users WHERE username = ? or email = ?";
		$query = $this->db->query($sql, array($data['username'], $data['email']));
		$result = $query->result();
		return sizeof($result) > 0;
	}

	/**
	 * Register a new user
	 * @param $data
	 * @return bool|array
	 */
	public function register($data) {
		$insert = $this->insert($data);
		if($insert) {
			return ['token' => $this->generateToken([
				'id' => $insert,
				'expiresAt' => time() + $this->config->item('jwt_expiration')
			]), 'expiresIn' => $this->config->item('jwt_expiration')];
		}
		return false;
	}

	/**
	 * Checks the status of a token
	 * @param $token
	 * @return array
	 */
	public function checkToken($token) {
		$decoded = null;
		try {
			$decoded = JWT::decode($token, $this->config->item('jwt_key'), array('HS256'));
		}
		catch (Exception $e) {
			// Invalid token
			return ['status' => false, 'error'=>'Invalid token', 'key'=>$this->config->item('jwt_key')];
		}

		$decoded = (array) $decoded;
		if(time() >= $decoded['expiresAt']) {
			return ['status' => false, 'error'=>'Invalid token'];
		}
		return ['status' => true, 'data' => $decoded];
	}

	/**
	 * Check if the given user (from the headers) is a valid one
	 * @param $headers
	 * @return array
	 */
	public function isValidUser($headers) {
		if(array_key_exists('x-token', $headers)) {
			$verification = $this->checkToken($headers['x-token']);
			if($verification['status']) {
				$data = $verification['data'];
				return ['valid'=>true, 'data'=>$data];
			}
			return ['valid' => false, 'data'=>$verification];
		}
		return ['valid'=>false];
	}
}
