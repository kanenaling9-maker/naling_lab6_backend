<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'status' => 'okay',
			'message' => 'LavaLust is running',
		]);
	}
}
?>