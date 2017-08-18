<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';
require '../vendor/autoload.php';
require '../include/simple_html_dom.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$path1 = dirname(__DIR__);
define('pic_image',$path1."/v1/uploads");
ini_set('display_errors', 'On');
error_reporting(E_ALL);
// User id from db - Global Variable
$user_id = NULL;


$app->post('/login', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('email', 'password'));

    // reading post params
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');
    $response = array();

    $db = new DbHandler();
    // check for correct email and password
    if ($db->checkLogin($email, $password)) {
        // get the user by email
        $user = $db->getUserByEmail($email);

        if ($user != NULL) {
            $response["error"] = false;
            $response['name'] = $user['name'];
            $response['email'] = $user['email'];
            $response['apiKey'] = $user['api_key'];
            $response['createdAt'] = $user['created_at'];
            $response['first_login']=$user['first_login'];
            $response['user_id']=$user['user_id'];
        } else {
            // unknown error occurred
            $response['error'] = true;
            $response['message'] = "An error occurred. Please try again";
        }
    } else {
        // user credentials are wrong
        $response['error'] = true;
        $response['message'] = 'Login failed. Incorrect credentials';
    }

    echoResponse(200, $response);
});
$app->post('/register', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('email', 'password'));

    $response = array();

    // reading post params
    $name = 'DefaultName';
    $email = $app->request->post('email');
    $password = $app->request->post('password');

    // validating email address
    validateEmail($email);

    $db = new DbHandler();
    $res = $db->createUser($name, $email, $password);

    if ($res == USER_CREATED_SUCCESSFULLY) {
        $response["error"] = false;
        $response["message"] = "You are successfully registered";
    } else if ($res == USER_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
    } else if ($res == USER_ALREADY_EXISTED) {
        $response["error"] = true;
        $response["message"] = "Sorry, this email already existed";
    }
    // echo json response
    echoResponse(201, $response);
});
$app->post('/authenticate', 'authenticate', function() {
    global $user_id;
    $response = array();
    $response["error"]="true";
    if($response["key"]=$user_id){
        $response["error"]="false";
        echoResponse(200, $response);
    }else {
        echoResponse(400, $response);
    }
});

$app->post('/lenta', 'authenticate', function() use($app) {
    verifyRequiredParams(array('last_review_date'));
    global $user_id;
    $lenta_date = $app->request->post('last_review_date');
    $db = new DbHandler();
    $response = array();

    $result = $db->getPublishersId($user_id);

    $response["tasks"] = array();
    $response2=array();
    $counter=0;

    if ($result != NULL) {

        while ($task = $result->fetch_assoc()) {
            array_push($response["tasks"], $task["user_publisher"]);
        }
        $result2 = $db->getLentaReviews($response["tasks"], $lenta_date);
        if($result2!=null) {
            $response["tasks2"] = array();
            $response2["reviewitemlist"] = array();
            while ($task2 = $result2->fetch_assoc()) {
                array_push($response2["reviewitemlist"], $task2);
                $counter++;
            }
            $response2["error"] = false;
            $response2["counter"] = $counter;
            echoResponse(200, $response2);
        }else if($result2==false){
            $response2["error"] = false;
            $response2["counter"] = 0;
            $response2["reviewitemlist"]=0;
            echoResponse(200, $response2);
        }
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(404, $response);
    }
});
$app->post('/getstats', 'authenticate', function() use($app){

    verifyRequiredParams(array('user_id'));
    $user_id = $app->request()->post('user_id');

    $db = new DbHandler();
    $response = array();

    $result = $db->getProfile($user_id);
    $result2 = $db->getSubscriptionCount($user_id);

    if($result!=null) {
        $response["name"] = $result["name"];
        $response["surname"] = $result["surname"];
        $response["nickname"] = $result["nickname"];
        $response["email"] = $result["email"];
        $response["status"] = $result["status"];
        $response["short_info"] = $result["short_info"];
        $response["avatar_image"] = $result["avatar_image"];
    }
    if($result2!=null) {
        $response["subscribed"] = $result2["subscribed"];
        $response["published"] = $result2["published"];
        $response["filmscount"] = $result2["filmscount"];
    }
    echoResponse(200, $response);
});
$app->post('/getfilmslist', 'authenticate', function() use ($app){
//    verifyRequiredParams(array('filmslist'));
    $response = array();
    $data = json_decode(file_get_contents('php://input'), true);
    try{

        $listofid=$data['filmslist'];

    }catch (Exception $exception){
        echoResponse(200, $exception);
    }

    if($data['filmslist']==null){
        $response['error']=true;
        $response['message']="Empty input";
        echoResponse(201, $response);}else{


    $db = new DbHandler();
    $response = array();

    $result = $db->getFilms($listofid);
    $response["response"]=$result;
    $filmslist = array();

    if ($result != NULL) {
        while ($task = $result->fetch_assoc()) {
            array_push($filmslist, $task);
        }
    }
    echoResponse(200, $filmslist);
}});
$app->post('/getuserslist', 'authenticate', function() use ($app){
//    verifyRequiredParams(array('filmslist'));
    $response = array();
    $data = json_decode(file_get_contents('php://input'), true);
    try{

        $listofid=$data['userslist'];

    }catch (Exception $exception){
        echoResponse(200, $exception);
    }

    if($data['userslist']==null){
        $response['error']=true;
        $response['message']="Empty input";
        echoResponse(201, $response);
    }else{


        $db = new DbHandler();
        $response = array();

        $result = $db->getUsers($listofid);
        $response["response"]=$result;
        $userslist = array();

        if ($result != NULL) {
            while ($task = $result->fetch_assoc()) {
                array_push($userslist, $task);
            }
            if($userslist==null){
                $answer["error"]=false;
                $answer["message"]="No users with such ID`s found";
                echoResponse(200, $answer);
            }
            echoResponse(200, $userslist);
        }else{
            $response["error"]=false;
            $response["message"]="No users with such ID`s found";
            echoResponse(200, $response);
        }

    }});
