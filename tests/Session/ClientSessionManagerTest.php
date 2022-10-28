<?php

namespace Jurager\Passport\Test\Session;

use Jurager\Passport\Session\ClientSessionManager;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Test\TestCase;

class ClientSessionManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->session = new ClientSessionManager();
    }

    public function testShouldSetSessionInCache()
    {
        $this->assertNull(Session::get('session_id'));

        $this->app['config']->set('passport.session_ttl', 60);
        $this->session->set('session_id', 'value');

        $this->assertEquals(Session::get('session_id'), 'value');
    }

    public function testShouldSetSessionInCacheForever()
    {
        Session::shouldReceive('forever')
                    ->once()
                    ->with('session_id', 'value');

        $this->app['config']->set('passport.session_ttl', null);

        $this->session->set('session_id', 'value');
    }

    public function testShouldForgetSessionInCache()
    {
        $this->session->set('session_id', 'value');
        $this->session->forget('session_id');

        $this->assertNull($this->session->get('session_id'));
    }
}
