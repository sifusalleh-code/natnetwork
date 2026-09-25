<?php

namespace App\Engines\ProjectContent\Events;

use App\Engines\ProjectContent\Models\ProjectContentItem;
use Illuminate\Foundation\Events\Dispatchable;

class ContentItemChanged
{
    use Dispatchable;

    public function __construct(public readonly ProjectContentItem $item, public readonly string $action)
    {
    }
}
