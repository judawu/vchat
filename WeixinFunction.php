<?php
// filename: WeixinFunction.php

require_once 'src/access.php'; // Include Access class

// Initialize class instances
$weixinAccess = new Access();
$media = new Media();
$material = new Material();
$menu = new Menu();
$common = new Common();
$draft=new Draft();





// Handle requests
$output = '';
$mediaIdInput = isset($_POST['media_id']) ? $_POST['media_id'] : '';
$materialdInput=isset($_POST['material_id']) ? $_POST['material_id'] : '';
// Set upload directory
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Test Access Token
if (isset($_POST['test_access_token'])) {
    $output = $weixinAccess->getAccessToken();
}

// Test Left Time
if (isset($_POST['test_left_time'])) {
    $output = "Remaining time: " . $weixinAccess->getLeftTime() . " seconds";
}

// Media Upload
if (isset($_POST['media_upload']) && isset($_FILES['media_file'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $file = $_FILES['media_file'];
    $fileName = $file['name'];
    $fileSize = $file['size']; // 文件大小（字节）
    $tmpFilePath = $file['tmp_name'];
    $filePath = $uploadDir . basename($fileName);
    
    if (move_uploaded_file($tmpFilePath, $filePath)) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
           // 支持的素材类型和格式
        $supportedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'], // 图片：最大2MB
            'voice' => ['mp3', 'wma', 'wav', 'amr'], // 语音：最大2MB
            'video' => ['mp4'],                      // 视频：最大10MB
            'thumb' => ['jpg', 'jpeg']              // 缩略图：最大64KB
        ];
        
        $mediaType = '';
        foreach ($supportedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $mediaType = $type;
                break;
            }
        }
        
       
        
        if ($mediaType) {
                $sizeLimits = [
                'image' => 2 * 1024 * 1024,  // 2MB
                'voice' => 2 * 1024 * 1024,  // 2MB
                'video' => 10 * 1024 * 1024, // 10MB
                'thumb' => 64 * 1024         // 64KB
            ];
            if ($fileSize > $sizeLimits[$mediaType]) {
                $output = "File too large for $mediaType: " . round($fileSize / 1024, 2) . "KB exceeds limit of " . round($sizeLimits[$mediaType] / 1024, 2) . "KB";
            } else {
            ob_start();
            $media->upload($accessToken, $filePath, $mediaType);
            $uploadOutput = ob_get_clean();
            $uploadResult = json_decode($uploadOutput, true);
            $mediaIdInput = $uploadResult['media_id'] ?? '';
            $output = "Upload Result: " . $uploadOutput;
            }
            unlink($filePath);
        } else {
            $output = "Unsupported file format: $extension";
             unlink($filePath);
        }
    } else {
        $output = "File move failed. Check server permissions.";
    }
}

