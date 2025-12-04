<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'customer_name', 'status', 'total_amount'];

    /**
     * Helper function untuk dipanggil oleh Controller nanti
     */
    public static function checkStatus($orderNumber)
    {
        // Cari order berdasarkan nomor
        $order = self::where('order_number', $orderNumber)->first();

        if (!$order) {
            return "Maaf, pesanan dengan nomor $orderNumber tidak ditemukan di database.";
        }

        return "Status pesanan $orderNumber milik $order->customer_name saat ini adalah: $order->status. Total belanja: Rp " . number_format($order->total_amount, 0);
    }
}