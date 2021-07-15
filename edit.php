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

session_start();

// 管理者としてログインしているか確認
if( empty($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true ) {

	// ログインページへリダイレクト
	header("Location: ./admin.php");
}

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
			header("Location: ./admin?board={$board_name}");
		}
		
		$mysqli->close();
	}
} elseif( !empty($_POST['message_id']) ) {

    $message_id = (int)htmlspecialchars( $_POST['message_id'], ENT_QUOTES);

    if( empty($_POST['view_name']) ) {
        $error_message[] = '表示名を入力してください。';
    } else {
        $message_data['view_name'] = htmlspecialchars($_POST['view_name'], ENT_QUOTES);
    }
    
    if( empty($_POST['message']) ) {
        $error_message[] = 'メッセージを入力してください。';
    } else {
        $message_data['message'] = htmlspecialchars($_POST['message'], ENT_QUOTES);
    }

    if( empty($error_message) ) {
    
        // データベースに接続
		$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
		// 接続エラーの確認
		if( $mysqli->connect_errno ) {
			$error_message[] = 'データベースの接続に失敗しました。 エラー番号 ' . $mysqli->connect_errno . ' : ' . $mysqli->connect_error;
		} else {
			$sql = "UPDATE $table_name set view_name = '$message_data[view_name]', message= '$message_data[message]' WHERE id =  $message_id";
			$res = $mysqli->query($sql);
		}
		
		$mysqli->close();
		
		// 更新に成功したら一覧に戻る
		if( $res ) {
			header("Location: ./admin?board={$board_name}");
		}
    }
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
	<title>起業研 掲示板 管理ページ（投稿の編集）</title>
</head>
<body>
	<div class="main">
		<h1>起業研 掲示板 管理ページ（投稿の編集）</h1>
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
				<input id="view_name" type="text" name="view_name" value="<?php if( !empty($message_data['view_name']) ){ echo $message_data['view_name']; } ?>">
			</div>
			<div>
				<label for="message">ひと言メッセージ</label>
				<textarea id="message" name="message"><?php if( !empty($message_data['message']) ){ echo $message_data['message']; } ?></textarea>
			</div>
			<a class="btn_cancel" href="admin.php">キャンセル</a>
			<input type="submit" name="btn_submit" value="更新">
			<input type="hidden" name="message_id" value="<?php echo $message_data['id']; ?>">
		</form>
	</div>
</body>
</html>