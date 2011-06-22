<?php
namespace enchinga{
/**
 * HTTP: el maestro de ceremonias
 * Esta clase estática nomás corre el programa y rutea las madres
 * Tiene propiedades estáticas que chance y puede que luego sirvan al 
 * controlador instanciado.
 *
 * @package	Enchinga
 * @author	Roberto Hidalgo
 * @copyright Partido Surrealista Mexicano
 */
class http {
	
	protected static $request = '';
	protected static $root = '';
	protected static $base = '';
	
	
	public static function init()
	{
		// por si estamos en un sub-directorio, de localhost, mejor averiguo 
		// antes para no meter mierda en los segmentos
		$uri = trim($_SERVER['REQUEST_URI'], '/');
		if( rtrim($_SERVER['DOCUMENT_ROOT'],'/') != dirname(__FILE__) ){
			$dir = pathinfo(dirname(__FILE__), PATHINFO_FILENAME);
			self::$base = "/$dir";
			$uri = preg_replace("/^$dir\/?/", '', $uri);
		}
				
		self::$request->method = $_SERVER['REQUEST_METHOD'];
		self::$request->segments = array();
		if( strlen($uri)>0 ){
			self::$request->segments = explode('/', $uri);
		}
		self::$root = dirname(__FILE__).'/';
		//le quito el primer argumento a get, por si las moscas;
		array_shift($_GET);
		self::route();
	}
	
	public static function route()
	{
		$page = 'main';
		$s = self::$request->segments;
		if( isset($s[0]) && ($s[0]!='main' && $s[0]!='index') ){
			$page = $s[0];
		}

		$method = isset($s[1])? $s[1] : 'index';
		
		if( self::$request->method != 'GET' ){
			$method .= '_post';
		}
		
		if( file_exists("$page.php") ) {
			
			require "$page.php";
			$controller = new $page;
			if( method_exists($controller, $method) ) {
				//quitamos el controller y método de los argumentos 
				$args = array_splice($s,2);
				call_user_func_array(array($controller, $method), $args);
			} else {
				throw new Exception("Método no implementado - $page/$method", 404);
			}
			
		} else {
			throw new Exception("$page.php no existe", 404);
		}
	}
} //end HTTP


/**
 * Controller, el objeto feliz que nos da felicidad y faclidad
 *
 * @package	Enchinga
 * @author	Roberto Hidalgo
 * @copyright Partido Surrealista Mexicano
 */
class controller extends http {
	
	protected $db;
	protected $session;
	public $version = 0.1;
	
	/**
	 * Constructor de Controlador
	 * Arma los desmadres y echa a andar el asunto.
	 *
	 * @author	Roberto Hidalgo
	 */
	public function __construct()
	{
		$this->request = parent::$request;
		$this->base = parent::$base;
		$this->root = parent::$root;
		$this->host = "http://".$_SERVER['HTTP_HOST'];
		if( file_exists("config.php") ){
			require "config.php";
			if( isset($db) ){
				$dbconfig = (object) $db;
				$this->db = new db($dbconfig);
			}
		}
		$this->session = new Session;
	}
	
	public function auth()
	{
		$this->session->usuario;
		if( !$this->session->usuario ){
			$this->location('main/login');
		} else {
			$this->usuario = $this->session->usuario;
		}
	}
	
	public function location($donde)
	{
		header("Location: $this->base/$donde");
		echo "Redirigiendo a $this->base/$donde";
		die();
	}
	
	public function get($que=null)
	{
		if( $que ){
			return $_GET[$que];
		} else {
			return $_GET;
		}
	}
	
	public function post($que=null)
	{
		if( $que ){
			return $_POST[$que];
		} else {
			return $_POST;
		}
	}
	
	public function view($view, $data=array())
	{
		extract($data);
		require("{$this->root}$view.php");
	}
	
}//end controller


/**
 * Pendejaditas de sesión
 *
 * @package	Enchinga
 * @author	Roberto Hidalgo
 */
class Session {
	
	private $vars = array();
	
	public function __construct()
	{
		session_start();
	}
	
	public function __set($nombre, $valor)
	{
		if( !session_id() ){
			session_start();
		}
		
		if( empty($valor) ){
			//delete de los pobres
			$this->__unset($nombre);
		}
		
		if( is_array($valor) || is_object($valor) ){
			$valor = serialize($valor);
		}
		$this->vars[$nombre] = $valor;
		$_SESSION[$nombre] = $valor;
	}
	
