<?php
namespace app\controller;

// use app\controller\BaseApi;

use app\BaseController;
use think\facade\Db as FacadeDb;

class Database extends BaseController {
    public function index() {
        $user = FacadeDb::name('users')->select();
        return json($user);
    }

    public function newsIndex() { // 首页新闻加载
        $userSchool = $this->request->param("userSchool");
        $news = FacadeDb::name('news')
                        ->whereOr([
                            ["schoolsFor", "=", "0"],
                            ["schoolsFor", "=", $userSchool],
                        ])
                        ->field('newsId, title, brief, time')
                        ->order('newsId', 'desc')
                        ->select();

        // $this->success($news);
        return json($news);
    }

    public function newsDetail() { // 新闻详情
        $id = $this->request->param("id");

        $content = FacadeDb::name('news')->withoutField('brief')->where('newsId',$id)->find();

        // $this->success($content);
        return json($content);
    }

    public function login() { // 登录 userId, pwd
        $params = $this->request->param();

        $exist = FacadeDb::name('users')->where('userId', $params['userId'])->find();

        if ($exist) { // 账号存在
            $correctPwd = FacadeDb::name('users')
                                ->where('userId', $params['userId'])
                                ->value('pwd');

            if ($params['pwd'] === $correctPwd) {
                $userInfo = FacadeDb::name('users')
                                ->field('userId, username, identity, schoolId')
                                ->where('userId', $params['userId'])
                                ->select()
                                ->toArray();

                return json([
                    'msg'       =>  'success',
                    'data'      => [
                        'userId'    =>   $userInfo[0]['userId'],
                        'username'  =>   $userInfo[0]['username'],
                        'identity'  =>  $userInfo[0]['identity'],
                        'userSchool'=>  $userInfo[0]['schoolId']
                    ],
                ]);
            } else {
                return json([
                    'msg'       =>  '密码错误',
                    'data'      => []
                ]);
            }

        } else {
            return json([
                'msg'           =>  '账号不存在',
                'data'          =>  []
            ]);
        }

    }

    public function progress() { // 进度查询
        $projectId = $this->request->param("projectId");

        $progress = FacadeDb::name('projects')
                            ->field('status, uploadTime, schoolPassTime, uniPassTime, judgePassTime, finalTime, isSchoolPass, isUniPass, isJudgePass')
                            ->where('projectId', $projectId)
                            ->find();

        return json([
            'msg'   =>  'success',
            'data'  =>  $progress
        ]);
    }

    public function project() { // 教师项目列表查询
        $userId = $this->request->param("userId");

        $projects = FacadeDb::name('projects')
                            ->field('projectId, title, brief, time')
                            ->order('projectId', 'desc')
                            ->where('userId', $userId)
                            ->select()
                            ->toArray();

        return json([
            'msg'   =>  'success',
            'data'  =>  $projects
        ]);
    }

    public function publish() { // 管理员发布项目

    }

    public function newsFromMe() { // 管理员已发布项目列表
        $userId = $this->request->param('userId');

        $outputList = FacadeDb::name('news')
                                ->field('title, brief, time, newsId')
                                ->where('userId', $userId)
                                ->order('newsId', 'desc')
                                ->select()
                                ->toArray();
        
        return json([
            'msg'   =>  'success',
            'data'  =>  $outputList
        ]);
    }

    public function judgeProjectsList() { // 评审项目列表
        $userId = $this->request->param('userId');

        $outputArr = array();

        $projectsArr = FacadeDb::name('judgeprojects')
                                ->field('projectId, judgeStatus, deadline')
                                ->where('userId', $userId)
                                ->select(); // [[key=>value]]

        if ( $projectsArr ) {
            foreach($projectsArr as $project) {
                $projectInfo = FacadeDb::name('projects')
                        ->field('title, brief')
                        ->where('projectId', $project['projectId'])
                        ->find(); // key-value array
    
                array_push($outputArr, [
                    'projectId'     =>  $project['projectId'],
                    'judgeStatus'   =>  $project['judgeStatus'],
                    'deadline'      =>  $project['deadline'],
                    'title'         =>  $projectInfo['title'],
                    'brief'         =>  $projectInfo['brief']
                ]);
            }
    
            return json([
                'msg'   => 'success',
                'data'  =>  $outputArr
            ]);
        } else {
            return json([
                'msg'   => 'success',
                'data'  =>  []
            ]);
        }
        
    }

    public function projectDetail() { // 项目详情
        $projectId = $this->request->param('projectId');

        $output = FacadeDb::name('projects')
                            ->field('title, content, time')
                            ->where('projectId', $projectId)
                            ->find();
        
        return json([
            'msg'   =>  'success',
            'data'  =>  $output
        ]);
    }

