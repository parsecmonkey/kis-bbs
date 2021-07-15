<?php

// メッセージを保存するファイルのパス設定
// define( 'FILENAME', './message.txt');

//強制的にGETをつける
if(empty($_GET["board"]))
    header('Location: ./?board=起業研');

$board_name=$_GET["board"];

//テーブルの名前
$BOARDS=[
        "起業研" => "message",
        "文学部" => "bungakubu",
        "理工学部" => "rikougakubu",
        "経済学部" => "keizaigakubu",
        "法学部" => "hougakubu",
        "経営学部" => "keieigakubu",
        "知能情報学部" => "tinoujouhougakubu",
        "マネジメント創造学部" => "manejiment",
        "フロンティアサイエンス学部" => "huronthia"];
$table_name=$BOARDS[$board_name];

// データベースの接続情報
include("setting.php");

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 変数の初期化
$now_date = null;
$data = null;
$file_handle = null;
$split_data = null;
$message = array();
$message_array = array();
$success_message = null;
$error_message = array();
$clean = array();

session_start();

if( !empty($_POST['btn_submit']) ) {

    // 表示名の入力チェック
	if( empty($_POST['view_name']) ) {
		$error_message[] = '表示名を入力してください。';
	} else {
		$clean['view_name'] = htmlspecialchars( $_POST['view_name'], ENT_QUOTES);
        $clean['view_name'] = preg_replace( '/\\r\\n|\\n|\\r/', '', $clean['view_name']);

        // セッションに表示名を保存
		$_SESSION['view_name'] = $clean['view_name'];
	}

    // メッセージの入力チェック
	if( empty($_POST['message']) ) {
		$error_message[] = 'ひと言メッセージを入力してください。';
	} else {
		$clean['message'] = htmlspecialchars( $_POST['message'], ENT_QUOTES);
        // $clean['message'] = preg_replace( '/\\r\\n|\\n|\\r/', '<br>', $clean['message']);
	}


	
    if( empty($error_message) ) {

        /*
        if( $file_handle = fopen( FILENAME, "a") ) {

            // 書き込み日時を取得
            $now_date = date("Y-m-d H:i:s");
        
            // 書き込むデータを作成
            $data = "'".$clean['view_name']."','".$clean['message']."','".$now_date."'\n";
        
            // 書き込み
            fwrite( $file_handle, $data);
        
            // ファイルを閉じる
            fclose( $file_handle);

            $success_message = 'メッセージを書き込みました。';
        }
        */

        // データベースに接続
		$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // 接続エラーの確認
		if( $mysqli->connect_errno ) {
			$error_message[] = '書き込みに失敗しました。 エラー番号 '.$mysqli->connect_errno.' : '.$mysqli->connect_error;
		} else {

			// 文字コード設定
			$mysqli->set_charset('utf8');
			
			// 書き込み日時を取得
			$now_date = date("Y-m-d H:i:s");
			
			// データを登録するSQL作成
			// $sql = "INSERT INTO message (view_name, message, post_date) VALUES ( '$clean[view_name]', '$clean[message]', '$now_date')";
			$sql = "INSERT INTO $table_name (view_name, message, post_date) VALUES ( '$clean[view_name]', '$clean[message]', '$now_date')";
			
			// データを登録
			$res = $mysqli->query($sql);
		
			if( $res ) {
                $_SESSION['success_message'] = 'メッセージを書き込みました。';
			} else {
				$error_message[] = '書き込みに失敗しました。';
			}
		
			// データベースの接続を閉じる
			$mysqli->close();
		}


        $to_slack_text="<{$board_name}掲示板で新規作成>\n表示名: {$_POST['view_name']}\nメッセージ: {$_POST['message']}";
        include("toSlack.php");

        header('Location: ./?board='.$board_name);
    }else{//エラーしている場合        
        //Slackへ通知
        $to_slack_text="Info: 入力不備\nメッセージ: ".implode(", ",$error_message)."\n場所: ".$board_name;
        include("toSlack.php");
    }
}

/*
if( $file_handle = fopen( FILENAME,'r') ) {
    while( $data = fgets($file_handle) ){
        
        $split_data = preg_split( '/\'/', $data);

        $message = array(
            'view_name' => $split_data[1],
            'message' => $split_data[3],
            'post_date' => $split_data[5]
        );
        array_unshift( $message_array, $message);
    }

    // ファイルを閉じる
    fclose( $file_handle);
}
*/

// データベースに接続
$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 接続エラーの確認
if( $mysqli->connect_errno ) {
	$error_message[] = 'データの読み込みに失敗しました。 エラー番号 '.$mysqli->connect_errno.' : '.$mysqli->connect_error;
} else {
	
    // $sql = "SELECT id,view_name,message,post_date FROM message ORDER BY post_date DESC";
    $sql = "SELECT id,view_name,message,post_date FROM $table_name ORDER BY post_date DESC";
	$res = $mysqli->query($sql);
	
	if( $res ) {
		$message_array = $res->fetch_all(MYSQLI_ASSOC);
	}
	
	$mysqli->close();
}

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scale=0">
    <link rel="stylesheet" href="stylesheet.css">
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="img/kigyoken-logo2.png">
    <link rel="apple-touch-icon" href="img/kigyoken-logo2.png">
    <?php  echo "<title>".$_GET['board']." 掲示板</title>";  ?>