$app->post('/getreviews', 'authenticate', function() use ($app){

    $response = array();
    global $user_id;
    $db = new DbHandler();
    $response = array();

    $result = $db->getReviews($user_id);
    $response["response"]=$result;
    $filmslist = array();
    $filmslist["reviewitemlist"]=array();
    $filmslist["error"]="true";
    $filmslist["isempty"]="true";

    if ($result != NULL) {
        $filmslist["error"]="false";

        while ($task = $result->fetch_assoc()) {

            array_push($filmslist["reviewitemlist"], $task);
            $filmslist["isempty"]="false";
        }
    }
    echoResponse(200, $filmslist);
});
$app->post('/getuser', 'authenticate', function() use ($app){
    verifyRequiredParams(array('user'));
    global $user_id;
    $user_search = $app->request()->post('user');
    $db = new DbHandler();
    $response["userslist"] = array();
    $response["publisherslist"] = array();

    $result = $db->getUserBySearch($user_search);
    $result2 = $db->getPublishersId($user_id);
        if ($result != NULL) {
    while ($task = $result->fetch_assoc()) {
        array_push($response["userslist"], $task);
    }
    if($result2!=null){
        while ($task = $result2->fetch_assoc()) {
            array_push($response["publisherslist"], $task);
        }
    }

        echoResponse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(404, $response);
    }

});
$app->post('/getuserbyid', function() use ($app){
    verifyRequiredParams(array('user_id'));
    $user_search = $app->request()->post('user_id');
    $db = new DbHandler();
 //   $response = array();

    $result = $db->getUserById($user_search);
    if ($result != NULL) {
        while ($task = $result->fetch_assoc()) {
            //array_push($response, $task);
            $response=$task;
        }
            if($response==null){
            $response["error"]=false;
            $response["message"]="The requested resource doesn't exists";
            }

        echoResponse(200, $response);
    }else if($result==false){
        $response["error"] = false;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(404, $response);
    }

});
$app->post('/getfilm', 'authenticate', function() use ($app){
    verifyRequiredParams(array('film'));
    $film_search = $app->request()->post('film');
    global $user_id;
    $db = new DbHandler();
    $response["filmslist"] = array();
    $response["reviewlist"]=array();


    $result = $db->getFilmBySearch($film_search);
    $result2 = $db->getReviews($user_id);
    if ($result != NULL) {
        while ($task = $result->fetch_assoc()) {
            array_push($response["filmslist"], $task);

        }
        if($result2!=null){
            while ($task = $result2->fetch_assoc()) {
                array_push($response["reviewlist"], $task);

            }
        }
        echoResponse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(404, $response);
    }

});
$app->post('/getuserallfilms', 'authenticate', function() use ($app){


    verifyRequiredParams(array('user_id'));
    $user_id = $app->request()->post('user_id');


    $db = new DbHandler();
    $response = array();


    $result = $db->getFilmByUser($user_id);
    if ($result != NULL) {
        while ($task = $result->fetch_assoc()) {
            array_push($response, $task);

        }
        echoResponse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoResponse(404, $response);
    }

});

