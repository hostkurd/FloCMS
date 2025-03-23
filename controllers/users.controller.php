<?php
class UsersController extends Controller{
    protected $limit_per_page =10;
    protected $user_statuses = array(1=>'Active', 2=>'Pending', 3=>'Suspended');
    protected $user_roles = array(0=> 'User', 1=>'Editor', 2=>'Admin', 3=>'Super Admin');
    protected $status_colors = array(1=>'success', 2=>'purple', 3=>'danger');
    protected $verify_types = array(0=>'Not Verified', 1=>'Email', 2=>'Manual');

    // UserGroups which has access to Admin Panel
    // 0: user | 1: Editor | 2: Admin | 3: Super Admin
    protected $admin_access_roles = array('1', '2', '3');

    public function __construct(array $data = array())
    {
        parent::__construct($data);
        $this->model = new UsersModel();
    }

    public function admin_login(){
        if(Session::get('isloggedin')){
            if(!Session::get('admin_access')){
                Session::setFlash('<strong>You are Logged in!</strong><br>Your account does not has permition to access this area, please <a href="'.SITE_URI.'/admin/users/logout">Logout</a> from Here.', 'warning');
            }
        }

        if ($_POST && $_POST['email'] && $_POST['password']){
            if(Session::get('isloggedin')){
                Session::setFlash('You are Logged in Before!<br>Your are logged in before please <a href="'.SITE_URI.'/admin/users/logout">Logout</a> before try to login again.');
                return;
            }
            $user = $this->model->getByEmail($_POST['email']);
            $hash = md5(Config::get('salt').$_POST['password']);
            if ($user && $hash==$user['password']){
                $status = $user['status'];
                switch ($status){
                    case 0:
                        Session::setFlash('Login Failed!<br>Your Account has not been Activated.');
                        break;
                    case 1:
                        //Session::setFlash('Login oK !');
                        Session::set('admin_access', false);
                        Session::set('role',$user['role']);
                        Session::set('username',$user['login']);
                        Session::set('isloggedin',true);
                        if(in_array($user['role'], $this->admin_access_roles)){
                            Session::set('admin_access', true);
                        }
                        // Set Cookies for two hours
                        Cookie::set('rememberMe',1,24);
                        Cookie::set('email',$user['email'],24);
                        Cookie::set('fullname',$user['email'],24);
                        Cookie::set('username',$user['login'],24);

                        Router::redirect(SITE_URI.DS.ACTIVE_LANG.DS.'admin/');
                        break;
                    case 2:
                        Session::setFlash('<strong>Login Failed!</strong><br>Your Account has been <strong>suspended</strong>, Please Contact Website Administrator for more information.', 'danger');
                        break;
                    case 3:
                        Session::setFlash('<strong>Login Failed!</strong><br>Your Account is pending Verification, please verify your email address.', 'info');
                        break;
                }
                
            }else{
                Session::setFlash('Login Failed, User email or password is incorrect.', 'danger');
            }
        }
    }
    
    public function admin_logout(){
        // Destroy Login session
        Session::destroy();

        // Delete Cookies
        Cookie::delete('rememberMe');
        Cookie::delete('fullname');
        Cookie::delete('username');
        Cookie::delete('email');

        Router::redirect(SITE_URI.DS.ACTIVE_LANG.'/admin/');
    }

    public function admin_profile(){
        $this->data['fullName'] = Cookie::get('fullname');;
        $this->data['userName'] = Cookie::get('username');;
        $this->data['userMail'] = Cookie::get('email');;
        $this->data['userRole'] = $_SESSION['role'];
        $this->data['lang'] = 'en';

        if ($_POST){
            if($this->model->updateUser($_POST,$_SESSION['email'])){
                Router::redirect(SITE_URI.'/admin/');
            }else{
                Session::setFlash('Error Adding question, Unknown error.');
            }
        }
    }

    public function admin_add(){
        if ($_POST){
            $token = bin2hex(random_bytes(30));

            if($this->model->save($_POST, $token)){
                // Send Verification Email
                $to = $_POST['email'];
                $subject = "Verify your account On ".Config::get('Site_Name');
                $message = '<html>
                            <head>
                            <title>Email Verification</title>
                            </head>
                            <body>
                            <div style="width: 100%; text-align: center;font-family: Arial, Helvetica, sans-serif;">
                            <img src="'.imgPath.'/logo.png">
                            <h2>Verify your email address</h2>
                            <p style="max-width: 450px; margin: 20px auto; color: rgb(106, 106, 109) !important; line-height: 22px;font-size: 16px;">
                                Hello '.$_POST['fullname'].', welcome to '.Config::get("Site_Name").' For your security, we will not create your account until you verify your email address. </p>
                            <a href="'.SITE_URI.'/users/verify?token='.$token.'" style="text-decoration: none; font-weight: bold; font-size: 18px; color: rgb(63 120 224) !important;display:block;margin: 40px 0;">Verify your email ›</a>
                            <p>If the link above not worked Copy this link and paste it in your browser: <br> '.SITE_URI.'/users/verify?token='.$token.'</P>
                            </div>
                            </body>
                            </html>';
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: ". Config::get('primary_email') . "\r\n";

                mail($to,$subject,$message,$headers);

                // Redirect Back to Users
                Router::redirect(SITE_URI.'/admin/users');
            }else{
                Session::setFlash('Error Adding question, Unknown error.');
            }
        }
    }

