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


    public function test_xml_responses_can_be_filtered_by_place() {
        $xml = <<< XML
<result is_array="true">
	<item>
		<startdate is_array="true">
			<item>28.07.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>29.07.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 00:00</item>
		</time>
		<Nid>65</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-05/dirty.png" alt="" height="1165" width="2212" title="" />
			</item>
		</billede>
		<title>Dirty Fences (US) + De Høje Hæle</title>
		<field_teaser>Rockfest du sent vil glemme!</field_teaser>
		<field_display_institution is_array="true">
			<item>Demokratiets Hus</item>
		</field_display_institution>
	</item>
	<item>
		<startdate is_array="true">
			<item>20.07.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>21.07.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 01:00</item>
		</time>
		<Nid>58</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-04/vt.png" alt="" height="1165" width="2212" title="" />
			</item>
		</billede>
		<title>Valient Thorr (US) + Liar Thief Bandit (SE)</title>
		<field_teaser>Sveddryppende sommerrock!</field_teaser>
		<field_display_institution is_array="true">
			<item>Vanløse Hallerne</item>
		</field_display_institution>
	</item>
	<item>
		<startdate is_array="true">
			<item>04.08.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>04.08.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 23:59</item>
		</time>
		<Nid>63</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-04/277741949_1022919355019562_3179308792317594551_n.jpg" alt="" height="1079" width="2048" title="" />
			</item>
		</billede>
		<title>Melt-Banana (JP) + Uraño </title>
		<field_teaser>Kult-rock fra Japan - og måske et af verdens vildeste liveshows - rammer Basement denne sommer!</field_teaser>
		<field_display_institution is_array="true">
			<item>Bellahøj Svømmestadion </item>
			<item>Demokratiets Hus</item>
		</field_display_institution>
	</item>
</result>
XML;

        $this->postXmlWithBasicAuth('/xml/path', $xml, [], 'local', 'local');
        $response = $this->get('/xml/path?place=Demokratiets%20Hus');

        $response->assertStatus(200);

        $count = fluidxml($response->getContent())
            ->query("//item[text() = 'Demokratiets Hus']")
            ->size();
        $this->assertEquals(2, $count);

        $count = fluidxml($response->getContent())
            ->query("//item")
            ->filter(function($i, \DOMNode $node) {
                return fluidxml($node)
                        ->query("//field_display_institution/item[text() != 'Demokratiets Hus']")
                        ->size() > 0;
            })
            ->size();
        $this->assertEquals(1, $count);
    }

    public function test_service_spots_xml_responses_can_be_filtered_by_display() {
        $xml = <<< XML
<result is_array="true">
	<item>
		<field_display_institution is_array="true">
			<item>Demokratiets Hus</item>
		</field_display_institution>
		<nid>6</nid>
		<title_field>Dette er en TEstServicemeddelelser 1</title_field>
		<field_background_color>#1046e0</field_background_color>
		<body>Her skriver vi op og ned</body>
		<field_os2_display_list_spot is_array="true">
			<item>demokratihus_screen01</item>
		</field_os2_display_list_spot>
	</item>
	<item>
		<field_display_institution is_array="true">
			<item>Bellahøj Svømmestadion </item>
		</field_display_institution>
		<nid>7</nid>
		<title_field>Test AService to</title_field>
		<field_background_color>#2fd20f</field_background_color>
		<body>Vi priøver igen er det rigtig fin eller hvordan ser det ud? Vi priøver igen er det rigtig fin eller hvordan ser det ud?</body>
		<field_os2_display_list_spot is_array="true">
			<item>bellahoejsvoem-screen01</item>
		</field_os2_display_list_spot>
	</item>
	<item>
		<field_display_institution is_array="true">
			<item>Bellahøj Svømmestadion </item>
		</field_display_institution>
		<nid>8</nid>
		<title_field>Hjalte tester på TEST</title_field>
		<field_background_color>#802020</field_background_color>
		<body>Her er en servicemeddelelse til Os2</body>
		<field_os2_display_list_spot is_array="true">
			<item>bellahoejsvoem-screen01</item>
		</field_os2_display_list_spot>
	</item>
</result>
XML;
        $this->postXmlWithBasicAuth('/xml/path', $xml, [], 'local', 'local');

        $response = $this->get('/xml/path?display=bellahoejsvoem-screen01');
        $response->assertStatus(200);

        $count = fluidxml($response->getContent())
            ->query("//item[text() = 'bellahoejsvoem-screen01']")
            ->size();
        $this->assertEquals(2, $count);

        $count = fluidxml($response->getContent())
            ->query("//item")
            ->filter(function($i, \DOMNode $node) {
                return fluidxml($node)
                        ->query("//field_os2_display_list_spot/item[text() != 'bellahoejsvoem-screen01']")
                        ->size() > 0;
            })
            ->size();
        $this->assertEquals(0, $count);
    }

    public function test_event_results_are_sorted_by_date() {
        $xml = <<< XML
<result is_array="true">
	<item>
		<startdate is_array="true">
			<item>28.07.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>29.07.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 00:00</item>
		</time>
		<Nid>65</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-05/dirty.png" alt="" height="1165" width="2212" title="" />
			</item>
		</billede>
		<title>Dirty Fences (US) + De Høje Hæle</title>
		<field_teaser>Rockfest du sent vil glemme!</field_teaser>
		<field_display_institution is_array="true">
			<item>Demokratiets Hus</item>
		</field_display_institution>
	</item>
	<item>
		<startdate is_array="true">
			<item>20.07.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>21.07.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 01:00</item>
		</time>
		<Nid>58</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-04/vt.png" alt="" height="1165" width="2212" title="" />
			</item>
		</billede>
		<title>Valient Thorr (US) + Liar Thief Bandit (SE)</title>
		<field_teaser>Sveddryppende sommerrock!</field_teaser>
		<field_display_institution is_array="true">
			<item>Vanløse Hallerne</item>
		</field_display_institution>
	</item>
	<item>
		<startdate is_array="true">
			<item>04.08.2022</item>
		</startdate>
		<enddate is_array="true">
			<item>04.08.2022</item>
		</enddate>
		<time is_array="true">
			<item>19:00 til 23:59</item>
		</time>
		<Nid>63</Nid>
		<billede is_array="true">
			<item>
				<img src="https://basement.kk.dk/sites/default/files/2022-04/277741949_1022919355019562_3179308792317594551_n.jpg" alt="" height="1079" width="2048" title="" />
			</item>
		</billede>
		<title>Melt-Banana (JP) + Uraño </title>
		<field_teaser>Kult-rock fra Japan - og måske et af verdens vildeste liveshows - rammer Basement denne sommer!</field_teaser>
		<field_display_institution is_array="true">
			<item>Bellahøj Svømmestadion </item>
			<item>Demokratiets Hus</item>
		</field_display_institution>
	</item>
</result>
XML;

        // It is important that the path is /events since we only sort for
        // that path
        $this->postXmlWithBasicAuth('/events', $xml, [], 'local', 'local');
        $response = $this->get('/events');

        $xml = fluidxml($response->getContent());
        $this->assertEquals(
            'Valient Thorr (US) + Liar Thief Bandit (SE)',
            $xml->query('/result/item[1]/title')->current()->nodeValue
        );
        $this->assertEquals(
            'Dirty Fences (US) + De Høje Hæle',
            $xml->query('/result/item[2]/title')->current()->nodeValue
        );
        $this->assertEquals(
            'Melt-Banana (JP) + Uraño ',
            $xml->query('/result/item[3]/title')->current()->nodeValue
        );
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
