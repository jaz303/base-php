<?php
require '../configure.php';

$table = new H_Table('#foo.bar.baz');
$table->add_class('bleem')
      ->cycle('row-1', 'row-2', 'row-3')
      ->if_empty("Sorry, couldn't find anything", "#empty.error")
      ->pad(10, '.padding')
      ->columns(array('surname' => 'Surname', 'forename' => 'Forename'));

echo $table->to_html();
echo "<hr/>";

$table->add(array('id' => 1, 'forename' => 'Jason', 'surname' => 'Frame'))
      ->add(array('id' => 2, 'forename' => 'Tommy', 'surname' => 'Bubba'))
      ->add(array('id' => 3, 'forename' => 'Jason', 'surname' => 'Frame'))
      ->add(array('id' => 4, 'forename' => 'Tommy', 'surname' => 'Bubba'));

echo $table->to_html();
echo "<hr/>";
?>