    public function admin_index(){
        if ($_POST){
            if (isset($_POST['keyword'])){
              echo json_encode($this->model->listUsers(Config::get('LIMIT_PER_PAGE'),1, $_POST['keyword']));
              exit;
            }
        }else{
            $this->data['users'] = $this->model->listUsers($this->limit_per_page,1);
            $this->data['pagination'] = $this->model->paginationData(1,Config::get('LIMIT_PER_PAGE'),$this->model->getTotal(),'users');
            $this->data['pageID'] = 1;
            $this->data['userStatus'] = $this->user_statuses;
            $this->data['statusColors'] = $this->status_colors;
            $this->data['verifyTypes'] = $this->verify_types;
            $this->data['userRole'] = $this->user_roles;
        }
    }

    public function admin_edit(){
        if (isset($this->params[0])){
            if ($_POST){
                if($this->model->save($_POST, null, $this->params[0])){
                    Router::redirect(SITE_URI.'/admin/users');
                }else{
                    Session::setFlash('Error Adding question, Unknown error.');
                }
            }else{
                $this->data = $this->model->getByID($this->params[0]);
            }
        }
    }

    public function admin_delete(){
        if (isset($this->params[0])){
            if ($this->model->delete($this->params[0])){
                //print_r($this->currentPath());
                Router::redirect(SITE_URI.DS.'admin/users/');
            }else{
                Session::setFlash('Invalid ID');
            }
        }
    }

    public function verify(){
        if (isset($_GET) && isset($_GET['token'])){
            $token = $_GET['token'];
            if($this->model->isUserTokenExist($token )){
                if($this->model->verifyUser($token)){
                    Session::setFlash('<strong>Verification Succeeded!</strong><br>Your Account has been <strong>Verifies</strong>, Please <a href = "'.SITE_URI.'/admin/'.'" >login from here</a>.', 'success');
                    $this->data['status'] = 'verified';
                }else{
                    Session::setFlash('<strong>Verification Failed!</strong><br>we can not verify your account, an unknown error Occured.', 'danger');
                    $this->data['status'] = 'error';
                }
            }else{
                Session::setFlash('<strong>Verification Failed!</strong><br>Invalid Verification Token, or the token code has been used before.', 'danger');
                $this->data['status'] = 'invalid';
            }
        }else{
            Session::setFlash('<strong>No Token Applied!</strong><br>No Token has been applied, please supply a valid token for verification purpose.', 'danger');
            $this->data['status'] = 'notoken';
        }
    }

    public function admin_verify(){
        if (isset($_POST)){
            if(isset($_POST['token'])){
                $token = $_POST['token'];
                // // Check if Token code is not empty or null
                // if($token =='' or $token = null){
                //     $data = array('status'=>'error','message'=>'The provided token code is Empty..');
                //     echo json_encode($data);
                //     exit;
                // }
                
                if($this->model->isUserTokenExist($token )){
                    if($this->model->verifyUser($token, 'manual')){
                        $data = array('status'=>'success','message'=>'Verification Succeeded. The Account has been Verifies.');
                        echo json_encode($data);
                        exit;
                    }else{
                        $data = array('status'=>'error','message'=>'Unknown error has occured.');
                        echo json_encode($data);
                        exit;
                    }
                }else{
                    $data = array('status'=>'error','message'=>'Invalid Token! Invalid Verification Token has been applied.');
                    echo json_encode($data);
                    exit;
                }
            }
        }else{
            $data = array('status'=>'error','message'=>'This Page is Restricted !');
            echo json_encode($data);
            exit;
        }
    }

    public function admin_suspend(){
        if (isset($_POST) && isset($_POST['userid'])){
                $id = $_POST['userid'];
                if($this->model->isUserExist($id)){
                    if(!isset($_POST['action'])){
                        $data = array('status'=>'error','message'=>'No Action has been Specified.');
                        echo json_encode($data);
                        exit;
                    }
                    // Catching User Action
                    $action = $_POST['action'];
                    // Switching bwtween different Actions
                    switch ($action){
                        case "suspend":
                            if($this->model->suspendUser($id)){
                                $data = array('status'=>'success','message'=>'User Has been Suspended.');
                                echo json_encode($data);
                                exit;
                            }else{
                                $data = array('status'=>'error','message'=>'Unknown error has occured.');
                                echo json_encode($data);
                                exit;
                            }
                        break;
                        case "unsuspend":
                            if($this->model->unsuspendUser($id)){
                                $data = array('status'=>'success','message'=>'The user Has been Unblocked Successfully.');
                                echo json_encode($data);
                                exit;
                            }else{
                                $data = array('status'=>'error','message'=>'Unknown error has occured.');
                                echo json_encode($data);
                                exit;
                            }
                        break;
                    }


                }else{
                    $data = array('status'=>'error','message'=>'No Such User in our system.');
                    echo json_encode($data);
                    exit;
                }
        }else{
            $data = array('status'=>'error','message'=>'This Page is Restricted !');
            echo json_encode($data);
            exit;
        }
    }
}