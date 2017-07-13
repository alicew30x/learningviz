<?php

session_start();

//connect to mongo database
function connectMongo($HOST, $NAME){
	
	global $db;
	
	if (!extension_loaded('mongo')) {
		if (!dl('mongo.so')) {
			echo '<p> Mongo extension not loaded.</p>';
			exit;
		}
	}
	
	try{
		$mongo = new MongoClient("mongodb://$HOST");
		$db = $mongo->selectDB($NAME);
		return TRUE;
	}
	catch (MongoConnectionException $e){
		echo '<p>Couldn\'t connect to mongodb, is the "mongo" process running?</p>';
		exit;
	}
}

//mix and match default keyword list and user / class data
function matchData($template, $input){
    $count = count($template);
    $output = $template;
    //check the input result against template
    for($i=0; $i<$count; $i++){
        //store the keyword
        $k = $template[$i]['_id'];
        //check it against the user result
        foreach($input as $words){
            for($j=0; $j<count($words); $j++){
                //if found a match
                if($k == $words[$j]["_id"]){
                    $output[$i]['avg_quality'] = $words[$j]['avg_quality'];
                }
            }
        }
    }
    return $output;
}
	

//print sort data from database for current user and current discussion topic
function printMongo($UID, $DID){
	
	global $db;
    
    //EXPLODE discussion ID
    $topics = explode(",", $DID);
    for($i=0;$i<count($topics);$i++){
        $topics[$i] = intval($topics[$i]);
    }

    
	//Current User
	$user_ops = array ('discussion_id' => array('$in' => $topics), 'user_id' => $UID);
	$user_results = $db->RAW_DATA->find($user_ops);
    $user_count = $user_results->count();
    
    //find user photo
    $user_avatar_ops = array ('student_id' => $UID);
    $user_avatar_results = $db->USERS->find($user_avatar_ops);
    $user = $user_avatar_results->getNext();
    $user_avatar = $user['avatar_url'];
    $user_name = $user['student_name'];
    
    $user_array = array ( array ('_id' => $UID, 'count' => $user_count, 'avatar' => $user_avatar, 'name' => $user_name) );
    $user_data = json_encode($user_array);
    

//	//Class
	$class_ops_ini = array(
        array ('$match' => array('discussion_id' => array ('$in' => $topics))),
        array ('$group' => array (
                '_id' => '$user_id',
				'count' => array ('$sum' => 1)
        )),
        array ('$sort' => array ('count' => -1)),
        array ('$limit' => 5)
    );
						
	$class_results_ini = $db->RAW_DATA->aggregate($class_ops_ini);
    $last= count($class_results_ini['result']) -1;
    $thresh = $class_results_ini['result'][$last]['count'];
    
    $class_ops = array(
        array ('$match' => array('discussion_id' => array ('$in' => $topics))),
        array ('$group' => array (
                '_id' => '$user_id',
				'count' => array ('$sum' => 1)
        )),
        array ('$match' => array('count' => array ('$gte' => $thresh, '$gt' => 1))),
        array ('$sort' => array ('count' => -1))
    );
    
    $class_results= $db->RAW_DATA->aggregate($class_ops);
    
    for($i=0; $i<count($class_results['result']);$i++){
        $student_avatar_ops = array ('student_id' => $class_results['result'][$i]['_id']);
        $student_avatar_cursor = $db->USERS->find($student_avatar_ops);
        $student = $student_avatar_cursor->getNext();
        
        $class_results['result'][$i]['avatar'] = $student['avatar_url'];
        $class_results['result'][$i]['name'] = $student['student_name'];
    }
    
    $class_data = json_encode($class_results['result']);
    
    if(count($class_results['result']) == 0){
        $max = 1;
    }else{
        $max = $class_results['result'][0]['count'];
    }
    
    echo "<div>";
    
   // echo "<content>"; 
    //print_r($thresh);
   // echo "</content>";
    

    
    $usr = $_POST['UNA'];
    
    //fetching topic title
    $titlequery = array('discussion_id' => array('$in' => $topics));
    $title_cursor = $db->TOPICS->find($titlequery);
    $title = $title_cursor->getNext();
    $title = $title['discussion_title'];
    
    //fetching last database update time
    $timequery = array('status_id' => "update_time");
    $time_cursor = $db->STATUS->find($timequery);
    $time = $time_cursor->getNext();
    $time = $time['update_time'];
    
    //fetching last database changes flag
    $flagquery = array('status_id' => "changes_flag");
    $flag_cursor = $db->STATUS->find($flagquery);
    $flag = $flag_cursor->getNext();
    $flag = $flag['changes_flag'];
    
    //check if time is session
    if($_POST['TIM'] == 0){
        echo "<update>graph</update>";
        echo "<script>
            runBar($class_data, 'Top Contributors in Class', $max);
            runBar($user_data, '$usr' , $max);
            updateTitle('$title');
            updateTime('$time');
            updateDescription('<p>How do I compare with top contributors in the class?</p><p>Please note that it may take <b>up to 5 minutes</b> to reflect your latest post in the graphics. The graphics will update automatically.</p>');
            </script>";
    }else if($_POST['TIM'] != $time){
        //if flag is true
        //if($flag == true){
            echo "<update>graph</update>";
            echo "<script>
            runBar($class_data, 'Top Contributors in Class', $max);
            runBar($user_data, '$usr' , $max);
            updateTitle('$title');
            updateTime('$time');
            </script>";
       //}else{
            //echo "<update>time</update>";
            //echo "<script>updateTime('$time')</script>";
            //echo "<content>TIME CHANGED</content>";
        //}
    }else{
        echo "<content>NO CHANGES</content>";
    }
//    echo "<content>";
//    print_r($class_results);
//    echo "</content>";
    echo "</div>";
}

//database in mongo
$db;

//database
$DB_HOST = 'localhost';
$DB_NAME = 'CanvasDiscussion';

if(connectMongo($DB_HOST , $DB_NAME)==TRUE){
    printMongo(intval($_POST['UID']), $_POST['DID']);
    //for debugging
}
?>
