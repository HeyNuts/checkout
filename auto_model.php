<?php
require_once("./dp/helpers/price_strategy_helper.php");
define('DEVIATION', 0.00000001);
date_default_timezone_set('PRC');
function splitLine($line){
    //echo $line;
    $features = explode(' ',$line);
    //$features = split('[\s\t]',$line);
    $dic = array();
    foreach($features as $fea){
        $key = explode(':',$fea)[0];
        $value = explode(':',$fea)[1];
        echo "key:${key},value:${value}\n";
        $dic[$key] = $value;
    }
    return $dic;
}

function getTestCase(){
    $handle  = fopen('testcase','r');
    while(!feof($handle)){
        $line = fgets($handle,1024);
        if(!empty($line)){
            $testcase = splitLine($line);
            //echo $testcase[31];
            //echo "getTestCaseEnd\n";
        }
    }
    fclose($handle);
    return $testcase;
}
//创建特征词典
function createParDic($filename){
    $dictionay =  array();
    $handle = fopen($filename,'r');
    while(!feof($handle)){
        $line = fgets($handle,1024);
        //trim($line);
        $line = str_replace(array("\r\n","\n","\r"),"",$line);
        if(!empty($line)){
            $k = explode(' ',$line)[0];
            $v = explode(' ',$line)[1];
            $dictionay[$k] = $v;
        }
    }
    fclose($handle);
    return $dictionay;
}

//读取标准答案文件
function buildAnswerArray($dictionary){
    $answer = array();
    $handle = fopen('answer','r');
    echo "build answer test \n";
    while(!feof($handle)){
        $line = fgets($handle,4096);
        //去掉行尾换行符和回车
        $line = str_replace(array("\r\n", "\r", "\n"), "", $line);
        //echo $line;
        if(!empty($line)){
            $features = explode(' ',$line);
            echo count($features);
            $model = explode(':',$features[0])[1];
            echo $model;
            $answer[$model] = array();
            foreach($features as $fea){
                $tk = explode(':',$fea)[0];
                $k = $dictionary[$tk];
                $v = explode(':',$fea)[1];
                $answer[$model][$k] = $v;
                echo "tk = ${tk}, key = ${k}, value = ${v} ";
                echo "answer value = ";
                echo $answer[$model][$k];
                echo "\n";
                //echo "answerkey = ${k}, answer = ${$answer[$k]} \n";
                //echo $k;
                //echo $answer[$k];
            }
            echo "answer num:";
            echo count($answer[$model]);
            echo "\n";
        }
    }
    fclose($handle);
    return $answer;
}

function getCalcResult(){
 //   $getRate();
}

//构造passenger info 输入array
function buildInputArrayPi($testcase,$para){
    //loadDiction();
    $inputarr_pi = array();
    foreach(array_keys($testcase) as $key){
        if($key >= 110)
        {
            $name = $para[$key];
            if($name != "")
            {
                $inputarr_pi[$name] = $testcase[$key];
            }
        }
    }
    //echo "input array cnt:";
    //$num = count($inputarr_pi);
    //echo $num;
    return $inputarr_pi;
}

//构造basic info输入array
function buildInputArrayBs($testcase,$para){
    //loadDiction();
    $inputarr_bs = array();
    foreach(array_keys($testcase) as $key){
        if($key <= 100)
        {
            $name = $para[$key];
            if($name != "")
                $inputarr_bs[$name] = $testcase[$key];
        }
    }
    return $inputarr_bs;
}

function log_notice($msg){
    $handle = fopen("checklog",'a+');
    if(!$handle){
        echo 'Log file create failed';
        exit;
    }
    fwrite($handle,$msg);
    fclose($handle);
}