$app->post('/uploadfilm', function() use ($app)
{
    verifyRequiredParams(array('name_ru', 'name_en', 'description', 'janr',
        'country', 'film_year', 'outer_id', 'duration', 'producer'));
    try{
        $files = $_FILES['uploads'];
    }catch (Exception $e){
        echoResponse(200, 'No image sent!');
        $app->stop();
    }
    $files = $_FILES['uploads'];
    $response = array();

    $film_name_ru=$app->request()->post('name_ru');
    $film_name_en=$app->request()->post('name_en');
    $film_description=$app->request()->post('description');
    $film_janr=$app->request()->post('janr');
    $film_strana=$app->request()->post('country');
    $film_year=$app->request()->post('film_year');
    $film_outer_id=$app->request()->post('outer_id');
    $film_duration=$app->request()->post('duration');
    $film_producer=$app->request()->post('producer');

    $name = uniqid('img-' . date('YMdHi') . '-');
    $image_address=pic_image.'/'.$name;

;

    $data = array($film_name_ru, $film_name_en, $film_description,
        $film_janr, $film_strana, $film_year,
        $film_outer_id, $film_duration, $image_address, $film_producer);

    $db = new DbHandler();

    $res = $db->saveNewFilm($data);

    if ($res != NULL) {
        $users[0]=$res;

        $response["error"] = false;
        $response["message"] = "submited successfully";
        move_uploaded_file($files['tmp_name'], pic_image.'/'.$name);
        echoResponse(201, $response);
    }
    else if($res === false)
    {
        $response["error"] = true;
        $response["message"] = "user already exists";
        echoResponse(200, $response);
    }

    else
    {
        $response["error"] = true;
        $response["message"] = "Failed to insert data. Please try again";
        echoResponse(200, $response);
    }

});
$app->post('/updateprofile', 'authenticate', function() use ($app)
{
    global $user_id;
    $files="empty";
//    verifyRequiredParams(array('name','surname', 'nickname', 'short_info'));
    try{
        $files = $_FILES['uploads'];
    }catch (Exception $e){
//        echoResponse(200, 'No image sent!');
//        $app->stop();
    }

    $response = array();

    $name=$app->request()->post('name');
    $surname=$app->request()->post('surname');
    $nickname=$app->request()->post('nickname');
    $short_info=$app->request()->post('short_info');
   $users[0]="still not";

     $name=(preg_replace('/[^A-Za-zА-Яа-я]+/msiu', '', $name));
     $surname=(preg_replace('/[^A-Za-zА-Яа-я]+/msiu', '', $surname));
     $nickname=(preg_replace('/[^A-Za-zА-Яа-я]+/msiu', '', $nickname));
   // $short_info=(preg_replace('/[^A-Za-zА-Яа-я0-9]+/msiu', '', $short_info));
    if(empty($name)){$name="empty";}
    if(empty($surname)){$surname="empty";}
    if(empty($nickname)){$nickname="empty";}
    if(empty($short_info)){$short_info="empty";}
//    $users[0]=$name;


    $image_address="empty";
    if($files!="empty") {
       // $im = imagecreatefromjpeg($_FILES['image']['uploads1']);
        $picname = uniqid('img-' . date('YMdHi') . '-');
        $image_address = pic_image . '/' . $picname;
    }



    $data = array($name, $surname, $nickname, $short_info, $user_id, $image_address);

    $db = new DbHandler();

    $res = $db->updateProfile($data);

    if ($res != NULL) {

        $response["error"] = false;
        $response["message"] = "submited successfully";
        $response["user_id"]=$user_id;
       if($image_address!="empty"){move_uploaded_file($files['tmp_name'], pic_image.'/'.$picname);}
        echoResponse(201, $response);
    }
    else if($res === false)
    {
        $response["error"] = true;
        $response["message"] = "user already exists";
        echoResponse(200, $response);
    }
    else
    {
        $response["error"] = true;
        $response["message"] = "Failed to insert data. Please try again";
        echoResponse(200, $response);
    }

});

$app->post('/add_subscription', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('publisher_id'));

    $response = array();
