<?php

namespace App\Console\Commands;

use App\Services\DeadlineReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deadlines:send-reminders')]
#[Description('Invia i promemoria push delle scadenze del giorno')]
class SendDeadlineReminders extends Command
{
    public function handle(DeadlineReminderService $reminders): int
    {
        $sent = $reminders->send();

        $this->info("Promemoria inviati: {$sent}");

        return self::SUCCESS;
    }
}
