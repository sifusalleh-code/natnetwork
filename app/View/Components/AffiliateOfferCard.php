<?php

namespace App\View\Components;

use App\Engines\Affiliate\Models\Affiliate;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Card tawaran program affiliate untuk dashboard portal bukan affiliate (Client, Partnership).
 * Tidak dipaparkan jika emel pengguna sudah berdaftar sebagai affiliate.
 */
class AffiliateOfferCard extends Component
{
    public function __construct(public ?string $email = null)
    {
    }

    public function shouldRender(): bool
    {
        return filled($this->email)
            && ! Affiliate::query()->where('email', Str::lower(trim($this->email)))->exists();
    }

    public function render(): View
    {
        return view('components.affiliate-offer-card');
    }
}
