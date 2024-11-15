<?php
require_once 'config.php';
require_once 'api/ApiService.php';

$config = require 'config.php';
$apiService = new ApiService($config);

$apiService->login('', '');
$requests = $apiService->getUpdatedRequests();
$lastTask = end($requests)['taskId'];

$apiService->getTaskDetails($lastTask);
$apiService->addAttachments($lastTask, ['dog.jpg', 'dog.jpg']);
$apiService->addComment($lastTask, 'Test comment');
$apiService->logout();