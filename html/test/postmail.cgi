#!/usr/bin/perl

$ver = 'PostMail v2.41';
#┌─────────────────────────────────
#│ [注意事項]
#│ 1. このスクリプトはフリーソフトです。このスクリプトを使用した
#│    いかなる損害に対して作者は一切の責任を負いません。
#│ 2. BlatJのインストールに関する質問はサポート対象外とさせて
#│    いただきます。
#│ 3. 送信フォームのHTMLページの作成に関しては、HTML文法の範疇
#│    となるため、サポート対象外となります。
#│ 4. それ以外の設置に関する質問はサポート掲示板にお願いいたし
#│    ます。直接メールによる質問はお受けいたしておりません。
#└─────────────────────────────────
#
# [ 送信フォーム (HTML) の記述例 ]
#
# ・タグの記述例 (1)
#   おなまえ <input type=text name="name" size=25>
#   → このフォームに「山田太郎」と入力して送信すると、
#      「name = 山田太郎」という形式で受信します
#
# ・タグの記述例 (2)
#   お好きな色 <input type=radio name="color" value="青">
#   → このラジオボックスにチェックして送信すると、
#      「color = 青」という形式で受信します
#
# ・タグの記述例 (3)
#   E-mail <input type=text name="email" size=25>
#   → name値に「email」という文字を使うとこれはメールアドレス
#      と認識し、アドレスの書式を簡易チェックします
#   → (○) abc@xxx.co.jp
#   → (×) abc.xxx.co.jp → 入力エラーとなります
#
# ・タグの記述例 (4)
#   E-mail <input type=text name="_email" size=25>
#   → name値の先頭に「アンダーバー 」を付けると、その入力値は
#     「入力必須」となります。
#      上記の例では、「メールアドレスは入力必須」となります。
#
# ・タグの記述例 (5)
#   <input type=checkbox name="cc" value="1" checked> メール控え必要
#   → メールの控えを CC で送信者へも返信します。
#      ただし、name="email" のフィールドへの入力が必須となります。
#
# ・name値への「全角文字」の使用は可能です
#  (例) <input type=radio name="年齢" value="20歳代">
#  → 上記のラジオボックスにチェックを入れて送信すると、
#     「年齢 = 20歳代」という書式で受け取ることができます。
#
# ・mimew.pl使用時、name値を「name」とするとこれを「送信者名」と認識
#   して送信元のメールアドレスを「送信者 <メールアドレス>」という
#   フォーマットに自動変換します。
#  (フォーム記述例)  <input type=text name="name">
#  (送信元アドレス)  太郎 <taro@email.xx.jp>
#
# ・コマンドタグ (1)
#   → 入力必須項目を強制指定する（半角スペースで複数指定可）
#   → ラジオボタン、チェックボックス対策
#   → name値を「need」、value値を「必須項目1 + 半角スペース +必須項目2 + 半角スペース ...」
#   (例) <input type=hidden name="need" value="名前 メールアドレス 性別">
#
#  [ 簡易チェック ]
#   http://～～/postmail.cgi?mode=check
#
#  [ 設置例 ]
#
#  public_html / index.html (トップページ等）
#       |
#       +-- postmail / postmail.cgi [705]
#                      jcode.pl     [604]
#                      mimew.pl     [604] ... 任意
#                      postmail.html

#-------------------------------------------------
#  ▼基本設定
#-------------------------------------------------

# 文字コード変換ライブラリ
$jcode = './jcode.pl';

# MIMEエンコードライブラリを使う場合（推奨）
#  → メールヘッダの全角文字をBASE64変換する機能
#  → mimew.plを指定
$mimew = './mimew.pl';

# メールソフトまでのパス
#  → sendmailの例 ：/usr/lib/sendmail
#  → BlatJの例    ：c:\blatj\blatj.exe
$mailprog = '/usr/lib/sendmail';

# 送信先メールアドレス
$mailto = 'yamaji@is-is.co.jp';

# 送信前確認
#  0 : no
#  1 : yes
$preview = 1;

# メールタイトル
$subject = 'フォームメール';

