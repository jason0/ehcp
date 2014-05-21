<?php

#cya include once...
include_once("classapp.php");

class EroiApplication extends Application{
# so the only thing I need do here is re-define functions where necessary

	function updateWebstats(){
		global $skipUpdateWebstats;
		if($skipUpdateWebstats or $this->miscconfig['enablewebstats']=='') {
			# if you put webstats.sh in crontab
			echo "\nSkipping ".__FUNCTION__." because of config directive (\$skipUpdateWebstats) or enablewebstats is not checked in options.\n";
			return false;
		}

		$this->requireCommandLine(__FUNCTION__);
		$res=$this->query("select domainname,homedir from domains where status='$this->status_active' and homedir<>''");
		$str='';
		foreach($res as $dom){
			if ($this->miscconfig['enablecommonwebstats']=='') {
				passthru2("mkdir -p ".$dom['homedir']."/httpdocs/webstats/");
				$str.="webalizer -q -p -n www.".$dom['domainname']." -o ".$dom['homedir']."/httpdocs/webstats ".$dom['homedir']."/logs/access_log -R 100 TopReferrers -r ".$dom['domainname']." HideReferrer \n";
			}else {
				passthru2("mkdir -p " .$this->miscconfig["commonwebstatsdir"] ."/".$dom['domainname']);
				$str.="webalizer -q -p -n www.".$dom['domainname']
					. " -o "
					. $this->miscconfig['commonwebstatsdir']
					. "/"
					. $dom['domainname']
					. " "
					. $dom['homedir']
					. "/logs/access_log -R 100 TopReferrers -r "
					. $dom['domainname']." HideReferrer \n";

			}


		}
		echo $str;

		writeoutput2("/etc/ehcp/webstats.sh",$str,"w");
		passthru2("chmod a+x /etc/ehcp/webstats.sh");
	#	passthru2("/etc/ehcp/webstats.sh");
		echo "\nwrite webstats file to /etc/ehcp/webstats.sh complete... need to put this in crontab or run automatically.. \n";

	}

	function build_logrotate_conf($arr2,$host){
		if($this->debuglevel>0) print_r($arr2);
		$logrotate="# jason was here\n";
		foreach($arr2 as $dom) {
			$logrotate.=$dom['homedir']."/logs/access_log ".$dom['homedir']."/logs/error_log ";
		}
		#	$logrotate.=" /var/log/ehcp.log /var/log/apache_common_access_log {
		#}";
		$logrotate.=" /var/log/ehcp.log /var/log/apache_common_access_log {\n\tweekly\n\tmissingok\n\trotate 52\n\tcompress\n\tdelaycompress\n\tnotifempty\n\tcreate 644 root root\n\tsharedscripts\n\tpostrotate\n\t\t/etc/init.d/apache2 reload\n\tendscript\n
}";
		passthru2('mkdir -p '.$this->ehcpdir.'/etc/logrotate.d/');
		writeoutput($this->ehcpdir.'/etc/logrotate.d/ehcp',$logrotate,'w',True);

