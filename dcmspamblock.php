<?php
/*
Plugin Name:D-Climbs's English Spam Block
Plugin URI:http://www.d-clibms.co.jp/soft/internet/spamblock/
Description:ホワイトリストキーワードのない英文コメント・トラックバックを拒否します
Version:1.1
Author:株式会社ディークライム
Author URI:http://www.d-clibms.co.jp/
*/

function dcmesb_main($v)
{
	$content = $v['comment_content'];

	// 漢字熟語＋助詞（ひらがな一文字）が含まれているか
	if(preg_match("/[\x{4E00}-\x{9FFF}]{2,}[あ-ん]/u", $content) == 0){
		$incase = get_option('dcmesb_incase');
		$wdata = strtok(get_option('dcmesb_whitelist'), " ");
		
		$spam = true;
		do{
			if($incase == true){
				if(stripos($content, $wdata) !== false){
					$spam = false; break;
				}
			}else{
				if(strpos($content, $wdata) !== false){
					$spam = false; break;
				}
			}
		}while($wdata = strtok(" "));
		
		
		// ホワイトリストデータが含まれていなければスパムと判断し抹消
		if($spam == true){
			$message = "スパム定義にあてはまりました。" .
				"もし、送ったコメントが問題ないのであれば、" .
				"ブログの管理者にお問い合わせください。";
			if ($v['comment_type'] == 'trackback'){
				trackback_response(1, $message);
				wp_die();
			}else{
				wp_die($message);
			}
			exit;
		}
	}
	
	return $v;
}

function dcmesb_setmenu()
{
	if (function_exists('add_options_page')) {
		add_options_page('D-Climbs\'s English Spam Block', 'DcmEngSpam', 8, basename(__FILE__), 'dcmesb_option');
	}		
}

function dcmesb_option()
{
	if(isset($_POST['update'])){
		$whitelist = $_POST['whitelist'];
		$incase = $_POST['incase'];
		update_option('dcmesb_whitelist', (string)$whitelist);
		update_option('dcmesb_incase', (bool)$incase);
		?>
		<div id="message" class="updated fade"><p><strong>更新が完了しました。</strong></p></div>
		<?php
	}else{
		$whitelist = get_option('dcmesb_whitelist');
		$incase = get_option('dcmesb_incase');
	}
?>
	<div class="wrap">
		<h2>D-Climbs's English Spam Block</h2>

		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">

		<fieldset class="options"> 
		<legend>ホワイトリストの設定</legend> 
		<p><em>英文のコメント・トラックバック本文に下記のキーワードが含まれていたときに限り、スパムとは見なさなくなります。キーワードは半角スペースで区切ってください。</em></p>
		<textarea name="whitelist" rows="4" cols="50"><?php echo $whitelist; ?></textarea>
		大文字・小文字の同一視<input type="checkbox" name="incase" value="true" <?php if($incase == true) echo "checked"; ?> />
		
		<div class="submit">
		<input type="submit" name="update" value="保存" />
		</div>
		</form>
	</div>
<?php
}

add_action('preprocess_comment', 'dcmesb_main', 1);
if (function_exists('akismet_init')) {
	remove_action('preprocess_comment', 'akismet_auto_check_comment', 1);
	add_action('preprocess_comment', 'akismet_auto_check_comment', 2);
}

add_action('admin_menu', 'dcmesb_setmenu');
?>