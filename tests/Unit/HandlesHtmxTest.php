<?php

namespace Tests\Unit;

use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class HandlesHtmxTest extends TestCase
{
    use HandlesHtmx;

    public function test_is_htmx_returns_true_when_hx_request_header_present(): void
    {
        $request = new Request();
        $request->headers->set('HX-Request', 'true');

        $this->assertTrue($this->isHtmx($request));
    }

    public function test_is_htmx_returns_false_for_standard_request(): void
    {
        $request = new Request();

        $this->assertFalse($this->isHtmx($request));
    }

    public function test_htmx_redirect_returns_correct_header(): void
    {
        $response = $this->htmxRedirect('/app/dashboard');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/app/dashboard', $response->headers->get('HX-Redirect'));
    }

    public function test_htmx_trigger_returns_correct_header(): void
    {
        $response = $this->htmxTrigger('eggCreated', '<div>Updated</div>');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('eggCreated', $response->headers->get('HX-Trigger'));
        $this->assertEquals('<div>Updated</div>', $response->getContent());
    }
}
