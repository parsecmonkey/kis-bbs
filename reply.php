<?php


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
$board_name=$_GET["board"];
$table_name=$BOARDS[$board_name];

// メッセージを保存するファイルのパス設定
// define( 'FILENAME', './message.txt');

// データベースの接続情報
include("setting.php");

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 変数の初期化
$message_id = null;
$mysqli = null;
$sql = null;
$res = null;
$error_message = array();
$message_data = array();

$now_date = null;
$data = null;
$message_array = array();
$success_message = null;
$clean = array();

session_start();

if( !empty($_GET['message_id']) && empty($_POST['message_id']) ) {

	$message_id = (int)htmlspecialchars($_GET['message_id'], ENT_QUOTES);
	
	// データベースに接続
	$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	// 接続エラーの確認
	if( $mysqli->connect_errno ) {
		$error_message[] = 'データベースの接続に失敗しました。 エラー番号 '.$mysqli->connect_errno.' : '.$mysqli->connect_error;
	} else {
	
		// データの読み込み
		$sql = "SELECT * FROM $table_name WHERE id = $message_id";
		$res = $mysqli->query($sql);
		
		if( $res ) {
			$message_data = $res->fetch_assoc();
		} else {
		
			// データが読み込めなかったら一覧に戻る
			header("Location: ./");
		}
		
		$mysqli->close();
	}
}

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
        $clean['message'] = '>> ' . $message_data['view_name'] . '\n' . $clean['message'];
        // $clean['message'] = preg_replace( '/\\r\\n|\\n|\\r/', '<br>', $clean['message']);
	}
	
    if( empty($error_message) ) {

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
		
        //Slackへ通知
        $to_slack_text="<{$board_name}で返信>\n表示名: {$_POST['view_name']}\nメッセージ: \nto: {$message_data['view_name']}\n{$_POST['message']}";
        include("toSlack.php");

        header("Location: ./?board={$board_name}");
    }else{//エラーしている場合        
        //Slackへ通知
        $to_slack_text="Error: ".$error_message;
        include("toSlack.php");
    }
}

// データベースに接続
$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 接続エラーの確認
if( $mysqli->connect_errno ) {
	$error_message[] = 'データの読み込みに失敗しました。 エラー番号 '.$mysqli->connect_errno.' : '.$mysqli->connect_error;
} else {
	
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
	<title><?php echo $board_name; ?> 掲示板 返信ページ</title>
</head>
<body>
	<div class="main">
		<h1><?php echo $board_name; ?> 掲示板 返信ページ</h1>


        <section>
            <!-- 投稿されたメッセージ -->
            <?php if( !empty($message_data) ): ?>
            <article>
                <div class="info">
                    <h2><?php echo $message_data['view_name']; ?></h2>
                    <time><?php echo date('Y年m月d日 H:i', strtotime($message_data['post_date'])); ?></time>
                </div>
                <p><?php echo nl2br($message_data['message']); ?></p>
            </article>
            <?php endif; ?>
        </section>
        <hr>
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
            <a class="btn_cancel" href="./?board=<?php echo $board_name; ?>">キャンセル</a>
			<input type="submit" name="btn_submit" value="返信">
        </form>
	</div>
</body>
</html>