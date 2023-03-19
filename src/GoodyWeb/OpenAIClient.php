<?php

namespace GoodyWeb;

use GuzzleHttp\Client;

class OpenAIClient {

    public $api_key;
    public $organization;
    public $model;

    public function __construct() {
        $this->api_key = "";
        $this->organization = "";
        $this->model = "davinci";
        $this->file_name_prefix = "GW";
    }

    // $jsonl is an array of json objects
    public function createFile( $jsonl ) {

        $contents = "";

        if( is_array($jsonl) ) {
            foreach( $jsonl as $json_string ) {
                $json = json_decode( $json_string );
                if( !is_null($json) ) {
                    if( isset($json->prompt) && isset($json->completion) ) {
                        $contents = $contents . $json_string . PHP_EOL;
                    }
                }
            }
        }

        if( !empty( $contents ) ) {
            $temporary_file = tempnam(sys_get_temp_dir(),$this->file_name_prefix);

            $stem_name = basename($temporary_file, ".tmp");

            $jsonl_file = sys_get_temp_dir() . "/" . $stem_name . ".jsonl";

            $write_success = file_put_contents( $temporary_file, $contents );

            $rename_success = rename( $temporary_file, $jsonl_file );

            if( !($write_success === false) && !($rename_success === false)  ) {
                return $jsonl_file;
            }
        }

        return false;
    }

    // $file_path is the full path of a .jsonl file
    public function uploadFile( $file_path ) {

        $filename = basename( $file_path );

        $client = new Client();

        $response = $client->request('POST', 'https://api.openai.com/v1/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'multipart' => [
                [
                    'name' => 'purpose',
                    'contents' => 'fine-tune',
                ],
                [
                    'name' => 'file',
                    'contents' => fopen( $file_path, 'r'),
                    'filename' => $filename,
                ],
            ],
        ]);

        return $response->getBody();

    }

    // $file_id is the ID of the uploaded file
    public function retrieveFile( $file_id ) {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/v1/files/{$file_id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
        ]);

        return $response->getBody();

    }

    // $fine_tune_id is the ID of the fine-tune event
    public function retrieveFineTune( $fine_tune_id ) {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/v1/fine-tunes/{$fine_tune_id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
        ]);

        return $response->getBody();

    }

    public function createFineTune( $training_file ) {

        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
        ]);

        $response = $client->post('fine-tunes', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'json' => [
                'training_file' => $training_file,
                'model' => $this->model,
            ],
        ]);

        return $response->getBody();

    }

    public function listModels() {

        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer '.$this->api_key,
            ],
        ]);

        $response = $client->request('GET', 'models');

        return $response->getBody();

    }


}