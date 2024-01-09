<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    // login
    public function index()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email', [
            'required' => 'Email cannot be empty!',
            'valid_email' => 'Invalid email!'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'required|trim', [
            'required' => 'Password cannot be empty!'
        ]);

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Sign In Page';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/login', $data);
            $this->load->view('templates/auth_footer');
        } else {
            // validation success 
            $this->_login();
        }
    }

    // valid login success
    private function _login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        // if the user exist
        if ($user) {
            // if the user is active 
            if ($user['is_active'] == 1) {
                // check password 
                if (password_verify($password, $user['password'])) {
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    $this->session->set_userdata($data);
                    // check role 
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                }else{
                    // if it fails 
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Wrong Password!</div>');
                    redirect('auth');
                }
            } else {
                // not active
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                This email has not been activated yet!</div>');
                redirect('auth');
            }   
        } else {
            // there are no users with that mail
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Email is not registered!</div>');
            redirect('auth');
        }
    }

    // registrasi
    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        
        $this->form_validation->set_rules('name', 'Name', 'required|trim', [
            'required' => 'Name cannot be empty!'
        ]);
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'required' => 'Ahaa, email cannot be empty!',
            'valid_email' => 'Invalid email!',
            'is_unique' => 'Sorry, this email is already registered!'
        ]);
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[5]|matches[password2]', [
            'required' => 'Nope, password cannot be empty!',
            'matches' => 'Passwords are not the same!',
            'min_length' => 'Password is too freaking short!'
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|min_length[5]|matches[password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Create Account';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration', $data);
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => htmlspecialchars($email),
                'image' => 'default.jpg',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 0,
                'date_created' => time()
            ];

            // token
            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('user', $data);
            $this->db->insert('user_token', $user_token);

            $this->_sendemail($token, 'verify');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Your account has been created successfully. Please check your email to activate your account!</div>');
            redirect('auth');
        }
    }

    // _sendemail
    private function _sendemail($token, $type)
    {
        $config = array();
        $config['protocol']  = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.gmail.com';
        $config['smtp_user'] = 'emicastro@mail.com';
        $config['smtp_pass'] = 'asdfjkl';
        $config['smtp_port'] = 465;
        $config['mailtype']  = 'html';
        $config['charset']   = 'utf-8';

        $this->load->initialize($config);
        $this->email->initialize($config);
        $this->email->set_newline("\r\n");

        $this->email->from('emicastro@mail.com', 'Complaint Management System PHP');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Account Verification');
            $this->email->message('Dear User, Please click the URL for your account verification : <a href="' . base_url() .'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) .'">Activate</a>');
        } else if ($type == 'forgot') {
            $this->email->subject('Reset password');
            $this->email->message('Click this address to reset your password: <a href="' . base_url() .'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) .'">Reset password</a>');
        }

        if ($this->email->send()) {
            return true; 
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    // verify
    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            //if the email is correct 
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();

            if ($user_token) {
                //if token is true

                if (time() - $user_token['date_created'] < (60*60*24)) {
                    // token are  expired
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');
                    $this->db->delete('user_token', ['email' => $email]);

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    '. $email .' Activated! Please login.</div>');
                    redirect('auth');
                } else {
                    // token expired
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);

                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Account activation failed! Token expired.</div>');
                    redirect('auth');
                }

            } else {
                // wrong or incorrect token 
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Account activation failed! Invalid token.</div>');
                redirect('auth');
            }

        } else {
            // wrong email
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Account activation failed! Email does not exist.</div>');
            redirect('auth');
        }
    }

    // logout
    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');

        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Signed Out Successfully!</div>');
        redirect('auth');
    }

    // blocked
    public function blocked()
    {
        $this->load->view('auth/blocked');
    }

    // forgot password
    public function forgotpassword()
    {
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email', [
            'required' => 'Email must be filled!',
            'valid_email' => 'Invalid email!'
        ]);

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/forgot_password', $data);
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email');
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

            if ($user) {
                // if there is , a user can crwate a toke
                $token = base64_encode(random_bytes(32));
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time()
                ];

                $this->db->insert('user_token', $user_token);
                $this->_sendemail($token, 'forgot');
                
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                Please check your email to reset your password!</div>');
                redirect('auth/forgotpassword');
            } else {
                // email tidak terdaftar
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Email is not registered or not yet activated!</div>');
                redirect('auth/forgotpassword');
            }
        }
    }

    // reset password
    public function resetpassword()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            //if there is an email
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            
            if ($user_token) {
                // if the token is active 
                $this->session->set_userdata('reset_email', $email);
                $this->changepassword();
            } else {
                //if the token is inactive 
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Reset password failed! Invalid token</div>');
                redirect('auth');
            }

        } else {
            // if the email doesnt exist 
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Reset password failed! Email is not registered</div>');
            redirect('auth');
        }
    }

    // change password
    public function changepassword()
    {
        if (!$this->session->userdata('reset_email')) {
            redirect('auth');
        }
        $this->form_validation->set_rules('password1', 'New password', 'required|trim|min_length[5]|matches[password2]', [
            'required' => 'Enter a new password!',
            'min_length' => 'Password is too short!',
            'matches' => 'Passwords does not match at all!'
        ]);
        $this->form_validation->set_rules('password2', 'Confirm new password', 'required|trim|min_length[5]|matches[password1]', [
            'required' => 'Enter a new password!',
            'min_length' => 'Password is too short!',
            'matches' => 'Passwords does not match at all!' 
        ]);

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Change Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/change_password', $data);
            $this->load->view('templates/auth_footer');
        } else {
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('reset_email');

            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');

            $this->session->unset_userdata('reset_email');
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Password changed successfully! Please login.</div>');
            redirect('auth');
        }
    }

}