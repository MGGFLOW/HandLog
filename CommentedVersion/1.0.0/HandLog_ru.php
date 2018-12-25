<?php
/*  Name: HandLog
	Version: 1.0.0
	Description: PHP class for fast creation variative logs
	GitHub: https://github.com/MGGFLOW/HandLog
	
	Website: http://mggflow.in
	Contact: mggflow@outlook.com
	
	Copyright (c) 2018, MGGFLOW
	All rights reserved.
*/

	class HandLog{
		public $data = []; /* инициализация массива хранения данных лога  */
		/* инициализация массива настроек лога  */
		public $settings = [
								'note_string' => '[#time#] >> #string#', /* формат строки одной записи лога  */
								'string_separator' => "\r\n", /* разделитель между записями  */
								'time_template' => "d-m-Y H:i:s", /* формат даты для функции date(), которое заменит метки #time#  */
								'event_start_string' => ['[#time#]---> Event [ #event# ] started',1], /* формат строки записи о начале события  */
								'event_end_string' => ['[#time#]---> Event [ #event# ] ended. Spent time: #stime# sec',1], /* формат стркои записи о конце события  */
								'log_start_string' => ['[#time#]---> Log [ #log# ] started',1], /* формат строки записи о начале лога */
								'log_end_string' => ['[#time#]---> Log [ #log# ] ended. Spent time: #stime# sec',1], /* формат строки записи о конце лога  */
								'select_mode' => 0, /* режим выбора записей если число:
																			0 - последняя успешная запись, первая ошибка и нейтральные;
																			1 - все успешыне записи, первая ошибка и нейтральные;
																			2 - последняя успешная запись, все ошибки и нейтральные;
																			3 - последняя успешная запись перед первой ошибкой и нейтральные.
														Если массив, то будут выбираться те, чьи типы указаны; пример: [0,2] - выбираются ошибки и нейтральные	*/
								'default_event' => 'Main', /* название события по умолчанию  */
								'default_log' => 'Default', /* название лога по умолчанию  */
								'echo_mode' => 2, /* 0 - прямое сохранение в файл, 1 - вывод лога на экран, 2 - вывод и на экран и в файл  */
								'log_order' => 0, /* 0 - сначала новые записи, 1 - сначала старые  */
								'filename' => '#log#_lastLog.txt', /* имя файла для хранения последнего лога  */
								'refile_time_template' => "d.m.Y_H-i-s", /* формат даты для функции date(), которое заменит метку #time# в имени логфайла при переполнении  */
								're_filename' => 'logsave_#log#_#time#.txt', /* имя файла лога, в который переименуется старый после переполнения  */
								'file_size_limit' => 5*1024*1024, /* размер файла лога в байтах, после которого запись будет осуществляться в новый файл  */
								'dir' => 'Data/Logs',/* директория сохранения лога  */
								'rewrite_mode' => 1, /* режим добавления лога в файл: 0 - дозапись, 1 - перезапись  */
								'line_break_html' => 1, /* заменить \r\n на <br> при выводе  */
								'datum_filename' => 'handlog_datum.txt' /* имя файла выгрузки data и settings  */
							];
							
		/* Инициализация лога-объекта  */				
		public function __construct($logname = '#'){ /* $logname - необязательный параметр названия лога, в случае отсутствия используется название из настроек  */
			if($logname=='#'){ /* выбор имени лога в соответствии с переданным параметром  */
				$logname = $this->settings['default_log'];
			}else{
				$this->settings['default_log'] = $logname;
			}
			
			$this->new_log($logname); /* создание лога  */
		}
		
		/* Добавление нового лога  */
		public function new_log($logname){ /* $logname - имя создаваемого лога, обязательный параметр  */
			$timestart = number_format(microtime(true),8,'.',''); /* установка времени создания лога  */
			$this->data[$logname] = ['meta'=>['timestart'=>$timestart],'events'=>[],'notes'=>[]]; /* добавление ячейки лога в общий массив данных  */
		}
		
		/* Добавление события в лог  */
		public function new_event($eventname,$logname = '#'){ /* $eventname - имя создаваемого события, обязательно; $logname - имя лога, необязательно   */
			$timestart = number_format(microtime(true),8,'.',''); /* установка времени создания события  */
			if($logname=='#'){ /* установка имени лога по умолчанию  */
				$logname = $this->settings['default_log'];
			}
			
			$ind = count($this->data[$logname]['events']); /* вычисление индекса события в массиве событий (подразумевается, что индексы массива событий не будут изменяться)  */
			$this->data[$logname]['events'][$eventname] = ['timestart'=>$timestart,'ind'=>$ind]; /* создание описания событий в массиве событий  */
		}
		
		
		/* Нормализация массива действия  */
		private function action_fix($action){ /* $action - массив действия  */
			for($i=2;$i<4;$i++){
				if(isset($action[$i]) and $action[$i]=='#' or !isset($action[$i])){ /* дополнение массива действия (цикл подразумевает возможность добавления иных параметров при необходимости)  */
					switch($i){
						case 2:
							$action[2] = $this->settings['default_event']; /* устанвливает имя события по умолчанию, если оно не задано при создании записи  */
							break;
						case 3:
							$action[3] = $this->settings['default_log']; /* устанавливает имя лога по умолчанию, если оно не задано при создании записи  */
							break;
					}
				}
			}
			return $action;
		}
		
		
		/* Добавление новой записи в лог  */
		public function new_note(array $action){ /* $action = [0=>строка действия, 1=>тип действия, 2=>имя события, 3=>имя лога]; если имя события и\или не указаны, будут использованы значения по умолчанию  */
			$timeadd = number_format(microtime(true),8,'.',''); /* установка времени создания записи  */
			$action = $this->action_fix($action); /* нормализация массива действия  */
			
			switch(isset($this->data[$action[3]])){ /* проверка существования переданного события и лога  */
				default:
					$this->new_log($action[3]); /* создание лога  */
				case true:
					if(!isset($this->data[$action[3]]['events'][$action[2]])){
						$this->new_event($action[2],$action[3]); /* создание события  */
					}
				break;
			}
			
			$ind = count($this->data[$action[3]]['notes']); /* вычисление индекса записи в логе(подразумевается, что индексы массива записей не будут изменяться)  */
			$this->data[$action[3]]['notes'][] = [$timeadd,$action[0],$action[1],$this->data[$action[3]]['events'][$action[2]]['ind']]; /* добавление записи в массив записей  */
			
			if($action[1]==0){ /* отслеживание первой ошибки и последнего успеха  */
				if(!isset($this->data[$action[3]]['events'][$action[2]]['firstfalse'])){
					$this->data[$action[3]]['events'][$action[2]]['firstfalse'] = $ind;
				}
			}elseif($action[1]==1){
				$this->data[$action[3]]['events'][$action[2]]['lasttrue'] = $ind;
			}
			
			if(!isset($this->data[$action[3]]['events'][$action[2]]['first'])){ /* отслеживание первой записи события  */
				$this->data[$action[3]]['events'][$action[2]]['first'] = $ind;
			}
			$this->data[$action[3]]['events'][$action[2]]['last'] = $ind; /* отслеживание последней записи события  */
		}
		
		/* Преобразование массива записи в форматированную строку  */
		public function note2string($note){ /* $note = [ 0=>время создания записи; 1=>строка записи; 2=>тип действия; 3=>индекс события ]  */
			$time = date($this->settings['time_template'],floor($note[0])); /* преобразование времени добавления записи согласно заданному формату  */
			$outstring = str_replace(['#time#','#string#'],[$time,$note[1]],$this->settings['note_string']); /* создание строки записи согласно заданному формату  */
			return $outstring;
		}
		
		/* Создание строк начала\конца лога  */
		public function log_borders($logname,$track){ /* $logname - название лога; $track - флаг начала или конца 0 или 1(0 - начало, 1 - конец)  */
			$timestart = $this->data[$logname]['meta']['timestart']; /* определение времени начала лога  */
			if($track==1){ /* определение типа границы  */
				$lastnoteind = count($this->data[$logname]['notes'])-1; /* определение индекса последней записи в логе  */
				$endtime = $this->data[$logname]['notes'][$lastnoteind][0]; /* определение времени конца лога  */ 
				$spenttime = number_format(abs($endtime-$timestart),8,'.',''); /* вычисление затраченного времени  */
				$endtime = date($this->settings['time_template'],floor($endtime)); /* преобразование времени конца лога согласно заданному формату  */
				$outstring = str_replace(['#time#','#log#','#stime#'],[$endtime,$logname,$spenttime],$this->settings['log_end_string'][0]); /* создание строки записи конца лога  */
			}else{
				$timestart = date($this->settings['time_template'],floor($timestart)); /* преобразование времени начала лога согласно заданному формату  */
				$outstring = str_replace(['#time#','#log#'],[$timestart,$logname],$this->settings['log_start_string'][0]); /* создание строки записи начала лога  */
			}
			
			return $outstring;
		}
		
		/* Создание строк начала\конца события  */
		public function event_borders($logname,$event,$track){ /* $logname - название лога; $event - названия события; $track - флаг начала или конца 0 или 1(0 - начало, 1 - конец)  */
			$timestart = $this->data[$logname]['events'][$event]['timestart']; /* определение времени начала события  */
			if($track==1){ /* определение типа границы  */
				$lastnoteind = $this->data[$logname]['events'][$event]['last']; /* определение индекса последней записи события  */
				$endtime = $this->data[$logname]['notes'][$lastnoteind][0]; /* определение времени конца события  */
				$spenttime = number_format(abs($endtime-$timestart),8,'.',''); /* вычисление затраченного времени  */
				$endtime = date($this->settings['time_template'],floor($endtime)); /* преобразование времени конца события согласно заданному формату  */
				$outstring = str_replace(['#time#','#event#','#stime#'],[$endtime,$event,$spenttime],$this->settings['event_end_string'][0]); /* создание строки записи конца события  */
			}else{
				$timestart = date($this->settings['time_template'],floor($timestart)); /* определение времени начала события  */
				$outstring = str_replace(['#time#','#event#'],[$timestart,$event],$this->settings['event_start_string'][0]); /* создание строки записи конца события  */
			}
			
			return $outstring;
		}
		
		/* Создание названия файла лога  */
		public function log_filename($logname,$track = 0){ /* $logname - имя лога; $track - флаг типа файла лога 0 или 1 (0 - последний лог; 1 - переименованный лог)  */ 
			if($track){ /* определение типа файла лога  */
				$time = date($this->settings['refile_time_template'],time()); /* определение времени переименования лога согласно шаблону  */
				$outstring = str_replace(['#time#','#log#'],[$time,$logname],$this->settings['re_filename']); /* создание строки названия лога для переименования  */
			}else{
				$outstring = str_replace(['#log#'],[$logname],$this->settings['filename']); /* создание строки названия последнего лога  */
			}
			
			return($outstring);
		}
	
		/* Выбор записей(их индексов) лога согласно настрйокам  */
		private function notes_selector($logname = '#',$events = '#'){ /* $logname - название лога; $events - массив событий для выбора(необязательно, по умолчанию все события)  */
			if($logname=='#'){ /* установка значения имени лога по умолчанию  */
				$logname = $this->settings['default_log'];
			}
			$eventskeys = array_keys($this->data[$logname]['events']); /* создание массива имён событий  */
			$eventsflag = is_array($events); /* установка флага выбора событий  */
			$mode = $this->settings['select_mode']; /* определение режима выбора записей  */
			
			$out = []; /* инициализация массива хранения индексов отобранных записей  */
			
			foreach($this->data[$logname]['notes'] as $num=>$note){ /* пербор записей лога  */
				
				$notetype = $note[2]; /* определение типа записи  */
				$eventkey = $note[3]; /* определение индекса события  */
				
				switch($eventsflag){ /* проверка флага выбора событий  */
					case true:
						if(!in_array($eventskeys[$eventkey],$events)){
							break;
						}
					default:
						if($num==$this->data[$logname]['events'][$eventskeys[$eventkey]]['first']){ /* определение начала события  */
							$out[] = [$eventkey,0];
						}
						
						if(is_array($mode)){ /* отбор событий, если в качестве режима установлен массив  */
							if(in_array($notetype,$mode)){
								$out[] = $num;
							}
						}else{
							switch($mode){ /* отбор событий, если в качестве режима установлено число   */
								case 0:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue']) ? $lasttrue = $this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue'] : $lasttrue = -1; /* определение индекса последней успешной записи или его отсутствия  */
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1; /* определение индекса первой записи об ошибке или его отсутствия  */
									if($notetype==2 or $num==$lasttrue or $num==$firstfalse){ /* добавление индекса записи при соблюдении условий  */
										$out[] = $num;
									}
									break;
								case 1:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1; /* определение индекса первой записи об ошибке или его отсутствия  */
									if($notetype==2 or $notetype==1 or $num==$firstfalse){ /* добавление индекса записи при соблюдении условий  */
										$out[] = $num;
									}
									break;
								case 2:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue']) ? $lasttrue = $this->data[$logname]['events'][$eventskeys[$eventkey]]['lasttrue'] : $lasttrue = -1; /* определение индекса последней успешной записи или его отсутствия  */
									if($notetype==2 or $notetype==0 or $num==$lasttrue){ /* добавление индекса записи при соблюдении условий  */
										$out[] = $num;
									}
									break;
								case 3:
									isset($this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse']) ? $firstfalse = $this->data[$logname]['events'][$eventskeys[$eventkey]]['firstfalse'] : $firstfalse = -1; /* определение индекса первой записи об ошибке или его отсутствия  */
									if($notetype==2 or $num==$firstfalse){ /* добавление индекса записи при соблюдении условий  */
										$out[] = $num;
									}elseif($notetype==1 and ($firstfalse==-1 or $num<$firstfalse)){ /* определение последней успешной записи перед первой ошибкой  */
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
						if($num==$this->data[$logname]['events'][$eventskeys[$eventkey]]['last']){ /* определение конца события  */
							$out[] = [$eventkey,1];
						}
						break;
				}
				
			}
			return $out;
		}
		
		/* Конструирование лога согласно настройкам  */
		public function construct_log($logname,$events = '#'){ /* $logname - название лога; $events - массив событий для выбора(необязательно, по умолчанию все события)  */
			$nums = $this->notes_selector($logname,$events); /* определение индексов записей согласно выбранному режиму  */
			
			$outlog = ''; /* инициализация строки для записи лога  */
			$separator = $this->settings['string_separator']; /* определение межстрочного разделителя между записями  */
			$logorder = $this->settings['log_order']; /* определение порядка вывода записей  */
			$eventstflag = $this->settings['event_start_string'][1]; /* флаг вывода записи начала события  */
			$eventedflag = $this->settings['event_end_string'][1]; /* флаг вывода записи конца события  */
			if($eventstflag or $eventedflag){ /* создание массива имён событий  */
				$eventskeys = array_keys($this->data[$logname]['events']); 
			}
			
			foreach($nums as $num){ /* перебор индексов записей  */
				if(is_array($num)){ /* проверка границ событий  */
					if(($eventstflag and $num[1]==0) or ($eventedflag and $num[1]==1)){ /* создание строки границы события  */
						$event = $eventskeys[$num[0]];
						$string = $this->event_borders($logname,$event,$num[1]);
					}
				}else{
					$note = $this->data[$logname]['notes'][$num]; /* определение записи по её индексу  */
					$string = $this->note2string($note); /* создание строки лога из записи  */
				}
				
				if($outlog==''){ /* добавление строки в лог  */
					$outlog = $string; 
				}else{
					if($logorder==0){
						$outlog = $string.$separator.$outlog;
					}elseif($logorder==1){
						$outlog = $outlog.$separator.$string;
					}
				}
				
			}
			
			if($this->settings['log_start_string'][1]){ /* проверка условия добавления записи начала лога  */
				if($outlog==''){ /* добавление строки записи начала лога  */
					$outlog = $this->log_borders($logname,0);
				}else{
					if($logorder==0){
						$outlog = $outlog.$separator.$this->log_borders($logname,0);
					}elseif($logorder==1){
						$outlog = $this->log_borders($logname,0).$separator.$outlog;
					}
				}
			}
			
			if($this->settings['log_end_string'][1]){ /* проверка условия добавления записи конца лога  */
				if($outlog==''){ /* добавление строки записи конца лога  */
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
		
		/* Вывод лога согласно настрйокам  */
		public function echo_log($logname = '#',$events = '#'){ /* $logname - название лога; $events - отобранные для показа события  */
			if($logname=='#'){ /* установка значения имени лога по умолчанию  */
				$logname = $this->settings['default_log'];
			}
			
			$log = $this->construct_log($logname,$events); /* конструирование лога  */
			$echomode = $this->settings['echo_mode']; /* определения режима вывода лога  */
			
			switch($echomode){ /* вывод лога согласно установленному режиму  */
				case 2: /* сохранение в файл и вывод лога на экран  */
				case 1: /* вывод лога на экран  */
					if($this->settings['line_break_html']){ /* проверка замены переносов строки согласно настройкам  */
						echo str_replace("\r\n","<br>",$log); /* вывод лога с заменёнными в формате html переносами строки  */
					}else{
						echo $log; /* вывод лога на экран  */
					}
					if($echomode<>2) break;
				case 0: /* сохранение лога в файл  */
					$file = $this->log_filename($logname); /* определение имени файла лога  */
					$dir = $this->settings['dir']; /* определение директории файла лога  */
					if($dir==''){ /* проверка сохранения файла в текущую директорию  */
						$path = $file;
					}else{
						$path = $dir.'/'.$file;
					}
					
					$rewrite = $this->settings['rewrite_mode']; /* определение режима перезаписи  */
					
					if($dir<>'' and !file_exists($dir)){ /* проверка существования директории  */
						mkdir($dir);
						$rewrite = 1;
					}
					
					switch($rewrite){ /* непосредственное сохранение лога в файл согласно настройкам  */
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
			
			if($echomode<>1){ /* проверка размера файла согласно утановленному лимиту  */
				$newlogsize = filesize($path);
				if($newlogsize>=$this->settings['file_size_limit']){
					$newname = $this->log_filename($logname,1);
					$newpath = str_replace($file,$newname,$path);
					rename($path,$newpath);
				}
			}
		}
		
		/* Сохранение переменной data в файл  */
		public function data_load_out($to = '#'){ /* $to - путь сохранения файла для выгрузки datum  */
			if($to=='#'){ /* установка пути выгрузки по умолчанию  */
				$to = $this->settings['datum_filename'];
			}
			$datum = [$this->settings,$this->data]; /* создание массива для хранения data и settings  */
			$save = json_encode($datum); /* создание строки datum в формате json  */
			$open = fopen($to,'wb'); /* инициализация переменной открытия файла  */
			fwrite($open,$save); /* запись строки datum в файл  */
			fclose($open); /* закрытие файла  */
		}
		
		/* Загрузка переменной data из файла  */
		public function data_load_in($from){ /* $from - путь к файлу datum для загрузки  */
			if(file_exists($from)){ /* проверка пути файла на существование  */
				$open = fopen($from,'rb'); /* инициализация переменной открытия файла  */
				$load = fread($open,filesize($from)); /* чтение файла в строку  */
				fclose($open); /* закрытие файла  */
				$datum = json_decode($load,true); /* преобразование json строки обратно в массив  */
				$this->settings = $datum[0]; /* присвоение settings загруженного значения  */
				$this->data = $datum[1]; /* присвоение data загруженного значения  */
			}
		}
		
		/* Очистка значений переменной data  */
		public function data_clean(){
			$this->data = []; /* очистка переменной хранения лога  */
		}
		
	}
	
?>