    public function reviewProjectsList() { // 审核项目列表查询
        $params = $this->request->param();

        if ($params['identity'] === 'schoolManager' || ($params['identity'] === 'test'  && $params['type'] === 'school')) {
            $outputArr = FacadeDb::name('projects')
                                    ->field('title, brief, time, projectId, isSchoolPass')
                                    ->order('projectId', 'desc')
                                    ->where('schoolReviewerId', $params['userId'])
                                    ->select()
                                    ->toArray();

            return json([
                'msg'   =>  'success',
                'data'  =>  $outputArr
            ]);

        } else if ($params['identity'] === 'uniManager' || ($params['identity'] === 'test' && $params['type'] === 'uni')) {
            $outputArr = FacadeDb::name('projects')
                                    ->field('title, brief, time, projectId, isUniPass')
                                    ->order('projectId', 'desc')
                                    ->where('uniReviewerId', $params['userId'])
                                    ->whereNotNull('isSchoolPass')
                                    ->select()
                                    ->toArray();

            return json([
                'msg'   =>  'success',
                'data'  =>  $outputArr
            ]);
        }

    }

    public function appendNews() { // 管理员发布项目
        $dataReq = $this->request->param();

        $count = FacadeDb::name('news')->count();

        $schoolsArray = $dataReq['schoolsFor'];

        $data = array();

        if (is_array($schoolsArray)) {
            $i = 1;
            foreach($schoolsArray as $school) {
                array_push($data, [
                    'newsId'    =>  $count+$i,
                    'userId'    =>  $dataReq['userId'],
                    'title'     =>  $dataReq['title'],
                    'brief'     =>  $dataReq['brief'],
                    'time'      =>  $dataReq['time'],
                    'content'   =>  $dataReq['content'],
                    'schoolsFor'=>  $school
                ]);

                $i+=1;
            }
        } else {
            array_push($data, [
                'newsId'    =>  $count+1,
                'userId'    =>  $dataReq['userId'],
                'title'     =>  $dataReq['title'],
                'brief'     =>  $dataReq['brief'],
                'time'      =>  $dataReq['time'],
                'content'   =>  $dataReq['content'],
                'schoolsFor'=>  $dataReq['schoolsFor']
            ]);
        }

        $res = FacadeDb::name('news')->insertAll($data);

        if ($res) {
            return json([
                'msg'   => 'success',
                'newId' =>  is_array($schoolsArray) ? $count+count($schoolsArray) : $count+1
            ]);
        } else {
            return json([
                'msg'   => 'insert fail'
            ]);
        }
    }

    public function decalreProject() { // 教师申报项目userId, title, brief, time, content (schoolId, uniReviewerId)
        /* 
            $schoolId = $dataReq('schoolId');

            $schoolRiviewerId = FacadeDb::name('users')->where('schoolId', $schoolId)->find()->value('userId'); // 查询二级学院管理员Id
            校级审核人暂且默认为1，或者独立注册一个校级审核人
        */
        $dataReq = $this->request->param();

        $count = FacadeDb::name('projects')->count();

        $schoolId = $dataReq['schoolId'];

        $schoolReviewerId = FacadeDb::name('users')
                                    ->where([
                                        ['schoolId', '=', $schoolId], 
                                        ['identity', '=', 'schoolManager']
                                    ])
                                    ->value('userId');


        $data = [
            'projectId' =>  $count+1,
            'userId'    =>  $dataReq['userId'],
            'title'     =>  $dataReq['title'],
            'brief'     =>  $dataReq['brief'],
            'time'      =>  $dataReq['time'],
            'content'   =>  $dataReq['content'],
            'targetId'  =>  1, // 暂时不启用该功能
            'status'    =>  1,  //  新申报项目进度状态为“已提交”
            'uploadTime'=>  $dataReq['time'],
            'schoolReviewerId'  =>  $schoolReviewerId,
            'uniReviewerId'     =>  1
        ];

        $res = FacadeDb::name('projects')->insert($data);

        if ($res) {
            return json([
                'msg'   => 'success',
                'newId' =>  $count+1
            ]);
        } else {
            return json([
                'msg'   => 'insert fail'
            ]);
        }
    }

