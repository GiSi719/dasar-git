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
