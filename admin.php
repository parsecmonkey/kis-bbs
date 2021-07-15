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

// 管理ページのログインパスワード
define( 'PASSWORD', 'admin');

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

if( !empty($_GET['btn_logout']) ) {
	unset($_SESSION['admin_login']);
}

if( !empty($_POST['btn_submit']) ) {

    if( !empty($_POST['admin_password']) && $_POST['admin_password'] === PASSWORD ) {
		$_SESSION['admin_login'] = true;
	} else {
		$error_message[] = 'ログインに失敗しました。';
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
    <title>掲示板 管理ページ</title>
</head>
<body>
    <div class="main">
        <h1>掲示板 管理ページ</h1>
        <h2><?php echo $board_name; ?></h2>
        <!-- 未入力メッセージ -->
        <?php if( !empty($error_message) ): ?>
            <ul class="error_message">
                <?php foreach( $error_message as $value ): ?>
                    <li>・<?php echo $value; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <section>

        <?php if( !empty($_SESSION['admin_login']) && $_SESSION['admin_login'] === true ): ?>
            <!-- 管理画面 -->
            <?php if(empty($_GET['board'])): ?>
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
            <?php else: ?>
                <p><a href='./admin'> 掲示板選択ページへ戻る</a></p>             

                <form method="get" action="./download.php">
                    <select name="limit">
                        <option value="">全て</option>
                        <option value="10">10件</option>
                        <option value="30">30件</option>
                    </select>
                    <select name="board">
                        <option value="<?php echo $board_name; ?>"><?php echo $board_name; ?></option>
                    </select>                    
                    <input type="submit" name="btn_download" value="ダウンロード">
                </form>

                <!-- 投稿されたメッセージ -->
                <?php if( !empty($message_array) ): ?>
                    <?php foreach( $message_array as $value ): ?>
                        <article>
                            <div class="info">
                                <h2><?php echo $value['view_name']; ?></h2>
                                <time><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
                                <p><a href="edit.php?board=<?php echo $board_name; ?>&message_id=<?php echo $value['id']; ?>">編集</a>  <a href="delete.php?board=<?php echo $board_name ?>&message_id=<?php echo $value['id']; ?>">削除</a></p>
                            </div>
                            <p><?php echo nl2br($value['message']); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endif; ?>
                <form method="get" action="">
                    <input type="submit" name="btn_logout" value="ログアウト">
                </form>
        <?php else: ?>
            <!-- ログイン画面 -->
            <form method="post">
                <div>
                    <label for="admin_password">ログインパスワード</label>
                    <input id="admin_password" type="password" name="admin_password" value="">
                </div>
                <input type="submit" name="btn_submit" value="ログイン">
            </form>
        <?php endif; ?>
        </section>
    </div>
</body>
</html>