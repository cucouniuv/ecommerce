<?php 

namespace Hcode\Model;

use \Hcode\Model;
use \Hcode\DB\Sql;

class User extends Model {

	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret";

	protected $fields = [
		"iduser", "idperson", "deslogin", "despassword", "inadmin", "dtregister", "desperson", "desemail", "nrphone"
	];

	public static function login($login, $password):User
	{

		$db = new Sql();

		$results = $db->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0) {
			throw new \Exception("Não foi possível fazer login.");
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"])) {

			$user = new User();
			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception("Não foi possível fazer login.");

		}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			|| 
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["iduser"] !== $inadmin //Se o usuário acessou a loja, ele não pode acessar o admin se não for
		) {
			
			header("Location: /admin/login");
			exit;

		}

	}

	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("select * from tb_users a inner join tb_persons b on a.idperson = b.idperson order by b.desperson");
	}

	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("call sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>$this->getdesperson(), //getdesperson chama function __call($name, $args) e divide get + desperson 
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()									
		));

		// Erro de Undefined offset. Solução: adicionar todos os campos em protected $fields que é do model em:
		// if (in_array($fieldName, $this->fields))
		//var_dump($results);
		//exit;

		$this->setData($results[0]);	
	}

	public function get($iduser)
	{
		$sql = new Sql();

		$results = $sql->select("select * from tb_users a inner join tb_persons b on a.idperson = b.idperson where a.iduser = :iduser", array(
			":iduser"=>$iduser
		));

		$this->setData($results[0]);		
	}

	public function update()
	{
		$sql = new Sql();

		$results = $sql->select("call sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(), //getdesperson chama function __call($name, $args) e divide get + desperson 
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()									
		));

		$this->setData($results[0]);			
	}

	public function delete()
	{
		$sql = new Sql();

		$results = $sql->select("call sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));
	}

	public static function getForgot($email)
	{
		$sql = new Sql();

		$results = $sql->select("select * from tb_persons a inner join tb_users b on a.idperson = b.idperson where a.desemail = :email;", array(':email'=>$email));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha");			
		}
		else
		{
			$data = $results[0];

			$retornoRecuperacao = $sql->select("call sp_userpasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($retornoRecuperacao) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha");
			}
			else
			{
				$dataRecovery = $retornoRecuperacao[0];

				$code = base64_encode(mcrypt_encrypt(MYCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

				$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

				$mailer = new Mailer($data["desemail"], $data["desperson"], "redifinir senha", "forgot", array(
					"name"=>$data["desperson"],
					"link"=>$link
				));

				$mailer->send();

				return $data;
			}
		}
	}

	public static function validForgotDecrypt($code)
	{
		$idrecovery = mcrypt_decrypt(MYCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

		$sql = new Sql();	

		$results = $sql->select("
			select * from tb_userspasswordsrecoveries a
			inner join tb_users b using(iduser)
			inner join tb_persons c using (idperson)
			where a.idrecovery = :idrecovery and a.dtrecovery is null and DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
			", array(
				":idrecovery"=>$idrecovery
			));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha");
			
		}
		else
		{
			return $results[0];
		}		
	}

	public static function setForgotUsed($idrecovery)
	{
		$sql = new Sql();

		$sql->query("update tb_userspasswordsrecoveries set dtrecovery = now() where idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));
	}

	public function setPassword($password)
	{
		$sql = new Sql();

		$sql->query("update tb_users set despassword = :password where iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));
	}

}

 ?>