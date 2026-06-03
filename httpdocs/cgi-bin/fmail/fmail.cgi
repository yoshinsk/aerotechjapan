#!/usr/bin/perl
# ↑加入しているプロバイダの｢perl｣言語が使用できる
# パスを指定します。
#########################################################
#　　　     　fmial.cgi（フォームメールCGI）
#　　　　　     by Anglers-net WebDesign
#               http://anglers-net.com/kyuukyuu
#
# - 本スクリプトの著作権は有限会社アングラーズネットが所
# 　有します。
# - ご利用はフリーですが、次の条件を必ずお守り下さい。
# - 著作に係る部分は削除しないこと
# - 有料、無料に関わらず再配布しないこと
# - 代行設置等を含めデザイン会社等による商用利用は認めま
# 　せん。
# - このスクリプトのご利用でトラブル等が発生しても責任を
#   求めないこと
#########################################################
########################################
$sendmail = '/usr/local/bin/sendmail';
# 　 ↑sendmailのパスを指定します
########################################
########################################
require './jcode.pl';
# 　 ↑jcode.plのパスを指定します。当CGIと同じ場所にあればこれでOK
########################################
########################################
#確認画面のテーブルの配色。変えたい場合は適当に変更します。
$bgcolor="WHITE";#ページの背景色
########################################
########################################
#海外サーバーの時差を指定します
$timediff = '0';
########################################
########################################
#不正アクセスチェック
#自分のページからの発信でないときにエラーを出すものです。
#設定する場合は、$accesscheck = 'on';として下さい。
#設定する場合は、@referers = ('yourdomain.com','www.yourdomain.com');
#この場合、('yourdomain.com','www.yourdomain.com')にドメイン名を入れて下さい。
$accesscheck = 'off';
@referers = ('yourdomain.com','www.yourdomain.com');
########################################
$mailheader = './mailheader.txt';
$mailfooter = './mailfooter.txt';
if (!open(DF,"$mailheader")){ &error; }
@DATAH = <DF>;
close(DF);
foreach $lineh (@DATAH) {
$header ="$header$lineh";
}
&jcode'convert(*header,'jis');
if (!open(DF,"$mailfooter")){ &error; }
@DATAF = <DF>;
close(DF);
foreach $linef (@DATAF) {
$footer ="$footer$linef";
}
&jcode'convert(*footer,'jis');
########################################

###############################################
#受付日時を取得

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time + ($timediff * 60 * 60));
if ($min < 10)  { $min = "0$min"; }
if ($hour < 10) { $hour = "0$hour";}
$year = $year+1900; 
$mon++;
$dt = "受付日：$year年$mon月$mday日";

###############################################
#各種初期設定

$mailname="no title"; 
$mailfrom="MailServer";
$errorh = "0";
$count=4;

########################################
#不正アクセスチェック
if ($accesscheck eq "on")  { &checkaccess;}

###############################################
#フォームデータ処理

if ($ENV{'REQUEST_METHOD'} eq "POST") { read(STDIN, $buffer,
$ENV{'CONTENT_LENGTH'}); }
else { $buffer = $ENV{'QUERY_STRING'}; }
@pairs = split(/&/,$buffer);
foreach $pair (@pairs)
{
($name, $value) = split(/=/, $pair);
$value =~ tr/+/ /;
$value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
$value =~ s/</&lt;/g;
$value =~ s/>/&gt;/g;
$value =~ s/"/&quot;/g;
# $value =~ s/\n//g;
$value =~ s/\*//g;
$value =~ s/^//g;
$name =~ tr/+/ /;
$name =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
&jcode'convert(*value,'sjis');
&jcode'convert(*name,'sjis');

 if($name ne "myaddress"){
  if($name ne "backurl"){
   if($name ne "mailtitle"){
    if($name ne "titlegif"){
     if($name ne "backgif"){
      if($name ne "conf"){
       if($name ne "cc"){
        if($name ne "csv"){
         @name1 = split(/\*/,$name);
          $name = @name1[0];
           if (@name1[2] eq "1") {
            $xx =$value;
            &trans;
            $value = $xx;
           }
           if (@name1[1] eq "1") {
            if ($value eq "") {
            @error[$count] = "$nameが記入<FONT SIZE=-1>（または選択）</FONT>されていません<BR>";
            $count = $count+1;
            $errorh = "1";
           }
          }
          if($name eq "E-mail"){
          $xx =$value;
          &trans;
          $value = $xx;
         }
        $value2 = "$name：$value";
        push(@NEW,$value2);
        $value3 = "<TR><TD ALIGN=RIGHT><INPUT TYPE=HIDDEN NAME=\"$name\" VALUE=\"$value\">$name\：</TD><TD>$value</TD></TR>";
        push(@NEW2,$value3);
        $value4 = "$value";
        push(@NEW3,$value4);
        } 
       } 
      }
     } 
    } 
   } 
  } 
 } 
 if($name eq "backurl"){$link=$value};
 if($name eq "myaddress"){$mail_address=$value};
 if($name eq "mailtitle"){$mailname=$value};
 if($name eq "titlegif"){$titlegif=$value};
 if($name eq "backgif"){$backgif=$value};
 if($name eq "conf"){$conf=$value};
 if($name eq "cc"){$cc=$value};
 if($name eq "csv"){$csv=$value};
 if($name eq "E-mail"){
 $emailh="1";
 $email=$value
 }
}