</head>
<script>
function sendPost(event) {
    // ポストで送る関数
    event.preventDefault();
    var form = document.createElement('form');
    form.action = event.target.href;
    form.method = 'post';
    document.body.appendChild(form);
    form.submit();
}
</script>
<body>
    <header>
        <div class="head-left">
            <div class="head-img">
                <a href="https://konan-kigyoken.work/web/"><img src="img/kigyoken-logo2.png" alt="起業研ロゴ"></a>
            </div>
            <div class="head-img">
                <a href="https://konan-kigyoken.work/kis/"><img src="img/kis_logo2.png" alt="KISロゴ"></a>
            </div>
            <!--
            <div class="head-right-img">
                <img src="img/plus-red.png" alt="メニュー">
            </div>
            -->
        </div>
        <div class="hamburger-menu">
            <input type="checkbox" id="menu-btn-check">
            <label for="menu-btn-check" class="menu-btn"><span></span></label>
            <!--ここからメニュー-->
            <div class="menu-content">
                <ul>
                    <li><a href="?board=文学部">文学部</a></li>
                    <li><a href="?board=理工学部">理工学部</a></li>
                    <li><a href="?board=経済学部">経済学部</a></li>
                    <li><a href="?board=法学部">法学部</a></li>
                    <li><a href="?board=経営学部">経営学部</a></li>
                    <li><a href="?board=知能情報学部">知能情報学部</a></li>
                    <li><a href="?board=マネジメント創造学部">マネジメント創造学部</a></li>
                    <li><a href="?board=フロンティアサイエンス学部">フロンティアサイエンス学部</a></li>
                </ul>
            </div>
            <!--ここまでメニュー-->
        </div>
    </header>
    <div class="main">
        <?php echo "<h1>".$_GET['board']." 掲示板</h1>"; ?>

        <!-- 投稿完了メッセージ -->
        <?php if( empty($_POST['btn_submit']) && !empty($_SESSION['success_message']) ): ?>
            <p class="success_message"><?php echo $_SESSION['success_message']; ?></p>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <!-- 未入力メッセージ -->
        <?php if( !empty($error_message) ): ?>
            <ul class="error_message">
                <?php foreach( $error_message as $value ): ?>
                    <li>・<?php echo $value; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <!-- メッセージの入力フォーム -->
        <form method="post">
            <div>
                <label for="view_name">表示名</label>
                <input id="view_name" type="text" name="view_name" value="<?php if( !empty($_SESSION['view_name']) ){ echo $_SESSION['view_name']; } ?>">
            </div>
            <div>
                <label for="message">ひと言メッセージ</label>
                <textarea id="message" name="message"></textarea>
            </div>
            <input type="submit" name="btn_submit" value="書き込む">
        </form>
        <hr>
        <section>
            <!-- 投稿されたメッセージ -->
            <?php if( !empty($message_array) ): ?>
            <?php foreach( $message_array as $value ): ?>
            <article>
                <div class="info">
                    <h2><?php echo $value['view_name']; ?></h2>
                    <time><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
                    <!-- <p><a href="reply.php?message_id=<?php echo $value['id']; ?>">返信</a></p> -->
                    <p><a href="reply.php?board=<?php echo  $_GET['board']; ?>&message_id=<?php echo $value['id']; ?>">返信</a></p>
                </div>
                <p><?php echo nl2br($value['message']); ?></p>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
    <footer>
        <!--
        <div class="foot-left">
            <div class="foot-img">
                <a href="https://konan-kigyoken.work/web/"><img src="img/kigyoken-logo2.png" alt="起業研ロゴ"></a>
            </div>
            <div class="foot-img">
                <a href="https://konan-kigyoken.work/kis/"><img src="img/kis_logo2.png" alt="KISロゴ"></a>
            </div>
        </div>
        -->
        <div class="foot-content">
            <ul>
                <li><a href="?board=文学部">文学部</a></li>
                <li><a href="?board=理工学部">理工学部</a></li>
                <li><a href="?board=経済学部">経済学部</a></li>
                <li><a href="?board=法学部">法学部</a></li>
                <li><a href="?board=経営学部">経営学部</a></li>
                <li><a href="?board=知能情報学部">知能情報学部</a></li>
                <li><a href="?board=マネジメント創造学部">マネジメント創造学部</a></li>
                <li><a href="?board=フロンティアサイエンス学部">フロンティアサイエンス学部</a></li>
            </ul>
        </div>
    </footer>
</body>
</html>