    public function reviewProject() { // 管理员审核项目 userId, projectId, isPass, time, identity, type
        $dataReq = $this->request->param();
        
        if ($dataReq['identity'] === 'uniManager') { // 校级管理员
            if ($dataReq['isPass'] === 'true') {
                $data = array();
                $nowIndex = FacadeDb::name('judgeprojects')
                                    ->count(); // 统计数量生成index

                for ($i=$nowIndex+1; $i <= count($dataReq['judgeList']) + $nowIndex; $i++) { // 生成插入数据
                    array_push($data, [
                        'index'         =>  $i,
                        'userId'        =>  $dataReq['judgeList'][$i-$nowIndex-1],
                        'projectId'     =>  $dataReq['projectId'],
                        'deadline'      =>  $dataReq['deadline'],
                        'judgeStatus'   =>  'false'
                    ]);
                }

                $success1 = FacadeDb::name('judgeprojects')
                                    ->insertAll($data);

                $success = FacadeDb::name('projects')
                                    ->where('projectId', $dataReq['projectId'])
                                    ->update([
                                        'isUniPass'   =>    $dataReq['isPass'],
                                        'uniPassTime' =>    $dataReq['time'],
                                        'status'      =>    3
                                    ]);
                
                if ($success && $success1) {
                    return json([
                        'msg'   =>  'success'
                    ]);
                } else {
                    return json([
                        'msg'   =>  'fail'
                    ]);
                }
            } else {
                $success = FacadeDb::name('projects')
                                    ->where('projectId', $dataReq['projectId'])
                                    ->update([
                                        'isUniPass'   =>    $dataReq['isPass'],
                                        'uniPassTime' =>    $dataReq['time'],
                                        'status'      =>    3
                                    ]);

                if ($success) {
                    return json([
                        'msg'   =>  'success'
                    ]);
                } else {
                    return json([
                        'msg'   =>  'fail'
                    ]);
                }
            }
            
        } else if ($dataReq['identity'] === 'test') { // 测试
            if ($dataReq['type'] === 'school') { // 测试作为学院管理员
                $success = FacadeDb::name('projects')
                                ->where('projectId', $dataReq['projectId'])
                                ->update([
                                    'isSchoolPass'   =>     $dataReq['isPass'],
                                    'schoolPassTime' =>     $dataReq['time'],
                                    'status'         =>     2
                                ]);
                
                if ($success) {
                    return json([
                        'msg'   =>  'success'
                    ]);
                } else {
                    return json([
                        'msg'   =>  'fail'
                    ]);
                }
            } else { // 测试作为学校管理员
                if ($dataReq['isPass'] === 'true') {
                    $data = array();
                    $nowIndex = FacadeDb::name('judgeprojects')
                                        ->count(); // 统计数量生成index
    
                    for ($i=$nowIndex+1; $i <= count($dataReq['judgeList']) + $nowIndex; $i++) { // 生成插入数据
                        array_push($data, [
                            'index'         =>  $i,
                            'userId'        =>  $dataReq['judgeList'][$i-$nowIndex-1],
                            'projectId'     =>  $dataReq['projectId'],
                            'deadline'      =>  $dataReq['deadline'],
                            'judgeStatus'   =>  'false'
                        ]);
                    }
    
                    $success1 = FacadeDb::name('judgeprojects')
                                        ->insertAll($data);
    
                    $success = FacadeDb::name('projects')
                                        ->where('projectId', $dataReq['projectId'])
                                        ->update([
                                            'isUniPass'   =>    $dataReq['isPass'],
                                            'uniPassTime' =>    $dataReq['time'],
                                            'status'      =>    3
                                        ]);
                    
                    if ($success && $success1) {
                        return json([
                            'msg'   =>  'success'
                        ]);
                    } else {
                        return json([
                            'msg'   =>  'fail'
                        ]);
                    }
                } else {
                    $success = FacadeDb::name('projects')
                                        ->where('projectId', $dataReq['projectId'])
                                        ->update([
                                            'isUniPass'   =>    $dataReq['isPass'],
                                            'uniPassTime' =>    $dataReq['time'],
                                            'status'      =>    3
                                        ]);
    
                    if ($success) {
                        return json([
                            'msg'   =>  'success'
                        ]);
                    } else {
                        return json([
                            'msg'   =>  'fail'
                        ]);
                    }
                }
            }
        } else { // 学院管理员
            $success = FacadeDb::name('projects')
                                ->where('projectId', $dataReq['projectId'])
                                ->update([
                                    'isSchoolPass'      =>     $dataReq['isPass'],
                                    'schoolPassTime'    =>     $dataReq['time'],
                                    'status'            =>      2
                                ]);

            if ($success) {
                return json([
                    'msg'   =>  'success'
                ]);
            } else {
                return json([
                    'msg'   =>  'fail'
                ]);
            }
        }
    }

