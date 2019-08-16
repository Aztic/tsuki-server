<?php

use \Firebase\JWT\JWT;

class Project extends CI_Model {
	public $username;
	public $createdAt;
	public $updatedAt;
	private $table;

	function __construct(){
		$this->load->database();
		$this->load->helper('string');
		$this->config->load('jwt_config');
	}

	/**
	 * Checks the existence of a project
	 * @param $projectName
	 * @return bool
	 */
	private function projectExistence($projectName) {
		$sql = 'SELECT id FROM projects WHERE project_identifier=?';
		$query = $this->db->query($sql, array($projectName));
		$result = $query->result();
		return sizeof($result) > 0;
	}

	/**
	 * Creates a new project for a determined user
	 * @param $data
	 * @return bool
	 */
	public function createProject($data) {
		$projectIdentifier = random_string('sha1', 20);
		while($this->projectExistence($projectIdentifier)) {
			$projectIdentifier = random_string('sha1', 20);
		}
		$sql = 'INSERT INTO projects(data, project_name, project_description, user_id, project_identifier) VALUES(?,?,?,?,?)';
		$query = $this->db->query(
			$sql,
			array($data['content'], $data['projectName'], $data['projectDescription'], $data['userId'], $projectIdentifier)
		);
		if($query) {
			return $projectIdentifier;
		}
		return false;
	}

	/**
	 * Get project by it's identifier. Returns a boolean if not found. Otherwise, a database set
	 * @param $projectId
	 * @return mixed
	 */
	public function getProject($projectId) {
		$sql = 'SELECT project_name, project_description, data, user_id FROM projects WHERE project_identifier=?';
		$query = $this->db->query($sql, array($projectId));
		$result = $query->result_array();
		if(sizeof($result) == 0) {
			return false;
		}
		return $result[0];
	}

	/**
	 * Gets projects paginated for an user
	 * @param $userId
	 * @param $page
	 * @return array
	 */
	public function getProjects($userId, $page) {
		$offset = ($page-1)*50;
		// Get total of projects
		$projectCount = $this->db->query('SELECT count(id) FROM projects WHERE user_id=?', array($userId));
		$projectCount = ($projectCount->result())[0]->count;

		$projectsSql = 'SELECT project_name, project_description, project_identifier, created_at, updated_at FROM projects WHERE user_id=? ORDER BY updated_at DESC LIMIT 50 OFFSET ?';
		$query = $this->db->query($projectsSql, array($userId, $offset));
		return [
			'total' => $projectCount,
			'result' => $query->result(),
			'itemsPerPage' => 50
		];
	}

	/**
	 * Deletes a specified project
	 * @param $projectId
	 * @return boolean
	 */
	public function deleteProject($projectId) {
		$sql = 'DELETE FROM projects WHERE project_identifier=?';
		$query = $this->db->query($sql, array($projectId));
		return $query;
	}

	/**
	 * Updates a given project
	 * @param $data
	 * @return boolean
	 */
	public function updateProject($data) {
		$projectIdentifier = $data['projectId'];
		$sql = 'UPDATE projects SET data=?, project_name=?, project_description=?, updated_at=now() WHERE project_identifier=?';
		$query = $this->db->query(
			$sql,
			array($data['content'], $data['projectName'], $data['projectDescription'], $projectIdentifier)
		);
		return $query;
	}
}
