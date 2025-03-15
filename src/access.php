<?php
// filename: Acess.php


class Access {
    private $accessToken = '';
    private $leftTime = 0;
    private $tokenFile = __DIR__ . '/access_token.json';

    private function realGetAccessToken() {
        $config = require __DIR__ . '/../config/config.php';
        $appId = $config['APPID'];
        $appSecret = $config['APPSECRET'];
        $postUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        
        $urlResp = file_get_contents($postUrl);
        if ($urlResp === false) {
            throw new Exception("Failed to fetch access token");
        }
        $data = json_decode($urlResp, true);
        if (!isset($data['access_token'])) {
            throw new Exception("Invalid response from token API");
        }
        
        $this->accessToken = $data['access_token'];
        $this->leftTime = $data['expires_in'];
        file_put_contents($this->tokenFile, json_encode(['token' => $this->accessToken, 'expires' => time() + $this->leftTime]));
    }

    public function getLeftTime() {
        return $this->leftTime;
    }

    public function getAccessToken() {
        if (file_exists($this->tokenFile)) {
            $data = json_decode(file_get_contents($this->tokenFile), true);
            if ($data && time() < $data['expires']) {
                $this->accessToken = $data['token'];
                $this->leftTime = $data['expires'] - time();
            }
        }
        if ($this->leftTime < 10) {
            $this->realGetAccessToken();
        }
        return $this->accessToken;
    }
      public function getStabletoken($force_refresh = false) {
         $config = require __DIR__ . '/../config/config.php';
        $postUrl = "https://api.weixin.qq.com/cgi-bin/stable_token";
        $postData = json_encode([
            "grant_type" => "client_credential",
            "appid"=> $config['APPID'],
            "secret" => $config['APPSECRET'],
           "force_refresh"  => $force_refresh
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        curl_close($ch);
        if ($urlResp === false) {
            throw new Exception("Failed to fetch access token");
        }
        $data = json_decode($urlResp, true);
        if (!isset($data['access_token'])) {
            throw new Exception("Invalid response from token API");
        }
        
        return $data['access_token'];
        
      
    }
    

}





class Common {
    public function __construct() {
        // No need for register_openers in PHP
    }
        public function get($commonfunction,$accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/{$commonfunction}?access_token={$accessToken}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);
        
        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
            echo "Can't fetch wexin server Ips";
        }
        
       }
    
    
       public function CallBackCheck($accessToken,$action,$checkoperator) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/callback/check?access_token={$accessToken}";
        $postData = json_encode([
              "action"=> $action,   //dns,ping,all
            "check_operator"=> $checkoperator //CHINANET,UNICOM,CAP,DEFAULT
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
     public function clear_quota($accessToken,$appid) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/clear_quota?access_token={$accessToken}";
        $postData = json_encode([
              "appid"=> $appid
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
         public function clear_quota_v2($appid,$appsecret) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/clear_quota/v2?appid={$appid}&appsecret={$appsecret}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);
        
        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
            echo "failed";
        }
        
    
    }
    
    
       public function get_quota($accessToken,$cgi_path) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/openapi/quota/get?access_token={$accessToken}";
        $postData = json_encode([
             "cgi_path"=> $cgi_path
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
           public function get_rid($accessToken,$rid) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/openapi/rid/get?access_token={$accessToken}";
        $postData = json_encode([
             "rid"=> $rid
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }





}

class Media {
    public function __construct() {
        // No need for register_openers in PHP
    }

    // 上传图片
    public function upload($accessToken, $filePath, $mediaType) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$accessToken}&type={$mediaType}";
        
                // 根据文件扩展名设置 MIME 类型
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wma' => 'audio/x-ms-wma',
            'wav' => 'audio/wav',
            'amr' => 'audio/amr',
            'mp4' => 'video/mp4'
        ];
        $mime = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        $file = new CURLFile(realpath($filePath), $mime, basename($filePath));
        
        
        
       // $file = new CURLFile(realpath($filePath), $mediaType);
        
        
        
        $postData = ['media' => $file];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);

        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

     public function uploadimg($accessToken, $filePath) {
    $postUrl = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token={$accessToken}&type=image";
    $file = new CURLFile(realpath($filePath), 'image'); // Fixed quotes
    $postData = ['media' => $file];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $urlResp = curl_exec($ch);

    if ($urlResp === false) {
        echo "cURL Error: " . curl_error($ch);
    } else {
        echo $urlResp;
    }

    curl_close($ch);
}




    // 获取素材
    public function get($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$accessToken}&media_id={$mediaId}";

        // Initialize cURL for GET request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        // Check Content-Type from headers
        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict); // PHP equivalent of Python's print for arrays
        } else {
       // 设置下载目录为绝对路径
    $downloadDir = '/tmp/downloads/'; // 注意修正拼写错误：原为 "/tmp/ownloads/"
    if (!file_exists($downloadDir)) {
        if (!mkdir($downloadDir, 0777, true)) {
            echo "Failed to create directory: $downloadDir - Check parent directory permissions";
            return;
        }
    }

    if (!is_writable($downloadDir)) {
        echo "Directory not writable: $downloadDir - Check permissions";
        return;
    }

    // 根据 Content-Type 确定文件后缀
    $ext = '.bin'; // 默认后缀，如果无法识别则使用 .bin
    if (preg_match('/Content-Type: (\w+\/\w+)/i', $headers, $mimeMatches)) {
        $mimeType = $mimeMatches[1];
        $extMap = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/bmp' => '.bmp',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'audio/aac' => '.aac',
            'audio/amr' => '.amr',
            'audio/speex' => '.speex',
            'video/mp4' => '.mp4',
            'video/x-msvideo' => '.avi',
            'video/x-matroska' => '.mkv',
            'video/quicktime' => '.mov',
            'video/x-ms-wmv' => '.wmv'
        ];
        $ext = $extMap[$mimeType] ?? '.bin';
    }

    // 使用 mediaId 作为文件名，加上动态后缀
    $fileName = $mediaId . $ext;
    $mediaFilePath = rtrim($downloadDir, '/') . '/' . $fileName;

    // 保存文件
    $mediaFile = fopen($mediaFilePath, "wb");
    if ($mediaFile === false) {
        echo "Failed to open file: $mediaFilePath - Error: " . error_get_last()['message'];
        return;
    }
    fwrite($mediaFile, $body);
    fclose($mediaFile);
    echo "get successful, saved as: " . $mediaFilePath;
  
        }
    }
}

