<?php

	namespace HolidayOrWorkday;
    // header('Content-type:text/html;charset=utf-8');
	use Cache;

    class holidayOrWorkday{

    	private $appkey ;

    	public function __construct($appkey){
    		$this->appkey = $appkey;
    	}

    	/**
		 * 判断是否是工作日或者假日或者休息日
		 * @param  [type]  $date [形如2017-09-07或者2017-9-7]
		 * @return boolean       [description]
		 */
		public function isWorkday($date){
			$date_timestamp = strtotime($date);
			$date = date('Y-n-j',$date_timestamp);//转换一下
			$tmpArr = explode('-',$date);
			$filename = __DIR__.'/data/'.$tmpArr[0].'.php';

			$arr = [];
			if(file_exists($filename)){
				$arr = require_once($filename);//这个也可能为空，那么就要重新生成
			}

			//如果arr数组为空，并且不大于当前年份，就去获取一下
			if( empty($arr) && ( date('Y',$date_timestamp) >= $tmpArr[0] ) )
				$arr = $this->cacheWorkDay($tmpArr[0]);

			if( isset($arr[$date]) )
				return $arr[$date];
			elseif( date('w',$date_timestamp) == '6' || date('w',$date_timestamp) == '0')
				return 2;
			else
				return 1;
		}

		/**
	     * 获取某一年所有的假日
	     * @param  [type] $year [description]
	     * @return [type]       [description]
	     */
	    private function cacheWorkDay($year){
			$filename = __DIR__.'/data/'.$year . '.php';

		    $arr = [];
		    $festival = [];//用来排除已经遍历过的
		    //12个月
		    for ($i = 1; $i <= 12; $i++) { 

			    $recentHoliday = $this->getRecentHoliday($year.'-'.$i);

			    if( is_array($recentHoliday) ){
				    $holiday = json_decode($recentHoliday['holiday'],true);

				    //需要判断是否是1个假日或者多个，多个比一个的多一维
				    if( isset($holiday['festival']) ){
				    	$holiday = [$holiday];
				    }
				    
				    //多个假日循环
				    foreach ( $holiday as $holi ) {
				    	//判断是否已经遍历过了
				    	if( in_array($holi['festival'] , $festival) ){
					    	continue;
				    	}

					    $festival[] = $holi['festival'];

				    	//每个假日放假或者工作循环
				    	if(!empty($holi['list'])){
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
			}
			file_put_contents($filename,"<?php\n return ".var_export($arr,true)."\n?>");
			return $arr;
		}

		/**
	     * 判断是否是工作日或者假日或者休息日-带缓存,测试用
	     * @param  [type]  $date [description]
	     * @return boolean       [description]
	     */
	    public function isWorkday_cache($date){
	        $date = date('Y-n-j',strtotime($date));//转换一下
	        $tmpArr = explode('-',$date);
	        $cacheName = $tmpArr[0].'_holiday';

	        $arr = Cache::has($cacheName) ? Cache::get($cacheName) : [];

	        //如果arr数组为空，并且不大于当前年份，就去获取一下
			if( empty($arr) && ( date('Y') >= $tmpArr[0] ) )
	            $arr = $this->cacheWorkDay_cache($tmpArr[0]);

	        if( isset($arr[$date]) )
	            return $arr[$date];
	        elseif( date('w',strtotime($date)) == '6' || date('w',strtotime($date)) == '0')
	            return 2;
	        else
	            return 1;
	    }

		/**
	     * 获取某一年所有的假日-带缓存，测试用
	     * @param  [type] $year [description]
	     * @return [type]       [description]
	     */
	    private function cacheWorkDay_cache($year){
	        $arr = [];
	        $festival = [];
	        //12个月
	        for ($i = 1; $i <= 12; $i++) { 
	            $recentHoliday = $this->getRecentHoliday($year.'-'.$i);

	            if( is_array($recentHoliday) ){
	                $holiday = json_decode($recentHoliday['holiday'],true);

	                //需要判断是否是1个假日或者多个，多个比一个的多一维
				    if( isset($holiday['festival']) ){
				    	$holiday = [$holiday];
	                }
	                
	                //多个假日循环
	                foreach ( $holiday as $holi ) {
	                    //判断是否已经遍历过了
	                    if( in_array($holi['festival'] , $festival) ){
	                        continue;
	                    }

	                    $festival[] = $holi['festival'];
	                    //每个假日放假或者工作循环
	                    if( !empty($holi['list']) ){
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
	        }
	        $cacheName = $year.'_holiday';//缓存的名称
	        Cache::forever($cacheName,$arr);
	        // Cache::put($cacheName,$arr,1);
	        return $arr;
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
		private function getRecentHoliday($year_month){
		    $url = "http://v.juhe.cn/calendar/month";
		    $params = array(
		          "key" => $this->appkey,//您申请的appKey
		          "year-month" => $year_month,//指定月份,格式为YYYY-MM,如月份和日期小于10,则取个位,如:2012-1
		    );
		    $paramstring = http_build_query($params);
		    $content = $this->curl($url,$paramstring);
		    $result = json_decode($content,true);
		    if($result){
		        if( $result['error_code'] == '0' )
		            return $result['result']['data'];
		        elseif( '217701' == $result['error_code'])//这个错误是接口返回没有对应数据的错误
		            return 'no_data';
		    }else{
		        return false;
		    }
		}

    }

    // echo '<pre>';
    // $myAppkey = 'xxxxxxx';//换上自己申请的免费APPkey
    // $day = new holidayOrWorkday($myAppkey);
    // $res = $day->isWorkday('2016-9-30');
    // var_dump($res);



?>