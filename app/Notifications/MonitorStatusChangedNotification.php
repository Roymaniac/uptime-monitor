<?php

namespace App\Notifications;

use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MonitorStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $previousStatus,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->monitor->url;
        $status = strtoupper($this->monitor->status);

        $subject = $this->monitor->isDown()
            ? "🔴 ALERT: {$url} is DOWN"
            : "🟢 RECOVERY: {$url} is back UP";

        $line = $this->monitor->isDown()
            ? "We detected that **{$url}** is currently unreachable."
            : "Good news – **{$url}** is back online and responding normally.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Monitor status changed: {$status}")
            ->line($line)
            ->line("Current uptime: {$this->monitor->uptime_percentage}%")
            ->action('View Monitor', url("/api/monitors/{$this->monitor->id}/history"))
            ->line('You are receiving this because you registered this URL for monitoring.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