	public function __get($que)
	{
		if( !session_id() ){
			session_start();
		}
		
		if( !isset($this->vars[$que]) && isset($_SESSION[$que]) ){
			//metamos a cache
			$this->vars[$que] = unserialize($_SESSION[$que]);
		}
		return $this->vars[$que];

	}
	
	public function __unset($que)
	{
		if( !session_id() ){
			session_start();
		}
		unset($this->vars[$que]);
		unset($_SESSION[$que]);
	}
	
}//end sesión


/**
 * Objetito pitero de DB, que hace cosas felices con magic methods
 *
 * @package	Enchinga
 * @author	Roberto Hidalgo
 */
class db {
	
	protected $dbo;
	protected $mysql=false;
	protected $mongo=false;
	
	public function __construct($config)
	{
		if( $config->driver=='mysql' ){
			$this->mysql = true;
			$this->dbo = @new \Mysqli($config->host, $config->user, $config->password, $config->database);
			if( $this->dbo->connect_error ){
				throw new Exception("DANG! No me pude conectar a la db: {$this->dbo->connect_error}", "MySQL Se cagó");
			}
			$this->dbo->set_charset("utf8");
		} elseif( $config->driver=='mongo' ){
			$this->mongo = true;
			$credenciales = '';
			if($config->user && $config->password){
				$credenciales = "$config->user:$config->password@";
			}
			try {
				$connection = new \Mongo("mongodb://{$credenciales}$config->host");
				$this->dbo = $connection->selectDB($config->database);
			} catch(\Exception $e){
				throw new Exception("DANG! No me pude conectar a la db: {$e->getMessage()}", "Mongo Se cagó");
			}
		}
		
	}
	
	public function __get($cual){
		if( $this->mysql ){
			return new MySQL_tabla($cual, $this->dbo);
		} else {
			return new Mongo_tabla($cual, $this->dbo);
		}
		
	}
	
}


/**
 * Acá sucede la magia de la db
 *
 * @package	Enchinga
 * @author	Roberto Hidalgo
 */
class MySQL_tabla {
	
	private $where = array();
	private $fields = array();
	
	public function __construct($tabla, \Mysqli $link)
	{
		$this->tabla = $tabla;
		$this->dbo = $link;
	}
	
	public function get($args)
	{
		$this->fields[] = $args;
		return $this;
	}
	
	
	public function update($set, $condiciones)
	{
		if( is_array($set) ){
			foreach( $set as $k=>$v){
				$c[] = "`$k`='$v'";
			}
			$this->fields = array_merge($this->where, $c);
		} else {
			$this->fields[] = "$set";
		}

		if( is_array($condiciones) ){
			foreach( $condiciones as $k=>$v){
				$c[] = "`$k`='$v'";
			}
			$this->where = join(',', array_merge($this->where, $c));
		} else {
			$this->where[] = "$condiciones";
		}
		
		$set = join(',', $this->fields);
		$where = count($this->where)>0? "WHERE ".join('AND ',$this->where) : '';
		$q = "UPDATE `$this->tabla` SET $set $where";
		$results = $this->dbo->query($q);
		return ( $this->dbo->affected_rows > 0 );
	}
	
	
	public function insert($set=array())
	{
		$set = (array) $set;
		foreach(array_values($set) as $valor){
			if( is_null($valor) || is_int($valor) || preg_match("/^'(.+)'$/", $valor) ){
				$valor = is_null($valor)? 'NULL' : $valor;
				$values[] = str_replace("'", '', $valor);
			} else {
				$valor = $this->dbo->real_escape_string($valor);
				$values[] = "'$valor'";
			}
		}
		$keys = join(',', array_keys($set));
		$values = join(',', $values);
		$q = "INSERT INTO $this->tabla ($keys) VALUES ($values)";
		$success = $this->dbo->query($q);
		if( $success ){
			$set = (object) $set;
			$set->id = $this->dbo->insert_id;
			return $set;
		} else {
			echo $this->dbo->error;
			return FALSE;
		}
	}
	
	
	public function find()
	{
		$args = func_get_args();
		if( count($args) == 2 ){
			$this->where = "`{$args[0]}`='{$args[0]}'";
		} elseif( count($args)==1 ) {
			if( is_array($args) ){
				foreach( $args[0] as $k=>$v){
					$c[] = "`$k`='$v'";
				}
				$this->where = array_merge($this->where, $c);
			} else {
				$this->where = $args;
			}
			
		}
		
		$fields = count($this->fields)>0 ? join(',', $this->fields) : '*';
		$where = count($this->where)>0? "WHERE ".join('AND ',$this->where) : '';
		$q = "SELECT $fields FROM `$this->tabla` $where";
		$results = $this->dbo->query($q);
		if( $results->num_rows==1 ){
			return $results->fetch_object();
		} elseif ( $results->num_rows>1 ){
			$rows = array();
			foreach( $results->fetch_all(MYSQLI_ASSOC) as $r ){
				$rows[] = (object) $r;
			}
			return (object) $rows;
		} else {
			return FALSE;
		}
		
	}
	
}


class Mongo_tabla extends \MongoCollection{
	
