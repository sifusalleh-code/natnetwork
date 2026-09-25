<?php

namespace App\Engines\Communication\Services;

use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Communication\Models\SupportTicket;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportService
{
    public function __construct(private readonly DocumentNumberService $numbers, private readonly NotificationService $notifications)
    {
    }

    public function open(User $customer, string $subject, string $body, ?Project $project): SupportTicket
    {
        if ($project && (int) $project->customer_user_id !== (int) $customer->id) {
            abort(404);
        }

        return DB::transaction(function () use ($customer, $subject, $body, $project): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'number' => $this->numbers->next('SUP', false), 'customer_user_id' => $customer->id, 'project_id' => $project?->id,
                'subject' => $subject, 'status' => SupportTicket::OPEN, 'last_activity_at' => now(),
            ]);
            $ticket->messages()->create(['author_type' => 'CLIENT', 'author_id' => $customer->id, 'body' => $body, 'created_at' => now()]);

            return $ticket;
        });
    }

    public function clientReply(User $customer, SupportTicket $ticket, string $body): void
    {
        abort_unless((int) $ticket->customer_user_id === (int) $customer->id, 404);
        if ($ticket->status === SupportTicket::CLOSED) {
            throw ValidationException::withMessages(['body' => ['Tiket ini telah ditutup. Sila buka tiket baharu.']]);
        }
        $ticket->messages()->create(['author_type' => 'CLIENT', 'author_id' => $customer->id, 'body' => $body, 'created_at' => now()]);
        $ticket->forceFill(['status' => SupportTicket::OPEN, 'last_activity_at' => now()])->save();
    }

    public function adminReply(Admin $admin, SupportTicket $ticket, string $body, bool $close): void
    {
        $ticket->messages()->create(['author_type' => 'ADMIN', 'author_id' => $admin->id, 'body' => $body, 'created_at' => now()]);
        $ticket->forceFill(['status' => $close ? SupportTicket::CLOSED : SupportTicket::ANSWERED, 'last_activity_at' => now()])->save();
        $this->notifications->toClient($ticket->customer_user_id, 'SUPPORT', 'Balasan untuk tiket '.$ticket->number, $ticket->subject, route('client.support.show', $ticket));
    }
}
