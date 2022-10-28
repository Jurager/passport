<?php

namespace Jurager\Passport\Test\Session;

use Jurager\Passport\Session\ServerSessionManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Jurager\Passport\Test\TestCase;

class ServerSessionManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->session = new ServerSessionManager();
    }

    public function testShouldSetSessionInCache()
    {
        $this->assertNull(Cache::get('session_id'));

        $this->app['config']->set('passport.session_ttl', 60);
        $this->session->set('session_id', 'value');

        $this->assertEquals(Cache::get('session_id'), 'value');
    }

    public function testShouldSetSessionInCacheForever()
    {
        Cache::shouldReceive('forever')
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

    public function testShouldSartSession()
    {
        $id = Session::getId();
        $this->session->start('session_id');

        $this->assertEquals(Cache::get('session_id'), $id);
    }
}
