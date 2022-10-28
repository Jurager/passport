<?php

namespace Jurager\Passport\Test;

use Jurager\Passport\Encryption;

class EncryptionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->encryption = new Encryption;
    }

    public function testShouldFailVerifyAttachChecksum()
    {
        $checksum = $this->encryption->generateChecksum('attach', 'b', 'c');

        $this->assertFalse($this->encryption->verifyAttachChecksum('b', 'd', $checksum));
    }

    public function testShouldVerifyAttachChecksum()
    {
        $checksum = $this->encryption->generateChecksum('attach', 'b', 'c');

        $this->assertTrue($this->encryption->verifyAttachChecksum('b', 'c', $checksum));
    }
    
    public function testShouldGenerateRandonToken()
    {
        $this->assertNotEquals($this->encryption->randomToken(), $this->encryption->randomToken());
    }
}
