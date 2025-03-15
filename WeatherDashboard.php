<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>天气仪表板</title>
    <style>
        body {
            font-family: Arial, 'Microsoft YaHei', sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #f0f0f0;
        }
        .weather-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: inline-block;
            text-align: left;
            width: 500px;
        }
        .weather-box h2 {
            text-align: center;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>天气仪表板</h1>
    <div class="weather-box">
        <?php
        $config = require 'config/config.php';
        $apiKey =  $config['openweathermap'];// Replace with your OpenWeatherMap API key
        $city = isset($_GET['city']) && !empty($_GET['city']) ? $_GET['city'] : "Wuxi"; // Default to Wuxi
        $units = "metric"; // metric for Celsius

        $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid=" . $apiKey . "&units=" . $units;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if ($response === false) {
            echo "cURL错误: " . curl_error($ch);
        } else {
            $data = json_decode($response, true);
            if ($data && $data['cod'] == 200) {
                echo "<h2>天气 - " . $data['name'] . " (" . $data['sys']['country'] . ")</h2>";

                // Coordinates
                echo "<div class='section'>";
                echo "<div class='section-title'>坐标</div>";
                echo "<p>经度: " . $data['coord']['lon'] . "</p>";
                echo "<p>纬度: " . $data['coord']['lat'] . "</p>";
                echo "</div>";

                // Weather
                echo "<div class='section'>";
                echo "<div class='section-title'>天气状况</div>";
                echo "<p>主要天气: " . $data['weather'][0]['main'] . "</p>";
                echo "<p>描述: " . ucfirst($data['weather'][0]['description']) . "</p>";
                echo "<img src='http://openweathermap.org/img/wn/" . $data['weather'][0]['icon'] . "@2x.png' alt='Weather Icon'>";
                echo "</div>";

                // Main (Temperature, Humidity, etc.)
                echo "<div class='section'>";
                echo "<div class='section-title'>主要数据</div>";
                echo "<p>温度: " . $data['main']['temp'] . " °C</p>";
                echo "<p>体感温度: " . $data['main']['feels_like'] . " °C</p>";
                echo "<p>最低温度: " . $data['main']['temp_min'] . " °C</p>";
                echo "<p>最高温度: " . $data['main']['temp_max'] . " °C</p>";
                echo "<p>气压: " . $data['main']['pressure'] . " hPa</p>";
                echo "<p>湿度: " . $data['main']['humidity'] . " %</p>";
                if (isset($data['main']['sea_level'])) {
                    echo "<p>海平面气压: " . $data['main']['sea_level'] . " hPa</p>";
                }
                if (isset($data['main']['grnd_level'])) {
                    echo "<p>地面气压: " . $data['main']['grnd_level'] . " hPa</p>";
                }
                echo "</div>";

                // Visibility
                echo "<div class='section'>";
                echo "<div class='section-title'>能见度</div>";
                echo "<p>" . $data['visibility'] . " 米</p>";
                echo "</div>";

                // Wind
                echo "<div class='section'>";
                echo "<div class='section-title'>风况</div>";
                echo "<p>风速: " . $data['wind']['speed'] . " 米/秒</p>";
                echo "<p>风向: " . $data['wind']['deg'] . "°</p>";
                if (isset($data['wind']['gust'])) {
                    echo "<p>阵风: " . $data['wind']['gust'] . " 米/秒</p>";
                }
                echo "</div>";

                // Clouds
                echo "<div class='section'>";
                echo "<div class='section-title'>云量</div>";
                echo "<p>云覆盖: " . $data['clouds']['all'] . " %</p>";
                echo "</div>";

                // System Info (Sunrise, Sunset, etc.)
                echo "<div class='section'>";
                echo "<div class='section-title'>系统信息</div>";
                echo "<p>国家: " . $data['sys']['country'] . "</p>";
                echo "<p>日出: " . date('H:i:s', $data['sys']['sunrise']) . " (本地时间)</p>";
                echo "<p>日落: " . date('H:i:s', $data['sys']['sunset']) . " (本地时间)</p>";
                echo "<p>更新时间: " . date('Y-m-d H:i:s', $data['dt']) . " (本地时间)</p>";
                echo "<p>时区偏移: " . ($data['timezone'] / 3600) . " 小时</p>";
                echo "</div>";
            } else {
                echo "<p>错误: " . ($data['message'] ?? '未知错误') . " (代码: " . ($data['cod'] ?? 'N/A') . ")</p>";
            }
        }
        curl_close($ch);
        ?>
    </div>

    <form method="GET" action="">
        <input type="text" name="city" placeholder="输入城市名称" value="<?php echo htmlspecialchars($city); ?>" style="margin-top: 20px; padding: 5px;">
        <input type="submit" value="获取天气" style="padding: 5px;">
    </form>
</body>
</html>