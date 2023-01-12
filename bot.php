<?php

// https://api.telegram.org/bot/setWebhook?url=https://test.eliteschool.uz/tg_bot.php

require_once "rb.php";

$DB_TYPE = "mysql";
$DB_HOST = "127.0.0.1";
$DB_NAME = "host1845737";
$DB_USER = "host1845737";
$DB_PASS = "994958514Tt";

R::setup($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME, $DB_USER, $DB_PASS);
RedBeanPHP\Util\DispenseHelper::setEnforceNamingPolicy( FALSE );

if(!R::testConnection()) {
  exit("There is no connection to the database :(");
}


$botToken="";



$mess = file_get_contents('php://input');
$mess = json_decode($mess, true)['message'];

$is_authorized = false;

if(isset($mess)) {

  $iusr_id = $mess['from']['id'];
  $ichat_id = $mess['chat']['id'];
  $imess_id = $mess['message_id'];
  $iusr_name = $mess['from']['username'];
  $iusr_first_name = $mess['from']['first_name'];
  $iusr_last_name = $mess['from']['last_name'];
  $itext = $mess['text'];
  $itext_type = $mess['entities']['type'];
  $iphone_num = $mess['contact']['phone_number'];


  $tg_usr = R::findOne("tg_bot_session", "chat_id = ?", [$ichat_id]);

  if($tg_usr) {
    $is_authorized = true;
  } else {
    $is_authorized = false;
  }

  if(isset($itext)) {
    if(!$is_authorized) {
      switch ($itext) {
        case '/start':
          send("<b>Здравствуйте</b> \nДля входа в аккаунт нажмите кнопку <i>Войти</i> ниже.");
          break;
        
        default:
          send("Извините неизвестная команда <i>:(</i>");
          break;
      }
    } else {
      if($tg_usr -> type == "student") {
        switch ($itext) {
          case '/start':
            send("Если хотите <b>Выйти с Аккаунта</b> введите /exit");
            break;
          
          case '/exit':
            R::trash($tg_usr);
            send("<b>Вы вышли с аккаунта</b>. \nНадеемся увидеться с вами ещё раз.");
            break;
          
          case 'Мои группы':
            $groups = R::findAll("groups");
            foreach($groups as $val) {
              $group_students = json_decode($val -> students, true);
              if(in_array($tg_usr -> user_id, $group_students)) {
                $teacher = R::findOne("personal", "id = ?", [$val -> teacher_id]);
                $text .= "    <b>Name: ".$val -> name."</b>. Time: ".$val -> time.". Teacher: ".$teacher -> full_name."\n";
              }
            }
            
            send($text);
              
            break;
          
          case 'Посещение/Оценки':
            
            $usr = R::findOne("clients", "id = ?", [$tg_usr -> user_id]);
            
            $groups = R::findAll("groups");
            foreach($groups as $val) {
              $group_students = json_decode($val -> students, true);
              if(in_array($usr -> id, $group_students)) { 
                $j = 0;
                while($j != 31) {
                  $dt = date('Y-m-d', strtotime('-'.$j.' day', strtotime(date('Y-m-d'))));
                  if(in_array(date('N', strtotime($dt)), json_decode($val -> schedule))) {
                    $tr = R::findOne("traffic", "group_id = ? AND student_id = ? AND date = ?", [$val -> id, $usr -> id, $dt]);
                    $text .= $dt." : ";
                    if($tr -> have) {
                      $text .= "✅";
                    } else {
                      $text .= "❌";
                    }

                    $text .= " ".$val -> name.".";
                    if($tr -> have) {
                      $text .= " Mark: ".$tr -> mark."\n";
                    } else {
                      $text .= "\n";
                    }
                  }
                    
               	  $j++;
                }
                unset($j);
              }
            }
            
            send($text);
            
            break;
            
          case 'Посещение/Оценки':
            
            $usr = R::findOne("clients", "id = ?", [$tg_usr -> user_id]);
            
            
            
            send($text);
            
            break;
          
          default:
            send("Извините неизвестная команда <i>:(</i>");
            break;
        }
      } else {
        switch ($itext) {
          case '/start':
            send("Если хотите <b>Выйти с Аккаунта</b> введите /exit");
            break;
          
          case '/exit':
            R::trash($tg_usr);
            send("<b>Вы вышли с аккаунта</b>. \nНадеемся увидеться с вами ещё раз.");
            break;
          
          case 'Мои дети':
            $usr = R::findOne("parents", "id = ?", [$tg_usr -> user_id]);
            $childs_id = json_decode($usr -> childs);
            
            $text = "";

            $i = 1;
            foreach($childs_id as $val) {
              $child = R::findOne("clients", "id = ?", [$val]);
              $text .= $i.". <b>".$child -> first_name." ".$child -> last_name."</b>\n";
              $i++;
            }
            unset($i);

            send($text);

            break;
            
          case 'Группы детей':
            $usr = R::findOne("parents", "id = ?", [$tg_usr -> user_id]);
            $childs_id = json_decode($usr -> childs);
            
            $text = "";

            $i = 1;
            foreach($childs_id as $val) {
              $child = R::findOne("clients", "id = ?", [$val]);
              $text .= $i.". <b>".$child -> first_name." ".$child -> last_name."</b>\n";
              
              $groups = R::findAll("groups");
              foreach($groups as $val2) {
                $group_students = json_decode($val2 -> students, true);
                if(in_array($child -> id, $group_students)) {
                  $teacher = R::findOne("personal", "id = ?", [$val2 -> teacher_id]);
                  $text .= "    Name: <b>".$val2 -> name."</b>. Time: <b>".$val2 -> time."</b>. Teacher: <b>".$teacher -> full_name."</b>\n";
                }
              }
              
              $i++;
            }
            unset($i);

            send($text);
            
            break;
            
          case 'Посещение/Оценки':

            // Надо сделать просмотр посящений через id группы и ученика в процессе перечислений по датам и потом вывод данных
            
            $usr = R::findOne("parents", "id = ?", [$tg_usr -> user_id]);
            $childs_id = json_decode($usr -> childs);
            
            $text = "<b>Успеваемость и посещяемость за последние 30дн</b>\n";

            $i = 1;
            foreach($childs_id as $val) {
              $child = R::findOne("clients", "id = ?", [$val]);
              $text .= "\n".$i.". <b>".$child -> first_name." ".$child -> last_name."</b>\n";
              
              $groups = R::findAll("groups");
              foreach($groups as $val2) {
                $group_students = json_decode($val2 -> students, true);
                if(in_array($child -> id, $group_students)) {
                  $j = 0;
                  while($j != 31) {
                    $dt = date('Y-m-d', strtotime('-'.$j.' day', strtotime(date('Y-m-d'))));
                    if(in_array(date('N', strtotime($dt)), json_decode($val2 -> schedule))) {
                      $tr = R::findOne("traffic", "group_id = ? AND student_id = ? AND date = ?", [$val2 -> id, $child -> id, $dt]);
                      $text .= "    ".$dt." : ";
                      if($tr -> have) {
                        $text .= "✅";
                      } else {
                        $text .= "❌";
                      }

                      $text .= " ".$val2 -> name.".";
                      if($tr -> have) {
                        $text .= " Mark: ".$tr -> mark."\n";
                      } else {
                        $text .= "\n";
                      }
                    }
                    
                  	$j++;
                  }
                  unset($j);
                }
              }
              
              $i++;
            }
            unset($i);
            
            send($text);
            break;
          
          default:
            send("Извините неизвестная команда <i>:(</i>");
            break;
        }
      }
    }
  } else if(isset($iphone_num) && !$is_authorized) {
    
    if(R::findOne("clients", "phone = ?", [$iphone_num])) {

      $client = R::findOne("clients", "phone = ?", [$iphone_num]);
      
      $usr = R::dispense("bot_tg_signeds");

      $usr -> user_id = $client -> id;
      $usr -> chat_id = $ichat_id;
      $usr -> type = "student";

      R::store($usr);

      send("Вы вошли в аккаунт как <b>ученик</b>. \nЗдравствуйте <i>".$client -> first_name."</i>");

      unset($client);
      unset($usr);

    } else if(R::findOne("parents", "phone = ?", [$iphone_num])) {

      $parent = R::findOne("parents", "phone = ?", [$iphone_num]);
      
      $usr = R::dispense("bot_tg_signeds");

      $usr -> user_id = $parent -> id;
      $usr -> chat_id = $ichat_id;
      $usr -> type = "parent";

      R::store($usr);

      send("Вы вошли в аккаунт как <b>родитель</b>. \nЗдравствуйте <i>".$parent -> full_name."</i>");

      unset($parent);
      unset($usr);

    } else {
      send("<b>Извините вы не найдены в Базе Данных. \nПовторите попытку поже!</b>");
    }
  }

} else if(@$_GET['code'] == '5280' && isset($_GET['send_to_student'])) {
  $data = json_decode($_GET['send_to_student']);
  $group = R::findOne("groups", "id = ?", [$data -> group_id]);
  $ichat_id = R::findOne("bot_tg_signeds", "user_id = ?", [$data -> student_id])['chat_id'];
  
  if(isset($data -> enable)) {
    
    if($data -> enable) {
      send("Вы пришли на урок: <b>".$group -> name."</b>");
    } else {
      send("Вы не пришли на урок: <b>".$group -> name."</b>");
    }
    
  } else if(isset($data -> mark)) {

    send("Вы получили оценку: ".$data -> mark.",\nНа уроке: <b>".$group -> name."</b>");
  }
}





