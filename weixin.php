<?php
// wechat.php

// 引入必要的类
require_once 'src/db.php';
require_once 'src/wechatMsgCrypt.php';
require_once 'src/message_handler.php';
require_once 'src/logger.php';
require_once 'src/handle.php';
require_once 'src/commonAPI.php';

// 引入配置文件
$config = require 'config/config.php';

// 初始化日志记录器
$logger = new Logger();

try {
    // 初始化数据库连接
    $db = DB::getInstance($logger);
    $db->initTables();
} catch (PDOException $e) {
    $logger->error("数据库初始化失败: " . $e->getMessage());
    exit("服务器内部错误");
}

// 初始化微信回调处理类
$wechatMsgCrypt = new WechatMsgCrypt($config['TOKEN'], $config['APPID'], $config['ENCODING_AES_KEY'], $logger);

// 获取微信服务器发送的参数

$full_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$signature = $_GET["signature"] ?? '';
$msg_signature = $_GET["msg_signature"] ?? '';
$timestamp = $_GET["timestamp"] ?? '';
$nonce = $_GET["nonce"] ?? '';
$echostr = $_GET["echostr"] ?? '';
$encryptType = isset($_GET['encrypt_type']) ? $_GET['encrypt_type'] : 'raw';



$ip = getClientIp() ?? '';



$locationData = getIpLocation($ip) ?? '';

if ($locationData) {
    $iplocation= "IP: ". $locationData['query'] . ",城市: " . $locationData['city']. ",区域: " .$locationData['regionName']. ",国家: ".$locationData['country'];
    
} else {
    $iplocation= "无法获取IP位置";
}
$logger->info("微信公众号平台 $iplocation 发起URL请求: $full_url");// }
//$logger->info("加密方式: $encryptType, signature= $signature,msg_signature =$msg_signature ,timestamp=$timestamp,nonce=$nonce,echostr=$echostr,ip=$ip,locationData=$iplocation");


$savetime = ($timestamp ? new DateTime("@".(int)$timestamp) : new DateTime())->format('Y-m-d H:i:s');
//$savetime = ($timestamp ? (new DateTime("@".(int)$timestamp)) : time())->format('Y-m-d H:i:s');


if (!saveIP($db, $savetime,$ip, $iplocation, $full_url)) {
        return generateJsonErrorResponse("服务器内部错误");
    }










// 验证URL
if ($signature && $timestamp && $nonce && $echostr) {
    if ($wechatMsgCrypt->valid($signature, $timestamp, $nonce)) {
        echo $echostr;
        exit;
    } else {
        $logger->error("URL验证失败: signature=$signature, timestamp=$timestamp, nonce=$nonce");
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

// 处理微信服务器发送的消息
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取 Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
   
    $XmlOrJson = strpos($contentType, 'application/json') !== false;
   
    // 统一获取原始数据
    $rawData = file_get_contents("php://input");
    $logger->info("接收原始数据: " .$rawData);

    $jsonData = json_decode($rawData, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['ToUserName'])) {
        $XmlOrJson = true;
        $logger->info("接收到的消息类型: json");
 
        if ($jsonData['ToUserName'] ===  $config['ENCODING_AES_KEY']) {
            
            $logger->info("你将EncodingAESKey放在ToUserName字段的进行了明文传递，目的是验证两边的EncodingAESKey一样的，都是 ".$config['ENCODING_AES_KEY']);
        }
        

        
    } else {
         $logger->info("接收到的消息类型: $contentType");
    }

   $echotest= false;
  if ($echotest === true && $encryptType === 'aes') {
      
        echo $rawData;
        exit;
      }
      
  

    
    try {
        // 解密处理（安全模式）
        $decryptMsg = '';
        if ($encryptType === 'aes') {
            $timestamp = $_GET['timestamp'] ?? time();
            $errCode = $wechatMsgCrypt->decryptMsg(
                $msg_signature,
                $nonce,
                $rawData,
                $decryptMsg,
                $timestamp,
                $XmlOrJson // 新增格式参数[1,7](@ref)
            );

            if ($errCode !== 0) {
                throw new Exception("解密失败，错误码：$errCode");
            }
           $logger->info("解密后数据: " . $decryptMsg);
        } else {
            $decryptMsg = $rawData;
        }
       
              // 消息解析
        if ($XmlOrJson) {
            $data = json_decode($decryptMsg, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON解析失败: " . json_last_error_msg());
            }
            $replyData = handleJSONMessage($data, $db, $logger);
        } else {
            $postObj = simplexml_load_string($decryptMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($postObj === false) {
                throw new Exception("XML解析失败");
                                     }
     
        // $replyData = handleXMLMessage($postObj, $db, $logger);
          $msg_handle= new Handle();
          // $logger->info("处理前数据: " .$decryptMsg ); 
          $replyData=$msg_handle->handleXmlMessage($postObj, $db,$logger,$iplocation) ;
           $logger->info("处理后数据: " .$replyData ); 
            }
     
        
                // 加密响应
        $encryptMsg = '';
        if ($encryptType === 'aes') {
            $errCode = $wechatMsgCrypt->encryptMsg(
                $XmlOrJson ? json_encode($replyData) : $replyData,
                $timestamp,
                $nonce,
                $encryptMsg,
                $XmlOrJson // 新增格式参数[1,7](@ref)
            );
            
            if ($errCode !== 0) {
                throw new Exception("加密失败，错误码：$errCode");
                                 }
          //  $logger->info("加密后响应: " . $encryptMsg);
                                      }
                // 构建最终响应
        $response = $encryptMsg ?: ($XmlOrJson ? json_encode($replyData) : $replyData);
   
        header('Content-Type: ' . ($XmlOrJson ? 'application/json' : 'text/xml'));
        echo $response;
       $logger->info("回复完成，已经处理完这个请求。回复给微信公众号平台的内容是：" . $response);
       // $logger->info("回复完成，已经处理完这个请求." );
        } catch (Exception $e) {
        $logger->error("处理失败: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo $isJson ? json_encode(['error' => $e->getMessage()]) : "<error>".$e->getMessage()."</error>";
        exit;
                              }                    
    

    
     } else {
    // 非 POST 请求的处理
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
       }





?>