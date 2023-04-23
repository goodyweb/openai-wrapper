<?php

namespace GoodyWeb;

use GuzzleHttp\Client;

class OpenAIClient {

    public $api_key;
    public $organization;
    public $model;

    public function __construct() {
        $this->version = "v1";
        $this->api_key = "";
        $this->organization = "";
        $this->model = "davinci";
        $this->file_name_prefix = "";
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

        $response = $client->request('POST', "https://api.openai.com/{$this->version}/files", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
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

    public function listFiles() {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/{$this->version}/files", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
            ],
        ]);

        $json_string_response = $response->getBody();
        return json_decode($json_string_response);

    }

    // $file_id is the ID of the uploaded file
    public function retrieveFile( $file_id ) {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/{$this->version}/files/{$file_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
            ],
        ]);

        return $response->getBody();

    }

    // $file_id is the ID of the uploaded file
    public function retrieveFileContent( $file_id ) {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/{$this->version}/files/{$file_id}/content", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
            ],
        ]);

        return $response->getBody();

    }


    // $fine_tune_id is the ID of the fine-tune event
    public function retrieveFineTune( $fine_tune_id ) {

        $client = new Client();

        $response = $client->request('GET', "https://api.openai.com/{$this->version}/fine-tunes/{$fine_tune_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
            ],
        ]);

        return $response->getBody();

    }

    public function createFineTune( $training_file, $options=null ) {

        // Default options
        $model = $this->model;
        $n_epochs = 4;
        $batch_size = null;
        $learning_rate_multiplier = null;
        $prompt_loss_weight = 0.01;
        $suffix = null;

        // Options override if options were specified
        if( !empty($options) && is_object($options) ) {

            if( isset($options->model) )
            $model = $options->model;

            if( isset($options->n_epochs) )
            $n_epochs = $options->n_epochs;

            if( isset($options->batch_size) )
            $batch_size = $options->batch_size;

            if( isset($options->learning_rate_multiplier) )
            $learning_rate_multiplier = $options->learning_rate_multiplier;

            if( isset($options->prompt_loss_weight) )
            $prompt_loss_weight = $options->prompt_loss_weight;

            if( isset($options->suffix) )
            $suffix = $options->suffix;

        }

        $client = new Client([
            'base_uri' => "https://api.openai.com/{$this->version}/",
        ]);

        $response = $client->post('fine-tunes', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->api_key}",
            ],
            'json' => [
                'training_file' => $training_file,
                'model' => $model,
                'n_epochs' => $n_epochs,
                'batch_size' => $batch_size,
                'learning_rate_multiplier' => $learning_rate_multiplier,
                'prompt_loss_weight' => $prompt_loss_weight,
                'suffix' => $suffix
            ],
        ]);

        return $response->getBody();

    }

    public function listModels() {

        $client = new Client([
            'base_uri' => "https://api.openai.com/{$this->version}/",
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
            ],
        ]);

        $response = $client->request('GET', 'models');

        return $response->getBody();

    }

    public function completion( $prompt, $options=null ) {

        // Default options
        $model = $this->model;
        $suffix = null;
        $max_tokens = 16;
        $temperature = 1;
        $top_p = 1;
        $n = 1;
        $stream = false;
        $logprobs = null;
        $echo = false;
        $stop = null;
        $presence_penalty = 0;
        $frequency_penalty = 0;
        $best_of = 1;
        $logit_bias = null;

        // Options override if options were specified
        if( !empty($options) && is_object($options) ) {

            if( isset($options->model) )
            $model = $options->model;

            if( isset($options->suffix) )
            $suffix = $options->suffix;

            if( isset($options->max_tokens) )
            $max_tokens = $options->max_tokens;

            if( isset($options->temperature) )
            $temperature = $options->temperature;

            if( isset($options->top_p) )
            $top_p = $options->top_p;

            if( isset($options->n) )
            $n = $options->n;

            if( isset($options->stream) )
            $stream = $options->stream;

            if( isset($options->logprobs) )
            $logprobs = $options->logprobs;

            if( isset($options->echo) )
            $echo = $options->echo;

            if( isset($options->stop) )
            $stop = $options->stop;

            if( isset($options->presence_penalty) )
            $presence_penalty = $options->presence_penalty;

            if( isset($options->frequency_penalty) )
            $frequency_penalty = $options->frequency_penalty;

            if( isset($options->best_of) )
            $best_of = $options->best_of;

            if( isset($options->logit_bias) )
            $logit_bias = $options->logit_bias;

        }
        $json = [
            'model' => $model,
            'prompt' => $prompt,
            'suffix' => $suffix,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'top_p' => $top_p,
            'n' => $n,
            'stream' => $stream,
            'logprobs' => $logprobs,
            'echo' => $echo,
            'stop' => $stop,
            'presence_penalty' => $presence_penalty,
            'frequency_penalty' => $frequency_penalty,
            'best_of' => $best_of
        ];

        if( !empty( $logit_bias ) ) {
            $json['logit_bias'] = $logit_bias;
        }

        // Request to OpenAI
        $client = new Client();
        $response = $client->post("https://api.openai.com/{$this->version}/completions", [
                'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->api_key}",
            ],
            'json' => $json
        ]);

        // Process the response
        $completion_response_json_string = $response->getBody();
        $completion_object = json_decode($completion_response_json_string);

        return $completion_object;

    }



}