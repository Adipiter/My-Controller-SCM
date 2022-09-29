<?php 
if (!defined("BASEPATH")) exit ("No direct script access allowed");

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library("ion_auth");
        $this->load->driver("cache", [ "adapter" => "file" ]);
        if( !$this->cache->get("settings") ) 
        {
            $this->cache->save("settings", $this->settings_model->getById("settings", 1), 1800);
        }

        $this->updateUserBalance();
    }

    public function site_view($view, $data = NULL)
    {
        $this->load->view("themes/" . settings("theme") . "/includes/header", $data);
        $this->load->view("themes/" . settings("theme") . "/" . $view, $data);
        $this->load->view("themes/" . settings("theme") . "/includes/footer", $data);
    }

    public function updateUserBalance()
    {
        if( $this->ion_auth->logged_in() ) 
        {
            $this->load->model("users_model");
            $this->users_model->updateUserBalance();
        }

    }

    public function edit_unique($value, $params)
    {
        $this->form_validation->set_message("edit_unique", "Sorry, that %s is already being used.");
        list($table, $field, $current_id) = explode(".", $params);
        $query = $this->db->select()->from($table)->where($field, $value)->limit(1)->get();
        return !($query->row() && $query->row()->id != $current_id);
    }

}


class Admin_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->creditCheck();
        $this->load->database();
        $this->load->library([ "ion_auth", "pagination" ]);
        if( $this->ion_auth->logged_in() && $this->ion_auth->is_admin() ) 
        {
            $this->auth_user = $this->ion_auth->user()->row();
        }

    }

    public function admin_view($view, $data = NULL)
    {
        $this->load->view("admin/includes/header", $data);
        $this->load->view("admin/" . $view, $data);
        $this->load->view("admin/includes/footer", $data);
    }

    public function admin_loggedin_redirect()
    {
        if( $this->ion_auth->logged_in() && $this->ion_auth->is_admin() ) 
        {
            return redirect(adminRoute());
        }

        if( $this->ion_auth->logged_in() && !$this->ion_auth->is_admin() ) 
        {
            return redirect("");
        }

    }

    public function is_admin_loggedin()
    {
        if( !$this->ion_auth->logged_in() || !$this->ion_auth->is_admin() ) 
        {
            return redirect(adminRoute("login"));
        }

    }

    public function admin_paginate($model, $base_url, $altMethod = NULL, $conditions = NULL)
    {
        $this->load->model($model, "current_model");
        if( isset($conditions) ) 
        {
            $total = $this->current_model->rowsCountWhere($this->current_model->getTableName(), $conditions[0], $conditions[1]);
        }
        else
        {
            $total = $this->current_model->rowsCount($this->current_model->getTableName());
        }

        $limit = settings("pagination");
        $config["base_url"] = $base_url;
        $config["total_rows"] = $total;
        $config["per_page"] = $limit;
        $config["num_links"] = 2;
        $config["use_page_numbers"] = true;
        $config["first_link"] = $this->lang->line("pagination_first_link");
        $config["first_url"] = $config["base_url"];
        $config["last_link"] = $this->lang->line("pagination_last_link");
        $config["full_tag_open"] = "<ul class=\"pagination\">";
        $config["num_tag_open"] = "<li class=\"page-item\">";
        $config["first_tag_open"] = "<li class=\"page-item\">";
        $config["last_tag_open"] = "<li class=\"page-item\">";
        $config["prev_tag_open"] = "<li class=\"page-item\">";
        $config["next_tag_open"] = "<li class=\"page-item\">";
        $config["cur_tag_open"] = "<li class=\"page-item active\"><a class=\"page-link\" href=\"#\">";
        $config["num_tag_close"] = "</li>";
        $config["first_tag_close"] = "</li>";
        $config["last_tag_close"] = "</li>";
        $config["prev_tag_close"] = "</li>";
        $config["next_tag_close"] = "</li>";
        $config["cur_tag_close"] = "</a></li>";
        $config["full_tag_close"] = "</ul>";
        $config["attributes"] = [ "class" => "page-link" ];
        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3) ? $this->uri->segment(3) - 1 : 0);
        if( isset($altMethod) ) 
        {
            if( method_exists($this->current_model, $altMethod) ) 
            {
                $results = $this->current_model->$altMethod($page * $limit, $limit);
            }

        }
        else
        {
            $results = $this->current_model->getAll($page * $limit, $limit);
        }

        $pagination = $this->pagination->create_links();
        return [ "items" => $results, "links" => $pagination, "total" => $total ];
    }

    protected function creditCheck()
    {
        $footer = VIEWPATH . "/admin/includes/footer.php";
        $home = VIEWPATH . "/admin/home.php";
        if( !file_exists($footer) ) 
        {
            show_error("Missing admin footer view!", 404);
        }

        if( strpos(file_get_contents($footer), "SC_NAME.' v.'.SC_VERSION") === false || strpos(file_get_contents($home), "https://smartyscripts.com") === false ) 
        {
            show_error("You are trying to use a illegal version of this script. Please, buy a official license on <a href=\"https://www.smartyscripts.com\">www.smartyscripts.com</a>", 401);
        }

    }

    public function edit_unique($value, $params)
    {
        $this->form_validation->set_message("edit_unique", "Sorry, that %s is already being used.");
        list($table, $field, $current_id) = explode(".", $params);
        $query = $this->db->select()->from($table)->where($field, $value)->limit(1)->get();
        return !($query->row() && $query->row()->id != $current_id);
    }

}


