<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ZYA CBT
 * Achmad Lutfi
 * achmdlutfi@gmail.com
 * achmadlutfi.wordpress.com
 */
class Tes_kerjakan extends Tes_Controller
{
    private $kelompok = 'ujian';
    private $url = 'tes_kerjakan';
    private $username;
    private $user_id;

    function __construct()
    {
        parent::__construct();
        $this->load->model('cbt_user_model');
        $this->load->model('cbt_user_grup_model');
        $this->load->model('cbt_tes_model');
        $this->load->model('cbt_tes_token_model');
        $this->load->model('cbt_tes_topik_set_model');
        $this->load->model('cbt_tes_user_model');
        $this->load->model('cbt_tesgrup_model');
        $this->load->model('cbt_soal_model');
        $this->load->model('cbt_jawaban_model');
        $this->load->model('cbt_tes_soal_model');
        $this->load->model('cbt_tes_soal_jawaban_model');

        $this->username = $this->access_tes->get_username();
        $this->user_id = $this->cbt_user_model->get_by_kolom_limit('user_name', $this->username, 1)->row()->user_id;
    }

    public function index($tes_id = null)
    {
        if (!empty($tes_id)) {
            $data['nama'] = $this->access_tes->get_nama();
            $data['group'] = $this->access_tes->get_group();
            $data['url'] = $this->url;
            $data['timestamp'] = strtotime(date('Y-m-d H:i:s'));

            $query_tes = $this->cbt_tes_user_model->get_by_user_tes_limit($this->user_id, $tes_id, 1);
            if ($query_tes->num_rows() > 0) {
                $query_tes = $query_tes->row();
                $tanggal = new DateTime();
                // Cek apakah tes sudah melebihi batas waktu
                $tanggal_tes = new DateTime($query_tes->tesuser_creation_time);
                $tanggal_tes->modify('+' . $query_tes->tes_duration_time . ' minutes');
                if ($tanggal >= $tanggal_tes) {
                    // jika waktu sudah melebihi waktu ketentuan, maka diarahkan ke dashboard
                    redirect('tes_dashboard');
                } else {
                    // mengambil soal sesuai dengan tes yang dikerjakan
                    $data['tes_id'] = $tes_id;
                    $data['tes_user_id'] = $query_tes->tesuser_id;
                    $data['tes_name'] = $query_tes->tes_nama;
                    $data['tes_waktu'] = $query_tes->tes_duration_time;
                    $data['tes_dibuat'] = $query_tes->tesuser_creation_time;
                    $data['tanggal'] = $tanggal->format('Y-m-d H:i:s');

                    // Mengambil selisih jam
                    $tanggal_tes = new DateTime($query_tes->tesuser_creation_time);
                    $tanggal_diff = $tanggal_tes->diff($tanggal);

                    $detik_berjalan = ($tanggal_diff->h * 60 * 60) + ($tanggal_diff->i * 60) + $tanggal_diff->s;
                    $detik_total = $query_tes->tes_duration_time * 60;

                    // untuk menangani Jika tes setelah ditambah waktunya melebihi jam saat itu
                    // jika time saat ini lebih besar dari time creation
                    if ($tanggal >= $tanggal_tes) {
                        $detik_sisa = $detik_total - $detik_berjalan;

                        // jika time creation lebih besar dari tanggal saat ini
                    } else {
                        $detik_sisa = $detik_total + $detik_berjalan;
                    }

                    $data['detik_berjalan'] = $detik_berjalan;
                    $data['detik_total'] = $detik_total;
                    $data['detik_sisa'] = $detik_sisa;

                    // Mengambil menu daftar semua soal
                    $data_soal = $this->get_daftar_soal($tes_id);

                    $data['tes_daftar_soal'] = $data_soal['tes_soal'];
                    $data['tes_soal_jml'] = $data_soal['tes_soal_jml'];

                    // Mengambil data soal ke 1
                    $tessoal = $this->cbt_tes_soal_model->get_by_testuser_limit($query_tes->tesuser_id, 1)->row();
                    $data_soal = $this->get_soal($tessoal->tessoal_id, $query_tes->tesuser_id);

                    $data['tes_soal'] = $data_soal['tes_soal'];
                    $data['tes_ragu'] = $data_soal['tes_ragu'];
                    $data['tes_soal_id'] = $tessoal->tessoal_id;
                    $data['tes_soal_nomor'] = $tessoal->tessoal_order;


                    $this->template->display_tes($this->kelompok . '/tes_kerjakan_view', 'Kerjakan Tes', $data);
                }
            } else {
                redirect('tes_dashboard');
            }
        } else {
            redirect('tes_dashboard');
        }
    }

    /**
     * Menghentikan tes yang sudah berjalan
     */
    function hentikan_tes()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules('hentikan-tes-id', 'Tes', 'required|strip_tags');
        $this->form_validation->set_rules('hentikan-tes-user-id', 'Tes', 'required|strip_tags');
        $this->form_validation->set_rules('hentikan-tes-nama', 'Nama Tes', 'required|strip_tags');

        if ($this->form_validation->run() == TRUE) {
            $tesuser_id = $this->input->post('hentikan-tes-user-id', TRUE);

            $centang = $this->input->post('hentikan-centang', TRUE);
            if (!empty($centang)) {
                $data_tes['tesuser_status'] = 4;
                $this->cbt_tes_user_model->update('tesuser_id', $tesuser_id, $data_tes);

                $status['status'] = 1;
                $status['pesan'] = "Tes berhasil dihentikan";
            } else {
                $status['status'] = 0;
                $status['pesan'] = "Centang terlebih dahulu kolom yang tersedia !";
            }
        } else {
            $status['status'] = 0;
            $status['pesan'] = validation_errors();
        }

