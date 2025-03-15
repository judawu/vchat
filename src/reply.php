<?php
// filename: reply.php
namespace Reply;

class Msg {
    public function __construct() {
    }

    public function send() {
        return "success";
    }
}

class TextMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName, $content) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['Content'] = $content;
    }

    public function send() {
        $xmlForm = "
            <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['Content']
        ]);
    }
}

class VoiceMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName, $mediaId) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['MediaId'] = $mediaId;
    }

    public function send() {
        $xmlForm = "
         <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType><![CDATA[voice]]></MsgType>
                <Voice>
                <MediaId><![CDATA[%s]]></MediaId>
                </Voice>
                </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['MediaId']
        ]);
    }
}

class VideoMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName, $mediaId,$title,$description) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['MediaId'] = $mediaId;
        $this->data['Title'] = $title;
        $this->data['Description'] = $description;
    }

    public function send() {
        $xmlForm = "
            <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType><![CDATA[video]]></MsgType>
                 <Video>
                <MediaId><![CDATA[%s]]></MediaId>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                </Video>
               </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['MediaId'],
            $this->data['Title'],
            $this->data['Description']
        ]);
    }
}


class MusicMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName, $mediaId,$title,$description,$musicurl,$hqmusicurl) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['MediaId'] = $mediaId;
        $this->data['Title'] = $title;
        $this->data['Description'] = $description;
        $this->data['MusicUrl'] = $musicurl;
        $this->data['HQMusicUrl'] = $hqmusicurl;
    }

    public function send() {
       
         $xmlForm = "
            <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType><![CDATA[music]]></MsgType>
              <Music>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <MusicUrl><![CDATA[%s]]></MusicUrl>
                <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
              </Music>
               </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['Title'],
            $this->data['Description'],
            $this->data['MusicUrl'],
            $this->data['HQMusicUrl'],
            $this->data['MediaId']
        ]);
    }
}


class ArticleMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName,$title,$description,$picurl,$url) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['Title'] = $title;
        $this->data['Description'] = $description;
        $this->data['PicUrl'] = $picurl;
        $this->data['Url'] = $url;
    }

    public function send() {
        $xmlForm = "
            <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                 <MsgType><![CDATA[news]]></MsgType>
                  <ArticleCount>1</ArticleCount>
                  <Articles>
                    <item>
                      <Title><![CDATA[%s]]></Title>
                      <Description><![CDATA[%s]]></Description>
                      <PicUrl><![CDATA[%s]]></PicUrl>
                      <Url><![CDATA[%s]]></Url>
                    </item>
                  </Articles>
                </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['Title'],
            $this->data['Description'],
            $this->data['PicUrl'],
            $this->data['Url']
      
        ]);
       
    }
}



class ImageMsg extends Msg {
    private $data = [];

    public function __construct($toUserName, $fromUserName, $mediaId) {
        parent::__construct();
        $this->data['ToUserName'] = $toUserName;
        $this->data['FromUserName'] = $fromUserName;
        $this->data['CreateTime'] = time();
        $this->data['MediaId'] = $mediaId;
    }

    public function send() {
        $xmlForm = "
            <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType><![CDATA[image]]></MsgType>
                <Image>
                <MediaId><![CDATA[%s]]></MediaId>
                </Image>
                </xml>
        ";
        return $this->formatXml($xmlForm);
    }

    private function formatXml($template) {
        return vsprintf(trim($template), [
            $this->data['ToUserName'],
            $this->data['FromUserName'],
            $this->data['CreateTime'],
            $this->data['MediaId']
        ]);
    }
}
?>