<?php
// 设置内容类型为 HTML，并指定字符集为 UTF-8
header('Content-Type: text/html; charset=utf-8');

// 引入必要的类
// 引入必要的类
require_once 'src/db.php';
require_once 'src/wechatMsgCrypt.php';
require_once 'src/message_handler.php';
require_once 'src/logger.php';

// 引入配置文件
$config = require 'config/config.php';
// 初始变量
$encodingAesKey = $config['ENCODING_AES_KEY'];
$token = $config['TOKEN'];

$timestamp = '1739754063'; // 使用当前时间戳
$nonce = "415670741"; // 使用唯一标识符
$appId = $config['APPID'];
$text ='{"URL":"https://vchat.juda.monster/weixin.php","ToUserName":"gh_4d7da9b4503d","FromUserName":"ojoZG7P4UDuHBp-Op_Eejp90cauU","CreateTime":1739754063,"MsgType":"text","Content":"nihao","MsgId":10033}';
$encrypText = '{"ToUserName":"gh_4d7da9b4503d","Encrypt":"dBz2R9G7+10lSkuFlrCwJF36WZdVeewU3tCkgzJzfvfGdZyAt6xZC9f/sthzManDn6qDyAU95Mq8tVI8Zw5uYJJS0Ki+Dvg7yaoDtf0CW1G/cKm1/2Menpe0ppqaGEYD90+bQGsarin9nQnFm/4KmjavprbXR+6dfP7LUvDu7lDfQ/+nvauMLgpmYoPBmk8GLsqo+TC8Agttj3Uq7lO6ajn4bBKaLR7AYg4/KrkEyAruHWzUXDllTPr1vZRTNVZvu+msGUrOvz1GQQrBIDVzUi50ya0GRQ+vXcXIXZkgCIgFeKpIoAAsslkdjNf7oledPe8Du6RKJWGztxnmBc/dZg=="}'; // 密文的初始值

$ur1='https://vchat.juda.monster/weixin.php?signature=ae63b68b989c9544f557a48278305a325e8b4188&timestamp=1739777133&nonce=1675170264&openid=ojoZG7P4UDuHBp-Op_Eejp90cauU&encrypt_type=aes&msg_signature=76d2668f128b2c6497a8935014d1e2c0a12710b1';
$text1='<xml><ToUserName><![CDATA[gh_4d7da9b4503d]]></ToUserName>
<FromUserName><![CDATA[ojoZG7P4UDuHBp-Op_Eejp90cauU]]></FromUserName>
<CreateTime>1739777095</CreateTime>
<MsgType><![CDATA[event]]></MsgType>
<Event><![CDATA[debug_demo]]></Event>
<debug_str><![CDATA[]]></debug_str>
</xml>';
$encrypText1 = '<xml>
    <ToUserName><![CDATA[gh_4d7da9b4503d]]></ToUserName>
    <Encrypt><![CDATA[u0jlWdwWHZBpg7Eysl507Ob1JsZ9NO4zYMrDqaa2lnI+9Vszpu9aDypvxYrK5YTXBxS2x9mVt4TnkqigfsecGHKb8iXLV5LlH4RDbLw6VlYlA4UoVmCwY93763yCD908Ch7JNlOt9/xWIxqcZWkQTS5CVxJAtUT8F0F6uo7PTafinOc21HrHTR6kJZ2TYhldxSPWGvu0qmrtOshTZ/9VPeiAS0auJVxWAz+DtdL1ZAg0RiUc/jds4QXWUCPg5ZI1I7+Kenuhti+7BzmWAJOvhNStCyGALo5vgto6coigG6FaaaoNthRZWRg60lB8notlS9vs5FURtUTtTOR0/ZLRibhmHJQ61PJyyxx13NP/OwMrtHe+tkn8jWgfQMeB2f7R3ILGBW505waLcr9gJIHrmWCRTEo2FGXxpBbPd2KX5Wo=]]></Encrypt>
</xml>
';

$signature = '99879c022745ae0d930813b183e68e034a282ace';
$openid = 'ojoZG7P4UDuHBp-Op_Eejp90cauU';
$encrypt_type = 'raw';
$msg_signature = '3ad8d4516ab98ad01e04128d856b65285f7f2fa3';
$xmlorjson = true;
// 默认值
$select_type = 'plaintext'; // 默认选择明文

echo "<!DOCTYPE html>";
echo "<html lang=\"zh-CN\">";
echo "<head>";
echo "    <meta charset=\"UTF-8\">";
echo "    <title>微信消息测试</title>";
echo "    <style>";
// CSS 样式
echo "        * { margin: 0; padding: 0; box-sizing: border-box; }";
echo "        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; color: #333; line-height: 1.6; }";
echo "        h1, h2 { color: #4CAF50; text-align: center; margin-bottom: 20px; }";
echo "        h2 { font-size: 1.8rem; }";
echo "        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow: auto; margin: 20px 0; }";
echo "        .error { color: red; }";
echo "        .form-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin: 0 auto; max-width: 900px; }";
echo "        .form-container label { font-size: 1.1rem; margin-bottom: 10px; display: inline-block; }";
echo "        .form-container textarea { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }";
echo "        .form-container input[type='submit'] { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 1.2rem; cursor: pointer; }";
echo "        .form-container input[type='submit']:hover { background-color: #45a049; }";
echo "        .form-container input[type='radio'] { margin-right: 10px; }";
echo "        .form-container .radio-group { margin-bottom: 20px; }";
echo "        footer { text-align: center; margin-top: 50px; color: #777; font-size: 0.9rem; }";
echo "        footer a { color: #4CAF50; text-decoration: none; }";
echo "    </style>";
echo "</head>";
echo "<body>";