# スクリプトURL
$script = './postmail.cgi';

# 送信後の形態
#  0 : 完了メッセージを出す.
#  1 : 戻り先 ($back) へ自動ジャンプさせる.
$reload = 0;

# 送信後の戻り先
#  → http://から記述する
$back = './';

# 送信は method=POST 限定 (0=no 1=yes)
#  → セキュリティ対策
$postonly = 1;

# bodyタグ
$body = '<body bgcolor="#FFFFFF" text="#000000" link="#000FF" vlink="#800080">';

# プレビュー画面の枠の色
$tbl_col1 = "red";

# プレビュー画面の下地の色
$tbl_col2 = "#FFFFFF";

# プレビュー画面の項目下地の色
$tbl_col3 = "#FFE1E1";

# アラーム色
$alm_col = "#DD0000";

# ホスト取得方法
# 0 : gethostbyaddr関数を使わない
# 1 : gethostbyaddr関数を使う
$gethostbyaddr = 0;

#-------------------------------------------------
#  ▲設定完了
#-------------------------------------------------

# フォームデコード
require $jcode;
$ret = &decode;

# 基本処理
if (!$ret) { &error("不明な処理です"); }
elsif ($in{'mode'} eq "check") { &check; }

# POSTチェック
if ($postonly && !$postflag) { &error("不正なアクセスです"); }

# メールプログラムの種類チェック
if ($mailprog =~ /blat/i) { $pgType = 2; } else { $pgType = 1; }

# 必須入力チェック
if ($in{'need'}) {
	local(@tmp,@uniq,%seen);

	# needフィールドの値を必須配列に加える
	@tmp = split(/\s+/, $in{'need'});
	push(@need,@tmp);

	# 必須配列の重複要素を排除する
	%seen=();
	foreach (@need) {
		push(@uniq,$_) unless $seen{$_}++;
	}

	# 必須項目の入力値をチェックする
	foreach (@uniq) {

		# フィールドの値が投げられてこないもの（ラジオボタン等）
		if (!defined($in{$_})) {
			$check++;
			push(@key,$_);
			push(@err,$_);

		# 入力なしの場合
		} elsif ($in{$_} eq "") {
			$check++;
			push(@err,$_);
		}
	}
}

# 入力チェック確認画面
if ($check) { &err_check; }