        echo json_encode($status);
    }



    /**
     * Menyimpan jawaban yang dipilih oleh User
     */
    function simpan_jawaban()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('tes-id', 'Tes', 'required|strip_tags');
        $this->form_validation->set_rules('tes-user-id', 'Tes User', 'required|strip_tags');
        $this->form_validation->set_rules('tes-soal-id', 'Soal', 'required|strip_tags');
        $this->form_validation->set_rules('tes-soal-nomor', 'Nomor Soal', 'required|strip_tags');

        if ($this->form_validation->run() == TRUE) {
            $jawaban = $this->input->post('soal-jawaban', TRUE);
            $tes_id = $this->input->post('tes-id', TRUE);
            $tes_user_id = $this->input->post('tes-user-id', TRUE);
            $tes_soal_id = $this->input->post('tes-soal-id', TRUE);
            $tes_soal_nomor = $this->input->post('tes-soal-nomor', TRUE);


            // Mengecek apakah tes masih berjalan dan waktu masih mencukupi
            //if($this->cbt_tes_user_model->count_by_status_waktu($tes_user_id)->row()->hasil>0){
            //
            // revisi 2018-11-15
            // agar waktu mengambil dari waktu php, bukan mysql
            $waktuuser = date('Y-m-d H:i:s');
            if ($this->cbt_tes_user_model->count_by_status_waktuuser($tes_user_id, $waktuuser)->row()->hasil > 0) {

                // Mengecek apakah soal ada
                $query_soal = $this->cbt_tes_soal_model->get_by_tessoal_limit($tes_soal_id, 1);

                if ($query_soal->num_rows() > 0) {
                    $query_soal = $query_soal->row();

                    $data_tes_soal['tessoal_change_time'] = date('Y-m-d H:i:s');

                    // menonatifkan ragu-ragu
                    $data_tes_soal['tessoal_ragu'] = 0;

                    // Memulai transaction mysql
                    $this->db->trans_start();
                    $jawaban_array = $this->input->post('soal-jawaban', TRUE);
                    if ($query_soal->soal_tipe == 4) {
                        if (empty($jawaban_array) || !is_array($jawaban_array)) {
                            $status['status'] = 0;
                            $status['pesan'] = "<p>Jawaban tidak boleh kosong.</p>";
                            echo json_encode($status);
                            return;
                        }
                    } else {
                        $this->form_validation->set_rules('soal-jawaban', 'Jawaban', 'required|strip_tags');
                    }

                    // Mengecek jenis soal
                    if ($query_soal->soal_tipe == 1) {
                        // Mendapatkan data tes
                        $query_tes = $this->cbt_tes_model->get_by_kolom_limit('tes_id', $tes_id, 1)->row();

                        // Mendapatkan data jawaban
                        $query_jawaban = $this->cbt_tes_soal_jawaban_model->get_by_tessoal_answer($tes_soal_id, $jawaban)->row();

                        // Mengupdate pilihan jawaban benar
                        $data_jawaban['soaljawaban_selected'] = 1;
                        $this->cbt_tes_soal_jawaban_model->update_by_tessoal_answer($tes_soal_id, $jawaban, $data_jawaban);
                        // Mengupdate pilihan jawaban salah
                        $data_jawaban['soaljawaban_selected'] = 0;
                        $this->cbt_tes_soal_jawaban_model->update_by_tessoal_answer_salah($tes_soal_id, $jawaban, $data_jawaban);

                        // Mengupdate score, change time jika pilihan benar
                        if ($query_jawaban->jawaban_benar == 1) {
                            $data_tes_soal['tessoal_nilai'] = $query_tes->tes_score_right;
                        } else {
                            $data_tes_soal['tessoal_nilai'] = $query_tes->tes_score_wrong;
                        }

                        $this->cbt_tes_soal_model->update('tessoal_id', $tes_soal_id, $data_tes_soal);

                        $status['status'] = 1;
                        $status['nomor_soal'] = $tes_soal_nomor;
                        $status['pesan'] = 'Jawaban yang dipilih berhasil disimpan';

                    } else if ($query_soal->soal_tipe == 2) {
                        // Mengupdate change time, dan jawaban essay
                        $data_tes_soal['tessoal_jawaban_text'] = $jawaban;
                        $data_tes_soal['tessoal_nilai'] = 0;
                        $this->cbt_tes_soal_model->update('tessoal_id', $tes_soal_id, $data_tes_soal);

                        $status['status'] = 1;
                        $status['nomor_soal'] = $tes_soal_nomor;
                        $status['pesan'] = 'Jawaban yang dimasukkan berhasil disimpan';
                    } else if ($query_soal->soal_tipe == 3) {
                        // Mendapatkan data tes
                        $query_tes = $this->cbt_tes_model->get_by_kolom_limit('tes_id', $tes_id, 1)->row();

                        // Mengupdate change time, dan jawaban essay
                        $data_tes_soal['tessoal_jawaban_text'] = $jawaban;
                        if (strtoupper($query_soal->soal_kunci) == strtoupper($jawaban)) {
                            $data_tes_soal['tessoal_nilai'] = $query_tes->tes_score_right;
                        } else {
                            $data_tes_soal['tessoal_nilai'] = $query_tes->tes_score_wrong;
                        }
                        $this->cbt_tes_soal_model->update('tessoal_id', $tes_soal_id, $data_tes_soal);

                        $status['status'] = 1;
                        $status['nomor_soal'] = $tes_soal_nomor;
                        $status['pesan'] = 'Jawaban yang dimasukkan berhasil disimpan';
                        // buat YANG TIPE 4
                    } else if ($query_soal->soal_tipe == 4) {
                        // Ambil data tes (skoring)
                        $query_tes = $this->cbt_tes_model
                            ->get_by_kolom_limit('tes_id', $tes_id, 1)
                            ->row();

                        // Ambil jawaban sebagai array
                        $jawaban_array = $this->input->post('soal-jawaban');

                        // Validasi: minimal pilih satu
                        if (!is_array($jawaban_array) || count($jawaban_array) === 0) {
                            $status = [
                                'status' => 0,
                                'pesan' => '<p>Silakan pilih minimal satu jawaban.</p>',
                                'nomor_soal' => $tes_soal_nomor
                            ];
                            return $this->output
                                ->set_content_type('application/json')
                                ->set_output(json_encode($status));
                        }

                            // Reset semua jawaban di soal ini menjadi 0 dulu
                            $this->db->where('soaljawaban_tessoal_id', $tes_soal_id)
                            ->update('cbt_tes_soal_jawaban', ['soaljawaban_selected' => 0]);
                            // Inisialisasi: anggap dulu semua jawaban benar
                        $salah = 0;

                        foreach ($jawaban_array as $jawaban_id) {

                            // Tandai jawaban user sebagai dipilih
                            $this->cbt_tes_soal_jawaban_model->update_by_tessoal_answer(
                                $tes_soal_id,
                                $jawaban_id,
                                ['soaljawaban_selected' => 1]
                            );

                            // Ambil data jawaban untuk cek kebenaran
                            $jr = $this->cbt_tes_soal_jawaban_model
                                ->get_by_tessoal_answer($tes_soal_id, $jawaban_id)
                                ->row();

                            // Jika salah, tandai dan hentikan loop
                            if ($jr->jawaban_benar != 1) {
                                $salah++;
                            }
                        }

                        // Hitung nilai akhir: 1 jika tak ada kesalahan, 0 jika ada
                        $nilai = ($salah > 0) ? 0 : 1;


                        // Simpan nilai dan timestamp
                        $this->cbt_tes_soal_model
                            ->update('tessoal_id', $tes_soal_id, [
                                'tessoal_nilai' => $nilai,
                                'tessoal_change_time' => date('Y-m-d H:i:s')
                            ]);

                        // Siapkan respon JSON sukses
                        $status = [
                            'status' => 1,
                            'nomor_soal' => $tes_soal_nomor,
                            'pesan' => 'Jawaban yang dipilih berhasil disimpan'
                        ];

                    }
                    // MENJODOHKAN
                    else if ($query_soal->soal_tipe == 5) {
                        $this->cbt_tes_soal_jawaban_model->reset_jawaban_jodohkan($tes_soal_id);
                    
                        $raw = $this->input->post('soal-jawaban', FALSE); // data JSON dari view
                        $jawaban_pairs = json_decode($raw, true);
                    
                        if (!is_array($jawaban_pairs)) {
                            echo json_encode([
                                'status' => 0,
                                'pesan' => 'Format jawaban tidak valid',
                                'nomor_soal' => $tes_soal_nomor
                            ]);
                            return;
                        }
                    
                        // Reset jawaban dulu jadi null
                        $this->db->where('soaljawaban_tessoal_id', $tes_soal_id)
                                 ->update('cbt_tes_soal_jawaban', [
                                     'soaljawaban_selected' => 0,
                                     'soaljawaban_jawaban_id_target' => null
                                 ]);
                    
                        // Ambil semua jawaban di soal ini
                        $all_jawaban = $this->cbt_jawaban_model->get_by_soal($query_soal->soal_id)->result();
                    
                        // Kelompokkan jawaban berdasarkan sisi (kiri = 3, kanan = 2)
                        $jawaban_kiri = [];
                        $jawaban_kanan = [];
                        foreach ($all_jawaban as $j) {
                            if ($j->jawaban_benar == 3) {
                                $jawaban_kiri[$j->jawaban_id] = $j;
                            } elseif ($j->jawaban_benar == 2) {
                                $jawaban_kanan[$j->jawaban_id] = $j;
                            }
                        }
                    
                        // Anggap benar dulu
                        $nilai = 1;
                    
                        // Simpan semua pasangan user
                        foreach ($jawaban_pairs as $left_key => $right_ids) {
                            $left_id = (int) str_replace('s', '', $left_key);
                        
                            if (!isset($jawaban_kiri[$left_id])) {
                                $nilai = 0;
                                continue;
                            }
                        
                            if (!is_array($right_ids)) {
                                $right_ids = [$right_ids];
                            }
                        
                            $clean_rights = [];
                            foreach ($right_ids as $right_key) {
                                $right_id = (int) str_replace('j', '', $right_key);
                        
                                if (!isset($jawaban_kanan[$right_id])) {
                                    $nilai = 0;
                                    continue;
                                }
                                $clean_rights[] = $right_id;
                        
                                // Validasi group
                                if ($jawaban_kiri[$left_id]->jawaban_group !== $jawaban_kanan[$right_id]->jawaban_group) {
                                    $nilai = 0;
                                }
                            }
                        
                            // simpan dalam bentuk JSON biar support multi target
                            $this->cbt_tes_soal_jawaban_model->update_by_tessoal_answer(
                                $tes_soal_id,
                                $left_id,
                                [
                                    'soaljawaban_selected' => count($clean_rights) > 0 ? 1 : 0,
                                    'soaljawaban_jawaban_id_target' => !empty($clean_rights) ? json_encode($clean_rights) : null
                                ]
                            );
                        }
                        
                    
                        // Validasi tambahan: cek semua pasangan wajib
                        foreach ($jawaban_kiri as $lid => $lkiri) {
                            $expected_group = $lkiri->jawaban_group;
                    
                            // Ambil semua kanan yang harus dipasangkan
                            $harus_kanan = array_filter($jawaban_kanan, fn($k) => $k->jawaban_group == $expected_group);
                    
                            if (count($harus_kanan) > 0) {
                                // jika ada kanan, semua harus dipilih
                                $user_pairs = isset($jawaban_pairs['s'.$lid]) ? array_map(
                                    fn($rk) => (int) str_replace('j', '', $rk),
                                    (array)$jawaban_pairs['s'.$lid]
                                ) : [];
                    
                                $harus_ids = array_map(fn($x) => (int)$x->jawaban_id, $harus_kanan);

                                $user_pairs = isset($jawaban_pairs['s'.$lid]) ? array_map(
                                    fn($rk) => (int) str_replace('j', '', $rk),
                                    (array)$jawaban_pairs['s'.$lid]
                                ) : [];
                                
                                sort($harus_ids);
                                sort($user_pairs);
                                
                                if ($harus_ids !== $user_pairs) {
                                    $nilai = 0;
                                }
                                
                            } else {
                                // jika tidak ada pasangan, tapi user memasangkan -> salah
                                if (isset($jawaban_pairs['s'.$lid]) && count($jawaban_pairs['s'.$lid]) > 0) {
                                    $nilai = 0;
                                }
                            }
                        }
                    
                        // Simpan skor akhir
                        $this->cbt_tes_soal_model->update(
                            'tessoal_id',
                            $tes_soal_id,
                            [
                                'tessoal_nilai'       => $nilai,
                                'tessoal_change_time' => date('Y-m-d H:i:s'),
                            ]
                        );
                    
                        $this->db->trans_complete();
                    
                        echo json_encode([
                            'status' => 1,
                            'pesan' => 'Jawaban menjodohkan berhasil disimpan',
                            'nomor_soal' => $tes_soal_nomor
                        ]);
                        return;
                    }
                    
                     
                    
                 
    // Menutup transaction mysql
                    $this->db->trans_complete();
                } else {
                    $status['status'] = 0;
                    $status['pesan'] = 'Terjadi Kesalahan, silahkan hubungi Administrator';
                }
            } else {
                $status['status'] = 2;
                $status['pesan'] = 'Terjadi Kesalahan, Tes sudah selesai';
            }
        } else {
            $status['status'] = 0;
            $status['pesan'] = validation_errors();
        }
        echo json_encode($status);
    }

    /**
     * Mendapatkan info tes
     * 1. nama tes
     * 2. jumlah soal yang belum dijawab
     * 3. jumlah soal yang sudah dijawab
     *
     * @param      <type>  $tes_user_id  The tes user identifier
     */
    function get_tes_info($tes_id = null)
    {
        $data['data'] = 0;
        if (!empty($tes_id)) {
            $query_tes = $this->cbt_tes_user_model->get_by_user_tes_limit($this->user_id, $tes_id, 1);
            if ($query_tes->num_rows() > 0) {
                $query_tes = $query_tes->row();
                $data['data'] = 1;
                $data['tes_id'] = $tes_id;
                $data['tes_user_id'] = $query_tes->tesuser_id;
                $data['tes_nama'] = $query_tes->tes_nama;
                $data['tes_dijawab'] = $this->cbt_tes_soal_model->count_by_tesuser_dijawab($query_tes->tesuser_id)->row()->hasil . ' Soal';
                $data['tes_blum_dijawab'] = $this->cbt_tes_soal_model->count_by_tesuser_blum_dijawab($query_tes->tesuser_id)->row()->hasil . ' Soal';
            }
        }

        echo json_encode($data);
    }

    /**
     * Mendapatkan data cbt_tes_soal berdasarkan tessoal_id
     * @param  [type] $tessoal_id [description]
     * @return [type]            [description]
     */
    function get_tes_soal_by_tessoal($tessoal_id = null)
    {
        $data['data'] = 0;
        if (!empty($tessoal_id)) {
            $query_tes_soal = $this->cbt_tes_soal_model->get_by_kolom_limit('tessoal_id', $tessoal_id, 1);
            if ($query_tes_soal->num_rows() > 0) {
                $query_tes_soal = $query_tes_soal->row();
                $data['data'] = 1;
                $data['tessoal_id'] = $query_tes_soal->tessoal_id;
                $data['tessoal_ragu'] = $query_tes_soal->tessoal_ragu;

                $data['tessoal_dikerjakan'] = 0;
                if (!empty($query_tes_soal->tessoal_change_time)) {
                    $data['tessoal_dikerjakan'] = 1;
                }
            }
        }

        echo json_encode($data);
    }

    function update_tes_soal_ragu($tessoal_id = null, $ragu = null)
    {
        $data['data'] = 1;

        if (!empty($tessoal_id)) {
            if (!empty($ragu)) {
                $data_tes_soal['tessoal_ragu'] = $ragu;
            } else {
                $data_tes_soal['tessoal_ragu'] = 0;
            }

            $this->cbt_tes_soal_model->update('tessoal_id', $tessoal_id, $data_tes_soal);
        }

        echo json_encode($data);
    }

    /**
     * Mendapatkan setiap soal dan jawaban dengan output json 
     */
    function get_soal_by_tessoal($tessoal_id = null, $tesuser_id = null)
    {
        $data['data'] = 0;
        if (!empty($tessoal_id) and !empty($tesuser_id)) {
            $data_soal = $this->get_soal($tessoal_id, $tesuser_id);
            $data['data'] = $data_soal['data'];
            if (!empty($data_soal['tes_soal'])) {
                $data['tes_soal'] = $data_soal['tes_soal'];
                $data['tes_ragu'] = $data_soal['tes_ragu'];
                $data['tes_soal_id'] = $data_soal['tes_soal_id'];
                $data['tes_soal_nomor'] = $data_soal['tes_soal_nomor'];
            }
        }

        echo json_encode($data);
    }

    /**
     * Mendapatkan daftar soal berupa tombol untuk memilih soal yang akan dikerjakan
     *
     * @param      <type>  $tes_id  The tes identifier
     *
     * @return     <type>  The daftar soal.
     */
    private function get_daftar_soal($tes_id = null)
    {
        $data['tes_soal_jml'] = '';
        $data['tes_soal'] = '';
        $jml_soal = 0;
        $data_soal = '';
        if (!empty($tes_id)) {
            $query_tes = $this->cbt_tes_user_model->get_by_user_tes_limit($this->user_id, $tes_id);

            if ($query_tes->num_rows() > 0) {
                $query_tes = $query_tes->row();

                $query_soal = $this->cbt_tes_soal_model->get_by_testuser($query_tes->tesuser_id);
                $jml_soal = $query_soal->num_rows();

                if ($jml_soal > 0) {
                    $query_soal = $query_soal->result();
                    foreach ($query_soal as $soal) {
                        // Jika jawaban sudah diisi
                        if (!empty($soal->tessoal_change_time)) {
                            if ($soal->tessoal_ragu == 0) {
                                // Jika soal tidak ragu-ragu
                                $data_soal = $data_soal . '<button id="btn-soal-' . $soal->tessoal_order . '" onclick="soal(\'' . $soal->tessoal_id . '\')" class="btn btn-primary" style="margin-bottom: 5px;" title="Soal ke ' . $soal->tessoal_order . '">' . $soal->tessoal_order . '</button>

                                ';
                            } else {
                                // Jika soal ragu-ragu
                                $data_soal = $data_soal . '<button id="btn-soal-' . $soal->tessoal_order . '" onclick="soal(\'' . $soal->tessoal_id . '\')" class="btn btn-warning" style="margin-bottom: 5px;" title="Soal ke ' . $soal->tessoal_order . '">' . $soal->tessoal_order . '</button>

                                ';
                            }
                        } else {
                            if ($soal->tessoal_ragu == 0) {
                                // Jika soal tidak ragu-ragu
                                $data_soal = $data_soal . '<button id="btn-soal-' . $soal->tessoal_order . '" onclick="soal(\'' . $soal->tessoal_id . '\')" class="btn btn-default" style="margin-bottom: 5px;" title="Soal ke ' . $soal->tessoal_order . '">' . $soal->tessoal_order . '</button>

                                ';
                            } else {
                                // Jika soal ragu-ragu
                                $data_soal = $data_soal . '<button id="btn-soal-' . $soal->tessoal_order . '" onclick="soal(\'' . $soal->tessoal_id . '\')" class="btn btn-warning" style="margin-bottom: 5px;" title="Soal ke ' . $soal->tessoal_order . '">' . $soal->tessoal_order . '</button>

                                ';
                            }
                        }
                    }
                }
            }
        }
        $data['tes_soal_jml'] = $jml_soal;
        $data['tes_soal'] = $data_soal;

        return $data;
    }


    /**
     * Mendapatkan soal dan jawaban dalam bentuk html     *
     * @param      <type>  $tessoal_id  The tessoal identifier
     *
     * @return     string  The soal.
     */
    private function get_soal($tessoal_id = null, $tesuser_id = null)
    {
        $data['tes_soal_id'] = '';
        $data['tes_soal'] = '';
        $data['data'] = 0;
        if (!empty($tessoal_id) and !empty($tesuser_id)) {
            // Mengecek apakah tes masih berjalan
            // mengambil tesuser_id terus mendapatkan datanya, dicek statusnya dan waktunya
            //if($this->cbt_tes_user_model->count_by_status_waktu($tesuser_id)->row()->hasil>0){
            //
            // revisi 2018-11-15
            // agar waktu mengambil dari waktu php, bukan mysql
            $waktuuser = date('Y-m-d H:i:s');
            if ($this->cbt_tes_user_model->count_by_status_waktuuser($tesuser_id, $waktuuser)->row()->hasil > 0) {
                $data['data'] = 1;
                $query_soal = $this->cbt_tes_soal_model->get_by_tessoal_limit($tessoal_id, 1);
                $soal = '';
                if ($query_soal->num_rows() > 0) {
                    $data['tes_soal_id'] = $tessoal_id;

                    $query_soal = $query_soal->row();

                    // Soal Ragu-ragu
                    $data['tes_ragu'] = $query_soal->tessoal_ragu;

                    // Mengupdate tessoal_display_time pada table test_log
                    $data_tes_soal['tessoal_display_time'] = date('Y-m-d H:i:s');
                    $this->cbt_tes_soal_model->update('tessoal_id', $tessoal_id, $data_tes_soal);

                    // mengganti [baseurl] ke alamat sesungguhnya
                    $soal = $query_soal->soal_detail;
                    $soal = str_replace("[base_url]", base_url(), $soal);

                    // memberi file audio jika ada
                    if (!empty($query_soal->soal_audio)) {
                        $audio_play = 0;
                        if ($query_soal->soal_audio_play == 1) {
                            $audio_play = 1;
                        }
                        // jika batasan play audio masih bernilai 0
                        if ($query_soal->tessoal_audio_play == 0) {
                            $posisi = $this->config->item('upload_path') . '/topik_' . $query_soal->soal_topik_id;
                            $soal = $soal . '
                                <audio volume="1.0" id="audio-player" onended="audio_ended(\'' . $audio_play . '\')">
                                  <source src="' . base_url() . $posisi . '/' . $query_soal->soal_audio . '" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                                <div style="max-width:350px" id="audio-control">
                                    <div class="box">
                                        <div class="box-body">
                                            <input type="hidden" id="audio-player-status" value="0" />
                                            <input type="hidden" id="audio-player-update" value="0" />
                                            <a class="btn btn-app" onclick="audio(\'' . $audio_play . '\')">
                                                <i class="fa fa-play" id="audio-player-judul-logo"></i> <span id="audio-player-judul">Play</span>
                                            </a>
                                            &nbsp;&nbsp;Klik Play untuk memutar Audio
                                        </div>
                                    </div>
                                </div>
                            ';
                        }
                    }

                    $soal = $soal . '<hr />';

                    $data['tes_soal_nomor'] = $query_soal->tessoal_order;

                    $soal = $soal . '<div class="form-group">';
                    //PILIHAN GANDA
                    if ($query_soal->soal_tipe == 1) {
                        $query_jawaban = $this->cbt_tes_soal_jawaban_model->get_by_tessoal($query_soal->tessoal_id);
                        if ($query_jawaban->num_rows() > 0) {
                            $query_jawaban = $query_jawaban->result();
                            foreach ($query_jawaban as $jawaban) {
                                // mengganti [baseurl] ke alamat sesungguhnya pada tag img / gambars
                                $temp_jawaban = $jawaban->jawaban_detail;
                                $temp_jawaban = str_replace("[base_url]", base_url(), $temp_jawaban);

                                if ($jawaban->soaljawaban_selected == 1) {
                                    $soal = $soal . '<div class="radio"><label><input type="radio" onchange="jawab()" name="soal-jawaban" value="' . $jawaban->soaljawaban_jawaban_id . '" checked> ' . $temp_jawaban . '</label></div>';
                                } else {
                                    $soal = $soal . '<div class="radio"><label><input type="radio" onchange="jawab()" name="soal-jawaban" value="' . $jawaban->soaljawaban_jawaban_id . '" > ' . $temp_jawaban . '</label></div>';
                                }
                            }
                        }
                    }
                    // ESAI
                    else if ($query_soal->soal_tipe == 2) {
                        if (!empty($query_soal->tessoal_jawaban_text)) {
                            $soal = $soal . '<textarea class="textarea" id="soal-jawaban" name="soal-jawaban" style="width: 100%; height: 150px; font-size: 13px; line-height: 25px; border: 1px solid #dddddd; padding: 10px;">' . $query_soal->tessoal_jawaban_text . '</textarea>
                                <button type="button" onclick="jawab()" class="btn btn-default" style="margin-bottom: 5px;" title="Simpan Jawaban">Simpan Jawaban</button>
                                ';
                        } else {
                            $soal = $soal . '<textarea class="textarea" id="soal-jawaban" name="soal-jawaban" style="width: 100%; height: 150px; font-size: 13px; line-height: 25px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                                <button type="button" onclick="jawab()" class="btn btn-default" style="margin-bottom: 5px;" title="Simpan Jawaban">Simpan Jawaban</button>
                                ';
                        }
                    }
                    // JAWABAN SINGKAT
                    else if ($query_soal->soal_tipe == 3) {
                        if (!empty($query_soal->tessoal_jawaban_text)) {
                            $soal = $soal . '
                                <input type="text" class="form-control" style="max-width: 500px;" id="soal-jawaban" name="soal-jawaban" value="' . $query_soal->tessoal_jawaban_text . '" autocomplete="off" />
                                <br />
                                <button type="button" onclick="jawab()" class="btn btn-default" style="margin-bottom: 5px;" title="Simpan Jawaban">Simpan Jawaban</button>
                                ';
                        } else {
                            $soal = $soal . '
                                <input type="text" class="form-control" style="max-width: 500px;" id="soal-jawaban" name="soal-jawaban" autocomplete="off" />
                                <br />
                                <button type="button" onclick="jawab()" class="btn btn-default" style="margin-bottom: 5px;" title="Simpan Jawaban">Simpan Jawaban</button>
                                ';
                        }
                    }
                    // PILIHAN GANDA KOMPLEKS
                    else if ($query_soal->soal_tipe == 4) {
                        $query_jawaban = $this->cbt_tes_soal_jawaban_model->get_by_tessoal($query_soal->tessoal_id);
                        if ($query_jawaban->num_rows() > 0) {
                            $query_jawaban = $query_jawaban->result();
                            foreach ($query_jawaban as $jawaban) {
                                $temp_jawaban = $jawaban->jawaban_detail;
                                $temp_jawaban = str_replace("[base_url]", base_url(), $temp_jawaban);

                                if ($jawaban->soaljawaban_selected == 1) {
                                    $soal .= '<div class="checkbox"><label><input type="checkbox" onchange="jawab()" name="soal-jawaban[]" value="' . $jawaban->soaljawaban_jawaban_id . '" checked> ' . $temp_jawaban . '</label></div>';
                                } else {
                                    $soal .= '<div class="checkbox"><label><input type="checkbox" onchange="jawab()" name="soal-jawaban[]" value="' . $jawaban->soaljawaban_jawaban_id . '"> ' . $temp_jawaban . '</label></div>';
                                }
                            }
                        }
                    }

            // MENJODOHKAN (FULL REVISI: tampilkan semua kiri+kanan, multi-connection, hapus via double click/tap)
            else if ($query_soal->soal_tipe == 5) {
                // ambil semua jawaban utk soal ini
                $qj = $this->cbt_jawaban_model->get_by_kolom('jawaban_soal_id', $query_soal->soal_id);
            
                // pisahkan kiri (jawaban_benar==3) dan kanan (jawaban_benar==2)
                $lefts = [];
                $rights = [];
                foreach ($qj->result() as $jwb) {
                    if ((int)$jwb->jawaban_benar === 3) $lefts[] = $jwb;
                    elseif ((int)$jwb->jawaban_benar === 2) $rights[] = $jwb;
                }
            
                // session shuffle kanan (deterministik per tessoal+user)
                $acak_key = 'acak_jodoh_' . $query_soal->tessoal_id . '_' . $tesuser_id;
                $rights_original = $rights;
                if (!$this->session->userdata($acak_key)) {
                    $shuffled = $rights_original;
                    shuffle($shuffled);
                    $shuffled_ids = array_map(function($j){ return $j->jawaban_id; }, $shuffled);
                    $this->session->set_userdata($acak_key, $shuffled_ids);
                }
                // rebuild urutan kanan sesuai session ids
                $map_rights = [];
                foreach ($rights_original as $r) $map_rights[$r->jawaban_id] = $r;
                $rights_shuffled = [];
                $sess_ids = $this->session->userdata($acak_key) ?: [];
                foreach ($sess_ids as $id) {
                    if (isset($map_rights[$id])) $rights_shuffled[] = $map_rights[$id];
                }
                // fallback: jika session ids tidak mencakup semua (mis. karena perubahan), tambahkan sisanya
                foreach ($rights_original as $r) {
                    if (!in_array($r, $rights_shuffled, true)) $rights_shuffled[] = $r;
                }
            
                // existing saved pairs (banyak target per sumber)
                $existing_jodoh = $this->cbt_tes_soal_jawaban_model
                    ->get_by_kolom('soaljawaban_tessoal_id', $query_soal->tessoal_id)->result();
                // format: 's<leftId>' => ['j<rightId>', ...]
                $existing_pairs = [];
                foreach ($existing_jodoh as $row) {
                    // pastikan ada jawaban sumber
                    if (!isset($row->soaljawaban_jawaban_id)) continue;
                    $from = 's' . $row->soaljawaban_jawaban_id;
                    if (!isset($existing_pairs[$from])) $existing_pairs[$from] = [];
            
                    // jika field target ada dan tidak kosong, bisa berupa:
                    // - JSON array string -> ["931","932"]
                    // - single numeric string -> "931"
                    // - comma-separated -> "931,932"
                    // - atau model berulang: beberapa baris tiap target (row->soaljawaban_jawaban_id_target berisi single)
                    if (!empty($row->soaljawaban_jawaban_id_target)) {
                        $targets_raw = $row->soaljawaban_jawaban_id_target;
                        $decoded = @json_decode($targets_raw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            foreach ($decoded as $tid) {
                                $tid = (int)$tid;
                                if ($tid <= 0) continue;
                                $to = 'j' . $tid;
                                if (!in_array($to, $existing_pairs[$from])) $existing_pairs[$from][] = $to;
                            }
                        } else {
                            // coba cek comma separated
                            if (is_string($targets_raw) && strpos($targets_raw, ',') !== false) {
                                $parts = array_map('trim', explode(',', $targets_raw));
                                foreach ($parts as $p) {
                                    $tid = (int)$p;
                                    if ($tid <= 0) continue;
                                    $to = 'j' . $tid;
                                    if (!in_array($to, $existing_pairs[$from])) $existing_pairs[$from][] = $to;
                                }
                            } else {
                                // single numeric fallback
                                $tid = (int)$targets_raw;
                                if ($tid > 0) {
                                    $to = 'j' . $tid;
                                    if (!in_array($to, $existing_pairs[$from])) $existing_pairs[$from][] = $to;
                                }
                            }
                        }
                    } else {
                        // jika tidak ada target di kolom target, kemungkinan ada baris lain yang menyimpan target
                        // atau memang tidak berpasangan (tidak perlu memasukkan)
                        // Tapi jika row->soaljawaban_selected==1 dan tidak punya target, kita tidak tambahkan mapping.
                    }
                }
            
                ob_start();
                ?>
                <style>
                    .soal-text { margin: 20px; }
                    #wrapper { position: relative; width:100%; margin:20px 0; -webkit-overflow-scrolling: touch; }
                    .column { box-sizing: border-box; }
                    .item { position: relative; margin:8px 0; padding:8px; border:1px solid #ccc; background:#fff; border-radius:6px; }
                    .item img { max-width:100%; height:auto; display:block; pointer-events: none; -webkit-user-drag: none; user-drag: none; }
                    .endpoint { position:absolute; width:14px; height:14px; border-radius:50%; transform:translateY(-50%); cursor:pointer; }
                    .left .endpoint { right:-8px; top:50%; border:2px solid rgba(0,0,0,0.15); background:#bbb; }
                    .right .endpoint { left:-8px; top:50%; border:2px solid rgba(0,0,0,0.15); background:#bbb; }
                    .item.selected { box-shadow: 0 0 0 3px rgba(0,123,255,0.08); border-color: #007bff; }
                    #save-btn { display:block; margin:16px auto; }
                    @media (max-width:768px){
                        .column.left, .column.right { width: 48% !important; }
                    }
                </style>
            
                <div class="soal-text"><?= $query_soal->soal_detail ?></div>
                <?php if (!empty($query_soal->soal_audio)) {
                    $audio_play = 0;
                    if ($query_soal->soal_audio_play == 1) { $audio_play = 1; }
                    if ($query_soal->tessoal_audio_play == 0) {
                        $posisi = $this->config->item('upload_path') . '/topik_' . $query_soal->soal_topik_id;
                        echo '
                            <audio volume="1.0" id="audio-player" onended="audio_ended(\'' . $audio_play . '\')">
                              <source src="' . base_url() . $posisi . '/' . $query_soal->soal_audio . '" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <div style="max-width:350px" id="audio-control">
                                <div class="box"><div class="box-body">
                                    <input type="hidden" id="audio-player-status" value="0" />
                                    <input type="hidden" id="audio-player-update" value="0" />
                                    <a class="btn btn-app" onclick="audio(\'' . $audio_play . '\')">
                                        <i class="fa fa-play" id="audio-player-judul-logo"></i> <span id="audio-player-judul">Play</span>
                                    </a>
                                    &nbsp;&nbsp;Klik Play untuk memutar Audio
                                </div></div>
                            </div>
                        ';
                    }
                } ?>
            
                <div id="wrapper">
                    <svg id="canvas" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; overflow:visible;"></svg>
                    <input type="hidden" id="soal-jawaban-hidden" name="soal-jawaban" value="">
            
                    <div class="column left" style="width:45%; float:left;">
                        <?php foreach ($lefts as $l): ?>
                            <div class="item left-item left" id="s<?= $l->jawaban_id ?>" data-value="s<?= $l->jawaban_id ?>">
                                <?= $l->jawaban_detail /* raw HTML */ ?>
                                <div class="endpoint"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
            
                    <div class="column right" style="width:45%; float:right;">
                        <?php foreach ($rights_shuffled as $r): ?>
                            <div class="item right-item right" id="j<?= $r->jawaban_id ?>" data-value="j<?= $r->jawaban_id ?>">
                                <?= $r->jawaban_detail /* raw HTML */ ?>
                                <div class="endpoint"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
            
                    <div style="clear:both;"></div>
                    <button id="save-btn" style="display:block;margin:16px auto;" class="btn btn-default">Simpan Jawaban</button>
                </div>
            
                <script>
                (function () {
                    const svg = document.getElementById('canvas');
                    const wrap = document.getElementById('wrapper');
                    const btnSave = document.getElementById('save-btn');
            
                    // connections: { from: element(left), to: element(right), line: SVGLine, color }
                    let connections = [];
                    let dragLine = null, startEP = null, startSide = null; // startSide: 'left'|'right'
                    let selected = null; // { el, side }
                    let lastTap = { el:null, ts:0 }; // untuk mendeteksi double tap
            
                    // existing pairs dari server (multi): { 's<id>': ['j<id>', ...] }
                    const existingPairs = <?= json_encode($existing_pairs ?: new stdClass()) ?>;
            
                    function getRandomColor() {
                        const letters = '0123456789ABCDEF'; let c = '#';
                        for (let i=0;i<6;i++) c += letters[Math.floor(Math.random()*16)];
                        return c;
                    }
            
                    function getEPPos(el) {
                        const r = wrap.getBoundingClientRect();
                        const q = el.getBoundingClientRect();
                        return { x: q.left - r.left + q.width/2, y: q.top - r.top + q.height/2 };
                    }
            
                    function hasConnection(leftEl, rightEl) {
                        return connections.some(c => c.from === leftEl && c.to === rightEl);
                    }
            
                    function clearEndpointIfUnused(el){
                        const ep = el.querySelector('.endpoint');
                        if (!ep) return;
                        const inUse = connections.some(c => c.from === el || c.to === el);
                        if (!inUse) {
                            ep.style.background = '#bbb';
                            ep.style.borderColor = 'rgba(0,0,0,0.15)';
                        }
                    }
            
                    function attachRemoveEvents(line, leftEl, rightEl) {
                        // desktop double click
                        line.addEventListener('dblclick', e => {
                            e.preventDefault();
                            removeConnection(leftEl, rightEl, line);
                        });
                        // mobile double tap
                        let lastTapTime = 0;
                        line.addEventListener('touchend', e => {
                            const now = Date.now();
                            if (now - lastTapTime < 350) {
                                removeConnection(leftEl, rightEl, line);
                                e.preventDefault();
                            }
                            lastTapTime = now;
                        }, { passive:false });
                    }
            
                    function createConnection(leftEl, rightEl, color=null) {
                        if (!leftEl || !rightEl) return;
                        if (hasConnection(leftEl, rightEl)) return; // hindari duplikat
            
                        const startEP = leftEl.querySelector('.endpoint');
                        const endEP   = rightEl.querySelector('.endpoint');
                        const p1 = startEP ? getEPPos(startEP) : getEPPos(leftEl);
                        const p2 = endEP ? getEPPos(endEP) : getEPPos(rightEl);
            
                        if (!color) color = getRandomColor();
            
                        const line = document.createElementNS('http://www.w3.org/2000/svg','line');
                        line.setAttribute('x1', p1.x); line.setAttribute('y1', p1.y);
                        line.setAttribute('x2', p2.x); line.setAttribute('y2', p2.y);
                        line.setAttribute('stroke', color); line.setAttribute('stroke-width', 3);
                        line.setAttribute('stroke-linecap', 'round');
                        svg.appendChild(line);
            
                        if (startEP) { startEP.style.background = color; startEP.style.borderColor = color; }
                        if (endEP) { endEP.style.background = color; endEP.style.borderColor = color; }
            
                        connections.push({ from: leftEl, to: rightEl, line: line, color: color });
                        attachRemoveEvents(line, leftEl, rightEl);
                        updateHidden();
                    }
            
                    function removeConnection(leftEl, rightEl, line) {
                        connections = connections.filter(c => {
                            if (c.from===leftEl && c.to===rightEl && c.line===line) {
                                if (c.line && c.line.parentNode) c.line.parentNode.removeChild(c.line);
                                return false;
                            }
                            return true;
                        });
                        // bersihkan endpoint style bila tidak lagi dipakai
                        clearEndpointIfUnused(leftEl);
                        clearEndpointIfUnused(rightEl);
                        updateHidden();
                    }
            
                    function removeAllConnectionsOf(el){
                        let changed = false;
                        connections = connections.filter(c => {
                            const hit = (c.from === el || c.to === el);
                            if (hit) {
                                if (c.line && c.line.parentNode) c.line.parentNode.removeChild(c.line);
                                changed = true;
                            }
                            return !hit;
                        });
                        if (changed) {
                            // bersihkan endpoint pasangan yang mungkin jadi tidak terpakai
                            document.querySelectorAll('.item').forEach(i => clearEndpointIfUnused(i));
                            updateHidden();
                        }
                    }
            
                    function updateHidden() {
                        const payload = {};
                        connections.forEach(c => {
                            const fid = c.from.id;
                            const tid = c.to.id;
                            if (!payload[fid]) payload[fid] = [];
                            if (!payload[fid].includes(tid)) payload[fid].push(tid);
                        });
                        document.getElementById('soal-jawaban-hidden').value = JSON.stringify(payload);
                    }
            
                    function refreshAllLines() {
                        connections.forEach(c => {
                            const sp = c.from.querySelector('.endpoint'), ep = c.to.querySelector('.endpoint');
                            const p1 = sp ? getEPPos(sp) : getEPPos(c.from);
                            const p2 = ep ? getEPPos(ep) : getEPPos(c.to);
                            if (c.line) {
                                c.line.setAttribute('x1', p1.x); c.line.setAttribute('y1', p1.y);
                                c.line.setAttribute('x2', p2.x); c.line.setAttribute('y2', p2.y);
                            }
                        });
                    }
            
                    // init
                    function init() {
                        // disable drag image
                        document.querySelectorAll('#wrapper img').forEach(img => {
                            try { img.setAttribute('draggable','false'); } catch(e){}
                            img.addEventListener('dragstart', ev => ev.preventDefault());
                        });
            
                        // pilih item lalu klik item sisi berlawanan untuk membuat koneksi (multi allowed)
                        document.querySelectorAll('.left-item, .right-item').forEach(el => {
                            // klik untuk pilih & connect
                            el.addEventListener('click', function (ev) {
                                if (ev.target.closest('#save-btn')) return;
                                const side = this.classList.contains('left') ? 'left' : 'right';
            
                                if (!selected) {
                                    selected = { el: this, side: side };
                                    this.classList.add('selected');
                                    return;
                                }
            
                                if (selected.el === this) {
                                    this.classList.remove('selected');
                                    selected = null;
                                    return;
                                }
            
                                if (selected.side === side) {
                                    selected.el.classList.remove('selected');
                                    selected = { el: this, side: side };
                                    this.classList.add('selected');
                                    return;
                                }
            
                                // opposite side: buat koneksi (tanpa menghapus koneksi lama)
                                let leftEl, rightEl;
                                if (selected.side === 'left') { leftEl = selected.el; rightEl = this; }
                                else { leftEl = this; rightEl = selected.el; }
            
                                createConnection(leftEl, rightEl);
            
                                // clear selection
                                if (selected && selected.el) selected.el.classList.remove('selected');
                                selected = null;
                            }, { passive: true });
            
                            // double click (desktop) untuk hapus semua koneksi terkait item
                            el.addEventListener('dblclick', function(e){
                                e.preventDefault();
                                removeAllConnectionsOf(this);
                            });
            
                            // double tap (mobile)
                            el.addEventListener('touchend', function(e){
                                const now = Date.now();
                                if (lastTap.el === this && (now - lastTap.ts) < 350) {
                                    // dianggap double tap
                                    removeAllConnectionsOf(this);
                                    lastTap = { el:null, ts:0 };
                                    e.preventDefault();
                                    return;
                                }
                                lastTap = { el:this, ts:now };
                            }, { passive:false });
                        });
            
                        // drag dari endpoint (desktop & touch) untuk membuat koneksi
                        wrap.addEventListener('mousedown', function (e) {
                            const target = e.target;
                            if (!target.classList.contains('endpoint')) return;
                            const side = target.closest('.left') ? 'left' : (target.closest('.right') ? 'right' : null);
                            if (!side) return;
                            e.preventDefault();
                            startEP = target;
                            startSide = side;
                            const p = getEPPos(startEP);
                            dragLine = document.createElementNS('http://www.w3.org/2000/svg','line');
                            dragLine.setAttribute('x1', p.x); dragLine.setAttribute('y1', p.y);
                            dragLine.setAttribute('x2', p.x); dragLine.setAttribute('y2', p.y);
                            dragLine.setAttribute('stroke', '#999'); dragLine.setAttribute('stroke-width', 2);
                            svg.appendChild(dragLine);
            
                            function onMove(ev) { updateDrag(ev.clientX, ev.clientY); }
                            function onUp(ev) {
                                document.removeEventListener('mousemove', onMove);
                                document.removeEventListener('mouseup', onUp);
                                endDrag(ev.clientX, ev.clientY);
                            }
                            document.addEventListener('mousemove', onMove);
                            document.addEventListener('mouseup', onUp);
                        });
            
                        wrap.addEventListener('touchstart', function (e) {
                            const touch = e.touches[0];
                            const target = document.elementFromPoint(touch.clientX, touch.clientY);
                            if (!target || !target.classList.contains('endpoint')) return;
                            const side = target.closest('.left') ? 'left' : (target.closest('.right') ? 'right' : null);
                            if (!side) return;
                            e.preventDefault();
                            startEP = target;
                            startSide = side;
                            const p = getEPPos(startEP);
                            dragLine = document.createElementNS('http://www.w3.org/2000/svg','line');
                            dragLine.setAttribute('x1', p.x); dragLine.setAttribute('y1', p.y);
                            dragLine.setAttribute('x2', p.x); dragLine.setAttribute('y2', p.y);
                            dragLine.setAttribute('stroke', '#999'); dragLine.setAttribute('stroke-width', 2);
                            svg.appendChild(dragLine);
                        }, { passive: false });
            
                        wrap.addEventListener('touchmove', function (e) {
                            if (!dragLine) return;
                            const t = e.touches[0];
                            updateDrag(t.clientX, t.clientY);
                        }, { passive: false });
            
                        wrap.addEventListener('touchend', function (e) {
                            if (!dragLine) return;
                            const t = e.changedTouches[0];
                            endDrag(t.clientX, t.clientY);
                        });
            
                        function updateDrag(clientX, clientY) {
                            if (!dragLine) return;
                            const r = wrap.getBoundingClientRect();
                            dragLine.setAttribute('x2', clientX - r.left);
                            dragLine.setAttribute('y2', clientY - r.top);
                        }
            
                        function endDrag(clientX, clientY) {
                            if (!startEP || !dragLine) return;
                            const drop = document.elementFromPoint(clientX, clientY);
                            const droppedEndpoint = drop && drop.classList.contains('endpoint') ? drop : null;
            
                            let leftEl = null, rightEl = null;
                            if (startSide === 'left') {
                                const fromEl = startEP.closest('.item');
                                if (droppedEndpoint && droppedEndpoint.closest('.right')) {
                                    leftEl = fromEl;
                                    rightEl = droppedEndpoint.closest('.item');
                                }
                            } else if (startSide === 'right') {
                                const fromEl = startEP.closest('.item');
                                if (droppedEndpoint && droppedEndpoint.closest('.left')) {
                                    leftEl = droppedEndpoint.closest('.item');
                                    rightEl = fromEl;
                                }
                            }
            
                            if (leftEl && rightEl) {
                                // jadikan garis sementara sebagai garis final
                                const leftEP = leftEl.querySelector('.endpoint');
                                const rightEP = rightEl.querySelector('.endpoint');
                                const p1 = leftEP ? getEPPos(leftEP) : getEPPos(leftEl);
                                const p2 = rightEP ? getEPPos(rightEP) : getEPPos(rightEl);
                                dragLine.setAttribute('x1', p1.x); dragLine.setAttribute('y1', p1.y);
                                dragLine.setAttribute('x2', p2.x); dragLine.setAttribute('y2', p2.y);
                                const color = getRandomColor();
                                dragLine.setAttribute('stroke', color); dragLine.setAttribute('stroke-width', 3);
                                if (leftEP) { leftEP.style.background = color; leftEP.style.borderColor = color; }
                                if (rightEP) { rightEP.style.background = color; rightEP.style.borderColor = color; }
                                connections.push({ from: leftEl, to: rightEl, line: dragLine, color: color });
                                attachRemoveEvents(dragLine, leftEl, rightEl);
                                updateHidden();
                            } else {
                                // hapus garis sementara
                                if (dragLine && dragLine.parentNode) dragLine.parentNode.removeChild(dragLine);
                            }
            
                            dragLine = null; startEP = null; startSide = null;
                        }
                        
                        // preload existing pairs (multi)
                        for (let fromId in existingPairs) {
                            const tos = existingPairs[fromId] || [];
                            for (let i=0;i<tos.length;i++) {
                                const toId = tos[i];
                                const leftEl = document.getElementById(fromId);
                                const rightEl = document.getElementById(toId);
                                if (leftEl && rightEl) {
                                    const sp = leftEl.querySelector('.endpoint'), ep = rightEl.querySelector('.endpoint');
                                    const p1 = sp ? getEPPos(sp) : getEPPos(leftEl);
                                    const p2 = ep ? getEPPos(ep) : getEPPos(rightEl);
                                    const color = getRandomColor();
                                    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
                                    line.setAttribute('x1', p1.x); line.setAttribute('y1', p1.y);
                                    line.setAttribute('x2', p2.x); line.setAttribute('y2', p2.y);
                                    line.setAttribute('stroke', color); line.setAttribute('stroke-width', 3);
                                    svg.appendChild(line);
                                    if (sp) { sp.style.background = color; sp.style.borderColor = color; }
                                    if (ep) { ep.style.background = color; ep.style.borderColor = color; }
                                    connections.push({ from: leftEl, to: rightEl, line: line, color: color });
                                    attachRemoveEvents(line, leftEl, rightEl);
                                }
                            }
                        }
                        updateHidden();
            
                        window.addEventListener('resize', refreshAllLines);
                        window.addEventListener('scroll', refreshAllLines, true);
            
                        // SAVE: kirim payload (object of arrays) ke server / set ke hidden
                        btnSave.addEventListener('click', () => {
                            const payload = {};
                            connections.forEach(c => {
                                const fid = c.from.id, tid = c.to.id;
                                if (!payload[fid]) payload[fid] = [];
                                if (!payload[fid].includes(tid)) payload[fid].push(tid);
                            });
                            document.getElementById('soal-jawaban-hidden').value = JSON.stringify(payload);
            
                            // Contoh submit via FormData (silakan aktifkan & ganti URL sesuai kebutuhan)
                            const form = new FormData();
                            if (document.getElementById('tes-id')) form.append('tes-id', document.getElementById('tes-id').value);
                            if (document.getElementById('tes-user-id')) form.append('tes-user-id', document.getElementById('tes-user-id').value);
                            if (document.getElementById('tes-soal-id')) form.append('tes-soal-id', document.getElementById('tes-soal-id').value);
                            if (document.getElementById('tes-soal-nomor')) form.append('tes-soal-nomor', document.getElementById('tes-soal-nomor').value);
                            form.append('soal-jawaban', JSON.stringify(payload));
                         
                        });
                    } // end init
            
                    init(); // run
                })();
                </script>
                <?php
                $data['tes_soal'] = ob_get_clean();
                $data['tes_ragu'] = 0;
                $data['data'] = 1;
                $data['tes_soal_id'] = $query_soal->tessoal_id;
                $data['tesuser_id'] = $tesuser_id;
                return $data;
            }
            

                    $soal = $soal . '</div>';

                    $data['tes_soal'] = $soal;
                }
            } else {
                $data['data'] = 2;
            }
        }

        return $data;
    }

    function update_status_audio($tessoal_id = null)
    {
        $data['data'] = 0;
        if (!empty($tessoal_id)) {
            $data['data'] = 1;
            $data_tes['tessoal_audio_play'] = 1;
            $this->cbt_tes_soal_model->update('tessoal_id ', $tessoal_id, $data_tes);
            $data['pesan'] = 'Audio berhasil diputar';
        }
        echo json_encode($data);
    }
}