class Material {
    public function __construct() {
        // No need for register_openers in PHP
    }

    // 上传
  public function upload($accessToken, $filePath, $mediaType) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$accessToken}&type={$mediaType}";
        
        // 根据文件扩展名设置 MIME 类型
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wma' => 'audio/x-ms-wma',
            'wav' => 'audio/wav',
            'amr' => 'audio/amr',
            'mp4' => 'video/mp4'
        ];
        $mime = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        $file = new CURLFile(realpath($filePath), $mime, basename($filePath));
        $postData = ['media' => $file];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

 
  


    // 下载
    public function get($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token={$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            // 设置下载目录为绝对路径
    $downloadDir = '/tmp/downloads/'; // 注意修正拼写错误：原为 "/tmp/ownloads/"
    if (!file_exists($downloadDir)) {
        if (!mkdir($downloadDir, 0777, true)) {
            echo "Failed to create directory: $downloadDir - Check parent directory permissions";
            return;
        }
    }

    if (!is_writable($downloadDir)) {
        echo "Directory not writable: $downloadDir - Check permissions";
        return;
    }

    // 根据 Content-Type 确定文件后缀
    $ext = '.bin'; // 默认后缀，如果无法识别则使用 .bin
    if (preg_match('/Content-Type: (\w+\/\w+)/i', $headers, $mimeMatches)) {
        $mimeType = $mimeMatches[1];
        $extMap = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/bmp' => '.bmp',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'audio/aac' => '.aac',
            'audio/amr' => '.amr',
            'audio/speex' => '.speex',
            'video/mp4' => '.mp4',
            'video/x-msvideo' => '.avi',
            'video/x-matroska' => '.mkv',
            'video/quicktime' => '.mov',
            'video/x-ms-wmv' => '.wmv'
        ];
        $ext = $extMap[$mimeType] ?? '.bin';
    }

    // 使用 mediaId 作为文件名，加上动态后缀
    $fileName = $mediaId . $ext;
    $mediaFilePath = rtrim($downloadDir, '/') . '/' . $fileName;

    // 保存文件
    $mediaFile = fopen($mediaFilePath, "wb");
    if ($mediaFile === false) {
        echo "Failed to open file: $mediaFilePath - Error: " . error_get_last()['message'];
        return;
    }
    fwrite($mediaFile, $body);
    fclose($mediaFile);
    echo "get successful, saved as: " . $mediaFilePath;
        }
    }

    // 删除
    public function delete($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/del_material?access_token={$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

    // 获取素材列表
    public function batchGet($accessToken, $mediaType, $offset = 0, $count = 20) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token={$accessToken}";
        $postData = json_encode([
            'type' => $mediaType,
            'offset' => $offset,
            'count' => $count
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
     public function getCount($accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token={$accessToken}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);
        
        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
            echo "不能提取永久素材总数";
        }
        
       }
    
    
    
    

    // 添加图文消息,已经弃用
    public function addNews($accessToken, $news) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $news); // Assuming $news is a JSON string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
    
       // 修改永久图文素材,已经弃用
    public function updateNews($accessToken, $news) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/material/update_news?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $news); // Assuming $news is a JSON string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
}






