<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Install extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		/*
		are we installing into a subfolder?
		if not, then the subfolder variabel below will be empty. If we are it will contain a value.
		*/
		$subfolder			= rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\').'/';
		$data['subfolder']	= $subfolder;
		
		//make sure the config folder is writable
		$data['config_writable']	= is_writable($_SERVER['DOCUMENT_ROOT'].$subfolder.'carpooling/config/');
		$data['root_writable']		= is_writable($_SERVER['DOCUMENT_ROOT'].$subfolder);
		$data['relative_path']		= $subfolder.'carpooling/config/';
		
		
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'file'));
		
		$this->form_validation->set_rules('hostname', 'Hostname', 'required');
		$this->form_validation->set_rules('database', 'Database Name', 'required');
		$this->form_validation->set_rules('username', 'Username', 'required');
		$this->form_validation->set_rules('password', 'Password', 'trim');
		//$this->form_validation->set_rules('prefix', 'Database Prefix', 'trim');
		
		$this->form_validation->set_rules('admin_email', 'Admin Email', 'required|valid_email');
		$this->form_validation->set_rules('admin_password', 'Admin Password', 'required|min_length[5]');
		
		$this->form_validation->set_rules('company_name', 'Company Name', 'required');
		$this->form_validation->set_rules('website_email', 'Website Email', 'required|valid_email');
		$this->form_validation->set_rules('site_admin_email', 'Site Admin Email', 'required|valid_email');
		//$this->form_validation->set_rules('ssl_support');
		$this->form_validation->set_rules('mod_rewrite');
		
		$this->form_validation->set_rules('address1');
		$this->form_validation->set_rules('address2');
		$this->form_validation->set_rules('city');
		$this->form_validation->set_rules('state');
		$this->form_validation->set_rules('zip');
		$this->form_validation->set_rules('country','country code', 'required');
		
		//$this->form_validation->set_rules('facebook_app_id', 'Facebook App Id', 'required');
		//$this->form_validation->set_rules('facebook_app_secret_id', 'Facebook App Secret Id', 'required');
		//$this->form_validation->set_rules('google_app_id', 'Google App Id', 'required');
		//$this->form_validation->set_rules('google_app_secret_id', 'Google App Secret Id', 'required');
		
		if ($this->form_validation->run() == FALSE)
		{
			$data['errors']	= validation_errors();
			$this->load->view('install', $data);
		}
		else
		{
			//make sure we can connect to the database
			$config['hostname'] = $this->input->post('hostname');
			$config['username'] = $this->input->post('username');
			$config['password'] = $this->input->post('password');
			$config['database'] = $this->input->post('database');
			$config['dbdriver'] = "mysql";
			$config['dbprefix'] = "";
			$config['pconnect'] = FALSE;
			$config['db_debug'] = FALSE;
			$config['cache_on'] = FALSE;
			$config['cachedir'] = "";
			$config['char_set'] = "utf8";
			$config['dbcollat'] = "utf8_general_ci";
			$config['active_r'] = TRUE; 
			
			// Unset any existing DB information
			unset($this->db);		
			$this->load->database($config);
			
			if (is_resource($this->db->conn_id) OR is_object($this->db->conn_id))
			{	

				$queries	= $this->load->view('templates/sql', '', true);
				$queries	= explode('-- new query', $queries);
				
				foreach($queries as $q)
				{
					$query	= $q;					
					//$query	= str_replace('prefix_', $this->input->post('prefix'), $q);
					$this->db->query($query);
					
				}
				
				if($this->input->post('sample_data'))
				{
					$sample_queries	= $this->load->view('templates/sample_data', '', true);
					$sample_queries	= explode('-- new query', $sample_queries);
					
					foreach($sample_queries as $que)
					{
						$query	= $que;						
						//$query	= str_replace('prefix_', $this->input->post('prefix'), $q);
						$this->db->query($query);
						
					}
				}
					
				
				//set up the admin user
				$this->db->insert('tbl_admin', array('access'=>'Admin', 'admin_email'=>$this->input->post('admin_email'), 'admin_password'=>sha1($this->input->post('admin_password') ) ) );

				//setup the database config file
				$settings					= array();
				$settings['hostname']		= $this->input->post('hostname');
				$settings['username']		= $this->input->post('username');
				$settings['password']		= $this->input->post('password');
				$settings['database']		= $this->input->post('database');
				//$settings['prefix']			= $this->input->post('prefix');				
				$file_contents				= $this->load->view('templates/database', $settings, true);
				write_file($_SERVER['DOCUMENT_ROOT'].$subfolder.'carpooling/config/database.php', $file_contents);

				//setup the carpooling config file
				$settings					= array();
				$settings['company_name']	= $this->input->post('company_name');
				$settings['address1']		= $this->input->post('address1');
				$settings['address2']		= $this->input->post('address2');
				$settings['city']			= $this->input->post('city');
				$settings['state']			= $this->input->post('state');
				$settings['country']		= $this->input->post('country');
				$settings['zip']			= $this->input->post('zip');
				$settings['email']			= $this->input->post('website_email');
				$settings['admin_email']	= $this->input->post('site_admin_email');
				$settings['ssl_support']	= (bool)$this->input->post('ssl_support');
				$settings['fb_appid']		= $this->input->post('facebook_app_id');
				$settings['fb_appsecret']			= $this->input->post('facebook_app_secret_id');
				$settings['googleplus_appid']			= $this->input->post('google_app_id');
				$settings['googleplus_appsecret']	= (bool)$this->input->post('google_app_secret_id');
				$file_contents				= $this->load->view('templates/carpooling', $settings, true);
				write_file($_SERVER['DOCUMENT_ROOT'].$subfolder.'carpooling/config/carpooling.php', $file_contents);

				//setup the CodeIgniter default config file
				$config_index				= array('index'=>'index.php');
				if($this->input->post('mod_rewrite'))
				{
					$config_index			= array('index'=>'');
				}
				$file_contents				= $this->load->view('templates/config', $config_index, true);
				write_file($_SERVER['DOCUMENT_ROOT'].$subfolder.'carpooling/config/config.php', $file_contents);
				
				//setup the .htaccess file
				if($this->input->post('mod_rewrite'))
				{
					$file_contents				= $this->load->view('templates/htaccess', array('subfolder'=>$subfolder), true);
					write_file($_SERVER['DOCUMENT_ROOT'].$subfolder.'.htaccess', $file_contents);
				}

				//redirect to the admin login
				if($this->input->post('mod_rewrite'))
				{
					$index	= '';
				}
				else
				{
					$index	= 'index.php/';
				}
				if($this->input->post('ssl_support'))
				{
					header( 'Location: https://'.$_SERVER['HTTP_HOST'].$subfolder.$index.'admin/login' ) ;
				}
				else
				{
					header( 'Location: http://'.$_SERVER['HTTP_HOST'].$subfolder.$index.'admin/login' ) ;
				}
			}
			else
			{
				$data['errors']	= '<p>A connection to the database could not be established.</p>';
				$this->load->view('install', $data);
			}
		}
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/install.php */
