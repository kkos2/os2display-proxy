<?php

namespace Tests\Feature;

use Tests\TestCase;
use function FluidXml\fluidxml;

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

    public function test_xml_responses_can_be_filtered_by_display() {
        $xml = <<< XML
<result is_array="true">
	<item>
		<startdate is_array="true">
			<item>09.10.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>09.10.2022</item>
		</enddate>
		<time is_array="true">
			<item>20:00 til 22:00</item>
		</time>
		<Nid>477</Nid>
		<billede is_array="true">
			<item>
				<img src="https://kulturn.kk.dk/sites/default/files/2022-01/Sk%C3%A6rmbillede%202022-01-11%20kl.%2016.27.12_0.png" alt="" height="554" width="1200" title="" />
			</item>
		</billede>
		<title>Dana Fuchs (US)</title>
		<field_teaser>Glæd dig til at opleve en stemme, der tåler sammenligning med ikoner som Janis Joplin, Otis Redding and Mick Jagger. </field_teaser>
		<skærme is_array="true">
			<item>pilegaarden_screen01</item>
			<item>pilegaarden_screen02</item>
		</skærme>
	</item>
	<item>
		<startdate is_array="true">
			<item>28.10.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>28.10.2022</item>
		</enddate>
		<time is_array="true">
			<item>10:00 til 10:45</item>
		</time>
		<Nid>698</Nid>
		<billede is_array="true">
			<item>
				<img src="https://kulturn.kk.dk/sites/default/files/2022-06/Babymassage_1.png" alt="" height="517" width="689" title="" />
			</item>
		</billede>
		<title>Kursus i Babymassage (2-6 måneder)</title>
		<field_teaser>Kom og få en dejlig nærværende stund med dit barn.</field_teaser>
		<skærme is_array="true">
			<item>noerrebrohallen_screen01</item>
			<item>noerrebrohallen_screen02</item>
		</skærme>
	</item>
	<item>
		<startdate is_array="true">
			<item>16.06.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>16.06.2022</item>
		</enddate>
		<time is_array="true">
			<item>14:00 til 16:00</item>
		</time>
		<Nid>700</Nid>
		<billede is_array="true">
			<item>
				<img src="https://kulturn.kk.dk/sites/default/files/2022-06/jazz_lounge_0219-kopi.JPG" alt="" height="554" width="1198" title="" />
			</item>
		</billede>
		<title>Jazz Lounge</title>
		<field_teaser>Bestil en kop kaffe og få den serveret med en velspillet lydside!</field_teaser>
		<skærme is_array="true">
			<item>pilegaarden_screen01</item>
		</skærme>
	</item>
</result>
XML;

        $this->postXmlWithBasicAuth('/xml/path', $xml, [], 'local', 'local');
        $response = $this->get('/xml/path?display=pilegaarden_screen01');

        $response->assertStatus(200);

        $count = fluidxml($response->getContent())
            ->query("//item[text() = 'pilegaarden_screen01']")
            ->size();
        $this->assertEquals(2, $count);

        $count = fluidxml($response->getContent())
            ->query("//item")
            ->filter(function($i, \DOMNode $node) {
                return fluidxml($node)
                    ->query("//skærme/item[text() != 'pilegaarden_screen01']")
                    ->size() > 0;
            })
            ->size();
        $this->assertEquals(1, $count);
    }

    public function test_xml_filtering_returns_empty_response_for_unknown_displays() {
        $xml = <<< XML
<result is_array="true">
	<item>
		<startdate is_array="true">
			<item>09.10.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>09.10.2022</item>
		</enddate>
		<time is_array="true">
			<item>20:00 til 22:00</item>
		</time>
		<Nid>477</Nid>
		<billede is_array="true">
			<item>
				<img src="https://kulturn.kk.dk/sites/default/files/2022-01/Sk%C3%A6rmbillede%202022-01-11%20kl.%2016.27.12_0.png" alt="" height="554" width="1200" title="" />
			</item>
		</billede>
		<title>Dana Fuchs (US)</title>
		<field_teaser>Glæd dig til at opleve en stemme, der tåler sammenligning med ikoner som Janis Joplin, Otis Redding and Mick Jagger. </field_teaser>
		<skærme is_array="true">
			<item>pilegaarden_screen01</item>
			<item>pilegaarden_screen02</item>
		</skærme>
	</item>
</result>
XML;

        $this->postXmlWithBasicAuth('/xml/path', $xml, [], 'local', 'local');
        $response = $this->get('/xml/path?display=pilegaarden_screen03');

        $response->assertStatus(200);

        $count = fluidxml($response->getContent())
            ->query("//item")
            ->size();
        $this->assertEquals(0, $count);
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
