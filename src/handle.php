<?php
// filename: handle.php

require_once 'reply.php';
require_once 'receive.php';
require_once 'commonAPI.php';




class Handle {
    public function handleXmlMessage($xmldata, $db,$logger,$iplocation) {
        try {
        
          
            $recMsg = \Receive\Receive::parse_xml($xmldata,$logger);
            
           
            
            if ($recMsg instanceof \Receive\EventMsg) { // Replace EventMsg with the actual class name
                   $logger->info("处理EventMsg"); 
                    $toUser = $recMsg->FromUserName;
                    $fromUser = $recMsg->ToUserName;
                    $msgType=$recMsg->MsgType;
                    $MsgContent = $recMsg->MsgContent;
                    $CreateTime= $recMsg->CreateTime;
                    $SaveTime=  ($CreateTime?(new DateTime("@".(int)$CreateTime)): new DateTime())->format('Y-m-d H:i:s'); 
                    
                      if (! $this->messageToDb($db, $toUser, $msgType, $MsgContent,  $SaveTime)) {
                            return generateJsonErrorResponse("服务器内部错误", $toUser, $fromUser);
                 }
                    if ($recMsg->Event === 'CLICK') {
                        if ($recMsg->Eventkey === 'mpGuide') {
                            $content = mb_convert_encoding("编写中，尚未完成", 'UTF-8'); // UTF-8 encoding
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content); // Replace TextMsg with actual class
                            return $replyMsg->send();
                        }
                        else{
                           
                           // $logger->info(" $content"); 
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $MsgContent); // Replace TextMsg with actual class
                            return $replyMsg->send();
                        }
                    }
                    
                    if ($recMsg->Event === 'PUBLISHJOBFINISH') {
                        if ($recMsg->PublishEventInfo->publish_status === 0) {
                            
                            $content = mb_convert_encoding("发布成功", 'UTF-8'); // UTF-8 encoding
                            
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content); // Replace TextMsg with actual class
                            return $replyMsg->send();
                        }else {
                             $content = mb_convert_encoding("发布失败", 'UTF-8'); // UTF-8 encoding
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content); // Replace TextMsg with actual class
                            return $replyMsg->send();
                        }
                    }
                    
                    
                }
                            
            
            
               
            if ($recMsg instanceof \Receive\Msg) {
                 //$logger->info("处理Msg"); 
                $toUser = $recMsg->FromUserName;
                $fromUser = $recMsg->ToUserName;
                $msgType=$recMsg->MsgType;
                $MsgContent = $recMsg->MsgContent;
                $CreateTime= $recMsg->CreateTime;
                $SaveTime=  ($CreateTime?(new DateTime("@".(int)$CreateTime)): new DateTime())->format('Y-m-d H:i:s'); 
              //  $logger->info("处理recMsg: " .$MsgContent ); 
             if (! $this->messageToDb($db, $toUser, $msgType, $MsgContent,  $SaveTime)) {
                            return generateJsonErrorResponse("服务器内部错误", $toUser, $fromUser);
                 }
                    
    
    
    
    
                if ($recMsg->MsgType === 'text') {
                 
                    switch($recMsg->Content ) {
                          case '1':
                          $title="股票大数据学习";
                         $description="股票大数据学习第一课";
                         $picurl="http://mmbiz.qpic.cn/sz_mmbiz_jpg/4hhpjfAGvMianfWuIQoic2V6RwicuzJ2uRAAS5qKHKWU7OdBsHxl7pBOLIKJXYuHvK5jK5CdZtXH77fhaUwRTkNXg/0";
                         $url="https://mp.weixin.qq.com/s/y4iwpHQp5XuaBcFWYv_tDw";
                         $replyMsg = new \Reply\ArticleMsg($toUser, $fromUser, $title,$description,$picurl,$url);
                              break;
                          case '2':
                           $content="输入21+城市名 直接查询天气(请输入英文）\n输入22 打开查询网站查询";
                          $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                              break;
                            case '3':
                            $content="听音乐正在开发中...";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                               case '4':
                            $content="看视频正在开发中...";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                               case '5':
                            $content="发送语言正在开发中...";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                            case '6':
                                     
                              
                            $content="输入61+IP地址查IP地址位置\n输入62+地址查经纬度(计划开发...)\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                            
                            case '7':
                            $content="输入chat+ 聊天内容 \n 或者打开我的研究AI聊天室\nhttps://vchat.juda.monster/AIChatDashboard.php\n 进行AI聊天体验\n（因为API调用消耗个人API 的Token，请自觉限制调用次数，这只是一个个人开发项目,聊天内容会被后台记录，请保护好你的暴露隐私）\n你也可以访问以下网站或者下载他们的app进行聊天\n星火讯飞:\nhttps://xinghuo.xfyun.cn\n百度文心:\nhttps://yiyan.baidu.com\n火山豆包:\nhttps://www.doubao.com\n月之暗面:\nhttps://kimi.moonshot.cn\n深度探索:\nhttps://www.deepseek.com\n腾讯混元:\nhttps://yuanbao.tencent.com\n阿里千问:\nhttps://tongyi.aliyun.com\n阶跃星辰:\nhttps://yuewen.cn\n智谱:\nhttps://chatglm.cn\n海螺:\nhttps://hailuoai.com\nGROK:\nhttps://grok.com\nOPENAI:\nhttps://chatgpt.com\nGemini:\nhttps://gemini.google.com\nCoplilot:\nhttps://copilot.microsoft.com\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                            case '8':
                            $content="股票大数据学习第八课\nhttps://mp.weixin.qq.com/s/_RgOOcSc_MmLDH6jC5L7qA\n股票大数据学习第七课\nhttps://mp.weixin.qq.com/s/-aG8gTar_eSIvTD0qIbn3A\n股票大数据学习第六课\nhttps://mp.weixin.qq.com/s/z56GExivlhqfk8qmeLWUPw\n股票大数据学习第五课\nhttps://mp.weixin.qq.com/s/sF89eE-urO7NZHvFIN_9jg\n股票大数据学习第四课\nhttps://mp.weixin.qq.com/s/32ePjEh7PRTAv9Q_t3cC0g\n股票大数据学习第三课\nhttps://mp.weixin.qq.com/s/YsU5-LlNNzCRmgKTYVBSMQ\n股票大数据学习第二课\nhttps://mp.weixin.qq.com/s/oK0neczYeJPBD7ewwDGcCg\n股票大数据学习第一课\nhttps://mp.weixin.qq.com/s/y4iwpHQp5XuaBcFWYv_tDw\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                            
                               case '9':
                            $content="AI主流大模型对比\nhttps://mp.weixin.qq.com/s/8RBJIDp0ovvvERnNwiDNsw\n利用colab部署一个本地deepseek R1\nhttps://mp.weixin.qq.com/s/gJShEMJ0o98qrhoq1wj8kw\n大数据杂谈\nhttps://mp.weixin.qq.com/s/lyhITCt7ZmU4ysv-B7l1dw\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                                  case '10':
                            $content="Markdown转换器与微信草稿操作工具\nhttps://mp.weixin.qq.com/s/SzknHl0qn3Tn94ujaX0cMw\n微信公众号自动发布功能后台开发\nhttps://mp.weixin.qq.com/s/6Bvmwq4sXzKQaHnQSKtu9g?\n新建小站\nhttps://mp.weixin.qq.com/s/ImoX8NXlW8n1GSF58-QfOA?\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                                    case '11':
                            $content="个人平台管理登陆\nhttps://vchat.juda.monster/DatabaseLogin.php\n微信消息加密演示\nhttps://vchat.juda.monster/WeixinDemo.php\nMarkdownHTML\nhttps://vchat.juda.monster/MarkdownHTMLconverter.php\n个人AI 聊天室\nhttps://vchat.juda.monster/AIChatDashboard.php\n个人网站\nhttps://vchat.juda.monster\n";
                            $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            break;
                           default:
                          // Check if content starts with "21+" for weather lookup
                            if (preg_match('/^21\+(.+)$/', $recMsg->Content, $matches)) {
                                $city = $matches[1]; // Extract city name after "21+"
                              
                                $content = getWeatherData($city); // Call the weather function
                               // $logger->info($content);
                                $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            }
                            
                             elseif (preg_match('/^61\+(.+)$/', $recMsg->Content, $matches)) {
                              $ip = $matches[1];
                                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                    $location = getIpLocation($ip);
                                    if ($location) {
                                        $content = "IP地址归属地：\n" .
                                                   "国家: " . ($location['country'] ?? '未知') . "\n" .
                                                   "地区: " . ($location['regionName'] ?? '未知') . "\n" .
                                                   "城市: " . ($location['city'] ?? '未知') . "\n" .
                                                   "ISP: " . ($location['isp'] ?? '未知');
                                    } else {
                                        $content = "无法获取 $ip 的地理位置信息";
                                    }
                                } else {
                                    $content = "不是有效的IP地址";
                                }
                                                          
                                $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            }
                             elseif (preg_match('/^chat\+(.+)$/', $recMsg->Content, $matches)) {
                               $user_prompt = $matches[1]; // Extract city name after "chat:+"
    
                                // 设置默认回复前缀
                                $prefix = "请遵守法律进行健康聊天\n每次回复都是一个单轮会话\n如需要多轮会话请输入7访问网页版\n下面是AI回复内容:\n";
                                
                                //记录开始时间
                                $startTime = microtime(true);
                                
                                // 设置超时时间为4秒
                                $timeout = 4;
                                
                                // 调用 rapid_AIchat 并尝试获取结果
                                $aiResponse = null;
                                try {
                                    set_time_limit($timeout); // 设置PHP脚本最大执行时间
                                    $aiResponse = rapid_AIchat($user_prompt);
                                } catch (Exception $e) {
                                    // 如果有异常（包括超时），捕获并处理
                                    $aiResponse = null;
                                }
                                
                                // 计算执行时间
                                $executionTime = microtime(true) - $startTime;
                                
                                // 如果超过4秒或返回为空，使用默认回复
                                if ($executionTime >= $timeout || $aiResponse === null) {
                                    $content = $prefix . "AI回复超时（超过5秒），你的问题太难了。";
                                } else {
                                    $content = $prefix . $aiResponse;
                                }
                                
                               
                                $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            }
                            
                            // Check if content is exactly "22" for URL
                            elseif ($recMsg->Content === '22') {
                                $content = "https://vchat.juda.monster/WeatherDashboard.php";
                                $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                            }
                            // Default case for unrecognized input
                            else {
                                $content = "你发送了文本消息:\n" . $recMsg->Content . "\n请回复,\n1: 阅读公众号文章\n2: 查天气和经纬度\n3: 听音乐\n4: 看视频\n5: 发语音\n6: 查位置\n7: AI聊天\n8:股票系列文章\n9:大模型系列文章\n10:微信公众号开发系列文章\n11:个人网站";
                                $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                             }
                            break;
                                            
                    }
                    
                    
                      
                    
                    
                    
                   
                    return $replyMsg->send();
                }
                if ($recMsg->MsgType === 'image') {
                    $mediaId = $recMsg->MediaId;
                    //$logger->info("记录 $mediaId"); 
                    $replyMsg = new \Reply\ImageMsg($toUser, $fromUser, $mediaId);
                    return $replyMsg->send();
                }
                
                  if ($recMsg->MsgType === 'voice') {
                    $mediaId = $recMsg->MediaId;
                   
                    $content = "你发了语音\n". $recMsg->MsgContent;
                    $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                    return $replyMsg->send();
                }
                
                  if ($recMsg->MsgType === 'video') {
                    $mediaId = $recMsg->MediaId;
                   
                      $content = "你发了视频:\n". $recMsg->MsgContent;
                      $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                    return $replyMsg->send();
                }
                
                  if ($recMsg->MsgType === 'location') {
                     $content = "你发了位置:\n". $recMsg->MsgContent;
                       $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                    return $replyMsg->send();
                }
                if ($recMsg->MsgType === 'link') {
                     $content = "你发了链接:\n". $recMsg->MsgContent;
                     $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                    return $replyMsg->send();
                }
                
                  if ($recMsg->MsgType === 'event') {
                     $content = "你发了事件\n". $recMsg->MsgContent;
                       $replyMsg = new \Reply\TextMsg($toUser, $fromUser, $content);
                    return $replyMsg->send();
                }
                // 其他类型返回默认 Msg
                return (new \Reply\Msg())->send();
            } else {
                echo "暂且不处理\n";
                return (new \Reply\Msg())->send();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function messageToDb($db, $fromUser, $msgType, $content, $saveTime) {
    
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
    
    
}



?>