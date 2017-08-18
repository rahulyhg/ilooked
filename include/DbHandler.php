<?php

require '../vendor/autoload.php';
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
    public function incrementView($film_id) {

            $stmt=$this->conn->prepare("SELECT f.views from films f where f.outer_id='$film_id'");
            $stmt->execute();
            $stmt->bind_result($views);
            $stmt->fetch();
            $stmt->close();
            $views=$views+1;

        $stmt = $this->conn->prepare(" UPDATE films f SET  f.views='$views'
                  where f.outer_id='$film_id'");
            $stmt->execute();
            $stmt->close();
            return $views;

    }

    public function createSubscription($subscriber_id, $publisher_id) {

        $stmt = $this->conn->prepare("SELECT COUNT(s.user_publisher) 
          from subscribes s WHERE s.user_publisher='$publisher_id' AND s.user_subscriber='$subscriber_id'");

        $result = $stmt->execute();
        $stmt->bind_result($number);
        $stmt->fetch();
        $stmt->close();
        if($number>0){return false;}

        $stmt = $this->conn->prepare("INSERT INTO subscribes(user_subscriber, user_publisher) VALUES(?, ?)");

        $stmt->bind_param("ss", $subscriber_id, $publisher_id);

        $result = $stmt->execute();

        $stmt->close();


        if ($result) {

            return $result;
        } else {
            // task failed to create
            return NULL;
        }
    }
//    public function createReview($film_id, $user_id, $review_text) {
//        $stmt = $this->conn->prepare("SELECT COUNT(r.film_id) from review r  WHERE
//            r.film_id='$film_id' AND r.user_id='$user_id' AND r.review_type=1");
//
//        $result = $stmt->execute();
//        $stmt->bind_result($number);
//        $stmt->fetch();
//        $stmt->close();
//        if ($number>0) {
//            return false;
//        }
//        $stmt = $this->conn->prepare("INSERT INTO review(user_id, film_id, review_text, review_type) VALUES(?, ?, ?, ?)");
//        $review_type=1;
//        $stmt->bind_param("iisi", $user_id, $film_id, $review_text, $review_type);
//
//        $result = $stmt->execute();
//
//
//        $stmt->close();
//
//        if ($result) {
//
//
//            return $result;
//        } else {
//            // task failed to create
//            return NULL;
//        }
//    }
    public function createReview($film_id, $user_id, $review_text, $review_type) {
        if($review_text==null){
            $review_text="empty";
        }

        $stmt = $this->conn->prepare("SELECT COUNT(r.film_id) from review r  WHERE 
            r.film_id='$film_id' AND r.user_id='$user_id' AND r.review_type='$review_type'");

        $result = $stmt->execute();
        $stmt->bind_result($number);
        $stmt->fetch();
        $stmt->close();
        if ($number>0) {
            return false;
        }
        $stmt = $this->conn->prepare("INSERT INTO review(user_id, film_id, review_text, review_type) VALUES(?, ?, ?, ?)");

        $stmt->bind_param("iisi", $user_id, $film_id, $review_text, $review_type);

        $result = $stmt->execute();


        $stmt->close();

        if ($result) {


            return $result;
        } else {
            // task failed to create
            return NULL;
        }
    }
    public function createFavorite($film_id, $user_id) {

        $stmt = $this->conn->prepare("SELECT COUNT(r.film_id) from review r  WHERE 
            r.film_id='$film_id' AND r.user_id='$user_id' AND r.review_type=2");

        $result = $stmt->execute();
        $stmt->bind_result($number);
        $stmt->fetch();
        $stmt->close();
        if ($number>0) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO review(user_id, film_id,  review_type) VALUES(?, ?, ?)");
        $review_type=2;
        $stmt->bind_param("iii", $user_id, $film_id, $review_type);

        $result = $stmt->execute();


        $stmt->close();

        if ($result) {

            return $result;
        } else {
            // task failed to create
            return NULL;
        }
    }
    public function saveNewFilm($data)
    {

            $name_ru=$data[0];
            $name_en=$data[1];
            $description=$data[2];
            $janr=$data[3];
            $country=$data[4];
            $film_year=$data[5];
            $outer_id=$data[6];
            $duration=$data[7];
            $image_address=$data[8];
            $producer=$data[9];

        $stmt = $this->conn->prepare("SELECT f.outer_id FROM films f where f.outer_id='$outer_id'");

        if($stmt->execute()) {
            $stmt->bind_result($number);
            $stmt->fetch();

            $stmt->close();
            if ($number > 0) {
                return false;
            }

            $stmt = $this->conn->prepare("INSERT INTO films(name_ru, name_en, description, janr, 
                      country, film_year, outer_id, duration, 
                       main_image, producer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiiiss", $name_ru, $name_en, $description, $janr, $country, $film_year,
                $outer_id, $duration, $image_address, $producer);
            $result = $stmt->execute();

            $stmt->close();
            if ($result)

                return

                    FILM_CREATED_SUCCESSFULLY;

            else {

                return false;
            }
        }else return null;


    }
    public function updateProfile($data)
    {
        $name=$data[0];
        $surname=$data[1];
        $nickname=$data[2];
        $short_info=$data[3];
        $user_id=$data[4];
        $avatar_image=$data[5];
        $stmt = $this->conn->prepare("SELECT name, surname, nickname, short_info, avatar_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $stmt->bind_result($oldname, $oldsurname, $oldnick, $oldinfo, $oldava);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            if($name=="empty"){$name=$oldname;}
            if($surname=="empty"){$surname=$oldsurname;}
            if($nickname=="empty"){$nickname=$oldnick;}
            if($short_info=="empty"){$short_info=$oldinfo;}
            if($avatar_image=="empty"){$avatar_image=$oldava;}


        } $stmt->close();
            $users[0]="false";
            if($name=="empty"){$name=$oldname;$users[0]=$oldname;}
        if($surname=="empty"){$surname=$oldsurname;}
        if($nickname=="empty"){$nickname=$oldnickname;}
        if($short_info=="empty"){$short_info=$oldinfo;}
        if($avatar_image=="empty"){$avatar_image=$oldimage;}
        $short_info=substr($short_info, 1, strlen($short_info)-2);
        $stmt = $this->conn->prepare(" UPDATE users u SET u.name='$name', u.surname='$surname',
             u.nickname='$nickname', u.short_info='$short_info', u.first_login=0,
                 u.avatar_image='$avatar_image' where u.id='$user_id'");

        $result = $stmt->execute();


        $stmt->close();

        if ($result)

            return

                FILM_CREATED_SUCCESSFULLY;

        else {

            return false;
        }

    }
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at, first_login, id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at, $firstlogin, $user_id);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $user["first_login"]=$firstlogin;
            $user["user_id"]=$user_id;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
    public function getProfile($user_id) {
        $stmt = $this->conn->prepare("SELECT u.name, u.surname, u.nickname, u.email, u.short_info, u.avatar_image, u.status  FROM users u WHERE id = ?");

        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $surname, $nickname, $email, $short_info, $avatar, $status);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["surname"] = $surname;
            $user["nickname"] = $nickname;
            $user["email"] = $email;
            $user["status"] = $status;
            $user["short_info"]=$short_info;
            $user["avatar_image"]=$avatar;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
    public function getSubscriptionCount($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(s.user_subscriber) FROM subscribes s WHERE s.user_subscriber = ?");
        $stmt->bind_param("i", $user_id);
        $user = array();
        if ($stmt->execute()) {
            $stmt->bind_result($user_subscriber);
            $stmt->fetch();
            $user["subscribed"] = $user_subscriber;
            $stmt->close();

            $stmt = $this->conn->prepare("SELECT COUNT(s.user_publisher) FROM subscribes s WHERE s.user_publisher = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $stmt->bind_result($user_publisher);
                $stmt->fetch();
            $user["published"] = $user_publisher;
                $stmt->close();


            $stmt = $this->conn->prepare("SELECT COUNT(r.film_id) FROM review r WHERE r.user_id=?");
            $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $stmt->bind_result($film_id);
                    $stmt->fetch();
                    $user["filmscount"] = $film_id;
                    $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
        else{
            return null;
        }
    }
    else{
        return null;
        }
    }
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
    public function getUserBySearch($search_query) {
        $stmt = $this->conn->prepare("SELECT u.name, u.surname, u.nickname,
            u.email, u.short_info, u.avatar_image, u.id from users u WHERE u.email
          LIKE '$search_query%' OR u.name LIKE '$search_query%' OR u.surname
           LIKE '$search_query%' COLLATE 'utf8_general_ci'");
             if ($stmt->execute()) {

            $task = $stmt->get_result();
            $stmt->fetch();

            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
    public function getUserById($search_query) {
        $stmt = $this->conn->prepare("SELECT u.name, u.surname, u.nickname,
            u.email, u.short_info, u.status, u.avatar_image, u.id from users u WHERE u.id='$search_query'");
        if ($stmt->execute()) {

            $task = $stmt->get_result();
            $stmt->fetch();

            $stmt->close();

            if($task==null){return false;}
            return $task;
        } else {
            return NULL;
        }
    }
    public function getFilmBySearch($search_query) {
        $stmt = $this->conn->prepare("SELECT * from films f WHERE f.name_ru
          LIKE '$search_query%' OR f.name_en LIKE '$search_query%' ORDER BY f.created_at COLLATE 'utf8_general_ci'");
        if ($stmt->execute()) {

            $task = $stmt->get_result();
            $stmt->fetch();

            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
    public function getFilmByUser($user_id) {

        $stmt = $this->conn->prepare("SELECT r.film_id, r.created_at,
          r.review_type FROM review r WHERE r.user_id=?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $films=$stmt->get_result();

            $stmt->close();
            return $films;
        } else {
            return NULL;
        }


    }
    public function getFilms($films_id_list) {
        $stmt = $this->conn->prepare("SELECT * FROM films WHERE films.outer_id IN (".implode(',',$films_id_list).")");


        if ($stmt->execute()) {
            $films=$stmt->get_result();

            $stmt->close();
            return $films;
        } else {
            return NULL;
        }
    }
    public function getUsers($users_id_list) {
        $stmt = $this->conn->prepare("SELECT users.nickname, users.name, users.surname, users.short_info, users.email, users.id, users.avatar_image FROM users WHERE users.id IN (".implode(',',$users_id_list).")");


        if ($stmt->execute()) {
            $films=$stmt->get_result();

            $stmt->close();
            return $films;
        } else {
            return NULL;
        }
    }
    public function getReviews($user_id) {
        $stmt = $this->conn->prepare("SELECT r.film_id, r.created_at,
          r.review_type FROM review r WHERE r.user_id=?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $films=$stmt->get_result();

            $stmt->close();
            return $films;
        } else {
            return NULL;
        }
    }
    public function getLenta($user_id, $last_post_date) {
        $stmt = $this->conn->prepare("SELECT t.user_publisher from subscribes t WHERE t.user_subscriber = ?");
        $stmt->bind_param("i",$user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($user_publisher);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["user_publisher"] = $user_publisher;
            $stmt->close();


            $stmt2 = $this->conn->prepare("SELECT from review r WHERE r.user_id=? AND created_at>?");
            $stmt2->bind_param("ii", $res["user_publisher"], $last_post_date);
            if($stmt2->execute()){

                $stmt2->fetch();
                $res2=$stmt2->get_result();
                $stmt2->close();
                return $res2;
            }
            return $res;
        } else {
            return NULL;
        }
    }

    public function getPublishersId($user_id) {
    $stmt = $this->conn->prepare("SELECT user_publisher FROM subscribes WHERE user_subscriber = ?");
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        $publishers=$stmt->get_result();

        $stmt->close();

        return $publishers;
    } else {
        $stmt->close();
        return NULL;
    }
}
    public function getLentaReviews($publishers_array, $last_film_date) {

        if($publishers_array!=null){
//        $stmt = $this->conn->prepare("SELECT r.film_id, r.user_id, r.review_text, r.created_at, r.review_type FROM review r WHERE r.created_at>? AND r.review_type=? AND  r.user_id IN (".implode(',',$publishers_array).")");
        $stmt = $this->conn->prepare("SELECT r.film_id, r.user_id, r.review_text, r.created_at, r.review_type, f.views FROM review r, films f WHERE r.created_at>? AND r.review_type=? AND r.film_id=f.outer_id AND  r.user_id IN (".implode(',',$publishers_array).")");
        $review_type=1;

        $review_type=1;
        $stmt->bind_param("si", $last_film_date, $review_type);

        if ($stmt->execute()) {
            $publishers=$stmt->get_result();

            $stmt->close();
            return $publishers;
        } else {
            return NULL;
        }}
        else{
            return false;
        }
    }
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();

        $stmt->close();
        return $tasks;
    }

    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    public function createTask($user_id, $task) {

        $film_data=[];
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");

        $stmt->bind_param("s", $task);
        $film_data[0]=$stmt;

        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    public function deleteReview($film_id, $user_id) {
        $stmt = $this->conn->prepare("DELETE from review  WHERE user_id='$user_id' AND film_id='$film_id'");

        $users[0]=$stmt->error;

        $result = $stmt->execute();

        $stmt->fetch();
        $stmt->close();

        if($users[0]==null){
            return false;
        }
        if ($result) {

            return $result;
        } else {
            // task failed to create
            return NULL;
        }
    }
    public function deleteSubscription($user_id, $publisher) {
        $stmt = $this->conn->prepare("DELETE from subscribes  WHERE user_subscriber='$user_id' AND user_publisher='$publisher'");

        $users[0]=$stmt->error;

        $result = $stmt->execute();

        $stmt->fetch();
        $stmt->close();

//        if($users[0]==null){
//            return false;
//        }
        if ($result) {

            return $result;
        } else {
            // task failed to create
            return NULL;
        }

//        $stmt = $this->conn->prepare("SELECT COUNT(s.user_publisher)
//        from subscribes s WHERE s.user_publisher='$publisher_id' AND s.user_subscriber='$subscriber_id'");
//        $result = $stmt->execute();
//        $stmt->bind_result($number);
//        $stmt->fetch();
//        $stmt->close();
//        if($number<1){return false;}
//        $stmt = $this->conn->prepare("DELETE FROM subscribes where user_subscriber='$subscriber_id' AND
//                                user_subscriber='$publisher_id'");
//        $result = $stmt->execute();
//        $stmt->fetch();
//        $stmt->close();
//        if ($result) {
//            return $result;
//        } else {
//            // task failed to create
//            return NULL;
//        }
    }
    //    public function createFilm($film_id, $film_name, $film_description, $film_year, $main_image) {
//
//        $stmt = $this->conn->prepare("SELECT f.outer_id FROM films f where f.outer_id='$film_id'");
//
//        if($stmt->execute()) {
//            $stmt->bind_result($number);
//            $stmt->fetch();
//            $user[0]=$film_id;

//            $stmt->close();
//            if($number>0){return null;}
//
//                $stmt = $this->conn->prepare("INSERT INTO films(name_ru, description, outer_id, film_year, main_image) VALUES(?, ?, ?, ?, ?)");
//
//            $stmt->bind_param("ssiis", $film_name, $film_description, $film_id, $film_year, $main_image);
//
//            $result = $stmt->execute();
//
//
//            $stmt->close();
//
//            if ($result) {
//
//                return $result;
//            } else {
//                // task failed to create
//                return NULL;
//            }
//        }else return null;
//    }
}

?>
