<?php 

namespace Hcode;

class Model {

	private $values = [];

	public function setData($data)
	{
		foreach ($data as $key => $value)
		{
			$this->{"set".$key}($value); //cria os atributos dinâmicamente
		}
	}

	public function __call($name, $args)
	{
		$method = substr($name, 0, 3); //Identificar se é um get ou set
		$fieldName = substr($name, 3, strlen($name));

		if (in_array($fieldName, $this->fields))
		{
			switch ($method)
			{
				case "get":
					return $this->values[$fieldName];
				break;

				case "set":
					$this->values[$fieldName] = $args[0];
				break;
			}
		}
	}

	public function getValues()
	{
		return $this->values;
	}

}

 ?>
