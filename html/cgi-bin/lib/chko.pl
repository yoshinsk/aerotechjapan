sub gettime(){
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
	@dayname=('“ú','Œ','‰Î','…','–Ø','‹à','“y');
	$mon++;
	$date="$monŒ $mday“ú ($dayname[$wday])";
	$time=sprintf("%02d:%02d:%02d",$hour,$min,$sec);
	return $date,$time;
}

sub henkan(){
	$comment=~s/&/&amp;/g;
	$comment=~s/</&lt;/g;
	$comment=~s/>/&gt;/g;
	$comment=~s/"/&quot;/g;
}
1;
