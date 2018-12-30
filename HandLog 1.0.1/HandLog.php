<?php
/*  Name: HandLog
	Version: 1.0.1
	Description: PHP class for fast creation variative logs
	GitHub: https://github.com/MGGFLOW/HandLog
	
	Website: http://mggflow.in
	Contact: mggflow@outlook.com
	
	Copyright (c) 2018, MGGFLOW
	All rights reserved.
*/

	class HandLog{
		public $data = [];
		public $settings = [
								'note_string' => '[#time#] >> #string#',
								'string_separator' => "\r\n",
								'time_template' => "d-m-Y H:i:s",
								'event_start_string' => ['[#time#]---> Event [ #event# ] started',1],
								'event_end_string' => ['[#time#]---> Event [ #event# ] ended. Spent time: #stime# sec',1],
								'log_start_string' => ['[#time#]---> Log [ #log# ] started',1],
								'log_end_string' => ['[#time#]---> Log [ #log# ] ended. Spent time: #stime# sec',1],
								'select_mode' => [0,1,2],
								'default_event' => 'Main',
								'default_log' => 'Default',
								'echo_mode' => 2,
								'log_order' => 0,
								'filename' => '#log#_lastLog.txt',
								'refile_time_template' => "d.m.Y_H-i-s",
								're_filename' => 'logsave_#log#_#time#.txt',
								'file_size_limit' => 5*1024*1024,
								'dir' => 'Data/Logs',
								'rewrite_mode' => 1,
								'line_break_html' => 1,
								'datum_filename' => 'handlog_datum.txt'
							];
											
		public function __construct($logname = '#'){
			if($logname=='#'){
				$logname = $this->settings['default_log'];
			}else{
				$this->settings['default_log'] = $logname;
			}
			
			$this->new_log($logname);
		}
		
		public function new_log($logname){
			$timestart = number_format(microtime(true),8,'.','');
			$this->data[$logname] = ['meta'=>['timestart'=>$timestart],'events'=>[],'notes'=>[]];
		}
		
		public function new_event($eventname,$logname = '#'){
			$timestart = number_format(microtime(true),8,'.','');
			if($logname=='#'){
				$logname = $this->settings['default_log'];
			}
			
			$ind = count($this->data[$logname]['events']);
			$this->data[$logname]['events'][$eventname] = ['timestart'=>$timestart,'ind'=>$ind];
		}
		
		private function action_fix($action){
			for($i=2;$i<4;$i++){
				if(isset($action[$i]) and $action[$i]=='#' or !isset($action[$i])){
					switch($i){
						case 2:
							$action[2] = $this->settings['default_event'];
							break;
						case 3:
							$action[3] = $this->settings['default_log'];
							break;
					}
				}
			}
			return $action;
		}
		
		public function new_note(array $action){
			$timeadd = number_format(microtime(true),8,'.','');
			$action = $this->action_fix($action);
			
			switch(isset($this->data[$action[3]])){
				default:
					$this->new_log($action[3]);
				case true:
					if(!isset($this->data[$action[3]]['events'][$action[2]])){
						$this->new_event($action[2],$action[3]);
					}
				break;
			}
			
			$ind = count($this->data[$action[3]]['notes']);
			$this->data[$action[3]]['notes'][] = [$timeadd,$action[0],$action[1],$this->data[$action[3]]['events'][$action[2]]['ind']];
			
			if($action[1]==0){
				if(!isset($this->data[$action[3]]['events'][$action[2]]['firstfalse'])){
					$this->data[$action[3]]['events'][$action[2]]['firstfalse'] = $ind;
				}
			}elseif($action[1]==1){
				$this->data[$action[3]]['events'][$action[2]]['lasttrue'] = $ind;
			}
			
			if(!isset($this->data[$action[3]]['events'][$action[2]]['first'])){
				$this->data[$action[3]]['events'][$action[2]]['first'] = $ind;
			}
			$this->data[$action[3]]['events'][$action[2]]['last'] = $ind;
		}
		
		public function note2string($note){
			$time = date($this->settings['time_template'],floor($note[0]));
			$outstring = str_replace(['#time#','#string#'],[$time,$note[1]],$this->settings['note_string']);
			return $outstring;
		}
		
		public function log_borders($logname,$track){
			$timestart = $this->data[$logname]['meta']['timestart'];
			if($track==1){
				$lastnoteind = count($this->data[$logname]['notes'])-1;
				$endtime = $this->data[$logname]['notes'][$lastnoteind][0];
				$spenttime = number_format(abs($endtime-$timestart),8,'.','');
				$endtime = date($this->settings['time_template'],floor($endtime));
				$outstring = str_replace(['#time#','#log#','#stime#'],[$endtime,$logname,$spenttime],$this->settings['log_end_string'][0]);
			}else{
				$timestart = date($this->settings['time_template'],floor($timestart));
				$outstring = str_replace(['#time#','#log#'],[$timestart,$logname],$this->settings['log_start_string'][0]);
			}
			
			return $outstring;
		}
		
		public function event_borders($logname,$event,$track){
			$timestart = $this->data[$logname]['events'][$event]['timestart'];
			if($track==1){
				$lastnoteind = $this->data[$logname]['events'][$event]['last'];
				$endtime = $this->data[$logname]['notes'][$lastnoteind][0];
				$spenttime = number_format(abs($endtime-$timestart),8,'.','');
				$endtime = date($this->settings['time_template'],floor($endtime));
				$outstring = str_replace(['#time#','#event#','#stime#'],[$endtime,$event,$spenttime],$this->settings['event_end_string'][0]);
			}else{
				$timestart = date($this->settings['time_template'],floor($timestart));
				$outstring = str_replace(['#time#','#event#'],[$timestart,$event],$this->settings['event_start_string'][0]);
			}
			
			return $outstring;
		}
		
		public function log_filename($logname,$track = 0){
			if($track){
				$time = date($this->settings['refile_time_template'],time());
				$outstring = str_replace(['#time#','#log#'],[$time,$logname],$this->settings['re_filename']);
			}else{
				$outstring = str_replace(['#log#'],[$logname],$this->settings['filename']);
			}
			
			return($outstring);
		}
	
		private function notes_selector($logname = '#',$events = '#'){
			if($logname=='#'){
				$logname = $this->settings['default_log'];
			}
			$eventskeys = array_keys($this->data[$logname]['events']);
			$eventsflag = is_array($events);
			$eventendflag = $this->settings['event_end_string'][1];
			$eventstartflag = $this->settings['event_start_string'][1];
			$mode = $this->settings['select_mode'];
			
			$out = [];
			
			foreach($this->data[$logname]['notes'] as $num=>$note){
				
				$notetype = $note[2];
				$eventkey = $note[3];
				
				switch($eventsflag){
					case true:
						if(!in_array($eventskeys[$eventkey],$events)){
							break;
						}
					default:
						if($num==$this->data[$logname]['events'][$eventskeys[$eventkey]]['first'] and $eventstartflag){
							$out[] = [$eventkey,0];
						}
						
						if(is_array($mode)){
							if(in_array($notetype,$mode)){
								$out[] = $num;
							}
						}else{
							switch($mode){
								case 0:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue']) ? $lasttrue = $this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue'] : $lasttrue = -1;
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1;
									if($notetype==2 or $num==$lasttrue or $num==$firstfalse){
										$out[] = $num;
									}
									break;
								case 1:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1;
									if($notetype==2 or $notetype==1 or $num==$firstfalse){
										$out[] = $num;
									}
									break;
								case 2:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue']) ? $lasttrue = $this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue'] : $lasttrue = -1;
									if($notetype==2 or $notetype==0 or $num==$lasttrue){
										$out[] = $num;
									}
									break;
								case 3:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1;
									if($notetype==2 or $num==$firstfalse){
										$out[] = $num;
									}elseif($notetype==1 and ($firstfalse==-1 or $num<$firstfalse)){
										if(isset($localtrue[$eventkey])){
											unset($out[$localtrue[$eventkey]]);
										}
										$out[] = $num;
										end($out);
										$localtrue[$eventkey] = key($out);
									}
									break;
							}
						}
						if($num==$this->data[$logname]['events'][$eventskeys[$eventkey]]['last'] and $eventendflag){
							$out[] = [$eventkey,1];
						}
						break;
				}
				
			}
			return $out;
		}
		
		public function construct_log($logname,$events = '#'){
			$nums = $this->notes_selector($logname,$events);
			
			$outlog = '';
			$separator = $this->settings['string_separator'];
			$logorder = $this->settings['log_order'];
			$eventstflag = $this->settings['event_start_string'][1];
			$eventedflag = $this->settings['event_end_string'][1];
			if($eventstflag or $eventedflag){
				$eventskeys = array_keys($this->data[$logname]['events']); 
			}
			
			foreach($nums as $num){
				if(is_array($num)){
					if(($eventstflag and $num[1]==0) or ($eventedflag and $num[1]==1)){
						$event = $eventskeys[$num[0]];
						$string = $this->event_borders($logname,$event,$num[1]);
					}
				}else{
					$note = $this->data[$logname]['notes'][$num];
					$string = $this->note2string($note);
				}
				
				if($outlog==''){
					$outlog = $string; 
				}else{
					if($logorder==0){
						$outlog = $string.$separator.$outlog;
					}elseif($logorder==1){
						$outlog = $outlog.$separator.$string;
					}
				}
				
			}
			
			if($this->settings['log_start_string'][1]){
				if($outlog==''){
					$outlog = $this->log_borders($logname,0);
				}else{
					if($logorder==0){
						$outlog = $outlog.$separator.$this->log_borders($logname,0);
					}elseif($logorder==1){
						$outlog = $this->log_borders($logname,0).$separator.$outlog;
					}
				}
			}
			
			if($this->settings['log_end_string'][1]){
				if($outlog==''){
					$outlog = $this->log_borders($logname,1);
				}else{
					if($logorder==0){
						$outlog = $this->log_borders($logname,1).$separator.$outlog;
					}elseif($logorder==1){
						$outlog = $outlog.$separator.$this->log_borders($logname,1);
					}
				}
			}
			
			return $outlog.$separator;
		}
		
		public function echo_log($logname = '#',$events = '#'){
			if($logname=='#'){
				$logname = $this->settings['default_log'];
			}
			
			$log = $this->construct_log($logname,$events);
			$echomode = $this->settings['echo_mode'];
			
			switch($echomode){
				case 2:
				case 1:
					if($this->settings['line_break_html']){
						echo str_replace("\r\n","<br>",$log);
					}else{
						echo $log;
					}
					if($echomode<>2) break;
				case 0:
					$file = $this->log_filename($logname);
					$dir = $this->settings['dir'];
					if($dir==''){
						$path = $file;
					}else{
						$path = $dir.'/'.$file;
					}
					
					$rewrite = $this->settings['rewrite_mode'];
					
					if($dir<>'' and !file_exists($dir)){
						mkdir($dir);
						$rewrite = 1;
					}
					
					switch($rewrite){
						case 0:
							if(file_exists($path)){
								if($this->settings['log_order']==0){
									$open = fopen($path,'rb');
									$lastlog = fread($open,filesize($path));
									$log = $log.$lastlog;
									fclose($open);
								}else{
									$open = fopen($path,'ab');
									fwrite($open,$log);
									fclose($open);
									break;
								}
							}
						default:
							$open = fopen($path,'wb');
							fwrite($open,$log);
							fclose($open);
							break;
					}
					if($echomode<>2) break;
			}
			
			if($echomode<>1){
				$newlogsize = filesize($path);
				if($newlogsize>=$this->settings['file_size_limit']){
					$newname = $this->log_filename($logname,1);
					$newpath = str_replace($file,$newname,$path);
					rename($path,$newpath);
				}
			}
		}
		
		public function data_load_out($to = '#'){
			if($to=='#'){
				$to = $this->settings['datum_filename'];
			}
			$datum = [$this->settings,$this->data];
			$save = json_encode($datum);
			$open = fopen($to,'wb');
			fwrite($open,$save);
			fclose($open);
		}
		
		public function data_load_in($from){
			if(file_exists($from)){
				$open = fopen($from,'rb');
				$load = fread($open,filesize($from));
				fclose($open);
				$datum = json_decode($load,true);
				$this->settings = $datum[0]; 
				$this->data = $datum[1];
			}
		}
		
		public function data_clean(){
			$this->data = [];
		}
		
	}
	
?>