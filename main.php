<?
class Main extends \enchinga\controller {
	
	public function index()
	{
		$data['version'] = $this->version;
		$this->view('views/main', $data);
	}

	
}