<?php
// includes/payment_helper.php

/**
 * Memproses respons status transaksi dari Midtrans dan memperbarui database
 * secara aman menggunakan transaksi ACID.
 */
function terapkanStatusPembayaran($conn, $kode_pesanan, $midtrans_status, $fraud_status = 'accept', $payment_type = null) {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "SELECT id, status, payment_status FROM transactions WHERE kode_pesanan = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, "s", $kode_pesanan);
        mysqli_stmt_execute($stmt);
        $trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$trx) {
            throw new Exception("Pesanan {$kode_pesanan} tidak ditemukan.");
        }

        $status_lama   = $trx['status'];
        $status_baru   = $status_lama;
        $payment_baru  = $midtrans_status;
        $is_paid       = 0;

        // Pemetaan standar status Midtrans -> status pesanan Fino Motor
        if ($midtrans_status === 'capture') {
            if ($fraud_status === 'challenge') {
                $status_baru  = 'pending';
                $payment_baru = 'challenge';
            } else {
                $status_baru  = in_array($status_lama, ['dikirim', 'selesai'], true) ? $status_lama : 'dibayar';
                $is_paid      = 1;
            }
        } elseif ($midtrans_status === 'settlement') {
            $status_baru = in_array($status_lama, ['dikirim', 'selesai'], true) ? $status_lama : 'dibayar';
            $is_paid     = 1;
        } elseif ($midtrans_status === 'pending') {
            $status_baru = in_array($status_lama, ['dibayar', 'dikirim', 'selesai'], true) ? $status_lama : 'pending';
        } elseif (in_array($midtrans_status, ['deny', 'expire', 'cancel'], true)) {
            $status_baru = 'batal';
        }

        // Kembalikan stok barang otomatis HANYA SEKALI jika pesanan berubah menjadi 'batal'
        if ($status_baru === 'batal' && $status_lama !== 'batal') {
            $stmt_det = mysqli_prepare($conn, "
                SELECT td.item_id, td.qty, i.tipe
                FROM transaction_details td
                JOIN items i ON td.item_id = i.id
                WHERE td.transaction_id = ?
            ");
            mysqli_stmt_bind_param($stmt_det, "i", $trx['id']);
            mysqli_stmt_execute($stmt_det);
            $res_det = mysqli_stmt_get_result($stmt_det);

            $stmt_restore = mysqli_prepare($conn, "UPDATE items SET stok = stok + ? WHERE id = ?");
            while ($d = mysqli_fetch_assoc($res_det)) {
                if ($d['tipe'] === 'barang') {
                    mysqli_stmt_bind_param($stmt_restore, "ii", $d['qty'], $d['item_id']);
                    mysqli_stmt_execute($stmt_restore);
                }
            }
            mysqli_stmt_close($stmt_det);
            mysqli_stmt_close($stmt_restore);
        }

        $stmt_upd = mysqli_prepare($conn, "
            UPDATE transactions
            SET status = ?,
                payment_status = ?,
                payment_type = COALESCE(?, payment_type),
                paid_at = IF(? = 1 AND paid_at IS NULL, NOW(), paid_at)
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt_upd, "sssii", $status_baru, $payment_baru, $payment_type, $is_paid, $trx['id']);
        mysqli_stmt_execute($stmt_upd);
        mysqli_stmt_close($stmt_upd);

        mysqli_commit($conn);

        return [
            'success'        => true,
            'kode_pesanan'   => $kode_pesanan,
            'status'         => $status_baru,
            'payment_status' => $payment_baru,
            'payment_type'   => $payment_type
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>