		$cmd="cp -vf ".$this->ehcpdir.'/etc/logrotate.d/ehcp /etc/logrotate.d/';
		if((!$host) or ($host=='localhost')) passthru2($cmd); # bu kısım bir fonksiyon yapılabilir.
		else $this->cmds[]=$cmd;	# multi server da kullanmak uzere
	}

	function showSimilarFunctions($func){
		# the text here may be read from a template
		$out1="Similar/Related $func Functions:";

		switch($func){
			case 'ftp'   : $out="<a href='?op=addftpuser'>Add New ftp</a>, <a href='?op=addftptothispaneluser'>Add ftp Under My ftp</a>, <a href='?op=addsubdirectorywithftp'>Add ftp in a subDirectory Under Domainname</a>, <a href='?op=addsubdomainwithftp'>Add subdomain with ftp</a>, <a href='?op=add_ftp_special'>Add ftp under /home/xxx (admin)</a>, <a href='net2ftp' target=_blank>WebFtp (Net2Ftp)</a>, <a href='?op=listallftpusers'>List All Ftp Users</a> ";break;
			case 'mysql' : $out="<a href='?op=domainop&amp;action=listdb'>List / Delete Mysql Db's</a>, <a href='?op=addmysqldb'>Add Mysql Db&amp;dbuser</a>, <a href='?op=addmysqldbtouser'>Add Mysql db to existing dbuser</a>, <a href='?op=dbadduser'>Add Mysql user to existing db</a>, <a href='/phpmyadmin' target=_blank>phpMyadmin</a>";break;
			case 'email' : $out="<a href='?op=listemailusers'>List Email Users / Change Passwords</a>, <a href='?op=addemailuser'>Add Email User</a>, Email forwardings: <a href='?op=emailforwardings'>List</a> - <a href='?op=addemailforwarding'>Add</a>, <a href='?op=bulkaddemail'>Bulk Add Email</a>, <a href='?op=editEmailUserAutoreply'>edit Email Autoreply</a> ,<a href='webmail' target=_blank>Webmail (Squirrelmail)</a>";break;
			case 'domain': $out="<a href='?op=addDomainToThisPaneluser'>Add Domain To my ftp user (Most Easy)</a> - <a href='?op=adddomaineasy'>Easy Add Domain (with separate ftpuser)</a> - <a href='?op=adddomain'>Normal Add Domain (Separate ftp&panel user)</a> - <a href='?op=bulkadddomain'>Bulk Add Domain</a> - <a href='?op=adddnsonlydomain'>Add dns-only hosting</a>- <a href='?op=adddnsonlydomainwithpaneluser'>Add dns-only hosting with separate paneluser</a><br>Different IP(in this server, not multiserver): <a href='?op=adddomaineasyip'>Easy Add Domain to different IP</a> - <a href='?op=setactiveserverip'>set Active webserver IP</a><br>List Domains: <a href='?op=listselectdomain'>short listing</a> - <a href='?op=listdomains'>long listing</a>";break;
			case 'redirect': $out="<a href='?op=editdomainaliases'>Edit Domain Aliases</a>";break;
			case 'options' : $out=	"
	<br><a href='?op=options&edit=1'>Edit/Change Options</a><br>
	<br><a href='?op=changemypass'>Change My Password</a>
	<br><a href='?op=listpanelusers'>List/Add Panelusers/Resellers</a>
	<br><a href='?op=dosyncdns'>Sync Dns</a>
	<br><a href='?op=dosyncdomains'>Sync Domains</a><br>
	<br><a href='?op=dosyncftp'>Sync Ftp (for non standard home dirs)</a><br>
	<hr><a href='?op=advancedsettings'>Advanced Settings</a><br><br>
	<br><a href='?op=dofixmailconfiguration'>Fix Mail Configuration<br>Fix ehcp Configuration</a> (This is used after changing ehcp mysql user pass, or if you upgraded from a previous version, in some cases)<br>
	<!-- <br><a href='?op=dofixapacheconfigssl'>Fix apache Configuration with ssl</a> -->
	<br><a href='?op=dofixapacheconfignonssl'>Fix apache Configuration without ssl</a>
	<br>
	<hr>
	<a href='?op=listservers'>List/Add Servers/ IP's</a><br>
	<hr>

	Experimental:
	<br><a href='?op=donewsyncdns'>New Sync Dns - Multiserver</a>
	<br><a href='?op=donewsyncdomains'>New Sync Domains - Multiserver</a><br>
	<br><a href='?op=multiserver_add_domain'>Multiserver Add Domain</a>
	<hr>

	";break;
			case 'customhttpdns': $out="Custom Http: <a href='?op=customhttp'>List</a> - <a href='?op=addcustomhttp'>Add</a>, Custom dns: <a href='?op=customdns'>List</a> - <a href='?op=addcustomdns'>Add</a>";break;
			case 'subdomainsDirs': $out="SubDomains: <a href='?op=subdomains'>List</a> - <a href='?op=addsubdomain'>Add</a> - <a href='?op=addsubdomainwithftp'>Add subdomain with ftp</a> - <a href='?op=addsubdirectorywithftp'>Add subdirectory with ftp (Under domainname)</a>";break;
			case 'HttpDnsTemplatesAliases': $out="<a href='?op=editdnstemplate'>Edit dns template for this domain </a> - <a href='?op=editapachetemplate'>Edit apache template for this domain </a> - <a href='?op=editdomainaliases'>Edit Aliases for this domain </a>";break;
			case 'panelusers': $out="<a href='?op=listpanelusers'>List All Panelusers/Clients</a>, <a href='?op=resellers'>List Resellers</a>, <a href='?op=addpaneluser'>Add Paneluser/Client/Reseller</a>";break;
			case 'server':$out="<a href='?op=listservers'>List Servers/IP's</a> - <a href='?op=addserver'>Add Server</a> - <a href='?op=addiptothisserver'>Add ip to this server</a> - <a href='?op=setactiveserverip'>set Active webserver IP</a>";break;
			case 'backup':$out="<a href='?op=dobackup'>Backup</a> - <a href='?op=dorestore'>Restore</a> - <a href='?op=listbackups'>List Backups</a>";break;
			case 'vps': $out="<a href='?op=vps'>VPS Home</a> - <a href='?op=add_vps'>Add new VPS</a> - <a href='?op=settings&group=vps'>VPS Settings</a> - <a href='?op=vps&op2=other'>Other Vps Ops</a>";break;

			default	 : $out="(internal ehcp error) This similar function is not defined in ".__FUNCTION__." : ($func)"; $out1='';break;
		}

		$this->output.="<br><br>$out1".$out."<br>";
	}

	function options(){
		$this->requireAdmin();

		global $edit,$_insert,$dnsip;
		$this->getVariable(array('edit','_insert','dnsip','localip'));
		#echo print_r2($this->miscconfig);

		# new style: options as an array, so, easy addition of new options..
		$optionlist=array(
		array('updatehostsfile','checkbox','lefttext'=>'This machine is used for Desktop access too (Update hosts file with domains)','default'=>'Yes','checked'=>$this->miscconfig['updatehostsfile']),
		array('localip','lefttext'=>'Local ip of server','default'=>$this->miscconfig['localip']),
		array('dnsip','lefttext'=>'dnsip (outside/real/static ip of server)','default'=>$this->miscconfig['dnsip']),
		array('dnsipv6','lefttext'=>'dnsip V6(outside/real/static V6 ip of server)','default'=>$this->miscconfig['dnsipv6'],'righttext'=>'Leave empty to disable (experimental even if enabled)'),
		array('updatednsipfromweb','checkbox','lefttext'=>'Do you use dynamic ip/dns?','righttext'=>'Check this if your server is behind a dynamic IP','default'=>'Yes','checked'=>$this->miscconfig['updatednsipfromweb']),
		array('banner','textarea','default'=>$this->miscconfig['banner']),
		array('adminemail','lefttext'=>'Admin Email','default'=>$this->miscconfig['adminemail']),
		array('defaulttemplate','default'=>$this->miscconfig['defaulttemplate']),
		array('defaultlanguage','default'=>$this->defaultlanguage),
		array('messagetonewuser','textarea','default'=>$this->miscconfig['messagetonewuser']),
		array('disableeditdnstemplate','checkbox','lefttext'=>'Disable Custom http for non-admins','default'=>'Yes','checked'=>$this->miscconfig['disableeditdnstemplate'],'righttext'=>'This is a security measure to disable non-experienced users to break configs'),
		array('disableeditapachetemplate','checkbox','lefttext'=>'Disable Custom dns for non-admins','default'=>'Yes','checked'=>$this->miscconfig['disableeditapachetemplate'],'righttext'=>'This is a security measure to disable non-experienced users to break configs'),
		array('turnoffoverquotadomains','checkbox','lefttext'=>'Turn off over quota domains','default'=>'Yes','checked'=>$this->miscconfig['turnoffoverquotadomains']),
		array('quotaupdateinterval','default'=>$this->miscconfig['quotaupdateinterval'],'righttext'=>'interval in hours'),
		array('userscansignup','checkbox','default'=>'Yes','checked'=>$this->miscconfig['userscansignup'],'righttext'=>'disabled by default, can users sign up for domains/ftp? (you should approve/reject them in short time)'),
		array('enablewebstats','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablewebstats'],'righttext'=>'enabled by default, this can use some of server resources, so, disabling it may help some slow servers to speed up'),
		array('enablecommonwebstats','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablecommonwebstats'],'righttext'=>'This will allow webstats to be located somewhere else accessible by the filesystem'),
		array('commonwebstatsdir','textarea','default'=>$this->miscconfig['commonwebstatsdir']),
		array('enablewildcarddomain','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablewildcarddomain'],'righttext'=>'do you want xxxx.yourdomain.com to show your domain homepage? disabled by default, and shows server home, which is default index, ehcp home.')

		#array('singleserverip','default'=>$this->miscconfig['singleserverip'])

		);



		if($_insert){
			$this->requireNoDemo();
			$old_webserver_type=$this->miscconfig['webservertype']."-".$this->miscconfig['webservermode'];
			if($old_webserver_type=='') $old_webserver_type='apache2-nonssl';

			$this->output.="Updating configuration...";
			$this->validate_ip_address($dnsip);

			foreach($optionlist as $option) {
				global $$option[0]; # make it global to be able to read in getVariable function..may be we need to fix the global thing..
				$this->getVariable($option[0]);
				$this->setConfigValue($option[0],${$option[0]});
			}

			# options that use longvalue:
			$this->setConfigValue("banner","",'value');# delete short value for banner, if there is any.. because longvalue is used for banner.
			$this->setConfigValue("banner",$banner,'longvalue');

			# operations that needs daemon or other settings.

			if($dnsip<>$this->miscconfig['dnsip']){ # fix all dnsip related config if dnsip is changed...
				$this->addDaemonOp("fixmailconfiguration",'','','','fix mail configuration'); # fixes postfix configuration, hope this works..yes, works...
			}

			if($defaultlanguage) { # update for current session too..
				$_SESSION['currentlanguage']='';
				$this->defaultlanguage=$this->currentlanguage=$defaultlanguage;
			}

			# load latest config again in this session.
			$this->loadConfigWithDaemon(); # loads config for this session, to show below..
			if($updatehostsfile<>'')  $this->addDaemonOp("syncdomains",'','','','sync domains-update hostsfile'); # updateHostsFile degistiginden dolayi, syncdomains gerekiyor..

			$this->output.="..update complete.";

		} elseif ($edit) {
			$optionlist[]=array('op','default'=>__FUNCTION__,'type'=>'hidden');
			$this->output.="<h2>Options:</h2><br>".inputform5($optionlist);

		} else {
			$this->output.="<h2>Options:</h2><br>".print_r3($this->miscconfig,"$this->th Option Name </th>$this->th Option Value </th>");
		}

		$this->showSimilarFunctions('options');
		$this->debugecho(print_r2($this->miscconfig),3,false);
	}

	function serverStatus(){
		$this->requireAdmin();
		#-------------- deconectat edit ---------------------------------------------------------
		#  ehcpdeveloper note: in fact, these html should be abstracted from source. left as of now.

		$this->output.="<div class='footer'>(It is normal that only one of apache2,nginx.. etc. webservers are running)<br><table> ";
		$this->check_program_service('apache2','dostartapache2','dostopapache2','dorestartapache2');
		#$this->check_program_service('nginx','dostartnginx','dostopnginx','dorestartnginx');
		$this->check_program_service('mysqld','dostartmysqld','dostopmysqld','dorestartmysqld');
		$this->check_program_service('vsftpd','dostartvsftpd','dostopvsftpd','dorestartvsftpd');
		$this->check_program_service('bind','dostartbind','dostopbind','dorestartbind');
		$this->check_program_service('postfix','dostartpostfix','dostoppostfix','dorestartpostfix');
		$this->output.="</table></div> ";

		$systemStatus=$this->executeProg3($this->ehcpdir."/misc/serverstatus.sh"); #moved the bash script in a separate file; this way it will be easyer to edit.

		$this->output.="<div class=\"footer\"><pre>".$systemStatus."</pre></div>";
		#-------------- end deconectat edit -----------------------------------------------------


		$topoutput=$this->executeProg3("top -b -n 1 | head -40");
		$this->output.="<hr><div align=left>Top output: <br> <pre>$topoutput</pre></div>";

		$topoutput=$this->executeProg3("tail -200 /var/log/syslog");
		$this->output.="<hr><div align=left>Syslog (to see this, you must chmod a+r /var/log/syslog on server console, <a target=_blank href='?op=adjust_system'>adjust system for this</a>): <br> <pre>$topoutput</pre></div>";

		return True;
	}

	function syncDomains($file='') {

		$this->requireCommandLine(__FUNCTION__);

		$success=parent::syncDomains($file='');

		# now we build the backups configuration, using hints from the actual ehcp backup functions
		# get backupdirname

		$backupdirname=$this->miscconfig['commonbackupconfdir'];
		$systemhostname=$this->misconfig['systemhostname'];
		$customer_template_filename=$backupdirname . "/customertemplate";

		$customer_template_file=file_get_contents($customer_template_filename);


		# outputfilename will be of the form <ftpusername>.conf
		#$file=;

		$filt=andle($this->activefilt,"(serverip is null or serverip='') and homedir<>''"); # exclude where serverip is set, that is, for remote dns hosted only domains..
		$arr=$this->getDomains($filt);
		if($this->debuglevel>0) print_r($arr);


		$arr2=array();


		foreach($arr as $dom) {
			#reset template
			$customer_template=$customer_template_file;
			$domainname=$dom['domainname'];
			$homedir=$dom['homedir'];
			$databases="";
			# get database info
			$dbs=$this->query("select * from mysqldb where domainname like '$domainname'");

			foreach($dbs as $line) {
				$databases .= $line['dbname'] . " "; #note space at the end!
			}

			$mysqlusers=$this->query("select * from mysqlusers where domainname like '$domainname' ");

			# get the ftpusername:
			$ftpaccounts=$this->query("select * from ftpaccounts where domainname like '$domainname' ");
			$temp=$ftpaccounts[0];
			$ftpuser=$temp['ftpusername'];
			$customer_configfile=$backupdirname . "/" . $systemhostname . "/customertest/" . $ftpuser . ".conf";

			$customer_template=str_replace(array('eroi_ftpuser','eroi_dblist','eroi_domain','eroi_homedir'),array($ftpuser,$databases,$domainname,$homedir ),$customer_template);

			#use writeoutput2 to write config file!
			$res=writeoutput2($customer_configfile, $customer_template, 'w', false );


		}
		return $success;
	}

}