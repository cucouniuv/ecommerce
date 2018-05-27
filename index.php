<?php 

require_once("vendor/autoload.php");

$app = new \Slim\Slim();

use \Hcode\Page;

$app->config('debug', true);

$app->get('/', function() {

	$page = new Page();
	$page->setTpl("index");
    
	//$sql = new Hcode\DB\Sql();	
	//$results = $sql->select("select * from tb_users");	
	//echo json_encode($results);
});

$app->run();

 ?>