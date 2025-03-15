<?php
// filename: receive.php

namespace Receive;


class Receive {
    public static function parse_xml($web_data,$logger) {
        if (empty($web_data)) {
         
            return null;
          
        }
      
        // 使用 SimpleXMLElement 解析 XML
        //$xmlData = new SimpleXMLElement($web_data);
         $xmlData = $web_data;
       
         $msg_type = (string)$xmlData->MsgType;
        // $logger->info("处理msgType : " .$msg_type ); 
        if ($msg_type === 'event') {
                $eventType = (string)$xmlData->Event;
                if ($eventType === 'CLICK') {
                    return new ClickEvent($xmlData);
                }
              //  Uncomment and adapt these if needed:
                elseif (in_array($eventType, ['subscribe_msg_popup_event','subscribe_msg_change_event','subscribe_msg_sent_event'])) {
                    return new SubscribeMsgEvent($xmlData);
                }
                  elseif ($eventType === 'subscribe' || $eventType === 'SCAN' ) {
                    return new subscribeEvent($xmlData);
                }
                  elseif ($eventType === 'MASSSENDJOBFINISH') {
                    return new MassSendJobFinishEvent($xmlData);
                }
                
                 elseif ($eventType === 'TEMPLATESENDJOBFINISH') {
                    return new templateEvent($xmlData);
                }
                elseif ($eventType === 'VIEW' || $eventType === 'view_miniprogram' ) {
                    return new ViewEvent($xmlData);
                }
                elseif ($eventType === 'LOCATION') {
                    return new LocationEvent($xmlData);
                }
                elseif ($eventType === 'scancode_push' || $eventType === 'scancode_waitmsg' ) {
                    return new scancodeEvent($xmlData);
                }
                
                 elseif ($eventType === 'pic_sysphoto' || $eventType === 'pic_photo_or_album' || $eventType === 'pic_weixin') {
                    return new PicEvent($xmlData);
                }
                    elseif ($eventType === 'location_select' ) {
                    return new locationselectEvent($xmlData);
                }
                
                 elseif ($eventType === 'PUBLISHJOBFINISH') {
                    return new PublishEvent($xmlData);
                }
                                   }
        elseif ($msg_type === 'text') {
           
              return new TextMsg($xmlData);
           
                                      } 
        elseif ($msg_type === 'image') {
            return new ImageMsg($xmlData);
        }
        
        
          elseif ($msg_type === 'voice') {
            return new VoiceMsg($xmlData);
        }
        
          elseif ($msg_type === 'video' || $msg_type === 'shortvideo') {
            return new VideoMsg($xmlData);
        }
            elseif ($msg_type === 'link') {
            return new LinkMsg($xmlData);
        }
        elseif ($msg_type === 'location') {
            return new LocationMsg($xmlData);
        }
        $logger->info("不匹配的msgType,返回Null"); 
         return null; // Return null if no conditions match
     }
}

class Msg {
    public $ToUserName;
    public $FromUserName;
    public $CreateTime;
    public $MsgType;
    public $MsgId;
    public $MsgContent; // 显式声明属性

    public function __construct($xmlData) {
        $this->ToUserName = (string)$xmlData->ToUserName;
        $this->FromUserName = (string)$xmlData->FromUserName;
        $this->CreateTime = (string)$xmlData->CreateTime;
        $this->MsgType = (string)$xmlData->MsgType;
        $this->MsgId = (string)$xmlData->MsgId;
        $this->MsgContent = "{'".$this->MsgId."'}";
    }
    

    
}

class TextMsg extends Msg {
    public $Content;
    public $MsgDataId;
    public $Idx;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        
        // 强制转换为 UTF-8 编码的字符串
        $this->Content =(string)$xmlData->Content;
        $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Content":"'.$this->Content.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';
    }
}



class ImageMsg extends Msg {
    public $PicUrl;
    public $MediaId;
    public $MsgDataId;
    public $Idx;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->PicUrl = (string)$xmlData->PicUrl;
        $this->MediaId = (string)$xmlData->MediaId;
       $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","MediaId":"'.$this->MediaId.'","PicUrl":"'. $this->PicUrl.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';
    }
}



class VoiceMsg extends Msg {
    public $Format;
    public $MediaId;
    public $MsgDataId;
    public $Idx;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Format = (string)$xmlData->Format;
        $this->MediaId = (string)$xmlData->MediaId;
         $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
           $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","MediaId":"'.$this->MediaId.'","Format":"'. $this->Format.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';
}

}