###############################################
#各種処理
if($titlegif ne ""){$titlegif2="<CENTER><IMG SRC=$titlegif></CENTER>"};
if($backgif ne ""){$backgif2="<BODY BGCOLOR=$bgcolor BACKGROUND=$backgif>";
}else{$backgif2="<BODY BGCOLOR=$bgcolor>";}

if($email ne ""){$mailfrom=$email};

@mail = split(/\,/,$mail_address);
$mail_address1=@mail[0];
$mail_address2=@mail[1];

@mail = split(/\@/,$mail_address1);
if ($mail_address eq "") {
@error[0] = "hiddenのメールアドレスが設定されていません<BR>";
$errorh = "1";}
if (@mail[0] ne "") {
 if (@mail[1] eq "") {
 @error[1] = "hiddenのメールアドレスが正しく設定されてません<BR>";
 $errorh = "1";}
}

@mail = split(/\@/,$mail_address2);
if (@mail[0] ne "") {
 if (@mail[1] eq "") {
 @error[1] = "hiddenのメールアドレスが正しく設定されてません<BR>";
 $errorh = "1";}
}

if($emailh eq "1"){
 @email = split(/\@/,$email);
  if (@email[0] ne "") {
   if (@email[1] eq "") {
   @error[3] = "E-mailが正しく入力されてません<BR>";
  $errorh = "1";}
 }
}

if ($errorh eq "1") { &error;}
if ($conf eq "1") { &conf;}

$new = join("\n",@NEW);
$new3 = join("\,",@NEW3);
if ($csv ne "1") {$new3="";}

###############################################
#メール送信
$message = <<END_OF_MESSAGE;
$new

$dt

$new3

END_OF_MESSAGE

&jcode'convert(*message,'jis');

if (!open(MAIL,"|$sendmail $mail_address1")) { &error; }
&jis("Subject: $mailname"); print MAIL "$msg\n";
print MAIL "To: $mail_address1\n";
print MAIL "From: $mailfrom\n";
print MAIL "\n";
print MAIL "$message";
close(MAIL);

###############################################
#メール送信2ケ所目
if($mail_address2 ne ""){
$message = <<END_OF_MESSAGE;
$new

$dt

$new3

END_OF_MESSAGE

&jcode'convert(*message,'jis');

if (!open(MAIL,"|$sendmail $mail_address2")) { &error; }
&jis("Subject: $mailname"); print MAIL "$msg\n";
print MAIL "To: $mail_address2\n";
print MAIL "From: $mailfrom\n";
print MAIL "\n";
print MAIL "$message";
close(MAIL);
}

###############################################
#CCメール送信
if($emailh eq "1"){
 if($cc eq "1"){

$message2 = <<END_OF_MESSAGE;
*******送信内容*******
$new

$dt
END_OF_MESSAGE

&jcode'convert(*message2,'jis');

if (!open(MAIL,"|$sendmail $email")) { &error; }
&jis("Subject:CC: $mailname"); print MAIL "$msg\n";
print MAIL "To: $email\n";
print MAIL "From: $mail_address1\n";
print MAIL "\n";
print MAIL "$header\n";
print MAIL "$message2";
print MAIL "$footer";
close(MAIL);
}
}