//对每一行答案，都进行一次pricestrategy的调用，对比完后进行下一行
function checkAnswer($answer,$inputarr_bs,$inputarr_pi){
    $an_cnt = count($answer);
    if($an_cnt != 0){
        foreach(array_keys($answer) as $model_num){
            echo "\ndebug checkAnswer: model_num =" . $model_num;
            if($model_num < 10000){
                log_notice("checkAnswer model_num wrong\n");
                continue;
            }
            else{
                $type = intval($model_num / 10000);
                $area = intval(($model_num % 10000) / 1000);
                $product_id = intval(($model_num %1000)/100);
                $hour = $model_num%100;
                $model_name = "";
                echo " type = " . $type;
                switch($type){
                    //如果有weight
                    //type == 1 调价乘客模型
                    //type == 2 不调价乘客模型
                    //type == 3 调价司机模型
                    //type == 4 不调价司机模型
                    case 1:$model_name .= "passenger_dp_";break;
                    case 2:$model_name .= "passenger_";break;
                    case 3:$model_name .= "driver_dp_";break;
                    case 4:$model_name .= "driver_";break;
                }
                $model_name .= "model_normlization";
                $model_type = $model_name;
                $model_name = $model_name . "_" . $area . "_" . $product_id . "_" . $hour;
                echo "In function checkAnswer,model name is:" . $model_name;
                //第一个参数modeltype

                //todo test begin here
                //构造inputarr basic和passengerinfo
                $inputarr_bs['area'] = $area;
                $inputarr_bs['dynamic_price_id'] = '201505271902205_337671';
                $inputarr_bs['passenger_id'] = '337672';
                $inputarr_bs['product_id'] = $product_id;
                $now = time();
                $weekday = intval(date('w', $now));
                $inputarr_bs['weekday'] = $weekday;
                $inputarr_bs['hour'] = $hour;
                $inputarr_bs['flnglat'] = intval($inputarr_bs['flng'] * 100) * 10000 + intval($inputarr_bs['flat'] * 100);
                $inputarr_bs['tlnglat'] = intval($inputarr_bs['tlng'] * 100) * 10000 + intval($inputarr_bs['tlat'] * 100);
                $inputarr_bs['threshold'] = 0.55;
                //验证basicinputarr
                echo "\nbasic Array count = " . count($inputarr_bs);
                foreach(array_keys($inputarr_bs) as $key){
                    echo "\nkey = " . $key . ",value = " . $inputarr_bs[$key];
                }

                echo "\npassinfo Array count " . count($inputarr_pi);
                foreach(array_keys($inputarr_pi) as $key){
                    echo "\nkey = " . $key . ",value = " . $inputarr_pi[$key];
                }
                //调用线上的方法
                //
                $return = getPredictRate($model_type,$inputarr_bs,$inputarr_pi,$test = 1);
                $return = array("v_date_total_last_call" => 0.23412561, "v_cnt_ord_bonus_w" => 0.5);
                $weight_m = $answer[$model_num];
                foreach(array_keys($weight_m) as $weight_key){
                    if(!empty($weight_key) && !empty($weight_m[$weight_key])){
                        if(array_key_exists($weight_key,$return)){
                            if(abs($weight_m[$weight_key] - $return[$weight_key]) <= DEVIATION ){
                                log_notice("Model:" . $model_name . "  KeySuccess" . $weight_key . "\n");
                                continue;
                            }
                            else{
                                log_notice("Model:" . $model_name . "  ErrorKey:" . $weight_key . "  answer=" . $weight_m[$weight_key] . "  result=" . $return[$weight_key] . "\n");
                                return false;
                            }
                        }
                        else{
                            log_notice("Model:" . $model_name . "  MissKey:" . $weight_key . "\n");
                            return false;
                        }
                    }
                }

            }
        }
    }
    return true;
}

function main(){
    echo "in main function\n";
    $testcase = getTestCase();
    echo 'testcase num :';
    echo count($testcase);
    echo "\n";
    $paraDictPi = createParDic("feature");
    $paraDictBs = createParDic("basic_feature");
    echo "feature dictionary lenth:";
    echo count($paraDictPi);
    echo "\n";
    echo $paraDictPi[118];
    echo "buildinputarray:\n";
    $inputarr_bs = buildInputArrayBs($testcase,$paraDictBs);
    $inputarr_pi = buildInputArrayPi($testcase,$paraDictPi);
    //foreach(array_keys($inputarr_pi) as $keys){
    //    echo "key is : ${keys} ";
    //    echo "value is :";
    //    echo $inputarr_pi[$keys];
    //    echo "\n";
    //}
    //$num  = count($inputarr_pi);

    echo "input arr num : $num \n";
    $answerDict = createParDic("weight");
    $answer = buildAnswerArray($answerDict);
    echo "check answer arr:*************\n";
    echo "answer cnt:";
    echo count($answer);
    echo $answer[11122][-1];
    //echo count($answer);
    //补充basic特征元素并且遍历城市和时间段
    checkAnswer($answer,$inputarr_bs,$inputarr_pi);

}
function test(){
    $st  = "haha";
    $num = 2;
    echo $st . $num;
}

main();
?>