    public function judgeProject() { // 专家评审 userId, projectId, isPass
        $dataReq = $this->request->param();

        switch ($dataReq['isPass']) {
            case 'true':
                $success1 = FacadeDb::name("projects") // projects表中修改数据
                                    ->where('projectId', $dataReq['projectId'])
                                    ->update([
                                        'passVote'  =>  FacadeDb::raw('passVote + 1')
                                    ]);

                $success2 = FacadeDb::name("judgeprojects") // judgeprojects表中标记为已评审
                                    ->where([
                                        'projectId' =>  $dataReq['projectId'],
                                        'userId'    =>  $dataReq['userId']
                                    ])
                                    ->update([
                                        'judgeStatus'  =>  'true'
                                    ]);

                if ($success1 && $success2) {
                    return json([
                        'msg'   =>  'success'
                    ]);
                } else {
                    return json([
                        'msg'   =>  'fail'
                    ]);
                }
                break;
            
            case 'false':
                $success1 = FacadeDb::name("projects")
                                    ->where('projectId', $dataReq['projectId'])
                                    ->update([
                                        'notpassVote'  =>  FacadeDb::raw('notpassVote + 1')
                                    ]);

                $success2 = FacadeDb::name("judgeprojects")
                                    ->where([
                                        'projectId' =>  $dataReq['projectId'],
                                        'userId'    =>  $dataReq['userId']
                                    ])
                                    ->update([
                                        'judgeStatus'  =>  'true'
                                    ]);
                                    
                if ($success1 && $success2) {
                    return json([
                        'msg'   =>  'success'
                    ]);
                } else {
                    return json([
                        'msg'   =>  'fail'
                    ]);
                }
                break;
                
            default:
                return json([
                    'msg'   =>  '参数错误',
                    'code'  =>  401
                ]);
                break;
        }
    }

    public function updatePwd() { // 修改密码 userId, oldPwd, newPwd
        $dataReq = $this->request->param();

        $oldPwdCorrect = FacadeDb::name('users')
                                ->where('userId', $dataReq['userId'])
                                ->value('pwd') === $dataReq['oldPwd'];
        
        if ($oldPwdCorrect) {
            if ($dataReq['newPwd'] === $dataReq['oldPwd']) {
                return json([
                    'code'  =>  401,
                    'msg'   =>  '新密码不可与现在的密码相同'
                ]);
            }

            $success = FacadeDb::name('users')
                    ->where('userId', $dataReq['userId'])
                    ->update([
                        'pwd'   =>  $dataReq['newPwd']
                    ]);

            if ($success) {
                return json([
                    'code'  =>  200,
                    'msg'   =>  'success'
                ]);
            } else {
                return json([
                    'code'  =>  555,
                    'msg'   =>  '修改失败'
                ]);
            }
        } else {
            return json([
                'code'  =>  200,
                'msg'   =>  '旧密码输入错误'
            ]);
        }
    }

    public function getJudgesList() { // 评审专家列表 schoolId
        $dataReq = $this->request->param();

        $outputArr = FacadeDb::name('users')
                            ->field('userId, username')
                            ->where([
                                ['identity', '=', 'teacher'],
                                ['schoolId', '=', $dataReq['schoolId']]
                            ])
                            ->select()
                            ->toArray();
        if ($outputArr) {
            return json([
                'msg'   =>  'success',
                'data'  =>  $outputArr
            ]);
        } else {
            return json([
                'msg'   =>  '未找到符合条件的用户',
                'data'  =>  []
            ]);
        }
    }

    public function getProjectSchoolId() { // 获取项目所属学院 projectId
        $dataReq = $this->request->param();

        $userId = FacadeDb::name('projects')
                        ->where('projectId', $dataReq['projectId'])
                        ->value('userId');
        if ($userId) {
            $schoolId = FacadeDb::name('users')
                                ->field('schoolId')
                                ->where('userId', $userId)
                                ->select();
            return json([
                'msg'   =>  'success',
                'data'  =>  [
                    'schoolId'  =>  $schoolId[0]['schoolId'],
                    'teacherUserId'    =>  $userId
                ]
            ]);
        } else {
            return json([
                'msg'   =>  '参数错误',
                'code'  =>  401,
                'data'  =>  []
            ]);
        }
    }

    public function test() {

        $dataReq = $this->request->param();

        $schoolId = $dataReq['schoolId'];

        $schoolRiviewerId = FacadeDb::name('users')
                                    ->where([
                                        ['schoolId', '=', $schoolId], 
                                        ['identity', '=', 'schoolManager']
                                    ])
                                    ->value('userId'); // 查询二级学院管理员Id

        return json($schoolRiviewerId);
    }
}

?>