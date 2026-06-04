<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\Wilayah;
use DB;
use Log;

class AdminController extends Controller
{
    public function index()
    {
        $title = 'Admin Page';

        return view('admin.index')->with(compact('title'));
    }

    public function switchServer()
    {
        $server = request()->input('server');
        session(['master_server' => $server]);

        return response()->json(['success' => true]);
    }

    public function laporan()
    {
        $wilayah = Wilayah::WhereRaw('LENGTH(kode)=2')->orderBy('nama', 'ASC')->get();

        $title = 'Laporan Pusat';

        return view('admin.wilayah')->with(compact('title', 'wilayah'));
    }

    public function transaksi()
    {
        $kecamatan = Kecamatan::where('id', 'like', '528')->orderBy('id', 'ASC')->get();

        foreach ($kecamatan as $item) {
            $lokasi = $item->id;

            try {
                // Drop existing triggers
                DB::unprepared("DROP TRIGGER IF EXISTS `create_saldo_{$lokasi}`");
                DB::unprepared("DROP TRIGGER IF EXISTS `delete_saldo_{$lokasi}`");
                DB::unprepared("DROP TRIGGER IF EXISTS `update_saldo_{$lokasi}`");
                DB::unprepared("DROP TRIGGER IF EXISTS `update_tanggal_{$lokasi}`");

                // Create new triggers
                DB::unprepared($this->createInsertTrigger($lokasi));
                DB::unprepared($this->createDeleteTrigger($lokasi));
                DB::unprepared($this->createUpdateTrigger($lokasi));
                DB::unprepared($this->createUpdateTanggalTrigger($lokasi));

                echo "✓ Triggers created for lokasi: {$lokasi}<br>";
                Log::info("Triggers created for lokasi: {$lokasi}");
            } catch (\Exception $e) {
                echo "✗ Failed for lokasi {$lokasi}: ".$e->getMessage().'<br>';
                Log::error("Trigger failed for lokasi {$lokasi}: ".$e->getMessage());
            }
        }
    }

    private function createInsertTrigger($lokasi)
    {
        return "
        CREATE TRIGGER `create_saldo_{$lokasi}` AFTER INSERT ON `transaksi_{$lokasi}`
        FOR EACH ROW 
        BEGIN
            DECLARE newTahun INT;
            DECLARE newBulan VARCHAR(2);
            DECLARE saldoDebitRekDebit DOUBLE;
            DECLARE saldoKreditRekDebit DOUBLE;
            DECLARE saldoDebitRekKredit DOUBLE;
            DECLARE saldoKreditRekKredit DOUBLE;

            SET newTahun = YEAR(NEW.tgl_transaksi);
            SET newBulan = LPAD(MONTH(NEW.tgl_transaksi), 2, '0');

            SET saldoDebitRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = NEW.rekening_debit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
            SET saldoKreditRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = NEW.rekening_debit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
            SET saldoDebitRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = NEW.rekening_kredit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
            SET saldoKreditRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = NEW.rekening_kredit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);

            INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
            VALUES (
                CONCAT(REPLACE(NEW.rekening_debit, '.', ''), newTahun, newBulan), 
                NEW.rekening_debit, 
                newTahun, 
                newBulan, 
                saldoDebitRekDebit, 
                saldoKreditRekDebit
            )
            ON DUPLICATE KEY UPDATE debit = saldoDebitRekDebit, kredit = saldoKreditRekDebit;

            INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
            VALUES (
                CONCAT(REPLACE(NEW.rekening_kredit, '.', ''), newTahun, newBulan), 
                NEW.rekening_kredit, 
                newTahun, 
                newBulan, 
                saldoDebitRekKredit, 
                saldoKreditRekKredit
            )
            ON DUPLICATE KEY UPDATE debit = saldoDebitRekKredit, kredit = saldoKreditRekKredit;
        END
        ";
    }

    private function createDeleteTrigger($lokasi)
    {
        return "
        CREATE TRIGGER `delete_saldo_{$lokasi}` AFTER DELETE ON `transaksi_{$lokasi}`
        FOR EACH ROW 
        BEGIN
            DECLARE oldTahun INT;
            DECLARE oldBulan VARCHAR(2);
            DECLARE saldoDebitRekDebit DOUBLE;
            DECLARE saldoKreditRekDebit DOUBLE;
            DECLARE saldoDebitRekKredit DOUBLE;
            DECLARE saldoKreditRekKredit DOUBLE;

            SET oldTahun = YEAR(OLD.tgl_transaksi);
            SET oldBulan = LPAD(MONTH(OLD.tgl_transaksi), 2, '0');

            SET saldoDebitRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = OLD.rekening_debit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
            SET saldoKreditRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = OLD.rekening_debit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
            SET saldoDebitRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = OLD.rekening_kredit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
            SET saldoKreditRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = OLD.rekening_kredit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);

            INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
            VALUES (
                CONCAT(REPLACE(OLD.rekening_debit, '.', ''), oldTahun, oldBulan), 
                OLD.rekening_debit, 
                oldTahun, 
                oldBulan, 
                saldoDebitRekDebit, 
                saldoKreditRekDebit
            )
            ON DUPLICATE KEY UPDATE debit = saldoDebitRekDebit, kredit = saldoKreditRekDebit;

            INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
            VALUES (
                CONCAT(REPLACE(OLD.rekening_kredit, '.', ''), oldTahun, oldBulan), 
                OLD.rekening_kredit, 
                oldTahun, 
                oldBulan, 
                saldoDebitRekKredit, 
                saldoKreditRekKredit
            )
            ON DUPLICATE KEY UPDATE debit = saldoDebitRekKredit, kredit = saldoKreditRekKredit;
        END
        ";
    }

    private function createUpdateTrigger($lokasi)
    {
        return "
        CREATE TRIGGER `update_saldo_{$lokasi}` AFTER UPDATE ON `transaksi_{$lokasi}`
        FOR EACH ROW 
        BEGIN
            DECLARE newTahun INT;
            DECLARE newBulan VARCHAR(2);
            DECLARE oldTahun INT;
            DECLARE oldBulan VARCHAR(2);
            DECLARE needsUpdate BOOLEAN;

            DECLARE newSaldoDebitRekDebit DOUBLE;
            DECLARE newSaldoKreditRekDebit DOUBLE;
            DECLARE newSaldoDebitRekKredit DOUBLE;
            DECLARE newSaldoKreditRekKredit DOUBLE;

            DECLARE oldSaldoDebitRekDebit DOUBLE;
            DECLARE oldSaldoKreditRekDebit DOUBLE;
            DECLARE oldSaldoDebitRekKredit DOUBLE;
            DECLARE oldSaldoKreditRekKredit DOUBLE;

            SET newTahun = YEAR(NEW.tgl_transaksi);
            SET newBulan = LPAD(MONTH(NEW.tgl_transaksi), 2, '0');
            SET oldTahun = YEAR(OLD.tgl_transaksi);
            SET oldBulan = LPAD(MONTH(OLD.tgl_transaksi), 2, '0');

            SET needsUpdate = (
                OLD.jumlah != NEW.jumlah OR 
                oldTahun != newTahun OR 
                oldBulan != newBulan OR
                OLD.rekening_debit != NEW.rekening_debit OR
                OLD.rekening_kredit != NEW.rekening_kredit OR 
                (OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL) OR
                (OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL)
            );

            IF needsUpdate THEN
                
                SET newSaldoDebitRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = NEW.rekening_debit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
                SET newSaldoKreditRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = NEW.rekening_debit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
                SET newSaldoDebitRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = NEW.rekening_kredit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);
                SET newSaldoKreditRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = NEW.rekening_kredit AND YEAR(tgl_transaksi) = newTahun AND MONTH(tgl_transaksi) <= newBulan AND deleted_at IS NULL), 0);

                INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
                VALUES (
                    CONCAT(REPLACE(NEW.rekening_debit, '.', ''), newTahun, newBulan), 
                    NEW.rekening_debit, 
                    newTahun, 
                    newBulan, 
                    newSaldoDebitRekDebit, 
                    newSaldoKreditRekDebit
                )
                ON DUPLICATE KEY UPDATE debit = newSaldoDebitRekDebit, kredit = newSaldoKreditRekDebit;

                INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
                VALUES (
                    CONCAT(REPLACE(NEW.rekening_kredit, '.', ''), newTahun, newBulan), 
                    NEW.rekening_kredit, 
                    newTahun, 
                    newBulan, 
                    newSaldoDebitRekKredit, 
                    newSaldoKreditRekKredit
                )
                ON DUPLICATE KEY UPDATE debit = newSaldoDebitRekKredit, kredit = newSaldoKreditRekKredit;

                IF (oldTahun != newTahun OR oldBulan != newBulan OR OLD.rekening_debit != NEW.rekening_debit OR OLD.rekening_kredit != NEW.rekening_kredit) THEN
                    
                    SET oldSaldoDebitRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = OLD.rekening_debit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
                    SET oldSaldoKreditRekDebit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = OLD.rekening_debit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
                    SET oldSaldoDebitRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_debit = OLD.rekening_kredit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
                    SET oldSaldoKreditRekKredit = COALESCE((SELECT SUM(jumlah) FROM transaksi_{$lokasi} WHERE rekening_kredit = OLD.rekening_kredit AND YEAR(tgl_transaksi) = oldTahun AND MONTH(tgl_transaksi) <= oldBulan AND deleted_at IS NULL), 0);
                
                    INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
                    VALUES (
                        CONCAT(REPLACE(OLD.rekening_debit, '.', ''), oldTahun, oldBulan), 
                        OLD.rekening_debit, 
                        oldTahun, 
                        oldBulan, 
                        oldSaldoDebitRekDebit, 
                        oldSaldoKreditRekDebit
                    )
                    ON DUPLICATE KEY UPDATE debit = oldSaldoDebitRekDebit, kredit = oldSaldoKreditRekDebit;

                    INSERT INTO saldo_{$lokasi} (`id`, `kode_akun`, `tahun`, `bulan`, `debit`, `kredit`)
                    VALUES (
                        CONCAT(REPLACE(OLD.rekening_kredit, '.', ''), oldTahun, oldBulan), 
                        OLD.rekening_kredit, 
                        oldTahun, 
                        oldBulan, 
                        oldSaldoDebitRekKredit, 
                        oldSaldoKreditRekKredit
                    )
                    ON DUPLICATE KEY UPDATE debit = oldSaldoDebitRekKredit, kredit = oldSaldoKreditRekKredit;
                END IF;
            END IF;
        END
        ";
    }

    private function createUpdateTanggalTrigger($lokasi)
    {
        return "
        CREATE TRIGGER `update_tanggal_{$lokasi}` BEFORE UPDATE ON `transaksi_{$lokasi}`
        FOR EACH ROW 
        BEGIN
            IF NEW.tgl_transaksi != OLD.tgl_transaksi THEN
                SET NEW.updated_at = CONCAT(NEW.tgl_transaksi, ' ', CURRENT_TIME());
            END IF;
        END
        ";
    }
}
