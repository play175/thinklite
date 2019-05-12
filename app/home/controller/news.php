<?php

namespace app\home\controller;

class News extends Base
{
    public function index()
    {
        //findAndPage会读取参数page，并分页输出
        $result = $this->db->findAndPage('SELECT id,title,summary FROM __NEWS__ ORDER BY id DESC');
        $this->assign('result', $result);
    }

    public function detail()
    {
        $id = I('id/int');
        $detail = $this->db->find('SELECT * FROM __NEWS__ WHERE id = ? ', [$id]);
        if (!$detail) {
            $this->err('数据不存在');
        }
        $this->assign('detail', $detail);
    }
}
