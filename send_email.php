<?php

require_once ('Aws_bulk_email.php');

//only 50 index at a time
$data = [
	[
		'id'	=> 1,
		'receipients'	=> 'test1@yopmail.com',
		'subject' => 'Test1 subject here',
		'message' => 'Test1 message here'
	],
	[
		'id'	=> 2,
		'receipients'	=> 'test2@yopmail.com',
		'subject' => 'Test2 subject here',
		'message' => 'Test2 message here'
	]
];

$obj = new Aws_bulk_email();

//first check or create email template
$obj->checkBulkEmailTemplateExistsOnAws();

//send email
$response = $obj->sendEmail($data);
echo "<pre>";
print_r($response);exit;