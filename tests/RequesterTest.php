<?php

namespace Jurager\Passport\Test;

use Jurager\Passport\Requester;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\UnauthorizedException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Illuminate\Support\Facades\Auth;

class RequesterTest extends TestCase
{
    public function testShouldSendRequest()
    {
        $client = $this->createMockClient(200, ['id' => 2]);
        $requester = new Requester($client);
        $json = $requester->request('ssid', 'POST', 'http://localhost');

        $this->assertEquals($json, ['id' => 2]);
    }

    public function testShouldThrowInvalidSessionException()
    {
        $this->expectException(InvalidSessionIdException::class);
        $this->expectExceptionMessage('Invalid session id.');

        $client = $this->createMockClient(401, ['code' => 'invalid_session_id', 'message' => 'Invalid session id.']);
        $requester = new Requester($client);
        $json = $requester->request('ssid', 'POST', 'http://localhost');
    }

    public function testShouldThrowInvalidClientException()
    {
        $this->expectException(InvalidClientException::class);
        $this->expectExceptionMessage('Invalid client id.');

        $client = $this->createMockClient(401, ['code' => 'invalid_client_id', 'message' => 'Invalid client id.']);
        $requester = new Requester($client);
        $json = $requester->request('ssid', 'POST', 'http://localhost');
    }

    public function testShouldThrowNotAttachedException()
    {
        $this->expectException(NotAttachedException::class);
        $this->expectExceptionMessage('Client not attached');

        $client = $this->createMockClient(401, ['code' => 'not_attached', 'message' => 'Client not attached']);
        $requester = new Requester($client);
        $json = $requester->request('ssid', 'POST', 'http://localhost');
    }
}