//    $subscriber = $app->request->post('subscriber_id');

    $publisher = $app->request->post('publisher_id');
    global $user_id;

    $db = new DbHandler();

    // creating new task
    $task_id = $db->createSubscription($user_id, $publisher);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoResponse(201, $response);
    }else if($task_id==false){
        $response["error"] = true;
        $response["message"] = "Already registered";
        echoResponse(200, $response);
    }
    else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoResponse(200, $response);
    }
});
$app->post('/add_review', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('film_id', 'review_type'));

    $response = array();
    $film_id = $app->request->post('film_id');
    global $user_id;
    $review_type= $app->request()->post('review_type');
    if($review_type==2){$review_text="";}else{
    $review_text = $app->request->post('review_text');}


    $db = new DbHandler();

    // creating new task
    $task_id = $db->createReview($film_id, $user_id, $review_text, $review_type);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
       $response["task_id"] = $task_id;
       if($review_type==1) {
           $review_count = $db->incrementView($film_id);
           $response["views"] = $review_count;
       }
        echoResponse(201, $response);
    }
    else if ($task_id == false) {
        $response["error"] = false;
        $response["message"] = "Already registered";

        echoResponse(201, $response);
    }else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoResponse(200, $response);
    }
});
$app->post('/add_favorite', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('film_id'));
    global $user_id;
    $response = array();
    $film_id = $app->request->post('film_id');

    $db = new DbHandler();

    $task_id = $db->createFavorite($film_id, $user_id);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoResponse(201, $response);
    }
    else if ($task_id == false) {
        $response["error"] = false;
        $response["message"] = "Already registered";
        echoResponse(201, $response);
    }else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoResponse(200, $response);
    }
});

$app->post('/delete_review', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('film_id', 'user_id'));

    $response = array();
    $film_id = $app->request->post('film_id');
    $user_id = $app->request->post('user_id');


    $db = new DbHandler();

    // creating new task
    $task_id = $db->deleteReview($film_id, $user_id);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task deleted successfully";
        $response["task_id"] = $task_id;
        echoResponse(201, $response);
    }
    if ($task_id == false) {
        $response["error"] = false;
        $response["message"] = "Task dalready deleted";

        echoResponse(201, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to delete task. Please try again";
        echoResponse(200, $response);
    }
});
$app->post('/delete_subscription', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('publisher_id'));

    $response = array();
//    $subscriber = $app->request->post('subscriber_id');

    $publisher = $app->request->post('publisher_id');
    global $user_id;

    $db = new DbHandler();

    // creating new task
    $task_id = $db->deleteSubscription($user_id, $publisher);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoResponse(201, $response);
    }else if($task_id==false){
        $response["error"] = true;
        $response["message"] = "Already registered";
        echoResponse(200, $response);
    }
    else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoResponse(200, $response);
    }
});

//$app->post('/tasks', 'authenticate', function() {
//    global $user_id;
//    $response = array();
//    $db = new DbHandler();
//
//    // fetching all user tasks
//    $result = $db->getAllUserTasks($user_id);
//
//    $response["error"] = false;
//    $response["tasks"] = array();
//
//    // looping through result and preparing tasks array
//    while ($task = $result->fetch_assoc()) {
//        $tmp = array();
//        $tmp["id"] = $task["id"];
//        $tmp["task"] = $task["task"];
//        $tmp["status"] = $task["status"];
//        $tmp["createdAt"] = $task["created_at"];
//        array_push($response["tasks"], $tmp);
//    }
//
//    echoResponse(200, $response);
//});
//$app->post('/tasks', 'authenticate', function() use ($app) {
//            // check for required params
//            verifyRequiredParams(array('task'));
//
//            $response = array();
//            $task = $app->request->post('task');
//
//            global $user_id;
//            $db = new DbHandler();
//
//            // creating new task
//            $task_id = $db->createTask($user_id, $task);
//
//            if ($task_id != NULL) {
//                $response["error"] = false;
//                $response["message"] = "Task created successfully";
//                $response["task_id"] = $task_id;
//                echoResponse(201, $response);
//            } else {
//                $response["error"] = true;
//                $response["message"] = "Failed to create task. Please try again";
//                echoResponse(200, $response);
//            }
//        });
//$app->get('/tasks/:id', 'authenticate', function($task_id) {
//    global $user_id;
//    $response = array();
//    $db = new DbHandler();
//
//    // fetch task
//    $result = $db->getTask($task_id, $user_id);
//
//    if ($result != NULL) {
//        $response["error"] = false;
//        $response["id"] = $result["id"];
//        $response["task"] = $result["task"];
//        $response["status"] = $result["status"];
//        $response["createdAt"] = $result["created_at"];
//        echoResponse(200, $response);
//    } else {
//        $response["error"] = true;
//        $response["message"] = "The requested resource doesn't exists";
//        echoResponse(404, $response);
//    }
//});
//$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
//            // check for required params
//            verifyRequiredParams(array('task', 'status'));
//
//            global $user_id;
//            $task = $app->request->put('task');
//            $status = $app->request->put('status');
//
//            $db = new DbHandler();
//            $response = array();
//
//            // updating task
//            $result = $db->updateTask($user_id, $task_id, $task, $status);
//            if ($result) {
//                // task updated successfully
//                $response["error"] = false;
//                $response["message"] = "Task updated successfully";
//            } else {
//                // task failed to update
//                $response["error"] = true;
//                $response["message"] = "Task failed to update. Please try again!";
//            }
//            echoResponse(200, $response);
//        });
//$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
//            global $user_id;
//
//            $db = new DbHandler();
//            $response = array();
//            $result = $db->deleteTask($user_id, $task_id);
//            if ($result) {
//                // task deleted successfully
//                $response["error"] = false;
//                $response["message"] = "Task deleted succesfully";
//            } else {
//                // task failed to delete
//                $response["error"] = true;
//                $response["message"] = "Task failed to delete. Please try again!";
//            }
//            echoResponse(200, $response);
//        });