function send($text) {

	global $ichat_id;
	global $botToken;
  global $is_authorized;

  $tg_usr = R::findOne("bot_tg_signeds", "chat_id = ?", [$ichat_id]);

	$website="https://api.telegram.org/bot".$botToken;

  $keyboard = [
    "keyboard" => [
      [

      ]
    ],
    "one_time_keyboard" => false,
    "resize_keyboard" => true
  ];

  if($tg_usr) {
    if($tg_usr -> type == "student") {
      array_push($keyboard['keyboard'][0], ['text' => 'Мои группы']);
      array_push($keyboard['keyboard'][0], ['text' => 'Посещение/Оценки']);
    } else {
      array_push($keyboard['keyboard'][0], ['text' => 'Мои дети']);
      array_push($keyboard['keyboard'][0], ['text' => 'Группы детей']);
      array_push($keyboard['keyboard'][0], ['text' => 'Посещение/Оценки']);
    }
  } else {
    array_push($keyboard['keyboard'][0], ['text' => 'Войти', 'request_contact' => true]);
  }

  // $fd = fopen("rst.txt", 'a') or die("не удалось создать файл");
  // $str = json_encode($keyboard);
  // fwrite($fd, $str);
  // fclose($fd);

  $params=[
		'chat_id'=>$ichat_id,
    'parse_mode' => 'html',
		'text'=> (string)$text,
    'reply_markup' => json_encode($keyboard),
  ];

  $ch = curl_init($website . '/sendMessage');
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $result = curl_exec($ch);
  curl_close($ch);
}
