<?php
  require 'lib/function.php';
  if($id){
    $forum=mysql_fetch_array(mysql_query("SELECT title,minpower,numthreads FROM forums WHERE id=$id"));
    $threadcount=$forum[numthreads];
    $postread=readpostread($loguserid);
  }elseif($user){
    $user1=mysql_fetch_array(mysql_query("SELECT name,sex,powerlevel FROM users WHERE id=$user"));
    $forum[title]="Threads by $user1[name]";
  }elseif($fav or ($act and $thread)){
    $forum[title]='Favorites';
  }
  $windowtitle="$boardname -- $forum[title]";
  require 'lib/layout.php';
  if($act=='add'){
    $t=mysql_fetch_array(mysql_query("SELECT title,forum FROM threads WHERE id=$thread"));
    $f=mysql_fetch_array(mysql_query("SELECT minpower FROM forums WHERE id=$t[forum]"));
    mysql_query("DELETE FROM favorites WHERE user=$loguserid AND thread=$thread");
    if($f[minpower]<1 or $f[minpower]<=$power) mysql_query("INSERT INTO favorites (user,thread) VALUES ($loguserid,$thread)");
    print "$header<br>$tblstart$tccell1>\"$t[title]\" has been added to your favorites.<br>".redirect("forum.php?id=$t[forum]",'return to the forum',0).$tblend.$footer;
    printtimedif($startingtime);
    die();
  }elseif($act=='rem'){
    $t=mysql_fetch_array(mysql_query("SELECT title,forum FROM threads WHERE id=$thread"));
    mysql_query("DELETE FROM favorites WHERE user=$loguserid AND thread=$thread");
    print "$header<br>$tblstart$tccell1>\"$t[title]\" has been removed from your favorites.<br>".redirect("forum.php?id=$t[forum]",'return to the forum',0).$tblend.$footer;
    printtimedif($startingtime);
    die();
  }
  if($id) $fonline=fonlineusers($id);
  $hotcount=mysql_result(mysql_query('SELECT hotcount FROM misc'),0,0);
  if($log && $id){
    $headlinks.=" | <a href=index.php?action=markforumread&forumid=$id>Mark forum read</a>";
    $header=makeheader($header1,$headlinks,$header2);
  }
  if(!$ppp) $ppp=(!$log?20:$loguser[postsperpage]);
  if(!$tpp) $tpp=(!$log?50:$loguser[threadsperpage]);
  if($id) $newpost="<td align=right class=fonts><a href=newthread.php?poll=1&id=$id>$newpollpic</a> | <a href=newthread.php?id=$id>$newthreadpic</a>";
  print "
	$header
	$tblstart$tccell1s>$fonline$tblend
	<table width=100%><td align=left>$fonttag<a href=index.php>$boardname</a> - $forum[title]</td>
	$newpost
	</table>
	$tblstart
  ";
  if($forum[minpower]>0 and ($log and $power<$forum[minpower]))
    print "
	$tccell1>Couldn't enter this restricted forum, as you don't have access to it.<br>
	".redirect('index.php','return to the board',0);
  elseif($forum[minpower]>0 and !$log)
    print "
	$tccell1>Couldn't enter this restricted forum, as you are not logged in.<br>
	".redirect('login.php','log in (then try again)',0);
  else{
    if($id){
	$anncs=mysql_query('SELECT user,date,announcements.title,name,sex,powerlevel FROM announcements,users WHERE forum=0 AND user=users.id ORDER BY date DESC LIMIT 1');
	if($annc=mysql_fetch_array($anncs)){
	  $namecolor=getnamecolor($annc[sex],$annc[powerlevel]);
	  print "
	    <td colspan=7 class='tbl tdbgh center fonts'>Announcements<tr>
	    <td colspan=7 class='tbl tdbg1 font'><a href=announcement.php>$annc[title]</a> -- Posted by <a href=profile.php?id=$annc[user]><font $namecolor>$annc[name]</font></a> on ".date($dateformat,$annc[date]+$tzoff)."<tr>
	  ";
	}
	$anncs=mysql_query("SELECT user,date,announcements.title,name,sex,powerlevel FROM announcements,users WHERE forum=$id AND user=users.id ORDER BY date DESC LIMIT 1");
	if($annc=mysql_fetch_array($anncs)){
	  $namecolor=getnamecolor($annc[sex],$annc[powerlevel]);
	  print "
	    $tccellhs colspan=7>Forum announcements<tr>
	    $tccell1l colspan=7><a href=announcement.php?f=$id>$annc[title]</a> -- Posted by <a href=profile.php?id=$annc[user]><font $namecolor>$annc[name]</font></a> on ".date($dateformat,$annc[date]+$tzoff)."<tr>
	  ";
	}
    }
    print "
	$tccellh>&nbsp</td>
	$tccellh colspan=2> Thread</td>
	$tccellh>Started by</td>
	$tccellh width=60> Replies</td>
	$tccellh width=60> Views</td>
	$tccellh width=150> Last post<tr>
    ";
    $min=$page*$tpp;
    if($id) $threads=mysql_query("SELECT t.*,u1.name AS name1,u1.sex AS sex1,u1.powerlevel AS power1,u2.name AS name2,u2.sex AS sex2,u2.powerlevel AS power2 FROM threads t,users u1,users u2 WHERE forum=$id AND u1.id=t.user AND u2.id=t.lastposter ORDER BY sticky DESC,lastpostdate DESC LIMIT $min,$tpp");
    elseif($user){
	$threadcount=mysql_result(mysql_query("SELECT COUNT(*) FROM threads where user=$user"),0,0);
	$threads=mysql_query("SELECT threads.*,'".addslashes($user1[name])."' AS name1,$user1[sex] AS sex1,$user1[powerlevel] AS power1,name AS name2,sex AS sex2,powerlevel AS power2,minpower FROM threads,users,forums WHERE user=$user AND users.id=threads.lastposter AND forums.id=forum ORDER BY sticky DESC,lastpostdate DESC LIMIT $min,$tpp");
    }elseif($fav){
	if(!$u or !$isadmin) $u=$loguserid;
	$threadcount=mysql_result(mysql_query("SELECT COUNT(*) FROM favorites where user=$u"),0,0);
	$threads=mysql_query("SELECT threads.*,u1.name AS name1,u1.sex AS sex1,u1.powerlevel AS power1,u2.name AS name2,u2.sex AS sex2,u2.powerlevel AS power2,minpower FROM threads,users AS u1,users AS u2,forums,favorites WHERE u1.id=threads.user AND u2.id=threads.lastposter AND favorites.thread=threads.id AND favorites.user=$u AND forums.id=forum ORDER BY sticky DESC,lastpostdate DESC LIMIT $min,$tpp");
    }
    if($threadcount>$tpp){
	$query=($id?"id=$id":"user=$user");
	$pagelinks2=$smallfont."Pages:";
	for($k=0;$k<($threadcount/$tpp);$k++)
	  $pagelinks2.=($k!=$page?" <a href=forum.php?$query&page=$k>".($k+1).'</a>':' '.($k+1));
    }
    $sticklast=0;
    for($i=1;$thread=mysql_fetch_array($threads);$i++){
	if($sticklast and !$thread[sticky]) print "<tr><td colspan=7 bgcolor=$tableborder>";
	$sticklast=$thread[sticky];
	$hot=0;
	if($thread[replies]>=$hotcount and $hotcount) $hot=1;
	if(($thread[lastpostdate]>$postread[$id] and $log and $id) or ($thread[lastpostdate]>ctime()-3600 and (!$log or !$id))){
	  $new='<img src=images/new.gif>';
	  if($hot) $new='<img src=images/hotnew.gif>';
	}else{
	  $new='&nbsp;';
	  if($hot) $new='<img src=images/hot.gif>';
	}
	if($thread[closed]) $new='<img src=images/off.gif>';
	if($hot and $thread[closed]) $new='<img src=images/hotoff.gif>';
	$posticon="<img height=15 src='$thread[icon]'>";
	$pagelinks='';
	if($thread[replies]>=$ppp){
	  $pagelinks=$smallfont.'(Pages:';
	  for($k=0;$k<(($thread[replies]+1)/$ppp);$k++) $pagelinks.=" <a href=thread.php?id=$thread[id]&page=$k>".($k+1).'</a>';
	  $pagelinks.=')';
	}
	$thread[title]=str_replace('<','&lt',$thread[title]);
	$threadtitle="<a href=thread.php?id=$thread[id]>$thread[title]</a>";
	$threadtitle=($thread[sticky]?'Sticky'.($thread[poll]?' poll':'').': ':($thread[poll]?'Poll: ':''))
			.(($thread[sticky] or $thread[poll])?"<i>$threadtitle</i>":$threadtitle);
	if(!$thread[icon]) $posticon='&nbsp;';
	if($i>1) print '<tr>';
	$namecolor1=getnamecolor($thread[sex1],$thread[power1]);
	$namecolor2=getnamecolor($thread[sex2],$thread[power2]);
	if((($user or $fav) && ($thread[minpower]<1 or $thread[minpower]<=$power)) or $id){
	  print "
	    $tccell1>$new</td>
	    $tccell2>$posticon</td>
	    $tccell2l>$threadtitle $pagelinks</td>
	    $tccell2><a href=profile.php?id=$thread[user]><font $namecolor1>$thread[name1]</td>
	    $tccell1>$thread[replies]</td>
	    $tccell1>$thread[views]</td>
	    $tccell2>".date($dateformat,$thread[lastpostdate]+$tzoff)."$smallfont<br>by <a href=profile.php?id=$thread[lastposter]><font $namecolor2>$thread[name2]</font></a></font></td>";
	}else print "$tccell2s colspan=7>(restricted)";
    }
  }
  print "$tblend$pagelinks2<br>".doforumlist($id).$footer;
  printtimedif($startingtime);
?>