//$app->post('/add_film', 'uploadfile', function() use ($app) {
//
//    verifyRequiredParams(array('film_id', 'film_description', 'film_name', 'film_year'));
//    $response = array();
//    $film_id = $app->request()->post('film_id');
//    $film_name = $app->request()->post('film_name');
//    $film_description = $app->request()->post('film_description');
//    $film_year = $app->request()->post('film_year');
//
//    $db = new DbHandler();
//    global $imgs;
//
//    // creating new task
//    $task_id = $db->createFilm($film_id, $film_name, $film_description, $film_year, $imgs);
//
//    if ($task_id != NULL) {
//        $response["error"] = false;
//        $response["message"] = "Task created successfully";
//
//        echoResponse(201, $response);
//    } else {
//        $response["error"] = true;
//        $response["message"] = "Failed to create task. Please try again";
//        echoResponse(200, $response);
//    }
//});
//$app->get('/getfilmid/:name', function($name){
//    $result=array();
//    $html = file_get_html("https://www.kinopoisk.ru/index.php?kp_query=%D0%B3%D0%B0%D1%80%D1%80%D0%B8");
//
//    $value=$html->find('div');
//    $ret = $value->find('.clear');
//    foreach ($html->find('.element') as $element){
//        array_push($result, $ret);
//    }
//
////    foreach($html->find('a') as $element){
////        array_push($result,$element->href);
////    }
//    //echo 'Hello there '.$name;
//    echoResponse(200, $result);
//
//});
//$app->post('/upload', function() {
//
//    $response = array();
//    $path1 = dirname(__DIR__);
//    $files = $_FILES['uploads'];
//    $file_content = file_get_contents($files['tmp_name']);
//    $name = uniqid('img-' . date('Ymd') . '-');
//    //   { if (move_uploaded_file($files['tmp_name'][0], 'uploads/' . $name) === true) {
//    //        $imgs[0] = array('url' => $path1 . $name, 'name' => $files['name'][0]);
//    //    }
//    //  define('pic_image',$path1."/v1/uploads");
//    //  move_uploaded_file($files['tmp_name'], pic_image.'/'.$name);

function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}
function uploadfile () {
    if (!isset($_FILES['uploads'])) {
        echo "No files uploaded!!";
        return;
    }
    global $imgs;
    $imgs = array();

    $files = $_FILES['uploads'];
    $cnt = count($files['name']);

    for($i = 0 ; $i < $cnt ; $i++) {
        if ($files['error'][$i] === 0) {
            $name = uniqid('img-'.date('Ymd').'-');
            if (move_uploaded_file($files['tmp_name'][$i], 'uploads/' . $name) === true) {
                $imgs[] = array('url' => '/uploads/' . $name, 'name' => $files['name'][$i]);
            }

        }
    }

    $imageCount = count($imgs);

    if ($imageCount == 0) {
        echo 'No files uploaded!!  <p><a href="/">Try again</a>';
        return;
    }

    $plural = ($imageCount == 1) ? '' : 's';

    foreach($imgs as $img) {
        printf('%s <img src="%s" width="50" height="50" /><br/>', $img['name'], $img['url']);
    }
}
function verifyRequiredParams($required_fields) {



    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {


        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';

        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
    }
}
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>