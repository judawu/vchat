 <?php
        // Function to fetch weather data and return formatted string
      
         
        function getWeatherData($city, $units = "metric") {
              $config = require __DIR__ . '/../config/config.php'; 
             if (!$config['openweathermap']) {
                return "错误:openweathermap api为空，请检查config文件";
            }
            $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid=" . $config['openweathermap']. "&units=" . $units;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response === false) {
                return "错误: 无法连接到API";
            }
                  $iconToEmoji = [
                '01d' => '☀️',
                '01n' => '🌙',
                '02d' => '🌤️',
                '02n' => '🌙☁️',
                '03d' => '☁️',
                '03n' => '☁️🌙',
                '04d' => '☁️', // 阴天多云，白天
                '04n' => '☁️🌙',
                '09d' => '🌧️',
                '09n' => '🌧️🌙',
                '10d' => '🌧️☀️',
                '10n' => '🌧️🌙',
                '11d' => '⛈️',
                '11n' => '⛈️🌙',
                '13d' => '❄️☀️',
                '13n' => '❄️🌙',
                '50d' => '🌫️☀️',
                '50n' => '🌫️🌙'
            ];
            $data = json_decode($response, true);
            if (!$data || $data['cod'] != 200) {
                return "错误: " . ($data['message'] ?? '未知错误') . " (代码: " . ($data['cod'] ?? 'N/A') . ")";
            }

            // Format the output string as requested
            $output = "☀️🌤️️🌤☔️天气🌦🌩️❄️🌬  \n\n";
            $output .= "位置: " . $data['name'] . " (经度: " . $data['coord']['lon'] . ", 纬度: " . $data['coord']['lat'] . ")\n\n";
            $output .= "🌈天气状况:\n";
            $output .= "- 状态: " . $data['weather'][0]['main'] . "\n";
            $output .= "- 描述: " . ucfirst($data['weather'][0]['description']) . "\n";
            $output .= "- 图标: " . $iconToEmoji[$data['weather'][0]['icon']] ?? '☁️'. "\n\n";
            $output .= "🌡️温度:\n";
            $output .= "- 当前温度: " . $data['main']['temp'] . "°C\n";
            $output .= "- 体感温度: " . $data['main']['feels_like'] . "°C\n";
            $output .= "- 🧊最低温度: " . $data['main']['temp_min'] . "°C\n";
            $output .= "- 🔥最高温度: " . $data['main']['temp_max'] . "°C\n\n";
            $output .= "🍃风速: " . $data['wind']['speed'] . " m/s, 风向: " . $data['wind']['deg'] . "°\n\n";
            $output .= "🌫️湿度: " . $data['main']['humidity'] . "%\n\n";
//             $output .= "🌅日出时间🌄: " . $data['sys']['sunrise'] . " (UNIX时间戳)\n";
//             $output .= "🌙日落时间⭐: " . $data['sys']['sunset'] . " (UNIX时间戳)";
            $output .= "🌅日出时间🌄: " . date('Y-m-d H:i:s', $data['sys']['sunrise']) . "\n";
            $output .= "🌙日落时间⭐: " . date('Y-m-d H:i:s', $data['sys']['sunset']) . "\n";
            return $output;
        }
        
        
        // Function to call Spark API
function callAI($url, $bearerToken, $model, $messages, $params = [], $stream = true) {
    $data = [
        "model" => $model,
        "messages" => $messages, // 直接使用传入的消息数组
        "stream" => $stream
    ];
    
    if (!empty($params) && is_array($params)) {
        $data = array_merge($data, $params);
    }
    
    $jsonData = json_encode($data);
   // error_log($jsonData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    
    $headers = [
        "Authorization: Bearer " . $bearerToken,
        "Content-Type: application/json"
    ];
    //error_log("header:" . json_encode($headers));
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = "cURL Error: " . curl_error($ch);
        error_log($error);
        curl_close($ch);
        return $error;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  // error_log("HTTP Status Code: " . $httpCode.$response);
    
    curl_close($ch);
    return $response;
}


function rapid_AIchat($userprompt) {
    $url = 'https://spark-api-open.xf-yun.com/v1/chat/completions';
    $bearerToken = 'jWprVPuSDfmXpvFbQPeY:RYDozacPrsqaHKSDjHID';
    $model = 'lite'; // 修正变量名
    $messages = [    // 变量名小写，与callAI参数一致
        [
            'role' => 'user',
            'content' => $userprompt
        ]
    ];
    
   
    $params = [];   // 在调用前定义默认参数
    $stream = false;
    
    $response = callAI($url, $bearerToken, $model, $messages, $params, $stream);
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // 返回错误而不是直接输出并退出
        return ['error' => 'Invalid response format: ' . json_last_error_msg()];
    }
    
    // 检查返回数据结构并提取内容
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        return ['error' => 'No valid response content found'];
    }
}

function getClientIp() {
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        // 检查是否通过proxy转发
        return $_SERVER['REMOTE_ADDR'];
    }
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // 检查是否通过代理服务器访问
       if
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // 检查是否通过 X-Forwarded-For 头部
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return $ipList[0];
    } else {
        // 直接获取客户端 IP
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getIpLocation($ip) {
    $url = "http://ip-api.com/json/$ip?lang=zh-CN";  // 中文信息
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && $data['status'] == 'success') {
        return $data; // 返回地理位置信息
    } else {
        return null;
    }
}

function saveIP($db, $savetime,$ip, $iplocation, $full_url) {
    
            // 存储消息到数据库
     // 存储消息到数据库
     
    try {
      
        $pdo = $db->getPdo(); // 使用 getPdo() 方法获取 PDO 实例
        $stmt = $pdo->prepare("INSERT INTO requests (timestamp, ip,location,full_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$savetime, $ip, $iplocation, $full_url]);
        return true;
    } catch (PDOException $e) {
        error_log("数据库插入失败: " . $e->getMessage());
        return false;
    }
}

        
?>
