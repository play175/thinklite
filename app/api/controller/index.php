<?php

namespace app\api\controller;

class Index extends \Api
{
    public function index()
    {        
        $news = $this->db->findAll('SELECT id,title FROM __NEWS__ ORDER BY id DESC LIMIT 6');
        $this->assign('news', $news);
    }
}
