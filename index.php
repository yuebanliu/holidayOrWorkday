<?php


    // header('Content-type:text/html;charset=utf-8');

    class holidayOrWorkday{

    	private $appkey ;

    	public function __construct($appkey){
    		$this->appkey = $appkey;
    	}

    	/**
		 * 请求接口返回内容,直接copy
		 * @param  string $url [请求的URL地址]
		 * @param  string $params [请求的参数]
		 * @param  int $ipost [是否采用POST形式]
		 * @return  string
		 */
		private function curl($url,$params = false,$ispost = 0){
		    $httpInfo = array();
		    $ch = curl_init();
		    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		    curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
		    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
		    curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
		    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		    if( $ispost ){
		        curl_setopt( $ch , CURLOPT_POST , true );
		        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
		        curl_setopt( $ch , CURLOPT_URL , $url );
		    }else{
		        if($params){
		            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
		        }else{
		            curl_setopt( $ch , CURLOPT_URL , $url);
		        }
		    }
		    $response = curl_exec( $ch );
		    if ($response === FALSE) {
		        return false;
		    }
		    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
		    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
		    curl_close( $ch );
		    return $response;
		}

		/**
		  * 获取某个月最近的节假日
		  * @param  [type] $appkey     [description]
		  * @param  [type] $year_month [description]
		  * @return [type]             [description]
		  */
		private function getRecentHoliday($appkey,$year_month){
		    $url = "http://v.juhe.cn/calendar/month";
		    $params = array(
		          "key" => $this->appkey,//您申请的appKey
		          "year-month" => $year_month,//指定月份,格式为YYYY-MM,如月份和日期小于10,则取个位,如:2012-1
		    );
		    $paramstring = http_build_query($params);
		    $content = juhecurl($url,$paramstring);
		    $result = json_decode($result,true);
		    if($result){
		        if( $result['error_code'] == '0' )
		            return $result['result']['data'];
		        elseif( '217701' == $result['error_code'])//这个错误是接口返回没有对应数据的错误
		            return 'no_data';
		    }else{
		        return false;
		    }
		}


		/**
	     * 获取某一年所有的假日
	     * @param  [type] $year [description]
	     * @return [type]       [description]
	     */
	    private function cacheWorkDay($year){
			$filename = __DIR__.'/data/'.$year . '.php';

		    $arr = [];
		    //12个月
		    for ($i = 1; $i <= 12; $i++) { 
			    $recentHoliday = getRecentHoliday($year.'-'.$i);

			    if( is_array($recentHoliday) ){
				    $holiday = json_decode($recentHoliday['holiday'],true);
				    //多个假日循环
				    foreach ( $holiday as $holi ) {
				    	//每个假日放假或者工作循环
				    	foreach ($holi['list'] as $one) {
				    		if( !isset($arr[$one['date']]) ){
				    			$arr[$one['date']] = [
				    				'status' => ( $one['status'] == '2' ? 1 : 3 ),//工作日1倍，节假日3倍
				    			];
				    		}
				    	}
				    }
				}
			}

			file_put_contents($filename,"<?php\n return ".var_export($arr,true)."\n?>");
			return $arr;
		}

		/**
		 * 判断是否是工作日或者假日或者休息日
		 * @param  [type]  $date [description]
		 * @return boolean       [description]
		 */
		public function isWorkday($date){
			$tmpArr = explode('-',$date);
			$filename = __DIR__.'/data/'.$tmpArr[0].'.php';

			if(file_exists($filename)){
				$arr = require_once($filename);
			}else{
				$arr = cacheWorkDay($tmpArr[0]);
			}

			if( isset($arr[$date]) )
				return $arr[$date];
			elseif( date('w',strtotime($date)) == '6' || date('w',strtotime($date)) == '0')
				return 2;
			else
				return 1;
		}

    }

    $myAppkey = 'd6fe2c62790e47db6f904e409483dc72';//换上自己申请的免费APPkey
    $day = new holidayOrWorkday($myAppkey);
    $res = $day->isWorkday('2017-9-30');
    // echo '<pre>';
    // var_dump($res);



?>