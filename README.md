# dasar-git

zyacbt todo:
# data modul - tambah  modul☑️✔️

# timer minimal waktu yang dihabiskan siswa saat ujian, jadi semisal siswa sudah selesai setelah 5 menit masih harus menuggu semisal variable timer minimal adalah 30 menit ☑✔️

# tambah tipe soal : menjodohkan☑️✔️, dan pilihan ganda kompleks☑️✔️

# pastikan pada analisis butir soal jika salah satu jawaban benar maka isi saja 1 jika tidak ada jawaban yang benar pada soal tsb maka isi 0☑️✔️

# Hentikan tes on off☑️✔️

# Order☑️✔️

# jawaban singkat masuk evaluasi EZ☑️✔️

# ngorkesi pakai tombol☑️✔️

# EZ boss : BOBOT NILAI ☑️✔️

# add fitur acak soal per tipe, sesua i pilihan, jadi bisa acak pilihan ganda saja atau dengan tipe lainnya. Jadi misal 1-20 acak (pilihan ganda) 21-25 tidak acak (esai) ☑️✔️

# fix hasil tes token operator (AGIL) ☑️✔️

# fix rekap hasil tes, pakai nilai akhir (AGIL) ☑️✔️

# jadi tes itu bisa diduplikat, istilahnya ada tombol duplikat tes, tes nya saja, tidak perlu duplikat hasilnya. Jadi semisal Tes Matematika, tekan tombol duplikat nanti muncul Tes Matematika (Copy) gitu

# bikin menu baru. Contohnya, "evaluasi tes dengan jawaban yg sama". Jadi di menu lain ini evaluasi tes nya di group berdasarkan jawaban yg hampir sama. Contohnya : Black dengan black sama, jadi tinggal koreksi satu aja abis itu ke jawaban yg lain nilainya sama.

# di kolom evaluasi tes jawabn sama, di beri kolom total jawabn yg sama, di samping nomor
Cara install zyacbt :
Download dari zyacbt versi 2020.11.27
Extract ke htdocs
Edit application/config/database
Kosongkan bagian password db nya
Masuk ke phpmyadmin
Buat db zyacbt
Import file sql tanpa database
Login zyacbt operator dengan admin : admin


ALTER TABLE cbt_tes_topik_set
ADD COLUMN tset_jumlah_pilihan_ganda INT(11) NOT NULL DEFAULT 0 AFTER tset_jumlah,
ADD COLUMN tset_jumlah_essay INT(11) NOT NULL DEFAULT 0 AFTER tset_jumlah_pilihan_ganda,
ADD COLUMN tset_jumlah_jawaban_singkat INT(11) NOT NULL DEFAULT 0 AFTER tset_jumlah_essay,
ADD COLUMN tset_jumlah_pg_kompleks INT(11) NOT NULL DEFAULT 0 AFTER tset_jumlah_jawaban_singkat,
ADD COLUMN tset_jumlah_menjodohkan INT(11) NOT NULL DEFAULT 0 AFTER tset_jumlah_pg_kompleks;

# LIST Fitur tambahan

3. Penjelasan jawaban di hasil tes, (memberikan penjelasan pada jawaban) AGIL

7. Fitur template tes AGIL

9. **Fitur AI dalam Membuat soal** [**DI REKOMENDASIKAN UNTUK DI APPROVE**] ( cara kerjanya, pilih modul dan topik, setelah itu pilih berapa banyak soal, tipe apa saja, dan nanti tinggal buat promt untuk soalnya, contoh "Buatkan 10 soal pilihan ganda untuk ujia harian matematika kelas 6" nanti akan otomatis soalnya masuk ke data base dan masuk ke dalam topiknya ) GIVO
