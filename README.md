# Validator
Simple form validation PHP class for POST request, useful when coding without a framework.

# Featured constraints
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
echo '<input type="text" name="username" value="' . $input[username] . '" />';
```
# Extensibility

