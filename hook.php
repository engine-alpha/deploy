<?php

ignore_user_abort(true);
set_time_limit(0);

header('Content-Type: text/plain');
header('Content-Length: 0');
flush();

require 'vendor/autoload.php';

$payload = file_get_contents('php://input');
$data = json_decode($payload);
$headers = getallheaders();

$event = @$headers['X-GitHub-Event'] ?: null;
$signature = @$headers['X-Hub-Signature'] ?: null;
$delivery = @$headers['X-GitHub-Delivery'] ?: null;

$hash = 'sha1=' . hash_hmac('sha1', trim($payload), GITHUB_SECRET, false);

if(!hash_equals($hash, $signature)) {
	die('Wrong signature.');
}

if($data->repository->name === 'engine-alpha') {
	$app = new App\EngineAlpha();
	$app->handleEvent($event, $data);
} else if($data->repository->name === 'figureneditor') {
	$app = new App\Figureneditor();
	$app->handleEvent($event, $data);
}
