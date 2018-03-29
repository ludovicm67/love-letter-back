<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class RedisTest extends TestCase
{
    /**
     * Test Redis connectivity
     *
     * @return void
     */
    public function testRedisConnectivity()
    {
        $strSet = 'redis is working, yay!';
        Redis::set('test', $strSet);
        $strGet = Redis::get('test');
        $this->assertEquals($strSet, $strGet);
        Redis::del('test');
        $strGet = Redis::get('test');
        $this->assertEmpty($strGet);
    }
}
