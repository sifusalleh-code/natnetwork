<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliatePoster extends Model
{
    protected $fillable = ['title', 'caption', 'image_path', 'image_mime', 'is_active', 'display_order', 'created_by_admin_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Caption dengan referral link. {link} diganti; jika tiada, link ditambah di hujung. */
    public function captionFor(string $link): string
    {
        return str_contains($this->caption, '{link}') ? str_replace('{link}', $link, $this->caption) : rtrim($this->caption)."\n\n".$link;
    }
}