echo "<h1>微信开发平台与服务器消息测试结果</h1>";

// 显示原始消息
echo "<h2>测试说明</h2>";
echo "<pre>测试支持xml和json格式，参考https://developers.weixin.qq.com/apiExplorer?type=messagePush 和https://mp.weixin.qq.com/debug?token=1834246867&lang=zh_CN</pre>";
echo "<h3>XML格式明文</h3>";
$xml_tree = new DOMDocument();
$xml_tree->loadXML($text1);
$text1_html1 = htmlspecialchars($xml_tree->saveXML(), ENT_QUOTES, 'UTF-8');
echo "<pre>{$text1_html1}</pre>";

echo "<h3>XML格式密文</h3>";
$xml_tree->loadXML($encrypText1);
$text1_html1 = htmlspecialchars($xml_tree->saveXML(), ENT_QUOTES, 'UTF-8');
echo "<pre>{$text1_html1}</pre>";

echo "<h3>JSON格式明文</h3>";
echo "<pre>" . json_encode(json_decode($text), JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>JSON格式密文</h3>";
echo "<pre>" . json_encode(json_decode($encrypText), JSON_PRETTY_PRINT) . "</pre>";
// 初始化微信回调处理类
$logger = new Logger();
$wechatMsgCrypt = new WechatMsgCrypt($token, $appId, $encodingAesKey, $logger);

// 默认值为空
$url = 'https://vchat.juda.monster/weixin.php?signature='.$signature.'&timestamp='.$timestamp.'&nonce='.$nonce.'&openid='.$openid.'&encrypt_type='.$encrypt_type.'&msg_signature='.$msg_signature;

// 自动判断数据格式：XML 还是 JSON
function detect_data_type($data) {
    if (empty($data)) {
        return true;
    }

    // 尝试解析 JSON 格式
    $json = json_decode($data, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return true;
    }

    // 尝试解析 XML 格式
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($data);
    if ($xml !== false) {
        return false;
    }

    return true;
}

// 解析输入的URL链接和包体内容
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 提取URL链接参数
    if (!empty($_POST['push_url'])) {
        parse_str(parse_url($_POST['push_url'], PHP_URL_QUERY), $urlParams);

        $url = $_POST['push_url'];
        $signature = $urlParams['signature'] ?? '';
        $timestamp = $urlParams['timestamp'] ?? '';
        $nonce = $urlParams['nonce'] ?? '';
        $openid = $urlParams['openid'] ?? '';
        $encrypt_type = $urlParams['encrypt_type'] ?? '';
        $msg_signature = $urlParams['msg_signature'] ?? '';
    }

    // 选择明文或密文
    if (isset($_POST['msg_type'])) {
        $select_type = $_POST['msg_type'];
    }

    // 处理明文或密文
    if ($select_type == 'plaintext') {
        // 明文处理
        $encrypt_type = 'raw';
        if (!empty($_POST['body_content'])) {
            $bodyContent = trim($_POST['body_content'],'"');
            $text = $bodyContent;  // 传递明文

            $xmlorjson = detect_data_type($bodyContent);
        }
    } else if ($select_type == 'ciphertext') {
        $encrypt_type = 'aes';
        // 密文处理
        if (!empty($_POST['body_content'])) {
            $bodyContent = trim($_POST['body_content'],'"');
            $encrypText = $bodyContent;  // 传递密文
            $xmlorjson = detect_data_type($bodyContent);
        }
    }
}

echo "<div class='form-container'>";
echo "<h2>输入推送的URL链接和发送的包体</h2>";
echo "<form method='POST' action=''>";
echo "    <label for='push_url'>推送的URL链接:</label><br>";
echo "    <textarea id='push_url' name='push_url' rows='2'>{$url}</textarea><br><br>";
echo "    <label for='body_content'>发送的包体:</label><br>";
echo "    <textarea id='body_content' name='body_content' rows='10'>{$text}</textarea><br><br>";
echo "    <div class='radio-group'>";
echo "        <label>包体类型:</label><br>";
echo "        <input type='radio' id='plaintext' name='msg_type' value='plaintext' ".($select_type == 'plaintext' ? "checked" : "")." onclick='updateBodyContent()'> 明文";
echo "        <input type='radio' id='ciphertext' name='msg_type' value='ciphertext' ".($select_type == 'ciphertext' ? "checked" : "")." onclick='updateBodyContent()'> 密文<br><br>";
echo "    </div>";
echo "    <input type='submit' value='解析'>";
echo "</form>";
echo "</div>";

