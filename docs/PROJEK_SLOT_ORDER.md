# Slot, Order, Projek, Fail, Support & Notifikasi (V1)

Status: dibina 24 Sep 2026. Terma guna `config/sales_terms.php` sedia ada (akan disemak semula).

## Aliran
Quotation diterima → pelanggan pilih minggu mula (Isnin, 12 minggu ke hadapan) → slot HELD (60 minit)
→ invois deposit 50% (sekali sahaja) → callback Billplz disahkan → slot RESERVED → ORDER CONFIRMED (`NAT-ORD`)
→ projek WAITING_TO_START (`NAT-PRJ`, milestone & bahan dari template) → admin START PROJECT → IN_PROGRESS.

- Bayar deposit disekat jika hold tamat; pelanggan perlu pilih slot semula (invois deposit tidak diduplikasi).
- Deposit disahkan selepas hold tamat & minggu penuh → slot tetap RESERVED dengan tanda **KONFLIK** untuk tindakan admin.
- Kapasiti (default 3 projek serentak), tempoh hold dan bilangan minggu: Admin → Development Queue.
- Anggaran tempoh (minggu) & template projek diisi admin semasa menghantar quotation.

## Progress & invois akhir
- Progress = jumlah weight milestone selesai (template = 100%). Hanya admin tandakan milestone selepas START PROJECT.
- Kali pertama progress melepasi 80% → invois akhir (jumlah quotation − telah diinvois) dijana automatik, sekali sahaja.
- Butang "Keluarkan invois baki" dibuang.
- Selesai: status READY_FOR_HANDOVER + 100% + tiada invois tertunggak → COMPLETED, slot dilepaskan, sokongan percuma bermula
  (hari ikut nilai projek: <2.5k 7, <5k 14, <10k 30, <20k 60, lain 90).
- Status WAITING_FOR_CLIENT, ON_HOLD, CANCELLED wajib sebab.

## Fail & bahan
- Pelanggan isi maklumat / muat naik / minta bantuan. Admin Terima atau Perlu kemas kini (sebab wajib).
- Fail disimpan private; muat turun melalui route bersemak pemilikan. INTERNAL tidak pernah dipaparkan kepada pelanggan.
- Maks 20MB; jenis: jpg, jpeg, png, webp, gif, pdf, doc(x), xls(x), csv, txt, zip, ppt(x).

## Support & notifikasi
- Tiket `NAT-SUP` dengan balasan; admin boleh tutup tiket.
- Notifikasi dalam portal sahaja (emel/Resend belum).

## Refund (Billing)
- Hanya sebelum START PROJECT. Billplz tiada API refund: admin pindah wang manual, kemudian rekod di halaman projek
  (amaun penuh/separa bagi setiap invois dibayar, sebab, rujukan pemindahan). Nombor `NAT-RF`.
- Kesan: projek CANCELLED (slot dilepaskan), order CANCELLED, CR terbuka dibatalkan, invois belum dibayar di-VOID,
  komisyen affiliate PENDING projek dibatalkan, pelanggan dimaklumkan. Rekod refund dipapar pada invois pelanggan.

## Emel (Resend)
- Admin → Tetapan Emel: API key (encrypted), emel & nama pengirim, aktif/tidak, emel ujian, log emel.
- Semua notifikasi portal + OTP dihantar ke emel. Resend tidak aktif → guna MAIL_MAILER dalam .env.
- Kegagalan emel dilog dan tidak menggagalkan proses. Tiada library baharu (API Resend dipanggil terus).

## Ubah milestone
- Hanya semasa WAITING_TO_START: ubah nama/label/berat, tambah/buang. Jumlah berat wajib 100%.

## Jadual semula slot (admin)
- Minggu mula (Isnin) + tempoh; sebab wajib; minggu penuh perlu tanda override. Tanda KONFLIK dibersihkan.
  Tarikh mula projek (jika belum bermula) dikemas kini; pelanggan dimaklumkan.

## Change Request (`NAT-CR`)
- Pelanggan mohon dari halaman projek → admin nilai: Revision dalam skop (tiada caj) / Additional Work (harga + minggu
  tambahan) / Tolak (sebab wajib) → pelanggan lulus/tolak sebut harga → invois ADDITIONAL_CHARGE 100% (sekali),
  tempoh slot dipanjangkan. Quotation asal kekal terkunci. Komisyen affiliate dikira untuk invois ini.
- Pengesahan bayaran projek selesai kini merangkumi invois kerja tambahan; invois tambahan selepas pengesahan
  memerlukan pengesahan semula.

## Program Partnership (portal `/partner`)
- Aliran: 1) Daftar → 2) Terma & syarat (butang Daftar aktif hanya selepas tanda setuju; disemak semula di pelayan)
  → 3) Maklumat asas (nama, emel, telefon, individu/syarikat) + amaun modal (min RM5,000) → OTP emel → akaun dicipta
  (terus aktif, tiada semakan admin) → invois modal → Billplz → 4) Bayaran berjaya → lengkapkan profil (No. IC /
  pendaftaran syarikat, bank, no. akaun; disimpan encrypted) → 5) Dashboard. Menu portal disekat sehingga langkah 5.
- Admin masih boleh Gantung akaun partner.
- Suis "Program Partnership dibuka" default OFF; admin hidupkan di Admin → Partnership → Tetapan.
- Modal: minimum RM5,000, had kumpulan RM100,000 (boleh ubah) → invois PARTNER_CAPITAL → Billplz → callback PAID → modal AKTIF.
  Invois modal bukan jualan: tiada komisyen affiliate, tidak masuk pool.
- Pool: setiap invois pelanggan production dibayar penuh → 10% (boleh ubah) daripada amaun dibayar diagih serta-merta
  ikut nisbah modal aktif (sen tepat, largest remainder). Tiada modal aktif → direkod "tidak diagih".
  Refund pelanggan → pembalikan berkadar dengan agihan asal. Lejar: Admin → Pool Partnership.
- Pengeluaran pulangan: admin pindah manual & rekod (NAT-PPO), tidak melebihi baki.

## Akses admin ke portal (Mod Admin)
- Admin → Pelanggan / Affiliate / Partnership → "Log masuk sebagai". Banner merah MOD ADMIN; "Keluar mod admin".
- Semua tindakan dibenarkan kecuali: bayar, terima quotation, pegang slot, keputusan Change Request, tambah modal.
- Mula/tamat dan setiap tindakan bukan-GET direkod dalam audit log. Sesi admin tamat → mod admin tamat.
