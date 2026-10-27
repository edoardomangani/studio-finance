<?php

namespace App\Services;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\DeadlineNotification;
use App\Notifications\DeadlineReminder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Promemoria push giornalieri delle scadenze (RN1–RN11). Gira senza utente
 * autenticato (endpoint del cron, comando artisan): ogni query filtra per
 * `user_id` in modo esplicito, mai via global scope.
 */
class DeadlineReminderService
{
    public const TIMEZONE = 'Europe/Rome';

    /** Giorni prima della scadenza in cui avvisare (RN2). */
    public const DAYS_BEFORE = [14, 7, 3, 1, 0];

    public function __construct(
        private readonly DeadlineContextBuilder $contextBuilder,
        private readonly DeadlineExpectation $expectation,
    ) {}

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::today(self::TIMEZONE);
    }

    /**
     * Scadenze da notificare oggi, non ancora notificate, di utenti con almeno
     * una subscription.
     *
     * @return Collection<int, Deadline>
     */
    public function dueToday(CarbonImmutable $today): Collection
    {
        $todayString = $today->toDateString();

        return Deadline::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('status', DeadlineStatus::Open)
            ->where(function (Builder $query) use ($today, $todayString): void {
                $query->whereDate('due_at', '<', $todayString);

                foreach (self::DAYS_BEFORE as $days) {
                    $query->orWhereDate('due_at', $today->addDays($days)->toDateString());
                }
            })
            ->whereHas('user.pushSubscriptions')
            ->whereDoesntHave('notificationLogs', fn (Builder $q) => $q->whereDate('sent_on', $todayString))
            ->with(['annualExpense.year', 'user.pushSubscriptions'])
            ->orderBy('due_at')
            ->get();
    }

    /**
     * Invia i promemoria del giorno; ritorna quante scadenze sono state notificate.
     */
    public function send(?CarbonImmutable $today = null): int
    {
        $today ??= self::today();
        $sent = 0;

        // Il context builder assume scadenze di un solo utente.
        foreach ($this->dueToday($today)->groupBy('user_id') as $deadlines) {
            $context = $this->contextBuilder->build($deadlines);

            foreach ($deadlines as $deadline) {
                $daysUntilDue = $this->daysUntilDue($deadline, $today);

                // Prima il log, poi l'invio: con due chiamate concorrenti vince l'unique.
                try {
                    DeadlineNotification::query()->create([
                        'user_id' => $deadline->user_id,
                        'deadline_id' => $deadline->id,
                        'days_until_due' => $daysUntilDue,
                        'sent_on' => $today->toDateString(),
                    ]);
                } catch (UniqueConstraintViolationException) {
                    continue;
                }

                $body = $this->body($deadline, $daysUntilDue, $this->expectation->for($deadline, $context));

                try {
                    $deadline->user->notify(new DeadlineReminder($deadline->id, $deadline->name, $body));
                } catch (Throwable $e) {
                    Log::warning('Invio promemoria scadenza fallito', [
                        'deadline_id' => $deadline->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Testo della notifica (RN7), es. "Tra 3 giorni (16/11) · € 2.340,00".
     */
    public function body(Deadline $deadline, int $daysUntilDue, ?float $amount): string
    {
        $when = match (true) {
            $daysUntilDue === 0 => 'Oggi',
            $daysUntilDue === 1 => 'Domani',
            $daysUntilDue === -1 => 'Scaduta ieri',
            $daysUntilDue < -1 => 'Scaduta da '.abs($daysUntilDue).' giorni',
            default => 'Tra '.$daysUntilDue.' giorni',
        };

        $body = $when.' ('.$deadline->due_at->format('d/m').')';

        if ($amount !== null) {
            $body .= ' · € '.number_format($amount, 2, ',', '.');
        }

        return $body;
    }

    private function daysUntilDue(Deadline $deadline, CarbonImmutable $today): int
    {
        $dueAt = CarbonImmutable::parse($deadline->due_at->toDateString(), self::TIMEZONE);

        return (int) $today->diffInDays($dueAt, false);
    }
}