//视频或小视频
class VideoMsg extends Msg {
    public $ThumbMediaId;
    public $MediaId;
    public $MsgDataId;
    public $Idx;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->ThumbMediaId = (string)$xmlData->ThumbMediaId;
        $this->MediaId = (string)$xmlData->MediaId;
        $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","MediaId":"'.$this->MediaId.'","ThumbMediaId":"'. $this->ThumbMediaId.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';

    }
}


class LocationMsg extends Msg {
    public $Location_X;
    public $Location_Y;
    public $Scale;
    public $Label;
    public $MsgDataId;
    public $Idx;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Location_X = (string)$xmlData->Location_X;
        $this->Location_Y = (string)$xmlData->Location_Y;
        $this->Scale = (string)$xmlData->Scale;
        $this->Label = (string)$xmlData->Label;
        $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Location_X":"'.$this->Location_X.'","Location_Y":"'. $this->Location_Y.'","Scale":"'. $this->Scale.'","Label":"'. $this->Label.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';
    }
}





class LinkMsg extends Msg {
    public $Title;
    public $Description;
    public $Url;
    public $MsgDataId;
    public $Idx;


    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Title = (string)$xmlData->Title ?? '';
        $this->Url = (string)$xmlData->Url ?? '';
        $this->Description= (string)$xmlData->Description ?? '';
        $this->MsgDataId =(string)$xmlData->MsgDataId ?? '';
        $this->Idx =(int)$xmlData->Idx ?? 0;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Title":"'.$this->Title.'","Description":"'. $this->Description.'","Url":"'. $this->Url.'","MsgDataId":"'. $this->MsgDataId.'","Idx":"'. $this->Idx.'"}';
    }
}




class EventMsg {
    public $ToUserName;
    public $FromUserName;
    public $CreateTime;
    public $MsgId;
    public $MsgType;
    public $Event;
    public $MsgContent; // 显式声明属性


    public function __construct($xmlData) {
        $this->ToUserName = (string)$xmlData->ToUserName;
        $this->FromUserName = (string)$xmlData->FromUserName;
        $this->CreateTime = (string)$xmlData->CreateTime;
        $this->MsgType = (string)$xmlData->MsgType;
        $this->Event = (string)$xmlData->Event;
        $this->MsgId = (string)$xmlData->MsgId ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'"}';
    }
}

class ClickEvent extends EventMsg {
    public $Eventkey;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Eventkey = (string)$xmlData->EventKey ?? '';
         $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'"}';
    }
}

class ViewEvent extends EventMsg {
    public $Eventkey;
    public $MenuId;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Eventkey = (string)$xmlData->EventKey ?? '';
        $this->MenuId = (string)$xmlData->MenuId ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'","MenuId":"'.$this->MenuId.'"}';
    }
}

class subscribeEvent extends EventMsg {
    public $Eventkey;
    public $Ticket;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Eventkey = (string)$xmlData->EventKey ?? '';
        $this->Ticket = (string)$xmlData->Ticket ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'","Ticket":"'.$this->Ticket.'"}';
    }
}



class scancodeEvent extends EventMsg {
    public $Eventkey;
    public $ScanType;
    public $ScanResult;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Eventkey = (string)$xmlData->EventKey ?? '';
        $this->ScanType = (string)$xmlData->ScanCodeInfo->ScanType ?? '';
        $this->ScanResult = (string)$xmlData->ScanCodeInfo->ScanResult ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'","ScanCodeInfo":{"ScanType":"'.$this->ScanType.'","ScanResult":"'.$this->ScanResult.'"}}';
    }
}


class locationselectEvent extends EventMsg {
    public $Eventkey;
    public $Location_X;
    public $Location_Y;
    public $Scale;
    public $Label;
    public $Poiname;
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Eventkey = (string)$xmlData->SendLocationInfo->EventKey ?? '';
        $this->Location_X = (string)$xmlData->SendLocationInfo->ELocation_X ?? '';
        $this->Location_Y = (string)$xmlData->SendLocationInfo->ELocation_Y ?? '';
        $this->Scale = (string)$xmlData->SendLocationInfo->EScale ?? '';
        $this->Label = (string)$xmlData->SendLocationInfo->ELabel ?? '';
        $this->Poiname = (string)$xmlData->SendLocationInfo->EPoiname ?? '';
      
       
      $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'","SendLocationInfo":{"Location_X":"'.$this->Location_X.'","Location_Y":"'.$this->Location_Y.'","Scale":"'.$this->Scale.'","Label":"'.$this->Label.'","Poiname":"'.$this->Poiname.'"}}';
        
        
        
    }
}

// 子类 PicEvent
class PicEvent extends EventMsg {
    public $EventKey;
    public $SendPicsInfo;

