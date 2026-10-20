<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $judul,
        public readonly string $pesan,
        public readonly ?string $url = null,
        public readonly string $ikon = 'heroicon-o-bell',
        public readonly string $warna = 'warning',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->judul,
            'body' => $this->pesan,
            'url' => $this->url,
            'icon' => $this->ikon,
            'color' => $this->warna,
            'status' => $this->warna === 'danger' ? 'danger' : 'warning',
        ];
    }
}
