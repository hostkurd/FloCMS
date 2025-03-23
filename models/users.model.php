<?php
class UsersModel extends Model{

    public function save($data, $token, $id=null){
        $fullname = $this->db->escape($data['fullname']);
        $email = $this->db->escape($data['email']);
        $phone = str_replace(' ', '', $this->db->escape($data['phone']));
        $gender = $this->db->escape($data['gender']);
        $address = $this->db->escape($data['address']);
        $login = $this->db->escape($data['login']);
        $password = $this->db->escape($data['password']);
        $role = $this->db->escape($data['role']);
        $imagePath = $this->db->escape($data['imagePath']);

        $hash = md5(Config::get('salt').$password);

        if (!$id){
        $sql = "insert into users
                      set   full_name = '{$fullname}',
                            login='{$login}',
                            password='{$hash}',
                            email = '{$email}',
                            image = '{$imagePath}',
                            address = '{$address}',
                            phone = '{$phone}',
                            gender = '{$gender}',
                            role='{$role}',
                            status='2',
                            is_verified = '0',
                            token = '{$token}'";
            }else{
        $sql = "update users
                  set   full_name = '{$fullname}',
                        login='{$login}',
                        ". (!empty($password)?"password = '{$hash}',":"")."
                        email = '{$email}',
                        ". ($imagePath?"image = '{$imagePath}',":"")."
                        address = '{$address}',
                        phone = '{$phone}',
                        gender = '{$gender}',
                        role='{$role}' Where id = {$id}";
        }

        return $this->db->query($sql);

    }

    public function getByLogin($login){
        $login = $this->db->escape($login);
        $sql = "select * from users where login='{$login}' limit 1";
        $result = $this->db->query($sql);
        if (isset($result[0])){
            return $result[0];
        }
        return false;
    }

    public function getByEmail($email){
        $email = $this->db->escape($email);
        $sql = "select * from users where email='{$email}' limit 1";
        $result = $this->db->query($sql);
        if (isset($result[0])){
            return $result[0];
        }
        return false;
    }

    public function updateUser($data, $email){
    $password = $this->db->escape($data['password']);
    $login = $this->db->escape($data['login']);
    $hash = md5(Config::get('salt').$password);
        $sql = "update users
                      set login='{$login}',
                          password='{$hash}'
                      where email = '{$email}'";
        return $this->db->query($sql);

    }

    public function listUsers($limit, $pageid =1, $keyword = null){
        $pageid = (int)$pageid;

        // Fetching Where Parameters
        $parameters = [];
        $paramText = "";
        if($keyword != null){array_push($parameters,"full_name like '%{$keyword}%' or email like '%{$keyword}%' or phone like '%{$keyword}%' ");}
        if (count($parameters) > 0){
            $params = implode(' and ', $parameters);
            $paramText = "where ". $params;
        }

        $offset = ($pageid-1) * $limit;
        $sql = "select * from users {$paramText} ORDER By id DESC limit {$limit} Offset {$offset} ";
        $result = $this->db->query($sql);
        if ($result){
            return $this->db->query($sql);
        }else{
            return false;
        }

    }
    public function getByID($id){
        $id = (int)$id;
        $sql = "Select * from users where id='{$id}' limit 1";
        $result =  $this->db->query($sql);
        return isset($result[0])?$result[0]:false;
    }

    public function delete($id){
        $id = (int)$id;
        $sql = "Delete from users where id={$id}";
        return $this->db->query($sql);
    }

    public  function getTotal(){
        $sql = "select count(*) 'total_rows' from users";
        return $this->db->query($sql)[0]['total_rows'];
    }

    public function isUserTokenExist($token){
        $sql = "Select * from users where token = '{$token}'";
        $result = $this->db->query($sql);
        if (isset($result[0])){
            return true;
        }
        return false;
    }

    public function isUserExist($id){
        $sql = "Select * from users where id = '{$id}'";
        $result = $this->db->query($sql);
        if (isset($result[0])){
            return true;
        }
        return false;
    }

    public function verifyUser($token, $verifyType = 'email'){
        $verify_type = 0;
        switch($verifyType){
            case 'email':
                $verify_type = 1;
                break;
            case 'manual':
                $verify_type = 2;
                break;
            default:
                // Not Verified
                $verify_type = 0;
                break;
        }

        $sql = "Update users set is_verified = '1', verify_type = '{$verify_type}', status = '1', token ='' Where token = '{$token}'";
        return $this->db->query($sql);
    }


    public function suspendUser($id){
        $sql = "Update users set status = '3' Where id = '{$id}'";
        return $this->db->query($sql);
    }

    public function unsuspendUser($id){
        $sql = "Update users set status = '1' Where id = '{$id}'";
        return $this->db->query($sql);
    }

}