	protected $limit = false;
	protected $sort;
	protected $fields = array();
	
	public function __construct($coleccion, $link)
	{
		return parent::__construct($link, $coleccion);
	}
	
	public function get($args=array())
	{
		foreach($args as $field){
			$this->fields[$field] = true;
		}
		return $this;
	}
	
	public function limit($qty, $order=array('_id' => -1))
	{
		$this->limit = $qty;
		$this->sort = $order;
		return $this;
	}
	
	
	public function insert($set)
	{
		$set = (object) $set;
		$set = &$set;
		
		try {
			parent::insert((object) $set, array('safe' => true));
		} catch(\MongoCursorException $e) {
			$this->last_error = $e->getMessage();
			return FALSE;
		}
		
		return $set;
	}
	
	
	public function find($where=null, $smarts=true)
	{
		$cursor = !$where? parent::find() : parent::find($where);
		if( count($this->fields) > 0 ){
			$cursor->fields($this->fields);
		}
		if( $cursor->count() > 0 ){
			if( $this->limit ){
				$cursor = $cursor->sort($this->sort)->limit($this->limit);
				if( $cursor->count(true)==0 ){
					return FALSE;
				}
			}
			$results = array();
			$count = $cursor->count(true);
			if( $count==1 && $smarts){
				$results = $cursor->getNext();
				$results['id'] = "{$results['_id']}";
				unset($results['_id']);
				$results = (object) $results;
			} else {
				foreach($cursor as $id=>$object) {
					unset($object['_id']);
					$results[$id] = (object) $object;
					++$count;
				}
			}
			
			return $results;
			
		}
		return FALSE;
	}
	
	
}


class Exception {
	
	public function __construct($error, $titulo='WTF?')
	{
		echo "<html><head><title>o_O</title></head><body><h1>$titulo</h1><p>$error</p></body></html>";
		die();
	}
	
}

http::init();
}

namespace {

function e($que, $dump=false){
	if ( $dump || is_bool($que) ){
		var_dump($que);
	} elseif( is_array($que) || is_object($que) ){
		print_r($que);
	} else {
		echo $que;
	}
}

function fecha($timestamp) {
	$minuto = 60;
	$hora = 60*$minuto;
	$dia = 24*$hora;
	$diff = time()-$timestamp;
		
	if($timestamp==null){
		return "nunca";
	}
	
	if($diff<60){
		//hace diff segundos;
		return "hace $diff segundos";
	} elseif ($diff>60 AND $diff<$hora)  {
		//hace diff minutos;
		$minutos = floor($diff/$minuto);
		return "hace $minutos minutos";
	} elseif ($diff>=$hora AND $diff<$dia) {
		//hace diff horas;
		$horas = floor($diff/$hora);
		$mas = $diff%$hora>$hora/2? 'más de ' : 'poco más de ' ; //si es más de media hora, tons ponemos más de : poco más de
		$plural = $horas==1? '' : 's';
		return "hace $mas$horas hora$plural";
	} elseif ($diff>=$dia AND $diff<30*$dia){
		//hace x dias
		$dias = floor($diff/$dia);
		$plural = $dias==1? '' : 's';
		return "hace $dias día$plural";
	} else {
		return fechaAbsoluta($timestamp);
	}
}

function fechaAbsoluta($timestamp){
	$formato = '%A %e de %B de %Y - %r';
	return ucfirst(strftime($formato, $timestamp));
}

}