<?php

class Mahasiswa extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Mahasiswa_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        
        // $this->session->set_flashdata('flash', '');
        // print_r($this->session->flashdata());
        // die;
        $data['judul'] = 'Daftar Mahasiswa';
        $data['mahasiswa'] = $this->Mahasiswa_model->getAllMahasiswa();
        if( $this->input->post('keyword') ) {
            $data['mahasiswa'] = $this->Mahasiswa_model->cariDataMahasiswa();
        }

     
        $this->load->view('templates/header', $data);
        $this->load->view('mahasiswa/index', $data);
        $this->load->view('templates/footer');
    }

    public function tambah()
    {
        $data['judul'] = 'Form Tambah Data Mahasiswa';

        $this->form_validation->set_rules('nama', 'Nama', 'required');
        $this->form_validation->set_rules('nrp', 'NRP', 'required|numeric');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('mahasiswa/tambah');
            $this->load->view('templates/footer');
        } else {

            // UPLOAD FILE
            $config['upload_path']      = 'uploads/';
            $config['allowed_types']    = 'jpg|jpeg|png|gif';
            $config['max_size']         = 2048; // 2MB

            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('image')) {
                // Upload failed
                $error = array('error' => $this->upload->display_errors());
                $this->session->set_flashdata('flash', $error);
            } else {
            
                // Upload success
                $data = $this->upload->data();
                $file_path = $data['full_path'];
                $file_name = $data['file_name'];
            
                // Simpan sebagai Large Object
                $oid = $this->save_to_large_object($file_path);
                // echo '<pre>';
                // var_dump($oid);
                // echo '</pre>';
                // die;
                if ($oid) {
                    // Insert metadata to table
                    $result_upload = [
                        'image_oid' => $oid,
                        'file_name' => $file_name
                    ];
                    $this->Mahasiswa_model->tambahDataMahasiswa($result_upload);
                    $this->session->set_flashdata('flash', 'Ditambahkan');
                } else {
                    $this->session->set_flashdata('flash_gagal', 'Gagal ditambahkan');
                }
                
                // Hapus file sementara
                unlink($file_path);
            }
            redirect('mahasiswa');
        }
    }

    public function hapus($id)
    {
        if($this->Mahasiswa_model->hapusDataMahasiswa($id)){
            $mahasiwa = $this->Mahasiswa_model->getMahasiswaById($id);
            $image_oid = $mahasiwa['image_oid'];
            $this->delete_large_object($image_oid);
            $this->session->set_flashdata('flash', 'Dihapus');
        }else{
            $this->session->set_flashdata('flash_gagal', 'Gagal dihapus');
        }
        redirect('mahasiswa');
    }

    public function detail($id)
    {
        $mahasiwa = $this->Mahasiswa_model->getMahasiswaById($id);
        $image_oid = $mahasiwa['image_oid'];
        $image_data = $this->get_large_object($image_oid);
        
        $data['judul'] = 'Detail Data Mahasiswa';
        $data['mahasiswa'] = $mahasiwa;
        $data['image_data'] = 'data:image/jpeg;base64,' . base64_encode($image_data);

        $this->load->view('templates/header', $data);
        $this->load->view('mahasiswa/detail', $data);
        $this->load->view('templates/footer');
    }

    public function ubah($id)
    {
        $mahasiwa = $this->Mahasiswa_model->getMahasiswaById($id);
        $image_oid = $mahasiwa['image_oid'];
        $image_data = $this->get_large_object($image_oid);
        
        $data['judul'] = 'Form Ubah Data Mahasiswa';
        $data['mahasiswa'] = $mahasiwa;
        $data['jurusan'] = ['Teknik Informatika', 'Teknik Mesin', 'Teknik Planologi', 'Teknik Pangan', 'Teknik Lingkungan'];
        $data['image_data'] = 'data:image/jpeg;base64,' . base64_encode($image_data);

        $this->form_validation->set_rules('nama', 'Nama', 'required');
        $this->form_validation->set_rules('nrp', 'NRP', 'required|numeric');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('mahasiswa/ubah', $data);
            $this->load->view('templates/footer');
        } else {

                if ($_FILES['image']['name']){
                    $config['upload_path']      = 'uploads/';
                    $config['allowed_types']    = 'jpg|jpeg|png|gif';
                    $config['max_size']         = 2048; // 2MB
        
                    $this->load->library('upload', $config);
                    $this->upload->initialize($config);
        
                    if (!$this->upload->do_upload('image')) {
                        // Upload failed
                        $error = array('error' => $this->upload->display_errors());
                        $this->session->set_flashdata('flash', $error);
                    } else {
                        // Upload success
                        $data = $this->upload->data();
                        $file_path = $data['full_path'];
                        $file_name = $data['file_name'];
                        // Simpan sebagai Large Object
                        $oid = $this->save_to_large_object($file_path);
                        if ($oid) {
                            $result_upload_lob = [
                                'image_oid' => $oid,
                                'file_name' => $file_name
                            ];
                        } else {
                            $this->session->set_flashdata('flash_gagal', 'Large object gagal diupload');
                            redirect('mahasiswa');
                        }   
                        unlink($file_path);
                    }
                }
                if ($result_upload_lob){
                    $mahasiwa = $this->Mahasiswa_model->getMahasiswaById($id);
                    $image_oid = $mahasiwa['image_oid'];
                    $this->Mahasiswa_model->ubahDataMahasiswa($result_upload_lob);
                    $this->delete_large_object($image_oid);
                    $this->session->set_flashdata('flash', 'Diubah');
                }else{
                    $this->Mahasiswa_model->ubahDataMahasiswa();
                    $this->session->set_flashdata('flash_gagal', 'Large object gagal diupload');
                }

            /** 
             * upload large object baru
             * update data
             * delete lob lama 
             */
            redirect('mahasiswa');
        }
    }

    private function save_to_large_object($file_path) {
        $pg_conn = pg_connect("host={$this->db->hostname} dbname={$this->db->database} user={$this->db->username} password={$this->db->password} port={$this->db->port}");

        if ($pg_conn) {
            // Start a transaction
            pg_query($pg_conn, 'BEGIN');
            $lo_oid = pg_lo_import($pg_conn, $file_path); // Save file as Large Object

            if ($lo_oid) {
                pg_query($pg_conn, 'COMMIT');
                return $lo_oid; // Return OID of the Large Object
            } else {
                pg_query($pg_conn, 'ROLLBACK');
                return false;
            }
        }
        return false;
    }

    private function get_large_object($oid) {
        $pg_conn = pg_connect("host={$this->db->hostname} dbname={$this->db->database} user={$this->db->username} password={$this->db->password} port={$this->db->port}");
    
        if ($pg_conn) {
            // Mulai transaksi
            pg_query($pg_conn, 'BEGIN');
    
            // Buka Large Object untuk membaca
            $lo_handle = pg_lo_open($pg_conn, $oid, 'r');
    
            if ($lo_handle) {
                $data = '';
                while (($chunk = pg_lo_read($lo_handle, 8192)) !== false) {
                    if (strlen($chunk) === 0) {
                        break; // Berhenti jika tidak ada data lagi
                    }
                    $data .= $chunk; // Tambahkan data yang dibaca
                }
    
                pg_lo_close($lo_handle); // Tutup Large Object
                pg_query($pg_conn, 'COMMIT'); // Selesaikan transaksi
    
                return $data; // Kembalikan data LOB
            } else {
                pg_query($pg_conn, 'ROLLBACK'); // Batalkan transaksi jika gagal
                return false;
            }
        }
        return false;
    }

    private function delete_large_object($oid) {
        $pg_conn = pg_connect("host={$this->db->hostname} dbname={$this->db->database} user={$this->db->username} password={$this->db->password} port={$this->db->port}");
    
        if ($pg_conn) {
            // Mulai transaksi
            pg_query($pg_conn, 'BEGIN');
    
            // Hapus Large Object
            if (pg_lo_unlink($pg_conn, $oid)) {
                pg_query($pg_conn, 'COMMIT'); // Commit jika berhasil
                return true;
            } else {
                pg_query($pg_conn, 'ROLLBACK'); // Rollback jika gagal
                return false;
            }
        }
        return false;
    }  
}
