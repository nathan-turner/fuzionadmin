<?php
require 'vendor/autoload.php';
//require 'conn.php';
//Dotenv::load(__DIR__);
$sendgrid_username = 'physiciancareer'; //$_ENV['mfollowell'];
$sendgrid_password = '7853Pinn816!'; //$_ENV['Phg3356!'];
$to = "nturner@phg.com"; //$_ENV['TO'];
$sendgrid = new SendGrid($sendgrid_username, $sendgrid_password, array("turn_off_ssl_verification" => true));
$email = new SendGrid\Email();
$email->addTo($to)->
setFrom($to)->
setSubject('[sendgrid-php-example] Owl named %yourname%')->
setText('Owl are you doing?')->
setHtml('<strong>%how% are you doing?</strong>')->
addSubstitution("%yourname%", array("Mr. Owl"))->
addSubstitution("%how%", array("Owl"))->
addHeader('X-Sent-Using', 'SendGrid-API')->
addHeader('X-Transport', 'web');//->
//addAttachment('./gif.gif', 'owl.gif');
$response = $sendgrid->send($email);
var_dump($response);