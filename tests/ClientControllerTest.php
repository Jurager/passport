<?php

namespace Jurager\Passport\Test;

use Jurager\Passport\ClientBrokerManager;
use Jurager\Passport\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mockery;

/*class MockEncryption extends \Jurager\Passport\Encryption
{
    public function randomToken()
    {
        return 'emnxnx465ugcgsgk4gw0c888w';
    }
}
*/
class ClientControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Client config
        $this->app['config']->set('passport.broker.client_id', 'appid');
        $this->app['config']->set('passport.broker.client_secret', 'SeCrEt');
        $this->app['config']->set('passport.broker.server_url', 'http://localhost/sso/server');

        // Server config
        $this->app['config']->set('auth.providers.users.model', Models\User::class);
        $this->app['config']->set('passport.server.model', Models\App::class);
        $this->app['config']->set('passport.server.id_field', 'app_id');
        $this->app['config']->set('passport.server.secret_field', 'secret');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldAttachClientToServer()
    {
        $this->withoutExceptionHandling();

        $token = 'emnxnx465ugcgsgk4gw0c888w';

        $encr = Mockery::mock('overload:Jurager\Passport\Encryption')->makePartial();
        $encr->shouldReceive('randomToken')
             ->andReturn($token)
             ->shouldReceive('generateChecksum')
             ->andReturn('8611d544c942d45d9478e37329f3c88d9433463e5c5456a01d726bce3b60780f');

        $broker = new ClientBrokerManager;
        $session = new SessionManager;

        $key = $broker->sessionName();
        $checksum = $broker->generateAttachChecksum($token);

        $response = $this->get('/sso/client/attach?return_url=http://localhost');
        $redirect_url = '/sso/server/attach?' . http_build_query([
            'broker' => $broker->clientId(),
            'token' => $token,
            'checksum' => $checksum,
            'return_url'=> 'http://localhost'
        ]);

        $response->assertRedirect('http://localhost' . $redirect_url);
        $this->assertEquals(Session::get($key), $token);

        // Testing Server attach
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $sid = $this->generateSessionId('appid', $token, 'SeCrEt');

        $response = $this->get($redirect_url);

        $response->assertRedirect('http://localhost');
    }
}
