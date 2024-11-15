<?php
require 'vendor/autoload.php';
require_once 'config.php';

use GuzzleHttp\Client;

class ApiService
{
    private Client $client;
    private $token;
    private $config;

    public function __construct(array $config)
    {
        $this->client = new Client(['verify' => false,]);
        $this->config = $config;
    }

    public function login($username, $password)
    {
        $response = $this->client->post($this->config['auth'], [
            'json' => [
                'username' => $username,
                'password' => $password,
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->token = $data['token'];

        return $this->token;
    }

    public function getUpdatedRequests(int $days = 3)
    {
        $response = $this->jsonRpcRequest('M4GetTasks', [
            'params' => [
                'lastUpdate' => date('Y-m-d H:i:s', strtotime("-$days days"))
            ]
        ]);

        echo "Updated Requests: " . print_r($response['result'], true) . PHP_EOL;
        return $response['result'];
    }

    public function getTaskDetails($taskId)
    {
        $details = $this->jsonRpcRequest('M4GetTaskDetails', [
            'params' => [
                'taskId' => $taskId
            ]
        ]);
        echo "Task Details: " . print_r($details['result'], true) . PHP_EOL;

        return $details;
    }

    public function addAttachments(int $taskId, array $files): string
    {
        foreach ($files as $file) {
            $uploadResponse = $this->client->post($this->config['file_upload'], [
                'headers' => ['Authorization' => 'Bearer ' . $this->token],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($file, 'r'),
                    ]
                ]
            ]);

            $fileData = json_decode($uploadResponse->getBody(), true)['result'];

            $this->jsonRpcRequest('M4AddTaskAttach', [
                'params' => [
                    'taskId' => $taskId,
                    'files' => [
                        [
                            'guid' => $fileData['guid'],
                            'typeAttachId' => 5
                        ]
                    ]
                ]
            ]);
        }

        return "Attachments added successfully";
    }

    public function addComment(string $taskId, string $comment)
    {
        return $this->jsonRpcRequest('M4AddTaskComment', [
            'params' => [
                'taskId' => $taskId,
                'comment' => $comment,
                'isPublic' => true
            ]
        ]);
    }

    public function logout()
    {
        $response = $this->jsonRpcRequest('logout', [
            'id' => 1,
            'jsonrpc' => '2.0',
        ]);

        echo "Logout successful." . PHP_EOL;
        return $response;
    }

    private function jsonRpcRequest($method, $params)
    {
        $response = $this->client->post($this->config['api'], [
            'headers' => ['Authorization' => 'Bearer ' . $this->token],
            'json' => array_merge([
                'method' => $method,
                'id' => 1,
                'jsonrpc' => '2.0',
            ], $params)
        ]);

        return json_decode($response->getBody(), true);
    }
}