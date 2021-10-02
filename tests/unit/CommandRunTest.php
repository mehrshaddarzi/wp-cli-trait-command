<?php

namespace test\unit;

use PHPUnit\Framework\TestCase;

class CommandRunTest extends TestCase
{

    /** @test */
    public function ExistSpycLoadFunction()
    {
        $this->assertTrue(function_exists('spyc_load_file'));
    }

}
