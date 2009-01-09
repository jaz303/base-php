<?php
// Demonstration configuration file for BasePHP
// This is all there is to it.

// You need a database connection to run the tests...
// If you're using MySQL, the default 'test' database should suffice, so just
// fill in your username/password.
$_GDB['default'] = array(
    'driver'    => 'MySQL',
    'host'      => '127.0.0.1',
    'username'  => 'root',
    'password'  => '',
    'database'  => 'test'
);

$root = dirname(__FILE__);
ini_set("include_path", ".:{$root}/inc");

function base_php_autoloader($class) {
    
    // START-MAP
    static $map = array (
      'Annotation' => 'base/Annotation.php',
      'Arrays' => 'base/Arrays.php',
      'Base' => 'base/Base.php',
      'Callback' => 'base/Callback.php',
      'FunctionCallback' => 'base/Callback.php',
      'InstanceCallback' => 'base/Callback.php',
      'StaticCallback' => 'base/Callback.php',
      'Component' => 'base/Component.php',
      'Event' => 'base/Component.php',
      'Component_Extension' => 'base/Component.php',
      'Date' => 'base/Date.php',
      'Errors' => 'base/Errors.php',
      'File' => 'base/File.php',
      'GDB_Exception' => 'base/GDB.php',
      'GDB_RollbackException' => 'base/GDB.php',
      'GDB_QueryException' => 'base/GDB.php',
      'GDB_IntegrityConstraintViolation' => 'base/GDB.php',
      'GDB_ForeignKeyViolation' => 'base/GDB.php',
      'GDB_UniqueViolation' => 'base/GDB.php',
      'GDB_CheckViolation' => 'base/GDB.php',
      'GDB' => 'base/GDB.php',
      'GDBMySQL' => 'base/GDB.php',
      'GDBResult' => 'base/GDB.php',
      'GDBResultMySQL' => 'base/GDB.php',
      'H' => 'base/H.php',
      'H_Table' => 'base/H.php',
      'Error_UnsupportedImageType' => 'base/Image.php',
      'Image' => 'base/Image.php',
      'Inflector' => 'base/Inflector.php',
      'Validatable' => 'base/Interfaces.php',
      'MIME' => 'base/MIME.php',
      'Money_ConversionError' => 'base/Money.php',
      'Money' => 'base/Money.php',
      'Money_Bank' => 'base/Money.php',
      'HTTP_Request' => 'base/Request.php',
      'Template' => 'base/Template.php',
      'V' => 'base/V.php',
      'XML_RPC' => 'base/XML/RPC.php',
      'XML_RPC_Request' => 'base/XML/RPC.php',
      'XML_RPC_Response' => 'base/XML/RPC.php',
      'Error_AssertionFailed' => 'base/Test/Base.php',
      'Test_Base' => 'base/Test/Base.php',
      'Test_ConsoleReporter' => 'base/Test/ConsoleReporter.php',
      'Test_Invoker' => 'base/Test/Invoker.php',
      'Test_MethodInvoker' => 'base/Test/MethodInvoker.php',
      'Test_Reporter' => 'base/Test/Reporter.php',
      'Test_Suite' => 'base/Test/Suite.php',
      'Test_Unit' => 'base/Test/Unit.php',
      'Model_StateError' => 'base/Model/Base.php',
      'Model_NoSuchAttributeError' => 'base/Model/Base.php',
      'Model_Base' => 'base/Model/Base.php',
      'Model_Association' => 'base/Model/Base.php',
      'Model_Association_HasOne' => 'base/Model/Base.php',
      'Model_Association_HasMany' => 'base/Model/Base.php',
      'Model_Association_BelongsTo' => 'base/Model/Base.php',
      'ISO_Country' => 'base/ISO/Country.php',
      'ISO_Language' => 'base/ISO/Language.php',
      'GDB_SQL' => 'base/GDB/SQL.php',
      'File_Upload' => 'base/File/Upload.php',
      'Feed' => 'base/Feed/Model.php',
      'Feed_Item' => 'base/Feed/Model.php',
      'Error_IllegalArgument' => 'base/Error/IllegalArgument.php',
      'Error_IllegalState' => 'base/Error/IllegalState.php',
      'Error_IO' => 'base/Error/IO.php',
      'Error_MethodMissing' => 'base/Error/MethodMissing.php',
      'Error_NoSuchElement' => 'base/Error/NoSuchElement.php',
      'Error_NotFound' => 'base/Error/NotFound.php',
      'Error_OutOfBounds' => 'base/Error/OutOfBounds.php',
      'Error_Syntax' => 'base/Error/Syntax.php',
      'Error_UnsupportedOperation' => 'base/Error/UnsupportedOperation.php',
      'Date_Interval' => 'base/Date/Interval.php',
      'Date_Range' => 'base/Date/Range.php',
      'Date_Time' => 'base/Date/Time.php',
      'Contact_Form' => 'base/Contact/Form.php',
      'Contact_Form_Field' => 'base/Contact/Form.php',
      'Contact_Form_Text' => 'base/Contact/Form.php',
      'Contact_Form_Textarea' => 'base/Contact/Form.php',
      'Contact_Form_Select' => 'base/Contact/Form.php',
      'Contact_Form_Date' => 'base/Contact/Form.php',
      'Contact_Form_Time' => 'base/Contact/Form.php',
      'Contact_Form_Checkbox' => 'base/Contact/Form.php',
      'Contact_Form_File' => 'base/Contact/Form.php',
      'Contact_Mailer' => 'base/Contact/Form.php',
      'Contact_CSV' => 'base/Contact/Form.php',
    );
    // END-MAP
    
    if (isset($map[$class])) require $map[$class];

}

spl_autoload_register('base_php_autoloader');
require 'base/functions.php';
?>