    public function __construct($xmlData) {
        // 调用父类构造函数解析基本字段
        parent::__construct($xmlData);

        // 解析 PicEvent 特有的字段
        $this->parsePicEvent($xmlData);
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Eventkey":"'.$this->Eventkey.'"}';
    }

    private function parsePicEvent($xmlData) {
       // $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
          $xml = $xmlData;
        if ($xml === false) {
            throw new Exception("XML 解析失败");
        }

        // 解析 EventKey
        $this->EventKey = (string)$xml->EventKey ?? '';

        // 初始化 SendPicsInfo
        $this->SendPicsInfo = new stdClass();
        $this->SendPicsInfo->Count = (int)$xml->SendPicsInfo->Count ?? 0;

        // 解析 PicList 中的 item（可能有多个）
        $this->SendPicsInfo->PicList = [];
        foreach ($xml->SendPicsInfo->PicList->item as $item) {
            $picItem = new stdClass();
            $picItem->PicMd5Sum = (string)$item->PicMd5Sum ?? '';
            $this->SendPicsInfo->PicList[] = $picItem;
        }
    }
}


// 子类 SubscribeMsgEvent
class SubscribeMsgEvent extends EventMsg {
    public $SubscribeMsgData; // 通用存储事件数据的属性

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->parseSubscribeMsgData($xmlData);
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'"}';
    }

    private function parseSubscribeMsgData($xmlData) {
       // $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
         $xml = $xmlData;
        if ($xml === false) {
            throw new Exception("XML 解析失败");
        }

        $this->SubscribeMsgData = new stdClass();
        $this->SubscribeMsgData->List = [];

        // 根据 Event 类型选择对应的节点
        switch ($this->Event) {
        case 'subscribe_msg_popup_event':
            $eventNode = $xml->SubscribeMsgPopupEvent;
            break;
        case 'subscribe_msg_change_event':
            $eventNode = $xml->SubscribeMsgChangeEvent;
            break;
        case 'subscribe_msg_sent_event':
            $eventNode = $xml->SubscribeMsgSentEvent;
            break;
        default:
            throw new Exception("不支持的 Event 类型: " . $this->Event);
    }
        // 解析 List（可能有多个）
        foreach ($eventNode->List as $listItem) {
            $item = new stdClass();

            // 通用字段
            $item->TemplateId = (string)$listItem->TemplateId ?? '';
            $item->SubscribeStatusString = isset($listItem->SubscribeStatusString) ? (string)$listItem->SubscribeStatusString : null;

            // subscribe_msg_popup_event 特有字段
            if ($this->Event === 'subscribe_msg_popup_event') {
                $item->PopupScene = (string)$listItem->PopupScene ?? '';
            }

            // subscribe_msg_sent_event 特有字段
            if ($this->Event === 'subscribe_msg_sent_event') {
                $item->MsgID = (string)$listItem->MsgID ?? '';
                $item->ErrorCode = (string)$listItem->ErrorCode ?? '';
                $item->ErrorStatus = (string)$listItem->ErrorStatus ?? '';
            }

            $this->SubscribeMsgData->List[] = $item;
        }
    }
}





class LocationEvent extends EventMsg {
    public $Latitude;
    public $Longitude;
    public $Precision;
 
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Latitude = (string)$xmlData->Latitude;
        $this->Longitude = (string)$xmlData->Longitude;
        $this->Precision = (string)$xmlData->Precision;
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'",Latitude":"'.$this->Latitude.'","Longitude":"'.$this->Longitude.'","Precision":"'.$this->Precision.'"}';
        
        
    }
}

// 子类 PublishEvent
class PublishEvent extends EventMsg {
    public $PublishEventInfo;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->parsePublishEventInfo($xmlData);
    }

    private function parsePublishEventInfo($xmlData) {
   //    $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml = $xmlData;
        if ($xml === false) {
            throw new Exception("XML 解析失败");
        }

        $this->PublishEventInfo = new stdClass();

        // 填充公共字段
        $this->PublishEventInfo->publish_id = (string)$xml->PublishEventInfo->publish_id ?? '';
        $this->PublishEventInfo->publish_status = (string)$xml->PublishEventInfo->publish_status ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","publish_id":"'.$this->PublishEventInfo->publish_id .'","publish_status":"'.$this->PublishEventInfo->publish_status.'"}';
        // 处理 article_id（如果存在）
        if (isset($xml->PublishEventInfo->article_id)) {
            $this->PublishEventInfo->article_id = (string)$xml->PublishEventInfo->article_id ?? '';
          
        }

        // 处理 article_detail（如果存在）
        if (isset($xml->PublishEventInfo->article_detail)) {
            $this->PublishEventInfo->article_detail = new stdClass();
            $this->PublishEventInfo->article_detail->count = (int)$xml->PublishEventInfo->article_detail->count;

            $this->PublishEventInfo->article_detail->items = [];
            foreach ($xml->PublishEventInfo->article_detail->item as $item) {
                $itemData = new stdClass();
                $itemData->idx = (string)$item->idx ?? '';
                $itemData->article_url = (string)$item->article_url ?? '';
                $this->PublishEventInfo->article_detail->items[] = $itemData;
            }
        }

        // 处理 fail_idx（如果存在，可能有多个）
        if (isset($xml->PublishEventInfo->fail_idx)) {
            $this->PublishEventInfo->fail_idx = [];
            foreach ($xml->PublishEventInfo->fail_idx as $failIdx) {
                $this->PublishEventInfo->fail_idx[] = (int)$failIdx;
            }
        }
    }
}

        class templateEvent extends EventMsg {
            public $Status;
          
         
            public function __construct($xmlData) {
                parent::__construct($xmlData);
                $this->Status = (string)$xmlData->Status;
                $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Status":"'.$this->Status.'"}';
             
            }
        }