class Draft {
    public function __construct() {
        // No need for register_openers in PHP
    }
    
        // 添加图文消息
    public function add($accessToken, $news) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/add?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $news); // Assuming $news is a JSON string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
            // 修改图文消息
    public function update($accessToken, $news) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/update?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $news); // Assuming $news is a JSON string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
    
    
     public function get($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/get?access_token={$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            // 设置下载目录为绝对路径
    $downloadDir = '/tmp/downloads/'; // 注意修正拼写错误：原为 "/tmp/ownloads/"
    if (!file_exists($downloadDir)) {
        if (!mkdir($downloadDir, 0777, true)) {
            echo "Failed to create directory: $downloadDir - Check parent directory permissions";
            return;
        }
    }

    if (!is_writable($downloadDir)) {
        echo "Directory not writable: $downloadDir - Check permissions";
        return;
    }

    // 根据 Content-Type 确定文件后缀
    $ext = '.bin'; // 默认后缀，如果无法识别则使用 .bin
    if (preg_match('/Content-Type: (\w+\/\w+)/i', $headers, $mimeMatches)) {
        $mimeType = $mimeMatches[1];
        $extMap = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/bmp' => '.bmp',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'audio/aac' => '.aac',
            'audio/amr' => '.amr',
            'audio/speex' => '.speex',
            'video/mp4' => '.mp4',
            'video/x-msvideo' => '.avi',
            'video/x-matroska' => '.mkv',
            'video/quicktime' => '.mov',
            'video/x-ms-wmv' => '.wmv'
        ];
        $ext = $extMap[$mimeType] ?? '.bin';
    }

    // 使用 mediaId 作为文件名，加上动态后缀
    $fileName = $mediaId . $ext;
    $mediaFilePath = rtrim($downloadDir, '/') . '/' . $fileName;

    // 保存文件
    $mediaFile = fopen($mediaFilePath, "wb");
    if ($mediaFile === false) {
        echo "Failed to open file: $mediaFilePath - Error: " . error_get_last()['message'];
        return;
    }
    fwrite($mediaFile, $body);
    fclose($mediaFile);
    echo "get successful, saved as: " . $mediaFilePath;
        }
    }

    // 删除
    public function delete($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/delete?access_token={$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
        // 获取草稿列表
    public function batchGet($accessToken,$offset = 0, $count = 20,$no_content= 0) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/batchget?access_token={$accessToken}";
        $postData = json_encode([
          
            'offset' => $offset,
            'count' => $count,
            'no_content' => $no_content
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
       // 获取草稿品卡片所需的DOM结构
    public function getcardInfo($accessToken,$product_id, $article_type,$card_type) {
        $postUrl = "https://api.weixin.qq.com/channels/ec/service/product/getcardinfo?access_token={$accessToken}";
        $postData = json_encode([
          
            'product_id' => $product_id,
            'article_type' => $article_type,
            'card_type' => $card_type
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
    
    public function getCount($accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/draft/count?access_token={$accessToken}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);
        
        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
            echo "不能提取草稿总数";
        }
        
       }
    
   }
   
   class Publish {
    public function __construct() {
      
    }
    
   
    
    //发布
     public function submit($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/freepublish/submit?access_token=$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
             echo "不能进行$mediaId发布";
        }
    }


    //轮询
     public function get($accessToken, $mediaId) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/freepublish/get?access_token=$accessToken}";
        $postData = json_encode(['media_id' => $mediaId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
             echo "不能进行$mediaId轮询";
        }
    }

    //轮询
     public function getArticle($accessToken, $article_id) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/freepublish/getarticle?access_token=$accessToken}";
        $postData = json_encode(['article_id' => $article_id]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if (strpos($headers, 'Content-Type: application/json') !== false || 
            strpos($headers, 'Content-Type: text/plain') !== false) {
            $jsonDict = json_decode($body, true);
            print_r($jsonDict);
        } else {
            
             echo "不能进行$mediaId轮询";
        }
    }



    // 删除
    public function delete($accessToken, $article_id, $index = 1) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/freepublish/delete?access_token={$accessToken}";
        $postData = json_encode([
          
            'article_id' => $article_id,
            'index' => $index
         
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
        // 获取草稿列表
    public function batchGet($accessToken,$offset = 0, $count = 20,$no_content= 0) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/freepublish/batchget?access_token={$accessToken}";
        $postData = json_encode([
          
            'offset' => $offset,
            'count' => $count,
            'no_content' => $no_content
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
       // 
    public function openComment($accessToken,$msg_data_id, $index = 0) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/open?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
        public function closeComment($accessToken,$msg_data_id, $index = 0) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/close?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    

    
     public function listComment($accessToken,$msg_data_id, $index ,$begin,$count,$type) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/list?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'begin' => $begin,
            'count' => $count, 
            'type' => $type
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
     public function markComment($accessToken,$msg_data_id, $index ,$user_comment_id) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/markelect?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'user_comment_id' => $user_comment_id
          
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
     public function unmarkComment($accessToken,$msg_data_id, $index ,$user_comment_id) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/unmarkelect?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'user_comment_id' => $user_comment_id
           
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
         public function deleteComment($accessToken,$msg_data_id, $index ,$user_comment_id) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/delete?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'user_comment_id' => $user_comment_id
           
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
    
       public function replyComment($accessToken,$msg_data_id, $index ,$user_comment_id,$content) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/reply/add?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'user_comment_id' => $user_comment_id,
            'content' => $content
           
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
           public function deletereplyComment($accessToken,$msg_data_id, $index ,$user_comment_id) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/comment/reply/add?access_token={$accessToken}";
        $postData = json_encode([
          
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'user_comment_id' => $user_comment_id
       
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
    
    
    
    
   }
   
   

class Menu {
    public function __construct() {
        // No initialization needed, equivalent to Python's pass
    }

    public function create($postData, $accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$accessToken}";

        // Ensure postData is UTF-8 encoded if it's a string
        if (is_string($postData)) {
            $postData = mb_convert_encoding($postData, 'UTF-8');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

    public function query($accessToken) {
        //$postUrl = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$accessToken}";
         $postUrl = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token={$accessToken}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

    public function delete($accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }

    // 获取自定义菜单配置接口
    public function getCurrentSelfmenuInfo($accessToken) {
        $postUrl = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $urlResp = curl_exec($ch);
        if ($urlResp === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo $urlResp;
        }

        curl_close($ch);
    }
}

?>