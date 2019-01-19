<?php

namespace app\home\controller;

class Index extends Base
{
    public function index()
    {        
		//SQL语句里的__NEWS__会被替换为带前缀（yy_）的表名：yy_news
        $news = $this->db->findAll('SELECT id,title FROM __NEWS__ ORDER BY id DESC LIMIT 6');
        $this->assign('news', $news);
    }
}