echo "<h2>url解析后的原始参数</h2>";
echo "<pre>URL: " . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</pre>";
echo "<pre>signature: {$signature}</pre>";
echo "<pre>timestamp: {$timestamp}</pre>";
echo "<pre>nonce: {$nonce}</pre>";
echo "<pre>openid: {$openid}</pre>";
echo "<pre>encrypt_type: {$encrypt_type}</pre>";
echo "<pre>msg_signature: {$msg_signature}</pre>";


// 如果选择明文，执行加密
if ($select_type == 'plaintext') {
    $encryptMsg = '';

    $errCodeEncrypt = $wechatMsgCrypt->encryptMsg($text, $timestamp, $nonce, $encryptMsg, $xmlorjson);
    if ($errCodeEncrypt == 0) {
        echo "<h2>显示加密结果</h2>";

        if ($xmlorjson === true) {
            echo "<pre>{$encryptMsg}</pre>";
            $encryptMsg = json_decode($encryptMsg, true);
            $encrypt = $encryptMsg['Encrypt'];
            $msg_signature = $encryptMsg['MsgSignature'];
            $timestamp = $encryptMsg['TimeStamp'];
            $nonce = $encryptMsg['Nonce']; 
            $encrypText = '{"ToUserName":"'.$openid.'","Encrypt":"' . $encrypt . '"}';
        }
        else {
            $xml_tree = new DOMDocument();
            $xml_tree->loadXML($encryptMsg);
            $encryptMsg_html = htmlspecialchars($xml_tree->saveXML(), ENT_QUOTES, 'UTF-8');
            echo "<pre>{$encryptMsg_html}</pre>";
            $encrypt = $xml_tree->getElementsByTagName('Encrypt')->item(0)->nodeValue;
            $msg_signature = $xml_tree->getElementsByTagName('MsgSignature')->item(0)->nodeValue;
            $timestamp = $xml_tree->getElementsByTagName('TimeStamp')->item(0)->nodeValue;;
            $nonce = $xml_tree->getElementsByTagName('Nonce')->item(0)->nodeValue;; 
            $xml_format = "<xml><ToUserName><![CDATA[%s]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
            $encrypText = sprintf($xml_format,$openid, $encrypt);
        }
        $url = 'https://vchat.juda.monster/weixin.php?signature='.$signature.'&timestamp='.$timestamp.'&nonce='.$nonce.'&openid='.$openid.'&encrypt_type=aes&msg_signature='.$msg_signature;

        echo "<pre>URL: " . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</pre>";
        echo "<pre>encrypt: {$encrypt}</pre>";
        echo "<pre>msg_signature: {$msg_signature}</pre>";
        echo "<pre>timestamp: {$timestamp}</pre>";
        echo "<pre>nonce: {$nonce}</pre>";
    } else {
        echo "<pre>{$errCodeEncrypt}</pre>";
    }
}

echo "<h2>显示加密字段</h2>";

if ($xmlorjson === true) {
    echo "<pre>{$encrypText}</pre>";
} else {
    $xml_tree = new DOMDocument();
    $xml_tree->loadXML($encrypText);
    $encryptText_html = htmlspecialchars($xml_tree->saveXML(), ENT_QUOTES, 'UTF-8');
    echo "<pre>{$encryptText_html}</pre>";
}

echo "<h2>微信后台将消息加密发送给服务器，服务器收到以下消息</h2>";
$decrypt_msg = $encrypText;
$logger->info('显示消息 '.$decrypt_msg);
// 解密消息
$msg = '';
$errCodeDecrypt = $wechatMsgCrypt->decryptMsg($msg_signature, $nonce, $decrypt_msg, $msg, $timestamp, $xmlorjson);
if ($errCodeDecrypt == 0) {
    if ($xmlorjson === true) {
        echo "<pre>{$msg}</pre>";
    } else {
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($msg);
        $dencryptMsg_html = htmlspecialchars($xml_tree->saveXML(), ENT_QUOTES, 'UTF-8');
        echo "<pre>{$dencryptMsg_html}</pre>";
    }
} else {
    echo "<pre>{$errCodeDecrypt}</pre>";
}

?>

<script>
// JavaScript 代码，用于切换输入框的内容
function updateBodyContent() {
    var msgType = document.querySelector('input[name="msg_type"]:checked').value;
    var bodyContentField = document.getElementById('body_content');
    var urlContentField = document.getElementById('push_url');
    if (msgType == 'plaintext') {
        bodyContentField.value = '<?php echo json_encode($text);  ?>';
        urlContentField.value = '<?php echo $url; ?>';
    } else if (msgType == 'ciphertext') {
        bodyContentField.value = '<?php echo json_encode($encrypText);?>';
        urlContentField.value = '<?php echo $url; ?>';
    }
}

// 页面加载时自动更新
window.onload = updateBodyContent;
</script>
</body>
</html>
