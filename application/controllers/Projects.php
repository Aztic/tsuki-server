<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->model('user');
		$this->load->model('project');
		$this->load->helper('string');
	}

	/**
	 * Check if all input properties are present
	 * @param $elements
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
	 * Handles POST and GET request for /projects
	 * @return mixed
	 */
	public function index()
	{
		// Get user headers
		$headers = $this->input->request_headers();
		if($this->input->method() == 'post'){
			// POST method, so try to create a project
			if(!$this->checkElements(['content', 'projectName']) || strlen($this->input->post('projectName')) == 0) {
				// Content and project name are mandatory
				return $this->returnHelper(400, ['error' => 'Missing elements']);
			}

			// If 'projectDescription' is sent by the user, use it
			$projectDescription = $this->input->post('projectDescription') ? $this->input->post('projectDescription') : '';
			// Check if the user is valid
			$validUser = $this->user->isValidUser($headers);
			if(!$validUser['valid']) {
				return $this->returnHelper(401, ['error' => 'You can\'t do that']);
			}
			$userid = $validUser['data']['id'];
			$project = $this->project->createProject([
				'content' => $this->input->post('content'),
				'projectName' => $this->input->post('projectName'),
				'projectDescription' => $projectDescription,
				'userId' => $userid
			]);
			if(gettype($project) == 'boolean') {
				return $this->returnHelper(400, ['error' => 'error creating project']);
			}
			// Return the project identifier
			return $this->returnHelper(200, ['identifier' => $project]);
		}
		else if($this->input->method() == 'get') {
			// Get a list of all projects for the current user
			$validUser = $this->user->isValidUser($headers);
			if(!$validUser['valid']) {
				return $this->returnHelper(401, ['error' => 'You can\'t do that']);
			}
			$userid = $validUser['data']['id'];
			$page = $this->input->get('page') ? (int) $this->input->get('page') : 1;
			$page = max($page, 1);
			$projects = $this->project->getProjects($userid, $page);
			return $this->returnHelper(200, $projects);
		}
		return $this->returnHelper(400, ['error' => 'invalid method']);
	}

	/**
	 * Handles projects updates
	 * @param null $id
	 * @return mixed
	 */
	public function updateProject($id=null) {
		$data = [];
		$putData = json_decode(file_get_contents("php://input"),true);
		if(!$this->checkElements(['content', 'projectName'], 'put', $putData) || strlen($putData['projectName']) == 0) {
			return $this->returnHelper(400, ['error' => 'Missing elements', 'check' => $putData, 'keys'=>array_keys($putData)]);
		}
		$headers = $this->input->request_headers();
		$validUser = $this->user->isValidUser($headers);
		if($id == null) {
			return $this->returnHelper(404, ['error' => 'Invalid project']);
		}
		$project = $this->project->getProject($id);

		if(!$validUser['valid'] || $project['user_id'] != $validUser['data']['id']) {
			return $this->returnHelper(401, ['error' => 'You can\'t do that']);
		}
		$projectDescription = array_key_exists('projectDescription', $putData) ? $putData['projectDescription'] : $project->project_description;
		$result = $this->project->updateProject([
			'content' => $putData['content'],
			'projectName' => $putData['projectName'],
			'projectDescription' => $projectDescription,
			'projectId' => $id
		]);
		return $this->returnHelper(200, ['success'=>$result, 'data'=>$data]);
	}

	/**
	 * Handles deletions of projects
	 * @param null $id
	 * @return mixed
	 */
	public function deleteProject($id=null) {
		$headers = $this->input->request_headers();
		$validUser = $this->user->isValidUser($headers);
		if($id == null) {
			return $this->returnHelper(404, ['error' => 'Invalid project']);
		}
		$project = $this->project->getProject($id);
		if(gettype($project) == 'boolean') {
			return $this->returnHelper(404, ['error' => 'Invalid project']);
		}

		if(!$validUser['valid'] || $project['user_id'] != $validUser['data']['id']) {
			return $this->returnHelper(401, ['error' => 'You can\'t do that']);
		}
		$result = $this->project->deleteProject($id);
		return $this->returnHelper(200, ['success'=>$result]);
	}

	/**
	 * Get a project info. Either it's HTML or all its data
	 * @param null|string $id
	 * @return mixed
	 */
	public function getProject($id=null) {
		if($id == null) {
			return $this->returnHelper(404, ['error' => 'Invalid project']);
		}
		$project = $this->project->getProject($id);
		if(gettype($project) == 'boolean') {
			return $this->returnHelper(404, ['error' => 'Invalid project']);
		}
		if(!$this->input->get('edit') || $this->input->get('edit') != 'true') {
			return $this->returnHelper(200, $project['data'], 'text/html');
		}

		// Edit mode
		$headers = $this->input->request_headers();
		$validUser = $this->user->isValidUser($headers);
		if(!$validUser['valid'] || $project['user_id'] != $validUser['data']['id']) {
			return $this->returnHelper(401, ['error' => 'You can\'t do that']);
		}
		unset($project['user_id']);
		return $this->returnHelper(200, $project);
	}
}
