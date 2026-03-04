<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Rent;

class RentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rent;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(Rent $rent, $type = 'created')
    {
        $this->rent = $rent;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'whatsapp'];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp($notifiable)
    {
        $message = $this->getMessageByType();
        
        return [
            'phone' => $notifiable->phone, // Gunakan kolom phone yang sudah ada
            'text' => $message
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->getSubjectByType())
            ->line($this->getMessageByType())
            ->action('Lihat Detail', url('/dashboard/rents/' . $this->rent->id));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'rent_id' => $this->rent->id,
            'type' => $this->type,
            'message' => $this->getMessageByType(),
            'room_name' => $this->rent->room->name,
            'user_name' => $this->rent->user->name,
        ];
    }

    /**
     * Get message based on notification type
     */
    protected function getMessageByType()
    {
        $roomName = $this->rent->room->name;
        $userName = $this->rent->user->name;
        $startTime = $this->rent->time_start_use->format('d/m/Y H:i');
        $endTime = $this->rent->time_end_use->format('d/m/Y H:i');
        
        // Add notes if available
        $notes = $this->rent->notes ? "\nCatatan: *{$this->rent->notes}*" : "";

        return match ($this->type) {
            'created' => " *PEMINJAMAN BARU*\n\n" .
                        "Ruangan: *{$roomName}*\n" .
                        "Peminjam: *{$userName}*\n" .
                        "Waktu: *{$startTime} - {$endTime}*\n" .
                        "Status: *Pending*" .
                        $notes . "\n\n" .
                        "Silakan review dan approve peminjaman ini.",

            'approved' => "✅ *PEMINJAMAN DISETUJUI*\n\n" .
                         "Ruangan: *{$roomName}*\n" .
                         "Peminjam: *{$userName}*\n" .
                         "Waktu: *{$startTime} - {$endTime}*\n" .
                         "Status: *Disetujui*" .
                         $notes . "\n\n" .
                         "Peminjaman Anda telah disetujui. Silakan ambil ruangan sesuai jadwal.",

            'rejected' => "❌ *PEMINJAMAN DITOLAK*\n\n" .
                         "Ruangan: *{$roomName}*\n" .
                         "Peminjam: *{$userName}*\n" .
                         "Waktu: *{$startTime} - {$endTime}*\n" .
                         "Status: *Ditolak*" .
                         $notes . "\n\n" .
                         "Maaf, peminjaman Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.",

            'started' => "🚀 *PEMINJAMAN DIMULAI*\n\n" .
                        "Ruangan: *{$roomName}*\n" .
                        "Peminjam: *{$userName}*\n" .
                        "Waktu: *{$startTime} - {$endTime}*\n" .
                        "Status: *Sedang Berlangsung*" .
                        $notes . "\n\n" .
                        "Peminjaman telah dimulai. Selamat menggunakan ruangan!",

            'completed' => "🏁 *PEMINJAMAN SELESAI*\n\n" .
                          "Ruangan: *{$roomName}*\n" .
                          "Peminjam: *{$userName}*\n" .
                          "Waktu: *{$startTime} - {$endTime}*\n" .
                          "Waktu Pengembalian: *" . $this->rent->transaction_end->format('d/m/Y H:i') . "*\n" .
                          "Status: *Selesai*" .
                          $notes . "\n\n" .
                          $this->getPenaltyInfo() . "\n" .
                          "Peminjaman telah selesai. Terima kasih telah menggunakan layanan kami.",

            'reminder' => "⏰ *PENGINGAT PEMINJAMAN*\n\n" .
                         "Ruangan: *{$roomName}*\n" .
                         "Peminjam: *{$userName}*\n" .
                         "Waktu: *{$startTime} - {$endTime}*" .
                         $notes . "\n\n" .
                         "Peminjaman Anda akan dimulai dalam 30 menit. Silakan siapkan diri Anda.",

            'expired' => "⚠️ *PEMINJAMAN TELAH BERAKHIR*\n\n" .
                        "Ruangan: *{$roomName}*\n" .
                        "Peminjam: *{$userName}*\n" .
                        "Waktu: *{$startTime} - {$endTime}*\n" .
                        "Status: *Waktu Telah Berakhir*" .
                        $notes . "\n\n" .
                        "Waktu peminjaman Anda telah berakhir. Silakan kembalikan ruangan dan item yang dipinjam kepada petugas.",

            'overdue' => "🚨 *PEMINJAMAN TERLAMBAT (UNTUK SECURITY)*\n\n" .
                        "Ruangan: *{$roomName}*\n" .
                        "Peminjam: *{$userName}*\n" .
                        "Waktu: *{$startTime} - {$endTime}*\n" .
                        "Status: *Terlambat 5+ Menit*" .
                        $notes . "\n\n" .
                        "Peminjaman ini sudah terlambat lebih dari 5 menit. Silakan cek dan tindak lanjuti.",

            'auto_penalty' => "💰 *DENDA OTOMATIS DIKENAKAN*\n\n" .
                            "Ruangan: *{$roomName}*\n" .
                            "Peminjam: *{$userName}*\n" .
                            "Waktu: *{$startTime} - {$endTime}*\n" .
                            "Waktu Sekarang: *" . now()->format('d/m/Y H:i') . "*\n" .
                            "Status: *Terlambat Pengembalian*" .
                            $notes . "\n\n" .
                            $this->getPenaltyInfo(true) . "\n" .
                            "Denda telah otomatis dikenakan karena waktu peminjaman telah lewat. Silakan segera kembalikan ruangan untuk menghindari denda tambahan.",

            default => " *UPDATE PEMINJAMAN*\n\n" .
                      "Ruangan: *{$roomName}*\n" .
                      "Peminjam: *{$userName}*\n" .
                      "Waktu: *{$startTime} - {$endTime}*\n" .
                      "Status: *{$this->rent->status}*" .
                      $notes
        };
    }

    /**
     * Get subject based on notification type
     */
    protected function getSubjectByType()
    {
        return match ($this->type) {
            'created' => 'Peminjaman Baru - ' . $this->rent->room->name,
            'approved' => 'Peminjaman Disetujui - ' . $this->rent->room->name,
            'rejected' => 'Peminjaman Ditolak - ' . $this->rent->room->name,
            'started' => 'Peminjaman Dimulai - ' . $this->rent->room->name,
            'completed' => 'Peminjaman Selesai - ' . $this->rent->room->name,
            'reminder' => 'Pengingat Peminjaman - ' . $this->rent->room->name,
            'expired' => 'Peminjaman Telah Berakhir - ' . $this->rent->room->name,
            'overdue' => 'Peminjaman Terlambat - ' . $this->rent->room->name,
            'auto_penalty' => 'Denda Otomatis Dikenakan - ' . $this->rent->room->name,
            default => 'Update Peminjaman - ' . $this->rent->room->name
        };
    }

    /**
     * Get penalty information for notification
     */
    protected function getPenaltyInfo($isAutoPenalty = false)
    {
        if ($isAutoPenalty) {
            // For auto penalty (overdue rents)
            $penaltyAmount = $this->rent->calculateAutoPenalty();
            $hoursOverdue = $this->rent->getHoursOverdue();
            
            if ($penaltyAmount > 0) {
                return "⚠️ *DENDA KETERLAMBATAN*\n" .
                       "Jumlah: *Rp " . number_format($penaltyAmount, 0, ',', '.') . "*\n" .
                       "Terlambat: *{$hoursOverdue} jam*\n" .
                       "Tarif: *Rp 5.000 per jam* (dibulatkan ke atas)\n" .
                       "Status: *Denda otomatis aktif*";
            }
        } else {
            // For completed rents
            $penaltyAmount = $this->rent->penalty_amount > 0 ? $this->rent->penalty_amount : $this->rent->calculatePenalty();
            $hoursLate = $this->rent->getHoursLate();
            
            if ($penaltyAmount > 0) {
                return "⚠️ *DENDA KETERLAMBATAN*\n" .
                       "Jumlah: *Rp " . number_format($penaltyAmount, 0, ',', '.') . "*\n" .
                       "Terlambat: *{$hoursLate} jam*\n" .
                       "Tarif: *Rp 5.000 per jam* (dibulatkan ke atas)\n" .
                       "Status: *Denda telah dikenakan*";
            }
        }
        
        return "✅ *Tidak ada denda* - Peminjaman dikembalikan tepat waktu";
    }
} 