$new = join("\n",@NEW2);
###############################################
#送信後確認画面html
print <<EOM;
Content-type: text/html

<html><head><title>送信完了</title></head>
$backgif2
<CENTER>
$titlegif2
<BR><center>次の内容で送信が完了しました。</CENTER><BR>
<CENTER><TABLE BORDER=0 WIDTH=500>
$new
</TABLE></CENTER><BR>
EOM

if($emailh eq "1"){
 if($cc eq "1"){
 print"<center>また、この内容のコピーを<BR>$email<BR>あてに送信しましたのでご確認下さい。<BR>件名は「CC:$mailname」です。</center><BR><BR>\n";
 }
}

print <<EOM;
<CENTER><A HREF=\"$link\">戻る</A></CENTER>
</CENTER>
</body></html>

EOM

exit;

###############################################
#確認画面html
sub conf {
$new2 = join("\n",@NEW2);

print <<EOM;
Content-type: text/html

<html><head><title>確認です</title></head>
$backgif2
$titlegif2
<BR><CENTER>
<center>内容の確認です<BR>よろしければ下の「送信」ボタンを押して下さい。</CENTER>
<FORM ACTION="./fmail.cgi" METHOD=POST>
<INPUT TYPE=HIDDEN NAME=myaddress VALUE=\"$mail_address\">
<INPUT TYPE=HIDDEN NAME=backurl VALUE=\"$link\">
<INPUT TYPE=HIDDEN NAME=mailtitle VALUE=\"$mailname\">
<INPUT TYPE=HIDDEN NAME=titlegif VALUE=\"$titlegif\">
<INPUT TYPE=HIDDEN NAME=backgif VALUE=\"$backgif\">
<INPUT TYPE=HIDDEN NAME=cc VALUE=\"$cc\">
<INPUT TYPE=HIDDEN NAME=csv VALUE=\"$csv\">
<CENTER><TABLE BORDER=0 WIDTH=500>
$new2
</TABLE></CENTER>
<BR><BR>
<CENTER><INPUT TYPE=submit VALUE="送信する"></CENTER>
<BR>
<SCRIPT Language="JavaScript">
<!--
function PageBack(){
history.back();
}
//-->
</SCRIPT>
<FORM>
<CENTER><INPUT type="button" value="前のページに戻り入力しなおす"
onClick="PageBack()">
</CENTER></FORM></body></html>

EOM

exit;
}

###############################################
#エラー処理
sub error {
print <<EOM;
Content-type: text/html

<html><head>
<title>エラー</title></head>
$backgif2
$titlegif2
<CENTER><TABLE WIDTH=450 BORDER=0 BGCOLOR=$tablecolor2><TR><TD>
<BR><CENTER>
エラーです<BR><BR>
EOM

$n = 0;
while ($n <= $count ){
print"@error[$n] \n";
$n++;
}

print <<EOM;
</CENTER>
<BR>
<SCRIPT Language="JavaScript">
<!--
function PageBack(){
history.back();
}
//-->
</SCRIPT>
<FORM>
<CENTER><INPUT type="button" value="前のページに戻る"
onClick="PageBack()">
</CENTER></FORM><BR>
</TD></TR></TABLE></CENTER>
</body>
</html>
EOM

exit;
}

#不正アクセス処理
sub checkaccess {
 foreach $referer (@referers) {
  if ($ENV{'HTTP_REFERER'} =~ m|http?://([^/]*)$referer|i) {
  $accesserror=1;
  last;
  } 
 } 
 if ($accesserror != 1) { 
 @error[1] = "不正なアクセスです<BR>";
 &error
 }
}


sub trans{
 $from='[＠０１-９Ａ-Ｚａ-ｚ．＿－]';
 $to='[@01-9A-Za-z._-]';
 &jcode'convert(*xx, 'euc');
 &jcode'convert(*from, 'euc');
 &jcode'convert(*to, 'euc');
 &jcode'tr(*xx, $from, $to);
 &jcode'convert(*xx, 'sjis');
}

sub jis { $msg = $_[0]; &jcode'convert(*msg, 'jis'); }
