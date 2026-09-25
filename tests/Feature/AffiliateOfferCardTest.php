<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\View\Components\AffiliateOfferCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateOfferCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_is_offered_to_a_user_who_is_not_an_affiliate(): void
    {
        $this->component(AffiliateOfferCard::class, ['email' => 'client@example.test'])
            ->assertSee('sehingga 10%')
            ->assertSee(route('affiliate.register'));
    }

    public function test_card_is_hidden_when_email_is_already_an_affiliate(): void
    {
        Affiliate::query()->create(['name' => 'Ali', 'email' => 'ali@example.test', 'phone' => '012']);

        $this->assertFalse((new AffiliateOfferCard('ALI@example.test'))->shouldRender());
        $this->assertFalse((new AffiliateOfferCard(null))->shouldRender());
        $this->assertTrue((new AffiliateOfferCard('lain@example.test'))->shouldRender());
    }
}
