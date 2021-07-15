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

// データベースの接続情報
include("setting.php");

// 変数の初期化
$csv_data = null;
$sql = null;
$res = null;
$message_array = array();
$limit = null;

session_start();

// 取得件数
if( !empty($_GET['limit']) ) {
	if( $_GET['limit'] === "10" ) {
		$limit = 10;
	} elseif( $_GET['limit'] === "30" ) {
		$limit = 30;
	}
}

if( !empty($_SESSION['admin_login']) && $_SESSION['admin_login'] === true ) {
	//現在時刻取得
	$now_date = date("Y-m-d H:i:s");

	// 出力の設定
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=message_{$board_name}_{$now_date}.csv");
	header("Content-Transfer-Encoding: binary");

    // データベースに接続
	$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	// 接続エラーの確認
	if( !$mysqli->connect_errno ) {

        if( !empty($limit) ) {
			$sql = "SELECT * FROM $table_name ORDER BY post_date ASC LIMIT $limit";
		} else {
			$sql = "SELECT * FROM $table_name ORDER BY post_date ASC";
		}

		$res = $mysqli->query($sql);

		if( $res ) {
			$message_array = $res->fetch_all(MYSQLI_ASSOC);
		}

		$mysqli->close();
	}

    // CSVデータを作成
	if( !empty($message_array) ) {

		// 1行目のラベル作成
		// $csv_data .= '"ID","表示名","メッセージ","投稿日時"'."\n";
		$csv_data .= 'ID,表示名,メッセージ,投稿日時'."\n"; //CSVでダブルクォーテーションを付ける意味がわからないので書き換え

		foreach( $message_array as $value ) {
			// データを1行ずつCSVファイルに書き込む

			//改行はCSVでバグるのでスペースに変更
			$value['message'] = str_replace(array("\r", "\n"), ' ', $value['message']);

			// $csv_data .= '"' . $value['id'] . '","' . $value['view_name'] . '","' . $value['message'] . '","' . $value['post_date'] . "\"\n";
			$csv_data .= $value['id'] . ',' . $value['view_name'] . ',' . $value['message'] . ',' . $value['post_date'] . "\n";//CSVでダブルクォーテーションを付ける意味がわからないので書き換え
		}
	}

    // ファイルを出力	
	//Excelで開くようにSJISにする
    echo mb_convert_encoding($csv_data,"SJIS", "UTF-8");

} else {
	// リダイレクト
	header("Location: ./admin?board={$board_name}");
}

return;