// Media Get
if (isset($_POST['media_get'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $mediaId = $_POST['media_id'];
    if ($mediaId) {
        ob_start();
        $media->get($accessToken, $mediaId);
        $output = ob_get_clean();
    } else {
        $output = "Please enter a valid Media ID";
    }
}

// Material Upload
if (isset($_POST['material_upload']) && isset($_FILES['material_file'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $file = $_FILES['material_file'];
    $fileName = $file['name'];
    $fileSize = $file['size']; // 文件大小（字节）
    $tmpFilePath = $file['tmp_name'];
    $filePath = $uploadDir . basename($fileName);
    
    if (move_uploaded_file($tmpFilePath, $filePath)) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // 支持的素材类型和格式
        $supportedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'], // 图片：最大2MB
            'voice' => ['mp3', 'wma', 'wav', 'amr'], // 语音：最大2MB
            'video' => ['mp4'],                      // 视频：最大10MB
            'thumb' => ['jpg', 'jpeg']              // 缩略图：最大64KB
        ];
        
        $mediaType = '';
        foreach ($supportedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $mediaType = $type;
                break;
            }
        }
        
        if ($mediaType) {
            // 检查文件大小限制
            $sizeLimits = [
                'image' => 2 * 1024 * 1024,  // 2MB
                'voice' => 2 * 1024 * 1024,  // 2MB
                'video' => 10 * 1024 * 1024, // 10MB
                'thumb' => 64 * 1024         // 64KB
            ];
            
            if ($fileSize > $sizeLimits[$mediaType]) {
                $output = "File too large for $mediaType: " . round($fileSize / 1024, 2) . "KB exceeds limit of " . round($sizeLimits[$mediaType] / 1024, 2) . "KB";
            } else {
                ob_start();
                $material->upload($accessToken, $filePath, $mediaType);
           
                $output = ob_get_clean();
                $materiauploadResult = json_decode($output, true);
                $materialdInput =  $materiauploadResult['media_id'] ?? '';
            }
            unlink($filePath);
        } else {
            $output = "Unsupported file format for material: $extension. Supported: jpg, jpeg, png, gif, mp3, wma, wav, amr, mp4";
            unlink($filePath);
        }
    } else {
        $output = "File move failed. Check server permissions.";
    }
}

// Upload Image (for article logos)
if (isset($_POST['upload_img']) && isset($_FILES['img_file'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $file = $_FILES['img_file'];
    $fileName = $file['name'];
    $tmpFilePath = $file['tmp_name'];
    $filePath = $uploadDir . basename($fileName);
    
    if (move_uploaded_file($tmpFilePath, $filePath)) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            ob_start();
            $media->uploadimg($accessToken, $filePath);
            $output = ob_get_clean();
            $uploadResult = json_decode($output, true);
            $mediaIdInput = $uploadResult['url'] ?? '';
            $output = "Upload Image Result: " . $output;
            unlink($filePath);
        } else {
            $output = "Unsupported image format: $extension (only jpg, jpeg, png, gif allowed)";
        }
    } else {
        $output = "Image move failed. Check server permissions.";
    }
}





// Material Get
if (isset($_POST['material_get'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $mediaId = $_POST['material_id'];
    if ($mediaId) {
        ob_start();
        $material->get($accessToken, $mediaId);
        $output = ob_get_clean();
    } else {
        $output = "Please enter a valid Material ID";
    }
}

// Material Delete
if (isset($_POST['material_delete'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $mediaId = $_POST['material_id'];
    if ($mediaId) {
        ob_start();
        $material->delete($accessToken, $mediaId);
        $output = ob_get_clean();
    } else {
        $output = "Please enter a valid Material ID";
    }
}

// Material Batch Get
if (isset($_POST['material_batch_get'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $mediaType = $_POST['batch_media_type'] ?? 'image'; // 获取选择的 mediaType，默认为 image
    ob_start();
  // $result = json_decode($material->batchGet($accessToken, $mediaType), true);
     $result = $material->batchGet($accessToken, $mediaType);
    $output = ob_get_clean();
    if (isset($result['item'][0]['media_id'])) {
        $materialdInput = $result['item'][0]['media_id'];
    }
    $output = "Batch Get Result: " . $output;
}

// Menu Create
if (isset($_POST['news_create'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $newsData = $_POST['news_data'];
   // $news= json_encode($newsData, true);
   $news= $newsData;
    if ($news) {
        ob_start();
       $draft->add($accessToken, $news);
        $output = ob_get_clean();
    } else {
        $output = "请输入json格式文件 $newsData";
    }
}




// Menu Create
if (isset($_POST['menu_create'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $menuData = $_POST['menu_data'];
    if ($menuData) {
        ob_start();
        $menu->create($menuData, $accessToken);
        $output = ob_get_clean();
    } else {
        $output = "Please enter menu JSON data";
    }
}

// Menu Query
if (isset($_POST['menu_query'])) {
    $accessToken = $weixinAccess->getAccessToken();
    ob_start();
    $menu->query($accessToken);
    $output = ob_get_clean();
}



if (isset($_POST['get_api_domain_ip'])) {
    
    $accessToken = $weixinAccess->getAccessToken();
    ob_start();
    $common->get('get_api_domain_ip',$accessToken);
    $output = ob_get_clean();
}


if (isset($_POST['getcallbackip'])) {
    
    $accessToken = $weixinAccess->getAccessToken();
    ob_start();
    $common->get('getcallbackip',$accessToken);
    $output = ob_get_clean();
}


if (isset($_POST['callbackcheck'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $callbackcheck_action = $_POST['callbackcheck_action'] ?? 'all'; 
    $callbackcheck_operator = $_POST['callbackcheck_operator'] ?? 'DEFAULT'; 
    ob_start();
    $result =  $common->CallBackCheck($accessToken,$callbackcheck_action, $callbackcheck_operator);
    $output = ob_get_clean();

   
}


if (isset($_POST['clear_quota'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $appid = $_POST['quata1'];
    if ($appid) {
        ob_start();
        $common->clear_quota($accessToken,$appid) ;
        $output = ob_get_clean();
    } else {
        $output = "Please enter appid data";
    }
}

if (isset($_POST['clear_quota_v2'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $appid = $_POST['quata1'];
    $appsecret = $_POST['quata2'];
    if ($appid && $appsecret) {
        ob_start();
        $common->clear_quota_v2($$appid,$appsecret) ;
        $output = ob_get_clean();
    } else {
        $output = "Please enter appid and appsecret data";
    }
}


if (isset($_POST['get_quota'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $cgi_path = $_POST['quata1'];
    if ($cgi_path) {
        ob_start();
        $common->get_quota($accessToken,$cgi_path) ;
        $output = ob_get_clean();
    } else {
        $output = "Please enter cgi_path data in appid box";
    }
}



if (isset($_POST['get_rid'])) {
    $accessToken = $weixinAccess->getAccessToken();
    $rid = $_POST['quata1'];
    if ($rid) {
        ob_start();
        $common->get_rid($accessToken,$rid) ;
        $output = ob_get_clean();
    } else {
        $output = "Please enter rid data in appid box";
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>WeChat Function Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        h3 {
            color: #555;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        input[type="text"], input[type="file"], textarea {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            font-size: 14px;
        }
        input[type="text"] {
            width: 200px;
        }
        textarea[name="menu_data"] {
            width: 300px;
            height: 100px;
            resize: vertical;
        }
          textarea[name="news_data"] {
            width: 600px;
            height: 200px;
            resize: vertical;
        }
        
        
        #output {
            width: 100%;
            height: 150px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            margin-top: 20px;
        }
        .copy-btn {
            background-color: #28a745;
            margin-top: 10px;
        }
        .copy-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WeChat Function Test <a href="https://developers.weixin.qq.com/doc/offiaccount/Getting_Started/Overview.html" target="_blank">参考文档</a></h1>
        <form method="post" enctype="multipart/form-data">
            <h3>Access Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/Get_access_token.html" target="_blank">参考文档</a></h1></h3>
            <div class="form-group">
                <button type="submit" name="test_access_token">Get Access Token</button>
                <button type="submit" name="test_left_time">Get Left Time</button>
            </div>

            <h3>Media Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Get_temporary_materials.html" target="_blank">参考文档</a></h3>
            <div class="form-group">
                <input type="file" name="media_file" accept=".jpg,.jpeg,.png,.gif">
                <button type="submit" name="media_upload">Upload Media</button>
            </div>
            <div class="form-group">
                <input type="text" name="media_id" value="<?php echo htmlspecialchars($mediaIdInput); ?>" placeholder="Media ID or URL">
                <button type="submit" name="media_get">Get Media</button>
            </div>
            <div class="form-group">
                <input type="file" name="img_file" accept=".jpg,.jpeg,.png,.gif">
                <button type="submit" name="upload_img">Upload Image (Logo)</button>
            </div>
            
            <h3>图文 Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Getting_Started/Getting_Started_Guide.html" target="_blank">参考文档</a></h3>
              <div class="form-group">
                <textarea name="news_data" placeholder="输入Artice JSON"></textarea>
                <button type="submit" name="news_create">Create News</button>
              
             </div>
            
            
            

            <h3>Material Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Adding_Permanent_Assets.html" target="_blank">参考文档</a></h3>
            <div class="form-group">
                <input type="file" name="material_file" accept=".jpg,.jpeg,.png,.gif,.mp3,.wma,.wav,.amr,.mp4">
                <button type="submit" name="material_upload">Upload Material</button>
             </div>
          <div class="form-group">
                <input type="text" name="material_id" value="<?php echo htmlspecialchars($materialdInput); ?>" placeholder="Material ID">
                <button type="submit" name="material_get">Get Material</button>
                <button type="submit" name="material_delete">Delete Material</button>
                <select name="batch_media_type">
                    <option value="image">image</option>
                    <option value="voice">voice</option>
                    <option value="shortvideo">shortvideo</option>
                    <option value="video">video</option>
                </select>
                <button type="submit" name="material_batch_get">Batch Get Material</button>
            </div>

            <h3>Menu Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Custom_Menus/Creating_Custom-Defined_Menu.html" target="_blank">参考文档</a></h3></h3>
            <div class="form-group">
                <textarea name="menu_data" placeholder="Enter menu JSON here"></textarea>
                <button type="submit" name="menu_create">Create Menu</button>
                <button type="submit" name="menu_query">Query Menu</button>
             </div>
             
           <h3>Common Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/Get_the_WeChat_server_IP_address.html" target="_blank">参考文档</a></h3>
            <div class="form-group">
               
                <button type="submit" name="get_api_domain_ip"> 获取微信API接口 IP地址</button>
                <button type="submit" name="getcallbackip">获取微信callback IP地址</button>
                 <select name="callbackcheck_action">
                    <option value="all">all</option>
                    <option value="dns">dns</option>
                    <option value="ping">ping</option>
                 
                </select>
                 <select name="callbackcheck_operator">
                    <option value="DEFAULT">DEFAULT</option>
                    <option value="CHINANET">CHINANET</option>
                    <option value="UNICOM">UNICOM</option>
                    <option value="CAP">CAP</option>
                </select>
                <button type="submit" name="callbackcheck">网络检测</button>
            </div>
               <h3>OpenAPI Tests <a href="https://developers.weixin.qq.com/doc/offiaccount/openApi/clear_quota.html" target="_blank">参考文档</a></h3></h3>
            <div class="form-group">
                <textarea name="quata1" placeholder="Enter appid/rid/cgi_path here">appid</textarea>
                <textarea name="quata2" placeholder="Enter appsecret here">appsecret</textarea>
                <button type="submit" name="clear_quota">clear_quota</button>
                <button type="submit" name="clear_quota_v2">clear_quota_v2</button>
                 <button type="submit" name="get_quota">get_quota</button>
                 <button type="submit" name="get_rid">get_rid</button>
             </div>
            
            
            
        </form>
        <textarea id="output" readonly><?php echo htmlspecialchars($output); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard()">Copy Result</button>
    </div>
  
             
    <script>
        function copyToClipboard() {
            var textarea = document.getElementById('output');
            textarea.select();
            document.execCommand('copy');
            alert('Copied to clipboard!');
        }
    </script>
</body>
</html>