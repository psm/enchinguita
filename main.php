<?
namespace app;
use \enchinga\Controller;

class Main extends Controller {
	
	public function index()
	{
		$data['version'] = \enchinga\VERSION;
		$this->view('main', $data);
	}

	
}