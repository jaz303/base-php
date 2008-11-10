<?php
require '../configure.php';

$form = new Contact_CSV;

$form->text('forename', 10, 5)->label('Your name')->trim()->required();
$form->text('forename_confirmation');
$form->textarea('comments', 5, 40);
$form->select('gender', array('male', 'female'));
$form->date('booking_date');
$form->time('booking_time', 5);
$form->checkbox('receive_info');
$form->upload('my_file')->required();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form->handle($_POST, $_FILES);
}
?>

<form method='post' enctype='multipart/form-data'>
  <?= $form->to_html_table(array('with_errors' => true, 'submit_text' => 'Submit')); ?>
</form>