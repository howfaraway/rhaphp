<?php
// +----------------------------------------------------------------------
// | [RhaPHP System] Copyright (c) 2017 http://www.rhaphp.com/
// +----------------------------------------------------------------------
// | [RhaPHP] 并不是自由软件,你可免费使用,未经许可不能去掉RhaPHP相关版权
// +----------------------------------------------------------------------
// | 官方网站：RhaPHP.com 任何企业和个人不允许对程序代码以任何形式任何目的再发布
// +----------------------------------------------------------------------
// | Author: Geeson <qimengkeji@vip.qq.com>
// +----------------------------------------------------------------------


namespace app\mp\controller;


use app\common\model\MpFriends;
use app\common\model\MpReply;
use app\common\model\MpRule;
use app\common\model\Setting;
use think\Db;
use think\Request;
use think\Validate;

class member extends Base
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function index($do = 'group', $to = '')
    {
        if (Request::instance()->isAjax()) {

        } else {
            $model = new MpFriends();
            switch ($do) {
                case 'friend':
                    $memberList = $model->memberList(['mpid' => $this->mid], '', 'id DESC', 20);

                    $this->assign('data', $memberList);
                    break;
                case 'group':
                    $list = Db::name('member_group')->where(['mpid'=>$this->mid])->order('up_score ASC,up_money ASC,discount ASC')->select();
                    if ($to == 'updateGroup' && input('id')) {
                        $group = Db::name('member_group')->where([
                            'mpid' => $this->mid,
                            'gid' => input('id')
                        ])->find();
                        if (empty($group)) {
                            $this->error('找不到相应会员等级组');
                        }
                        $this->assign('group', $group);
                    } else {

                    }
                    $this->assign('to', $to);
                    $this->assign('data', $list);
                    break;
                case 'page':
                    $this->assign('memberUrl',getHostDomain().url('member/home/index',['mid'=>$this->mid]));
                    break;
                case 'register':
                    $settingModel=new Setting();
                    $setting=$settingModel->getSetting(['mpid'=>$this->mid,'name'=>'register']);
                        $data=[
                            'register_type'=>isset($setting['register_type'])?$setting['register_type']:'',
                            'verify'=>isset($setting['verify'])?$setting['verify']:'',
                            'up_score'=>isset($setting['up_score'])?$setting['up_score']:'',
                            'up_money'=>isset($setting['up_money'])?$setting['up_money']:'',
                            'keyword'=>isset($setting['keyword'])?$setting['keyword']:'',
                            'picurl'=>isset($setting['picurl'])?$setting['picurl']:'',
                            'ispwd'=>isset($setting['ispwd'])?$setting['ispwd']:'',
                            'redirect_url'=>isset($setting['redirect_url'])?$setting['redirect_url']:''
                        ];
                        $setting=$setting?$setting:[];
                        $data=array_merge($data,$setting);

                    $this->assign('st',$data);
                    break;
            }
            $this->assign('do', $do);
            return view();
        }
    }

    public function addGroup()
    {
        if (Request::instance()->isAjax()) {
            $validate = new Validate(
                [
                    'group_name' => 'require',
                ],
                [
                    'group_name.require' => '等级名称不能为空',
                ]
            );
            $result = $validate->check(input('post.'));
            if ($result === false) {
                ajaxMsg(0, $validate->getError());
            }
            $data = input('post.');
            $data['mpid'] = $this->mid;
            if (Db::name('member_group')->insert($data)) {
                ajaxMsg(1, '新增成功');
            } else {
                ajaxMsg(0, '新增失败');
            }
        }
    }

    public function delGroup($gid)
    {
        if (Request::instance()->isAjax()) {
            if (Db::name('member_group')->where(['mpid' => $this->mid, 'gid' => $gid])->delete()) {
                ajaxMsg(1, '删除成功');
            } else {
                ajaxMsg(0, '删除失败');
            }
        }
    }

    public function updateGroup()
    {
        if (Request::instance()->isAjax()) {
            Db::name('member_group')->where(['mpid' => $this->mid, 'gid' => input('gid')])->update(input('post.'));
            ajaxMsg(1, '更新成功');
        }
    }

    public function registerConfig()
    {
        if (Request::instance()->isAjax()) {
            $model = new Setting();
            $input=input();
            if(!is_numeric($input['up_score']) || !is_numeric($input['up_money'])){
                ajaxMsg(0, '积分|金额不是数字类型');
            }
            $data=[];
            if(input('register_type')==3){
                $ruleModel = new MpRule();
                $replyMode = new MpReply();
                $replyMode->where(['type'=>'member'])->delete();
                $ruleModel->where(['type'=>'member'])->delete();
                $data['url'] = $input['picurl'];
                $data['keyword']=$input['keyword'];
                $data['link']=getHostDomain().url('mp/Login/loginByReply',['mid'=>$this->mid]);
                $data['type'] = 'member';
                $data['mpid'] = $this->mid;
                if ($res_1 = $replyMode->allowField(true)->save($data)) {
                    $data['reply_id'] = $replyMode->reply_id;
                    if (!$res_2 = $ruleModel->allowField(true)->save($data)) {
                        $replyMode::destroy(['reply_id' => $data['reply_id']]);
                    }
                }
            }
            $result = $model->addSetting(['mpid' => $this->mid, 'name' => 'register'], array_merge($input,$data));
            if ($result) {
                ajaxMsg(1, '保存成功');
            } else {
                ajaxMsg(0, '保存失败');
            }
        }
    }

}