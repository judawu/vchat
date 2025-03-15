<?php
// message_handler.php

/**
 * 生成错误响应的 XML
 *
 * @param string $message 错误信息
 * @param string $fromUser 发送者
 * @param string $toUser 接收者
 * @return string 错误响应的 XML 字符串
 */
function generateXmlErrorResponse($message, $fromUser, $toUser) {
    return "<xml>
                <ToUserName><![CDATA[$fromUser]]></ToUserName>
                <FromUserName><![CDATA[$toUser]]></FromUserName>
                <CreateTime>" . time() . "</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[$message]]></Content>
             </xml>";
}

// JSON错误响应
function generateJsonErrorResponse($message, $fromUser, $toUser) {
    header('Content-Type: application/json');
    return json_encode([
        'ToUserName' => $fromUser,
        'FromUserName' => $toUser,
        'CreateTime' => time(),
        'MsgType' => 'text',
        'Content' => $message
    ], JSON_UNESCAPED_UNICODE);
}

function messageToDb($db, $fromUser, $msgType, $content, $saveTime) {
    
            // 存储消息到数据库
     // 存储消息到数据库
     
    try {
        $pdo = $db->getPdo(); // 使用 getPdo() 方法获取 PDO 实例
        $stmt = $pdo->prepare("INSERT INTO messages (openid, msg_type, content,created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fromUser, $msgType, $content, $saveTime]);
        return true;
    } catch (PDOException $e) {
        error_log("数据库插入失败: " . $e->getMessage());
        return false;
    }

      
   
}


function handleJSONMessage($msg, $db, $logger) {
    // 解析JSON数据
     
    // 字段提取（示例字段，需根据实际接口规范调整）
    $fromUser = isset($msg['FromUserName']) ?  $msg['FromUserName'] :'';
  
    $toUser = isset($msg['ToUserName']) ? $msg['ToUserName'] :  '';
    $msgType = isset($msg['MsgType']) ? strtolower($msg['MsgType']): 'unknown';
    $MsgId = isset($msg['MsgId']) ?$msg['MsgId'] :'';
    $CreateTime =isset($msg['CreateTime']) ? $msg['CreateTime']: '';
    $SaveTime = ($CreateTime?(new DateTime("@".(int)$CreateTime)): new DateTime())->format('Y-m-d H:i:s'); // 通过@符号传入时间戳
    //处理文本
    $content = isset($msg['Content']) ?$msg['Content']: '';
    //处理图片
    $PicUrl = isset($msg['PicUrl']) ? $msg['PicUrl'] : '';
  
    //处理事件
    
    $Event =isset($msg['Event']) ?$msg['Event'] : 'unknown';
    //事件debug_demo  微信调试
    $debug_str =isset($msg['debug_str']) ?$msg['debug_str'] : '';
  
    //事件location  地理
    $Latitude = isset($msg['Latitude']) ?$msg['Latitude'] : '';
    $Longitude = isset($msg['Longitude']) ?$msg['Longitude'] : '';
    $Precision = isset($msg['Precision']) ?? '';
    
    
     //事件lwxa_widget_data  小程序Widge
    $Query = isset($msg['Query']) ?$msg['Query'] : '';
    $Scene =isset($msg['Scene']) ? $msg['Scene']: '';
   
    
   //处理音频
    $MediaId= isset($msg['MediaId']) ? $msg['MediaId'] :'';
    $MediaFormat = isset($msg['Format']) ? $msg['Format'] : '';
   
   
   //处理视频
    $ThumbMediaId = isset($msg['ThumbMediaId']) ? $msg['ThumbMediaId'] : '';
   //处理地理位置
    $Location_X = isset($msg['Location_X']) ? $msg['Location_X'] : '';
    $Location_Y  = isset($msg['Location_Y']) ? $msg['Location_Y'] : '';
    $Scale = isset($msg['Scale']) ?$msg['Scale'] : '';
    $Label = isset($msg['Label']) ?$msg['Label'] : '';
   //处理链接
    $Title = isset($msg['Title']) ? $msg['Title'] :'';
    $Description = isset($msg['Description']) ? $msg['Description'] : '';
    $Url = isset($msg['Url']) ? $msg['Url'] : '';
  
    if (!$fromUser || !$toUser || !$msgType) {
        return generateJsonErrorResponse("无效的消息格式", $fromUser, $toUser);
    }
   
    // 消息处理逻辑（与XML处理保持一致）
    $SaveContent = ''; 
   
      switch ($msgType) {
        case 'text':
            $SaveContent = $MsgId. ":".$content;
             $reply = "您发送了文本消息:{" .$SaveContent."}";
           //  $reply = "您发送了文本消息";
            break;
        case 'event':
            
          

            if ($Event == 'ENTER') {
                $SaveContent = $MsgId . "：" . $Event;
                $reply = "您发送了事件会话:{" . $SaveContent . "}";
            } elseif ($Event == 'LOCATION') {
                // 注意：需确保 $Latitude, $Longitude, $Precision 变量已定义
                $SaveContent = $MsgId . "：" . $Event . "," . $Latitude . "," . $Longitude . "," . $Precision;
                $reply = "您发送了事件地理位置:{" . $SaveContent . "}";
                
            } elseif ($Event == 'wxa_widget_data') {
              
                $SaveContent = $Event . "：" . $Query . "," . $Scene ;
                $reply = "您发送了小程序Widget消息事件:{" .$SaveContent . "}";
            
            } elseif ($Event == 'debug_demo') {
                // 合并两个相同逻辑的 case
                // 注意：需确保 $debug_str 变量已定义
                $SaveContent = $MsgId . "：" . $Event . "," . $debug_str;
                $reply = "您发送了事件调试Demo{" . $SaveContent . "}";
            } else {
                // 默认处理逻辑
                $SaveContent = $MsgId . "：" . $Event;
                $reply = "您发送了事件:{" . $SaveContent . "}";
            }
              break;           
            
         case 'image':
            $SaveContent = $MsgId. "：". $PicUrl;
            $reply = "您发送了图片:{" .  $SaveContent."}";
            break;
        case 'voice':
            $SaveContent =$MsgId. "：". $MediaId.",". $MediaFormat;
            $reply = "您发送了音频:{" .  $SaveContent."}";
            break;
         case 'video':
            $SaveContent =$MsgId. "：". $MediaId.",". $ThumbMediaId;
            $reply = "您发送了视频:{" .  $SaveContent."}";
            break;
        case 'location':
            $SaveContent =$MsgId. "：".$Location_X.",".$Location_Y.",". $Scale.",".$Label;
            $reply = "您发送了地理位置:{" .  $SaveContent."}";
            break;  
            
        case 'link':
            $SaveContent =$MsgId. "：". $Title.",".$Description.",". $Url;
            $reply = "您发送了链接:{" .  $SaveContent."}";
            break;   
        // 处理其他消息类型
        default:
            $reply = "抱歉，我暂不支持这种消息类型";
    }
    

     $logger->info("Received message: fromUser={$fromUser}, toUser={$toUser}, msgType={$msgType}, content={$SaveContent}");
    // 存储数据库
 
    if (!messageToDb($db, $fromUser, $msgType, $SaveContent,  $SaveTime)) {
        return generateJsonErrorResponse("服务器内部错误", $fromUser, $toUser);
    }

    // 构建JSON响应
    header('Content-Type: application/json');
    return json_encode([
        'ToUserName' => $fromUser,
        'FromUserName' => $toUser,
        'CreateTime' => time(),
        'MsgType' => 'text',
        'Content' => $reply
    ], JSON_UNESCAPED_UNICODE);
}


function handleXMLMessage($msg, $db, $logger) {
   
 
 

    // 或者直接使用对象属性访问
    $fromUser = isset($msg->FromUserName) ? htmlspecialchars((string)$msg->FromUserName, ENT_QUOTES, 'UTF-8') : '';
    $toUser = isset($msg->ToUserName) ? htmlspecialchars((string)$msg->ToUserName, ENT_QUOTES, 'UTF-8') : '';
    $msgType = isset($msg->MsgType) ? strtolower(trim((string)$msg->MsgType)) : 'unknown';
    $MsgId = isset($msg->MsgId) ? htmlspecialchars((string)$msg->MsgId, ENT_QUOTES, 'UTF-8') : '';
    $CreateTime = isset($msg->CreateTime) ? htmlspecialchars((string)$msg->CreateTime, ENT_QUOTES, 'UTF-8') :‘’;
    $SaveTime = ($CreateTime?(new DateTime("@".(int)$CreateTime)): time())->format('Y-m-d H:i:s'); // 通过@符号传入时间戳
    //处理文本
    $content = isset($msg->Content) ? htmlspecialchars((string)$msg->Content, ENT_QUOTES, 'UTF-8') : '';
    //处理图片
    $PicUrl = isset($msg->PicUrl) ? htmlspecialchars((string)$msg->PicUrl, ENT_QUOTES, 'UTF-8') : '';
    //处理事件
    
    $Event = isset($msg->Event) ? trim((string)$msg->Event) : 'unknown';
    //事件debug_demo  微信调试
    $debug_str = isset($msg->debug_str) ? htmlspecialchars((string)$msg->debug_str, ENT_QUOTES, 'UTF-8') : '';
    //事件location  地理
    $Latitude = isset($msg->Latitude) ? htmlspecialchars((string)$msg->Latitude, ENT_QUOTES, 'UTF-8') : '';
    $Longitude = isset($msg->Longitude) ? htmlspecialchars((string)$msg->Longitude, ENT_QUOTES, 'UTF-8') : '';
    $Precision = isset($msg->Precision) ? htmlspecialchars((string)$msg->Precision, ENT_QUOTES, 'UTF-8') : '';
    
    
     //事件lwxa_widget_data  小程序Widge
    $Query = isset($msg->Query) ? htmlspecialchars((string)$msg->Query, ENT_QUOTES, 'UTF-8') : '';
    $Scene = isset($msg->Scene) ? htmlspecialchars((string)$msg->Scene, ENT_QUOTES, 'UTF-8') : '';
   
    
   //处理音频
    $MediaId= isset($msg->MediaId) ? htmlspecialchars((string)$msg->MediaId, ENT_QUOTES, 'UTF-8') : '';
    $MediaFormat = isset($msg->Format) ? htmlspecialchars((string)$msg->Format, ENT_QUOTES, 'UTF-8') : '';
   
   
   //处理视频
    $ThumbMediaId = isset($msg->ThumbMediaId) ? htmlspecialchars((string)$msg->ThumbMediaId, ENT_QUOTES, 'UTF-8') : '';
   //处理地理位置
    $Location_X = isset($msg->Location_X) ? htmlspecialchars((string)$msg->Location_X, ENT_QUOTES, 'UTF-8') : '';
    $Location_Y  = isset($msg->Location_Y ) ? htmlspecialchars((string)$msg->Location_Y , ENT_QUOTES, 'UTF-8') : '';
    $Scale = isset($msg->Scale) ? htmlspecialchars((string)$msg->Scale, ENT_QUOTES, 'UTF-8') : '';
    $Label = isset($msg->Label) ? htmlspecialchars((string)$msg->Label, ENT_QUOTES, 'UTF-8') : '';
   //处理链接
    $Title = isset($msg->Title) ? htmlspecialchars((string)$msg->Title, ENT_QUOTES, 'UTF-8') : '';
    $Description = isset($msg->Description) ? htmlspecialchars((string)$msg->Description, ENT_QUOTES, 'UTF-8') : '';
    $Url = isset($msg->Url) ? htmlspecialchars((string)$msg->Url, ENT_QUOTES, 'UTF-8') : '';
  
    if (!$fromUser || !$toUser || !$msgType) {
        return generateXmlErrorResponse("无效的消息格式", $fromUser, $toUser);
    }

    

    // 生成回复内容
    $reply = '';
    $SaveContent='';
    switch ($msgType) {
        case 'text':
            $SaveContent = $MsgId. ":".$content;
             $reply = "您发送了文本消息:{" .$content."}";
             $replyXml = "<xml>
                    <ToUserName><![CDATA[$fromUser]]></ToUserName>
                    <FromUserName><![CDATA[$toUser]]></FromUserName>
                    <CreateTime>" . time() . "</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[$reply]]></Content>
                 </xml>";
         
            break;
        case 'event':
            
              

            if ($Event == 'ENTER') {
                $SaveContent = $MsgId . "：" . $Event;
                $reply = "您发送了事件会话:{" . $Event . "}";
                  // 构建回复XML
              
            } elseif ($Event == 'LOCATION') {
                // 注意：需确保 $Latitude, $Longitude, $Precision 变量已定义
                $SaveContent = $MsgId . "：" . $Event . "," . $Latitude . "," . $Longitude . "," . $Precision;
                $reply = "您发送了事件地理位置:{" .  $Latitude . "," . $Longitude . "," . $Precision."}";
                
            } elseif ($Event == 'wxa_widget_data') {
              
                $SaveContent = $Event . "：" . $Query . "," . $Scene ;
                $reply = "您发送了小程序Widget消息事件:{" .$SaveContent ."}";
            
            } elseif ($Event == 'debug_demo') {
                // 合并两个相同逻辑的 case
                // 注意：需确保 $debug_str 变量已定义
                $SaveContent = $MsgId . "：" . $Event . "," . $debug_str;
                $reply = "您发送了事件调试Demo{" . $SaveContent ."}";
            } else {
                // 默认处理逻辑
                $SaveContent = $MsgId . "：" . $Event;
                $reply = "您发送了事件:{" . $SaveContent ."}";
            }
              break;           
            
         case 'image':
            $SaveContent = $MsgId. "：". $PicUrl;
           $reply = "您发送了图片:{" .  $PicUrl."}";
       
            break;
        case 'voice':
            $SaveContent =$MsgId. "：". $MediaId.",". $MediaFormat;
            $reply = "您发送了音频:{" . $MediaId."}";
            break;
         case 'video':
            $SaveContent =$MsgId. "：". $MediaId.",". $ThumbMediaId;
            $reply = "您发送了视频:{" .  $MediaId."}";
            break;
        case 'location':
            $SaveContent =$MsgId. "：".$Location_X.",".$Location_Y.",". $Scale.",".$Label;
            $reply = "您发送了地理位置:{" .",". $Label.",". $Location_X.",".$Location_Y.",". $Scale."}";
            break;  
            
        case 'link':
            $SaveContent =$MsgId. "：". $Title.",".$Description.",". $Url;
            $reply = "您发送了链接:{" . $Title.",".$Description.",". $Url."}";
            break;   
        // 处理其他消息类型
        default:
            $reply = "抱歉，我暂不支持这种消息类型";
             
    }
    
    

     

  
   // $logger->info("Received message: fromUser={$fromUser}, toUser={$toUser}, msgType={$msgType}, content={$SaveContent}");

    if (!messageToDb($db, $fromUser, $msgType, $SaveContent,  $SaveTime)) {
        return generateXmlErrorResponse("服务器内部错误", $fromUser, $toUser);
    }
 
 $reply_msgType='news';
 switch ($reply_msgType) {
        case 'text':
           
             $replyXml = "<xml>
                    <ToUserName><![CDATA[$fromUser]]></ToUserName>
                    <FromUserName><![CDATA[$toUser]]></FromUserName>
                    <CreateTime>" . time() . "</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[$reply]]></Content>
                 </xml>";
         
            break;
//         case 'event':
            
              

//             if ($Event == 'ENTER') {
//                   $replyXml = "<xml>
//                     <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[text]]></MsgType>
//                     <Content><![CDATA[$reply]]></Content>
//                  </xml>";
//             } elseif ($Event == 'LOCATION') {
//                  $replyXml = "<xml>
//                     <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[text]]></MsgType>
//                     <Content><![CDATA[$reply]]></Content>
//                  </xml>";
                
//             } elseif ($Event == 'wxa_widget_data') {
              
//                  $replyXml = "<xml>
//                     <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[text]]></MsgType>
//                     <Content><![CDATA[$reply]]></Content>
//                  </xml>";
            
//             } elseif ($Event == 'debug_demo') {
//               $replyXml = "<xml>
//                     <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[text]]></MsgType>
//                     <Content><![CDATA[$reply]]></Content>
//                  </xml>";
//             } else {
//                  $replyXml = "<xml>
//                     <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[text]]></MsgType>
//                     <Content><![CDATA[$reply]]></Content>
//                  </xml>";
//             }
//               break;           
            
//          case 'image':
         
          
//             $replyXml = "<xml>
//                   <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     MsgType><![CDATA[image]]></MsgType>
//                     <Image>
//                     <MediaId><![CDATA[$replyImageMediaId]]></MediaId>
//                     </Image>
//                         </xml>
//                     ";
//             break;
//         case 'voice':
//               $replyXml = "<xml>
//                   <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                     <MsgType><![CDATA[voice]]></MsgType>
//                   <Voice>
//                     <MediaId><![CDATA[$replyVoiceMediaId]]></MediaId>
//                   </Voice>
//                   </xml>
//                     ";
//             break;
//          case 'video':
//              $replyXml = "<xml>
//                   <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                      <MsgType><![CDATA[video]]></MsgType>
//                      <Video>
//                     <MediaId><![CDATA[$replyVideoMediaId]]></MediaId>
//                     <Title><![CDATA[$replyVideoTitle]]></Title>
//                     <Description><![CDATA[$replyVideoDesc]]></Description>
//   </Video>
// </xml>
//                     ";
//             break;
//         case 'music':
//               $replyXml = "<xml>
//                   <ToUserName><![CDATA[$fromUser]]></ToUserName>
//                     <FromUserName><![CDATA[$toUser]]></FromUserName>
//                     <CreateTime>" . time() . "</CreateTime>
//                   <MsgType><![CDATA[music]]></MsgType>
//                   <Music>
//                   <Title><![CDATA[$replyMusicTitle]]></Title>
//                   <Description><![CDATA[$replyMusicDesc]]></Description>
//                   <MusicUrl><![CDATA[$replyMusicId]]></MusicUrl>
//                   <HQMusicUrl><![CDATA[$replyHQMusicUrl]]></HQMusicUrl>
//                   <ThumbMediaId><![CDATA[$replyHQMediaId]]></ThumbMediaId>
//                   </Music>
//                  </xml>
//                     ";
//             break;  
            
        case 'news':
              $replyXml = "<xml>
                  <ToUserName><![CDATA[$fromUser]]></ToUserName>
                    <FromUserName><![CDATA[$toUser]]></FromUserName>
                    <CreateTime>" . time() . "</CreateTime>
                     <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>8</ArticleCount>
                  <Articles>
                    <item>
                  <Title><![CDATA[$reply]]></Title>
                  <Description><![CDATA[股票大数据学习第一课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/y4iwpHQp5XuaBcFWYv_tDw]]></Url>
                    </item>
                           <item>
                  <Title><![CDATA[利用colab部署一个本地deepseek R1]]></Title>
                  <Description><![CDATA[利用colab部署一个本地deepseek R1]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAX4smwmNL4rrjo0a7WzYFt2M9ObiaZu7l1mXce8VH9B7UpTwniaib7dMPw/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/gJShEMJ0o98qrhoq1wj8kw]]></Url>
                    </item>
              
                     <item>
                  <Title><![CDATA[大数据杂谈]]></Title>
                  <Description><![CDATA[大数据杂谈]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/lyhITCt7ZmU4ysv-B7l1dw]]></Url>
                    </item>
                    <item>
                      <Title><![CDATA[股票大数据学习第八课]]></Title>
                  <Description><![CDATA[股票大数据学习第八课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/_RgOOcSc_MmLDH6jC5L7qA]]></Url>
                    </item>
                   <item>
                      <Title><![CDATA[股票大数据学习第七课]]></Title>
                  <Description><![CDATA[股票大数据学习第七课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/-aG8gTar_eSIvTD0qIbn3A]]></Url>
                    </item>
                     <item>
                      <Title><![CDATA[股票大数据学习第三课]]></Title>
                  <Description><![CDATA[股票大数据学习第三课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/YsU5-LlNNzCRmgKTYVBSMQ]]></Url>
                    </item>
                   <item>
                      <Title><![CDATA[股票大数据学习第四课]]></Title>
                  <Description><![CDATA[股票大数据学习第四课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/32ePjEh7PRTAv9Q_t3cC0g]]></Url>
                    </item>
                       <item>
                      <Title><![CDATA[股票大数据学习第六课]]></Title>
                  <Description><![CDATA[股票大数据学习第六课]]></Description>
                  <PicUrl><![CDATA[http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0]]></PicUrl>
                  <Url><![CDATA[https://mp.weixin.qq.com/s/z56GExivlhqfk8qmeLWUPw]]></Url>
                    </item>
                  </Articles>
                    </xml>
                    ";
               break;   
        // 处理其他消息类型
        default:
            //$reply = "抱歉，我暂不支持这种消息类型";
             // $replyXml ="success";
              
              $replyXml = "<xml>
                    <ToUserName><![CDATA[$fromUser]]></ToUserName>
                    <FromUserName><![CDATA[$toUser]]></FromUserName>
                    <CreateTime>" . time() . "</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[$reply]]></Content>
                 </xml>";
           
            

            break;   
           
    }
 
           
             

    return $replyXml;
}
?>