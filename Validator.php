<?php

class Validator
{
	protected $trigger;
	protected $id;
	
	protected $input;
	protected $constraints;
	protected $unique;
	
	protected $defaults;
	
	protected $errors;
	protected static $errorCodes;
	
	public function __construct($args = array())
	{
		$this->input = array();
		$this->constraints = array();
		$this->errors = array();
		$this->unique = array();
		$this->trigger = '';
		$this->id = '';
		
		$this->init($args);
	}
	
	public function validate()
	{
		if (method_exists($this, 'preValidation'))
			$this->preValidation();
		
		if ( empty($this->input) )
			return false;
		
		$result = true;
		
		foreach ($this->input as $key => $value)
		{
			$this->errors[$key] = '';
			
			if (!isset( $_POST[ $value ] ) )
			{
				$this->errors[$key] = static::getErrorMessage('E_MISSING_FIELD');
				$result = false;
			}
			else
			{
				$result = $this->checkConstraints($key, $value) && $result;
				$_POST[$value] = trim($_POST[$value]);
			}
		}
		
		if (method_exists($this, 'postValidation'))
			return ($this->postValidation() && $result);
		
		return $result;
	}
	
	public function setTrigger($trigger)
	{
		$this->trigger = $trigger;
	}
	
	public function getTrigger()
	{
		return $this->trigger;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setInput($input)
	{
		if (!is_array($input) || empty($input))
			return false;
		
		$this->input = $input;
	}
	
	public function getInput()
	{
		return $this->input;
	}
	
	public function setUniqueCb($unique)
	{
		if (!is_array($unique) || empty($unique))
			return false;
		
		$this->unique = $unique;
	}
	
	public function getUniqueCb()
	{
		return $this->unique;
	}
	
	public function setConstraints($constraints)
	{
		if (!is_array($constraints) || empty($constraints))
			return false;
		
		foreach ($constraints as $key => $value)
			$this->constraints[$key] = explode(" ", $value);
	}
	
	public function getConstraints()
	{
		return $this->constraints;
	}
	
	public function getDefaults()
	{
		return $this->defaults;
	}
	
	public function hasData()
	{
		return isset( $_POST[ $this->trigger ] );
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
	
	public function checkConstraints($key, $value)
	{
		if ( !array_key_exists($key, $this->constraints) || $this->constraints[$key] == '')
			return true;
		
		$result = true;
		
		for ($i=0; $i<count($this->constraints[$key]); $i++)
		{
			
			switch ($this->constraints[$key][$i])
			{
				case 'required'	: $result = $result && $this->checkRequiredConstraint($key, $value); break;
				case 'unique'	: $result = $result && $this->checkUniqueConstraint($key, $value); break;
				case 'alpha'	: $result = $result && $this->checkAlphaConstraint($key, $value); break;
				case 'integer'	: $result = $result && $this->checkIntegerConstraint($key, $value); break;
				case 'float'	: $result = $result && $this->checkFloatConstraint($key, $value); break;
				case 'email'	: $result = $result && $this->checkEmailConstraint($key, $value); break;
			}
		}
		
		return $result;
	}
	
	
	public function init($args)
	{
		if ( !is_array($args) || empty($args) )
			return false;
		
		if ( !array_key_exists('trigger', $args) || !array_key_exists('id', $args) )
			return false;
		
		if ( !array_key_exists('input', $args) || !is_array($args['input']) )
			return false;
		
		if ( !array_key_exists('constraints', $args) || !is_array($args['constraints']) )
			return false;
		
		if (array_key_exists( 'uniqueCallbacks', $args ) )
			$this->unique = $args['uniqueCallbacks'];
		
		if (array_key_exists( 'defaults', $args ) )
			$this->defaults = $args['defaults'];
		
		$this->trigger = $args['trigger'];
		$this->id = $args['id'];
		
		$this->input = $args['input'];
		
		foreach ($args['constraints'] as $key => $value)
			$this->constraints[$key] = explode(" ", $value);
		
		foreach ($this->input as $key => $value)
			$this->errors[$key] = '';
		
		return true;
	
	}
	
	public function checkRequiredConstraint($key, $value)
	{
		$result = empty($_POST[$value]) && $_POST[$value] !== 0 && $_POST[$value] !== "0";
		
		if ($result == true)
			$this->errors[$key] = static::getErrorMessage('E_REQUIRED_FIELD');
		
		return ($result == false);
	}
	
	public function checkUniqueConstraint($key, $value)
	{
		if (empty($_POST[$value]))
			return true;
		
		if ( empty($this->id) || !isset($_POST[$this->id]) )
			$_POST[$this->id] = '0';
		
		$result = true;
		if (array_key_exists($key, $this->unique) && is_callable($this->unique[$key]) )
			$result = call_user_func($this->unique[$key], $_POST[$this->id], $_POST[$value]);
		
		if ($result == false)
			$this->errors[$key] = static::getErrorMessage('E_UNIQUE_FIELD');
		
		return $result;
	}
	
	public function checkAlphaConstraint($key, $value)
	{
		if (empty($_POST[$value]))
			return true;

		$result = preg_match('/^[\' \p{L}]+$/u', $_POST[$value]);
		
		if ($result == false)
			$this->errors[$key] = static::getErrorMessage('E_ALPHA_FIELD');
		
		return  $result;
	}
	
	public function checkIntegerConstraint($key, $value)
	{
		if (empty($_POST[$value]))
			return true;
		
		$number = $_POST[$value];
		
		// check for negative numbers
		if (substr($number, 0, 1) == "-")
			$number = substr($number, 1);
		
		$result = ctype_digit($number);
		
		if ($result == false)
			$this->errors[$key] = static::getErrorMessage('E_INTEGER_FIELD');
		
		return $result;
	}
	
	public function checkFloatConstraint($key, $value)
	{
		if (empty($_POST[$value]))
			return true;
		
		$result = false;
		$hasPointZero = false;
		
		// replace commas ',' with dots '.'
		$_POST[$value] = str_replace(",", ".", $_POST[$value]);
		
		// lets check if string terminates with '.0'
		// as this part is truncated with floatval
		// therefore it would return true with an integer comparison
		if (substr($_POST[$value], -2) == '.0')
			$hasPointZero = true;
		
		// Try to convert the string to a float
		$floatVal = floatval($_POST[$value]);
		
		// If the parsing succeeded and the value is not equivalent to an integer
		// or is equivalent to an integer but it has '.0'
		if($floatVal && ((intval($floatVal) != $floatVal) || $hasPointZero))
			$result = true;
		
		if ($result == false)
			$this->errors[$key] = static::getErrorMessage('E_FLOAT_FIELD');
		
		return $result;
	}
	
	public function checkEmailConstraint($key, $value)
	{
		if (empty($_POST[$value]))
			return true;
		
		$result = filter_var($_POST[$value], FILTER_VALIDATE_EMAIL);
		
		if ($result == false)
			$this->errors[$key] = static::getErrorMessage('E_EMAIL_FIELD');
		
		return $result;
	}
	
	public static function getErrorMessage($code)
	{
		switch($code)
		{
			case 'E_MISSING_FIELD'	: return 'This field is missing from data request';
			case 'E_REQUIRED_FIELD'	: return 'This field is required';
			case 'E_UNIQUE_FIELD'	: return 'This value is already in use';
			case 'E_ALPHA_FIELD'	: return 'This field cannot contain numbers or letters';
			case 'E_INTEGER_FIELD'	: return 'This field can only contain integer numbers';
			case 'E_FLOAT_FIELD'	: return 'This field can only contain decimal numbers';
			case 'E_EMAIL_FIELD'	: return 'This is not a valid email address';
		}
	}
	
	public static function initErrorCodes()
	{
		static::$errorCodes = [
			'E_MISSING_FIELD',
			'E_REQUIRED_FIELD',
			'E_UNIQUE_FIELD',
			'E_ALPHA_FIELD',
			'E_INTEGER_FIELD',
			'E_FLOAT_FIELD',
			'E_EMAIL_FIELD'
		];
	}
	
	public function setDefaults($input = array())
	{
		if ( !is_array($input) )
			return;
		
		$this->defaults = $input;
	}
	
	public function getOldInput()
	{
		$oldInput = array();
		
		if ( $this->hasData() )
		{
			foreach ($this->input as $key => $value)
			{
				$oldInput[$key] = isset($_POST[$value]) ? $_POST[$value] : '';
			}
			
			return $oldInput;
		}
		
		return $this->defaults;
	}
}

Validator::initErrorCodes();

?>
