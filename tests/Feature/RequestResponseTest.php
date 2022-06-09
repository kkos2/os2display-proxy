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

    public function test_post_request_stores_request()
    {
        $response = $this->post('/some/path', [ 'key' => 'value' ]);

        $response->assertStatus(201);
    }

    public function test_get_request_retrieves_response()
    {
        $this->postJson('/known/path', [ 'key' => 'value' ]);

        $response = $this->getJson('/known/path');

        $response->assertStatus(200);
        $response->assertJson([ 'key' => 'value' ]);
    }

    public function test_responses_are_retrieved_by_content_type() {
        $this->call('POST', '/xml/path', [], [], [], ['CONTENT_TYPE' => 'application/xml'], '<xml/>');
        $response = $this->getJson('/xml/path');

        $response->assertStatus(404);

        $response = $this->get('/xml/path', ['Accept' => 'application/xml']);
        $response->assertStatus(200);
        $response->assertSee('<xml/>', false);
    }

}
