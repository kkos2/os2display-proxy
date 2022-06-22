<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestResponseTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_unknown_path_returns_404()
    {
        $response = $this->get('/unknown/path');

        $response->assertStatus(404);
    }
    public function test_post_request_requires_authentication()
    {
        $this->markTestSkipped('Authentication with Shield is disabled for now.');

        $response = $this->post('/some/path', [ 'key' => 'value' ]);

        $response->assertStatus(401);
    }


    public function test_post_request_stores_request()
    {
        $response = $this->postJsonWithBasicAuth('/some/path', [ 'key' => 'value' ], [], 'local', 'local');

        $response->assertStatus(201);
    }

    public function test_get_request_retrieves_response()
    {
        $this->postJsonWithBasicAuth('/known/path', [ 'key' => 'value' ], [], 'local', 'local');

        $response = $this->getJson('/known/path');

        $response->assertStatus(200);
        $response->assertJson([ 'key' => 'value' ]);
    }

    public function test_responses_are_retrieved_by_content_type() {
        $this->postXmlWithBasicAuth('/xml/path', '<xml/>', [], 'local', 'local');
        $response = $this->getJson('/xml/path');

        $response->assertStatus(404);

        $response = $this->get('/xml/path', ['Accept' => 'application/xml']);
        $response->assertStatus(200);
        $response->assertSee('<xml/>', false);
    }

    public function test_responses_can_be_updated() {
        $this->postJsonWithBasicAuth('/known/path', [ 'key' => 'value' ], [], 'local', 'local');
        $response = $this->postJsonWithBasicAuth('/known/path', [ 'new key' => 'new value' ], [], 'local', 'local');
        $response->assertStatus(201);

        $response = $this->getJson('/known/path');
        $response->assertStatus(200);
        $response->assertJson([ 'new key' => 'new value' ]);
    }

    /**
     * Test JSON posts with support for authentication.
     *
     * This is basically a copy of MakesHttpRequests::postJson() with a few
     * arguments added.
     */
    public function postJsonWithBasicAuth($uri, array $data = [], array $headers = [], string $username, string $password) {
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $server_vars = $this->transformHeadersToServerVars($headers) + [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password
        ];

        return $this->call(
            'POST',
            $uri,
            [],
            $this->prepareCookiesForJsonRequest(),
            $files,
            $server_vars,
            $content
        );
    }

    public function postXmlWithBasicAuth(string $uri, string $content, array $headers = [], string $username, string $password) {
        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/xml',
            'Accept' => 'application/xml',
        ], $headers);

        $server_vars = $this->transformHeadersToServerVars($headers) + [
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password
            ];

        return $this->call(
            'POST',
            $uri,
            [],
            $this->prepareCookiesForJsonRequest(),
            [],
            $server_vars,
            $content
        );
    }

}
