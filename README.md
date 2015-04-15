# Validator
Simple form validation PHP class for POST requests, useful when coding without a framework.
It uses associative arrays to assign $_POST keys to shorthands, which are provided as arguments to the class constructor.

1. [Featured Constraints](#featured-constraints)
2. [Usage](#usage)
3. [Callback for unique constraint](#callback-for-unique-constraint)
4. [Error Handling](#error-handling)
5. [Old input](#old-input)
6. [Extensibility](#extensibility)
7. [Demo](#demo)
8. [Requirements](#requirements)

#Usage


# Featured Constraints
It supports the following constraints:
- required: checks if the field is not empty
- unique: checks if the field value is not taken through a callback function provided by yourself
- char: checks if the field does not contain digits (0-9)
- integer: checks if the field is an integer number
- float: checks if the field is a decimal number
- email: checks if the field is a valid email address

# Callback for unique constraint
This feature is intended for database checks and calls an user-provided function which should check for uniqueness of the value in the database.
To the user-provided function will be passed two arguments, $id and $value, respectively the id of the record we are modifying (0 in case we are adding a new record) 
and the value to check.
The user-provided function should return TRUE if $value is not taken by another record, FALSE otherwise.


A basic example for a valid callback would be:
```
function isEmailAvailable($id, $value)
{
	$connection = new PDO('mysql:host=localhost;dbname=testdb;charset=utf8', 'username', 'password');
	
	$statement = $connection->prepare('SELECT email FROM users WHERE email = :email AND id != :id');
	$statement->bindValue(':email', $value, PDO::PARAM_STR);
	$statement->bindValue(':id', $id, PDO::PARAM_INT);
	
	$statement->execute();
	
	return $statement->fetchAll() == null;
}
```
# Error handling
When a field does not pass a constraint check, an error message is saved for later view.
You can get all error messages by calling the method getErrors(). It returns an associative array with field shorthands as keys and messages as values.

Example:
```
// assuming our validator is instantiated as $validator
$errors = $validator->getErrors();
echo $errors['email'];
```

# Old input
When updating an element with a form, you may want to show the user what he typed after validation occurs, especially if some fields dont pass the checks. You can do this by setting the values of input fields to the values returned by the method getOldInput(). If there is no old input to display, which basically means the user never submitted the form, getOldInput() will return custom default values you can specify by passing them through the method setDefaults().

Example:
```
// assuming our validator is instantiated as $validator
$input = $validator->getOldInput();
echo '<input type="text" name="username" value="' . $input['username'] . '" />';
```
# Extensibility
This class is written to be extensible on the validation and the error handling.

### Extending validation
You may extend validation with two methods:
- preValidation() which is called before the validation takes any action, and should not return any value
- postValidation() which is called after validation has occured and affects the final result of the validation by returning TRUE or FALSE

### Extending error handling
You may alter the error messages by implementing your own static method getErrorMessage().
Standard method looks like this:
```
public static function getErrorMessage($code)
{
	switch($code)
	{
		case 'E_MISSING_FIELD'	: return 'This field is missing from data request';
		case 'E_REQUIRED_FIELD'	: return 'This field is required';
		case 'E_UNIQUE_FIELD'	: return 'This value is already in use';
		case 'E_CHAR_FIELD'		: return 'This field cannot contain numbers';
		case 'E_INTEGER_FIELD'	: return 'This field can only contain integer numbers';
		case 'E_FLOAT_FIELD'	: return 'This field can only contain decimal numbers';
		case 'E_EMAIL_FIELD'	: return 'This is not a valid email address';
	}
}
```
This is especially useful for multilanguage support.

# Demo

# Requirements
Validator requires PHP 5.3.0 to support 'Late Static Bindings'
