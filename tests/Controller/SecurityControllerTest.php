<?php

namespace App\Tests\Controller;

use App\Entity\Security\User;
use Liip\TestFixturesBundle\Test\FixturesTrait;

class SecurityControllerTest extends BaseControllerTest
{
    use FixturesTrait;

    /**
     * @var array|User[]
     */
    protected $entities = [];

    /**
     * @var string[]
     */
    protected $fixturePath = [];//__DIR__ . "/UserFixtures.yml"];


    public function __construct()
    {
        parent::__construct();
        $this->fixturePath = [dirname(dirname(__DIR__)) . "/fixtures/UserFixtures.yml"];
    }

    public function testRedirectToLoginIfNotConnected()
    {
        $client = static::createClient();

        $client->request('GET', $this->url($client, "admin"));
        $this->assertResponseRedirects($this->url($client, "security.login"), 302);
    }

    public function testGrantAccessIfConnected()
    {
        $client = static::createClient();
        $this->getFixtures();


        $admin = $this->entities["admin"];

        $crawler = $client->request('GET', "/login");
        $form = $crawler->selectButton("Se connecter")->form([
            "_email" => $admin->getEmail(),
            "_password" => "admin",
        ]);

        $client->submit($form);

        // post
//        $this->post($client, "security.login", [
//            "_email" => $admin->getEmail(),
//            "_password" => "admin",
//        ]);

        $this->assertResponseRedirects(self::BASE_HOST . "/admin", 302, "should redirect to admin");
        $client->followRedirect();

//        $this->assertRouteSame("admin");
        $this->assertSelectorNotExists(".alert.alert-danger");
    }

    public function testDisplayErrorIfBadCredentials()
    {
        $client = static::createClient();
        $this->getFixtures();

        /*
          // Alternate usage with pure HTML
          $crawler = $client->request('GET', $this->url($client, "security.login"));

          $form = $crawler->selectButton("Se connecter")->form([
          "_email" => "fake@email.com",
          "_password" => "fakePassword",
          ]);
          $client->submit($form);
         */

        // Correct Way of Posting requests
        $token = $client->getContainer()->get("security.csrf.token_manager")->getToken("authenticate");
        $client->request('POST', $this->url($client, "security.login"), [
            "_csrf_token" => $token,
            "_email" => "fake@email.com",
            "_password" => "fakePassword",
        ]);


        $this->assertResponseRedirects(self::BASE_HOST . "/login", 302, "Should redirect to login if bad credentials");

        $client->followRedirect();
        $this->assertSelectorExists(".alert.alert-danger");
    }

    public function testDenyAccessToAdminIfLoggedOut()
    {
        $client = static::createClient();
        $this->getFixtures();

        // login user
        $client->loginUser($this->entities["admin"]);
//        $client->request('GET', "/admin");

//        $this->assertResponseIsSuccessful("should grant access for connected user");
//        $this->assertRouteSame("admin");

        // logout
        $client->request('GET', "/logout");

        $client->request('GET', "/admin");
        $this->assertResponseRedirects(self::BASE_HOST . "/login", 302, "should redirect to login if logged out");
    }

}