# E-Mail書式チェック
if ($in{'cc'} && !$in{'email'}) {
	&error("写しが必要であればメールアドレスは必須です");
}
if ($in{'email'} =~ /\,/) {
	&error("メールアドレスにコンマ ( , ) が含まれています");
}
if ($in{'email'} && $in{'email'} !~ /[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
	&error("メールアドレスの書式が不正です");
}

# プレビュー
if ($preview && $in{'mode'} ne "send") {

	&header;
	print <<EOM;
<br><div align="center">
<h3>- 以下の内容でよろしければ送信ボタンを押して下さい -</h3>
<form action="$script" method="POST">
<input type="hidden" name="mode" value="send">
<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 width="80%" BORDER=0 class="fontsize_10"><TR>
<TD BGCOLOR="$tbl_col1">
<table border=0 cellspacing=1 cellpadding=7 width="100%" class="fontsize_10">
EOM

	foreach (@key) {
		next if ($bef eq $_);
		if ($_ eq "need" || $_ eq "cc") {
			print "<input type=hidden name=\"$_\" value=\"$in{$_}\">\n";
			next;
		}

		$in{$_} =~ s/\0/ /g;
		$in{$_} =~ s/\r\n/<br>/g;
		$in{$_} =~ s/\r/<br>/g;
		$in{$_} =~ s/\n/<br>/g;
		if ($in{$_} =~ /<br>$/) {
			while ($in{$_} =~ /<br>$/) { $in{$_} =~ s/<br>$//g; }
		}
		print "<tr><td bgcolor=\"$tbl_col3\" align=right width=\"25%\">$_</td>";
		print "<td bgcolor=\"$tbl_col2\" width=\"75%\">$in{$_}\n";
		print "<input type=hidden name=\"$_\" value=\"$in{$_}\"></td></tr>\n";

		$bef = $_;
	}

	print <<EOM;
</table>
</TD></TR></TABLE>
<p>
<input type="button" value="&lt;&lt; 前画面にもどる" onClick="history.back()">
&nbsp;&nbsp;&nbsp;
<input type=submit value="上記内容で送信 &gt;&gt;">
</form>
</div>
<!-- $ver -->
</body>
</html>
EOM
	exit;
}

# 時間・ホストを取得
($date,$dat2) = &get_time;
&get_host;

# blatj送信
if ($pgType == 2) {
	local($tmpfile,$bef,$param);

	# 一時ファイルを書き出し
	$tmpfile = "./$$\.tmp";
	open(OUT,">$tmpfile") || &error("Write Error: $tmpfile");
	print OUT "----------------------------------------------------------------------\n";
	print OUT "▼$title送信内容\n";
	print OUT "----------------------------------------------------------------------\n\n";

	foreach (@key) {
		next if ($_ eq "mode");
		next if ($_ eq "need");
		next if ($_ eq "cc");
		next if ($bef eq $_);

		$in{$_} =~ s/&lt;/</g;
		$in{$_} =~ s/&gt;/>/g;
		$in{$_} =~ s/&quot;/"/g;
		$in{$_} =~ s/&amp;/&/g;
		$in{$_} =~ s/\0/ /g;
		$in{$_} =~ s/<br>/\n/g;
		$in{$_} =~ s/\.\n/\. \n/g;

		# 添付ファイル拒否
		$in{$_} =~ s/Content-Disposition:\s*attachment;.*//ig;
		$in{$_} =~ s/Content-Transfer-Encoding:.*//ig;
		$in{$_} =~ s/Content-Type:\s*multipart\/mixed;\s*boundary=.*//ig;

		if ($in{$_} =~ /\n/) {
			print OUT "$_ = \n\n$in{$_}\n";
		} else {
			print OUT "$_ = $in{$_}\n";
		}

		$bef = $_;
	}

	print OUT "\n";
	print OUT "----------------------------------------------------------------------\n";
	print OUT "Date  : $date\n";
	print OUT "Host  : $host\n";
	print OUT "Agent : $ENV{'HTTP_USER_AGENT'}\n";
	print OUT "----------------------------------------------------------------------\n";
	close(OUT);

	# パラメータ
	$param = "$mailprog $tmpfile -t $mailto -s \"$subject\"";
	$param .= " -c $in{'email'}" if ($in{'cc'});

	# 送信処理
	open(MAIL,"| $param") || &error("メール送信失敗");
	close(MAIL);

	# 一時ファイル削除
	unlink($tmpfile);

# sendmail送信
} else {
	local($bef,$mbody,$email,$subject2);

	$mbody = <<EOM;
----------------------------------------------------------------------
▼$title送信内容
----------------------------------------------------------------------

EOM

	foreach (@key) {
		next if ($_ eq "mode");
		next if ($_ eq "need");
		next if ($_ eq "cc");
		next if ($bef eq $_);

		$in{$_} =~ s/&lt;/</g;
		$in{$_} =~ s/&gt;/>/g;
		$in{$_} =~ s/&quot;/\"/g;
		$in{$_} =~ s/&amp;/&/g;
		$in{$_} =~ s/\0/ /g;
		$in{$_} =~ s/<br>/\n/g;
		$in{$_} =~ s/\.\n/\. \n/g;

		# 添付ファイル拒否
		$in{$_} =~ s/Content-Disposition:\s*attachment;.*//ig;
		$in{$_} =~ s/Content-Transfer-Encoding:.*//ig;
		$in{$_} =~ s/Content-Type:\s*multipart\/mixed;\s*boundary=.*//ig;

		if ($in{$_} =~ /\n/) {
			$mbody .= "$_ = \n\n$in{$_}\n";
		} else {
			$mbody .= "$_ = $in{$_}\n";
		}
		$bef = $_;
	}

	# メールアドレスがない場合は送信先に置き換え
	if ($in{'email'} eq "") { $email = $mailto; }
	else { $email = $in{'email'}; }

	# MIMEエンコード
	if (-e $mimew) {
		require $mimew;
		$subject2 = &mimeencode($subject);
		if ($in{'name'}) {
			$from = &mimeencode("From: \"$in{'name'}\" <$email>");
		} else {
			$from = "From: $email";
		}
	} else {
		$subject2 = $subject;
		&jcode'convert(*subject2,'jis');

		$from = "From: $email";
	}

	# sendmail起動
	open(MAIL,"| $mailprog -t") || &error("メール送信失敗");
	print MAIL "To: $mailto\n";
	print MAIL $from, "\n";
	print MAIL "Cc: $email\n" if ($in{'cc'});
	print MAIL "Subject: $subject2\n";
	print MAIL "MIME-Version: 1.0\n";
	print MAIL "Content-type: text/plain; charset=ISO-2022-JP\n";
	print MAIL "Content-Transfer-Encoding: 7bit\n";
	print MAIL "Date: $dat2\n";
	print MAIL "X-Mailer: $ver\n\n";
	foreach ( split(/\n/, $mbody) ) {
		&jcode'convert(*_, 'jis' ,'sjis');
		print MAIL $_, "\n";
	}
	print MAIL "\n";
	print MAIL "----------------------------------------------------------------------\n";
	print MAIL "Date  : $date\n";
	print MAIL "Host  : $host\n";
	print MAIL "Agent : $ENV{'HTTP_USER_AGENT'}\n";
	print MAIL "----------------------------------------------------------------------\n";
	close(MAIL);
}

# リロード
if ($reload) {
	if ($ENV{'PERLXS'} eq "PerlIS") {
		print "HTTP/1.0 302 Temporary Redirection\r\n";
		print "Content-type: text/html\n";
	}
	print "Location: $back\n\n";
	exit;

# 完了メッセージ
} else {
	&header;
	print <<"EOM";
<div align="center">
<hr width=400>
<p><big><b class="fontsize_10">ありがとうございます.</b>
<p><b  class="fontsize_10">送信は正常に完了しました.</b></big>
<p><hr width=400>
<form>
<input type=button value="トップに戻る" onClick=window.open("$back","_top")>
</form>
<br><br>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  入力チェック
#-------------------------------------------------
sub err_check {
	local($f,$bef,$err);

	&header;
	print <<EOM;
<br><div align="center"class="fontsize_10">
入力内容に誤りがあります。
前画面に戻って正しく入力してください。
<p>
<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 width="80%" BORDER=0 class="fontsize_10"><TR>
<TD BGCOLOR="$tbl_col1">
<table border=0 cellspacing=1 cellpadding=7 width="100%" class="fontsize_10">
EOM

	foreach (@key) {
		next if ($_ eq "need");
		next if ($_ eq "cc");
		next if ($bef eq $_);

		print "<tr><td bgcolor=\"$tbl_col3\" align=right width=\"25%\">$_</td>";
		print "<td bgcolor=\"$tbl_col2\" width=\"75%\">";

		$f=0;
		foreach $err (@err) {
			if ($err eq $_) { $f++; last; }
		}
		if ($f) {
			print "<span style=\"color:$alm_col\">$_は入力必須です</span>";
		} else {
			$in{$_} =~ s/\r\n/<br>/g;
			$in{$_} =~ s/\r/<br>/g;
			$in{$_} =~ s/\n/<br>/g;
			$in{$_} =~ s/\0/ /g;

			print $in{$_};
		}

		print "</td></tr>\n";

		$bef = $_;
	}

	print <<EOM;
</table>
</TD></TR></TABLE>
<p>
<form>
<input type=button value="&lt;&lt; 前画面に戻る" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  フォームデコード
#-------------------------------------------------
sub decode {
	local($key,$val,$buf);
	undef(%in);

	if ($ENV{'REQUEST_METHOD'} eq "POST") {
		$postflag=1;
		read(STDIN, $buf, $ENV{'CONTENT_LENGTH'});
	} else {
		$postflag=0;
		$buf = $ENV{'QUERY_STRING'};
	}

	@key=(); @need=(); @err=(); $check=0;
	foreach ( split(/&/, $buf) ) {
		($key, $val) = split(/=/);
		$key =~ tr/+/ /;
		$key =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;
		$val =~ tr/+/ /;
		$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;

		&jcode'convert(*key, 'sjis');
		&jcode'convert(*val, 'sjis');

		# タグ排除
		$key =~ s/&/&amp;/g;
		$key =~ s/"/&quot;/g;
		$key =~ s/</&lt;/g;
		$key =~ s/>/&gt;/g;
		$val =~ s/&/&amp;/g;
		$val =~ s/"/&quot;/g;
		$val =~ s/</&lt;/g;
		$val =~ s/>/&gt;/g;

		# 必須入力項目
		if ($key =~ /^_(.+)/) {
			$key = $1;
			push(@need,$key);

			if ($val eq "") { $check++; push(@err,$key); }
		}

		$in{$key} .= "\0" if (defined($in{$key}));
		$in{$key} .= $val;

		push(@key,$key);
	}

	# 返り値
	if ($buf) { return (1); } else { return (0); }
}

#-------------------------------------------------
#  HTMLヘッダ
#-------------------------------------------------
sub header {
	$headflag=1;
	print "Content-type: text/html\n\n";
	print <<"EOM";
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<link href="style.css" rel="stylesheet" type="text/css">
<head>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=Shift_JIS">
<title>$subject</title></head>
$body
EOM
}

#-------------------------------------------------
#  エラー処理
#-------------------------------------------------
sub error {
	unlink($tmpfile) if (-e $tmpfile && $pgType == 2);

	&header if (!$headflag);
	print <<"EOM";
<div align="center">
<hr width=400>
<h3>ERROR !</h3>
<font color="#dd0000">$_[0]</font>
<p>
<hr width=400>
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  時間取得
#-------------------------------------------------
sub get_time {
	local($d1,$d2,@w,@m);

	$ENV{'TZ'} = "JST-9";
	local($sec,$min,$hour,$mday,$mon,$year,$wday) = localtime(time);
	@w = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
	@m = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

	# 日時のフォーマット
	$d1 = sprintf("%04d/%02d/%02d(%s) %02d:%02d",
			$year+1900,$mon+1,$mday,$w[$wday],$hour,$min);
	$d2 = sprintf("%s, %02d %s %04d %02d:%02d:%02d",
			$w[$wday],$mday,$m[$mon],$year+1900,$hour,$min,$sec) . " +0900";

	return ($d1,$d2);
}

#-------------------------------------------------
#  ホスト名取得
#-------------------------------------------------
sub get_host {
	$host = $ENV{'REMOTE_HOST'};
	$addr = $ENV{'REMOTE_ADDR'};

	if ($gethostbyaddr && ($host eq "" || $host eq $addr)) {
		$host = gethostbyaddr(pack("C4", split(/\./, $addr)), 2);
	}
	if ($host eq "") { $host = $addr; }
}

#-------------------------------------------------
#  チェックモード
#-------------------------------------------------
sub check {
	&header;
	print <<EOM;
<h3>Check Mode</h3>
<ul>
EOM

	# メールソフトチェック
	print "<li>メールソ\フトパス：";
	if (-e $mailprog) { print "OK\n"; }
	else { print "NG → $mailprog\n"; }

	# jcode.pl バージョンチェック
	print "<li>jcode.plバージョンチェック：";

	local($flag)=0;
	open(IN, $jcode) || &error("Open Error: $jcode");
	while (<IN>) {
		if ($_ =~ /jcode\.pl\,v (\d)\.(\d+)/) {
			$v1=$1; $v2=$2; $flag=1; last;
		}
	}
	close(IN);

	if ($v1 < 2 || $v2 < 13) {
		print "バージョンが低いようです。→ $v1.$v2\n";
	} else {
		print "バージョンOK (v $v1.$v2)\n";
	}

	print <<EOM;
</ul>
</body>
</html>
EOM
	exit;
}


__END__