// 子类 MassSendJobFinishEvent
class MassSendJobFinishEvent extends EventMsg {
    
    public $Status;
    public $TotalCount;
    public $FilterCount;
    public $SentCount;
    public $ErrorCount;
    public $CopyrightCheckResult;
    public $ArticleUrlResult;

    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->parseMassSendData($xmlData);
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Status":"'.$this->Status.'"}';
       
    }

    private function parseMassSendData($xmlData) {
       // $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
         $xml =$xmlData;
        if ($xml === false) {
            throw new Exception("XML 解析失败");
        }

        // 解析基本字段
      
        $this->Status = (string)$xml->Status ?? '';
        $this->TotalCount = (int)$xml->TotalCount ?? 0;
        $this->FilterCount = (int)$xml->FilterCount ?? 0;
        $this->SentCount = (int)$xml->SentCount ?? 0;
        $this->ErrorCount = (int)$xml->ErrorCount ?? 0;

        // 解析 CopyrightCheckResult
        $this->CopyrightCheckResult = new stdClass();
        $this->CopyrightCheckResult->Count = (string)$xml->CopyrightCheckResult->Count  ?? '';
        $this->CopyrightCheckResult->CheckState = (string)$xml->CopyrightCheckResult->CheckState  ?? '';

        $this->CopyrightCheckResult->ResultList = [];
        foreach ($xml->CopyrightCheckResult->ResultList->item as $item) {
            $resultItem = new stdClass();
            $resultItem->ArticleIdx = (string)$item->ArticleIdx  ?? '';
            $resultItem->UserDeclareState = (string)$item->UserDeclareState  ?? '';
            $resultItem->AuditState = (string)$item->AuditState  ?? '';
            $resultItem->OriginalArticleUrl = (string)$item->OriginalArticleUrl  ?? '';
            $resultItem->OriginalArticleType = (string)$item->OriginalArticleType  ?? '';
            $resultItem->CanReprint = (string)$item->CanReprint  ?? '';
            $resultItem->NeedReplaceContent = (string)$item->NeedReplaceContent  ?? '';
            $resultItem->NeedShowReprintSource = (string)$item->NeedShowReprintSource  ?? '';
            $this->CopyrightCheckResult->ResultList[] = $resultItem;
        }

        // 解析 ArticleUrlResult
        $this->ArticleUrlResult = new stdClass();
        $this->ArticleUrlResult->Count = (int)$xml->ArticleUrlResult->Count ?? 0;

        $this->ArticleUrlResult->ResultList = [];
        foreach ($xml->ArticleUrlResult->ResultList->item as $item) {
            $urlItem = new stdClass();
            $urlItem->ArticleIdx = (int)$item->ArticleIdx ?? 0;
            $urlItem->ArticleUrl = (string)$item->ArticleUrl ?? '';
            $this->ArticleUrlResult->ResultList[] = $urlItem;
        }
    }
}


class DebugEvent extends EventMsg {
    public $debug_str;
  
 
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->debug_str = (string)$xmlData->debug_str ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","debug_str":"'.$this->debug_str.'"}';
  
    }
}

   //事件lwxa_widget_data  小程序Widge
class lwxaEvent extends EventMsg {
    public $Query;
    public $Scene;
  
 
    public function __construct($xmlData) {
        parent::__construct($xmlData);
        $this->Query = (string)$xmlData->Query ?? '';
        $this->Scene = (string)$xmlData->Scene ?? '';
        $this->MsgContent = '{"MsgId":"'.$this->MsgId. '","Event":"'.$this->Event.'","Query":"'.$this->Query.'","Scene":"'.$this->Scene.'"}';
       
    }
}


?>