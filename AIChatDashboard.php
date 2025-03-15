<?php
// AIChatDashboard.php
require_once 'src/commonAPI.php';
require_once 'src/logger.php';
require_once 'src/db.php';
$config = require 'config/config.php';

$logger = new Logger();
try {
    // 初始化数据库连接
    $db = DB::getInstance($logger);
    $db->initTables();
} catch (PDOException $e) {
    $logger->error("数据库初始化失败: " . $e->getMessage());
    exit("服务器内部错误");
}

$ip = getClientIp() ?? '';
$locationData = getIpLocation($ip) ?? '';

if ($locationData) {
    $iplocation= "IP: ". $locationData['query'] . ",城市: " . $locationData['city']. ",区域: " .$locationData['regionName']. ",国家: ".$locationData['country'];
    
} else {
    $iplocation= "无法获取IP位置";
}
$logger->info("有人在 $iplocation 发起访问");// 

$savetime = (new DateTime())->format('Y-m-d H:i:s');
$full_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if (!saveIP($db, $savetime,$ip, $iplocation, $full_url)) {
        return generateJsonErrorResponse("服务器内部错误");
    }





$aiProviders = $config['ai_providers'];
$defaultParams = $config['default_params'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['ajax']) && $input['ajax'] === true) {
        if (!isset($input['content']) || empty($input['content'])) {
            echo json_encode(["error" => "Missing or empty 'content' parameter"]);
            $logger->error("Missing or empty 'content' parameter");
            exit;
        }
        
        $url = $input['url'] ?? $aiProviders['spark']['url'];
        $bearerToken = $input['apikey'] ?? $aiProviders['spark']['apikey'];
        $model = $input['model'] ?? "lite";
        $systemprompt = $input['systemprompt'] ?? "你是知识渊博的助理,请以 JSON 格式回答";
        $messages = $input['messages'] ?? [['role' => 'user', 'content' => $input['content']]];
        $stream = isset($input['stream']) ? (bool)$input['stream'] : true;
        $params = $input['params'] ?? $defaultParams;

        $fullMessages = [['role' => 'system', 'content' => $systemprompt]];
        $fullMessages = array_merge($fullMessages, $messages);

        // 第一次调用AI
        $response = callAI($url, $bearerToken, $model, $fullMessages, $params, $stream);
      
        // 处理流式响应的情况
        if ($stream) {
            echo $response;
        } else {
            $responseData = json_decode($response, true);
           
            if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error("JSON decode error: " . json_last_error_msg());
           echo json_encode(['error' => 'Invalid response format']);
           exit;
            }
           
            // 检查是否有工具调用
            if (isset($responseData['choices'][0]['message']['tool_calls'])) {
                $toolCall = $responseData['choices'][0]['message']['tool_calls'][0];
               $logger->info("Tool call detected: " . json_encode($toolCall));
                if ($toolCall['function']['name'] === 'getWeatherData') {
                    // 获取工具参数
                    $toolArgs = json_decode($toolCall['function']['arguments'], true);
                    $location = $toolArgs['location'];
                   // $logger->info($location);
                    // 调用天气函数
                    $weatherResult = getWeatherData($location);
                   // $logger->info($weatherResult);
                    
             
                    
                    
                    // 添加工具调用结果到消息历史
                    $fullMessages[] = [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [$toolCall]
                    ];
                    $fullMessages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $weatherResult
                    ];
                    
                    
                    // 修改 $params，将 tools 设置为 null
                    $paramsWithoutTools = $params;
                    $paramsWithoutTools['tools'] = null;
                   // $logger->info("Modified params for second call: " . json_encode($paramsWithoutTools));
                    
                    // 第二次调用，不带工具定义
                   // $logger->info("Calling AI again with tool result and tools=null");
                    $finalResponse = callAI($url, $bearerToken, $model, $fullMessages,  $paramsWithoutTools, $stream);
                    
                    // 检查第二次响应
                    $finalResponseData = json_decode($finalResponse, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $logger->error("Final response JSON decode error: " . json_last_error_msg());
                        echo json_encode(['error' => 'Invalid final response format']);
                        exit;
                    }
                    
                    
                  // 输出最终响应
                    $logger->info("Final response: " . substr($finalResponse, 0, 500));
                    echo $finalResponse;
                }
            } else {
              //  $logger->info("No tool calls, returning original response");
                echo $response;
            }
        }
        exit;
    }

    // 保留原有的保存和加载会话逻辑
    if (isset($input['action']) && $input['action'] === 'save_conversation') {
        $messages = $input['messages'] ?? [];
        $db->saveConversation($messages);
        echo json_encode(['status' => 'success', 'message' => '会话已保存']);
        exit;
    }

    if (isset($input['action']) && $input['action'] === 'load_conversation') {
        $conversation = $db->loadLatestConversation();
        echo json_encode(['status' => 'success', 'conversation' => $conversation]);
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 聊天室</title>
    <style>
        body { font-family: Arial, 'Microsoft YaHei', sans-serif; margin: 20px; background-color: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
        .input-box, .chat-box, .json-box, .params-box, .response-box { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .input-box textarea, .input-box input, .input-box select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .input-box button, .params-box button { margin-top: 10px; margin-right: 10px; padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .input-box button:hover, .params-box button:hover { background-color: #0056b3; }
        .chat-box, .json-box, .response-box { height: 300px; overflow-y: auto; border: 1px solid #ddd; }
        .chat-box p { margin: 5px 0; }
        .chat-box .user { color: #007bff; font-weight: bold; }
        .chat-box .ai { color: #28a745; }
        .json-box pre, .response-box pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .params-box { display: flex; gap: 20px; }
        .params-left, .params-right { flex: 1; }
        .params-left label { display: block; margin: 5px 0; }
        .params-right textarea { width: 100%; height: 400px; padding: 2px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; }
        .params-right .button-group { display: flex; gap: 10px; }
        .params-right button.clear { background-color: #dc3545; }
        .params-right button.clear:hover { background-color: #c82333; }
        .response-box { background-color: #fff3f3; }
        .response-box pre { color: #dc3545; }
        .ai-product { display: flex; align-items: center; }
        .ai-product label { margin-right: 10px; }
        .ai-product input[type="radio"] { margin-top: 2px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>AI 聊天室</h1>
         <pre>这是一个实验用的AI API 调用示例项目，请保护好你的隐私，并且请遵守法律，你的输入也许正在被后台记录。如果你同意，可以继续，不然请离开。</pre>
        <!-- Input Box -->
        <div class="input-box">
            <label>AI 产品:</label>
            <div class="ai-product">
            <?php
            foreach ($aiProviders as $key => $provider) {
                $checked = $key === 'spark' ? 'checked' : '';
              
                echo " <a href=\"{$provider['playground']}\" target=\"_blank\">{$provider['label']}</a><input type=\"radio\" name=\"ai_product\" value=\"$key\" $checked onclick=\"updateUrlAndModels()\">";
            }
            ?>
            </div>
            <label>模型:</label>
            <select id="modelSelect"></select>

            <label>System Prompt:</label>
            <input type="text" id="systemPrompt" value="你是知识渊博的助理,请以 JSON 格式回答">

            <label>User Prompt:</label>
            <textarea id="userInput" placeholder="请输入消息..."></textarea>

            <label>Stream:</label>
            <select id="streamSelect">
                <option value="true" >是</option>
                <option value="false" selected>否</option>
            </select>

            <button onclick="sendMessage()">发送</button>
            <button onclick="clearConversation()">清空会话</button>
            <button onclick="saveConversation()">保存会话</button>
            <button onclick="loadConversation()">读取会话</button>
        </div>

        <!-- Params Box -->
        <div class="params-box">
            <div class="params-left">
                <label>Temperature: <span id="tempValue">0.5</span></label>
                <input type="range" id="temperature" min="0" max="2" step="0.1" value="0.5" oninput="updateParams()">

                <label>Top K:</label>
                <input type="number" id="top_k" min="1" max="6" value="4" oninput="updateParams()">

                <label>Max Tokens:</label>
                <input type="number" id="max_tokens" min="1" max="8192" value="1024" oninput="updateParams()">

                <label>Presence Penalty:</label>
                <input type="number" id="presence_penalty" min="-2" max="2" step="0.1" value="1" oninput="updateParams()">

                <label>Frequency Penalty:</label>
                <input type="number" id="frequency_penalty" min="-2" max="2" step="0.1" value="1" oninput="updateParams()">
                
                  <label>Tool Choice:</label>
                <select id="tool_choice" oninput="updateParams()">
                    <option value="none" selected>none</option>
                    <option value="auto">auto</option>
                    <option value="required">required</option>
                </select>
                
                  <label>Log Probs:</label>
                <select id="logprobs" oninput="updateParams()">
                    <option value="true" >启用</option>
                    <option value="false"selected>禁用</option>
                </select>
                <label>Top Logprobs</label>
                <input type="number" id="top_logprobs" min="0" max="20" step="1" value="0" oninput="updateParams()">

                <label>Web Search:</label>
                <select id="web_search" oninput="updateParams()">
                    <option value="true" >启用</option>
                    <option value="false" selected>禁用</option>
                </select>
                
                
                 <label>Response Format:</label>
                <select id="response_format" oninput="updateParams()">
                    <option value="text" selected>text</option>
                    <option value="json_object">json_object</option>
                </select>
            </div>
            <div class="params-right">
                <label>Params JSON:</label>
                <textarea id="paramsJson"><?php echo json_encode($defaultParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></textarea>
                <div class="button-group">
                    <button class="clear" onclick="clearParamsJson()">清空</button>
                    <button onclick="updateParamsFromJson()">提交</button>
                </div>
            </div>
        </div>

        <!-- Chat Display Box -->
      
        <div class="chat-box" id="chatBox">
       <p>欢迎使用 AI 聊天室！你选择了<span id="aiProviderLabel">OPENAI</span>的大模型<span id="modelLabel">chatgpt</span>，请输入消息开始对话。</p>
       </div>
        <!-- JSON Response Box -->
        <div class="json-box" id="jsonBox">
            <pre>在这里显示 JSON 响应...</pre>
        </div>
        
        <!-- Response Box for Errors -->
        <div class="response-box" id="responseBox">
            <pre>在这里显示错误响应...</pre>
        </div>
    </div>

    <script>
        const aiProviders = <?php echo json_encode($aiProviders, JSON_UNESCAPED_SLASHES); ?>;

        const chatBox = document.getElementById('chatBox');
        const jsonBox = document.getElementById('jsonBox');
        const responseBox = document.getElementById('responseBox');
        const userInput = document.getElementById('userInput');
        const modelSelect = document.getElementById('modelSelect');
        const systemPrompt = document.getElementById('systemPrompt');
        const streamSelect = document.getElementById('streamSelect');
        const paramsJson = document.getElementById('paramsJson');

        let currentParams = <?php echo json_encode($defaultParams); ?>;
        let conversationHistory = [];

        function updateUrlAndModels() {
            const aiProduct = document.querySelector('input[name="ai_product"]:checked').value;
            const provider = aiProviders[aiProduct];
            const url = provider.url;
            const models = provider.models;
            const apikey = provider.apikey;

            modelSelect.innerHTML = '';
            models.forEach(model => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                if (model === (aiProduct === 'spark' ? 'lite' : aiProduct === 'baidu' ? 'ernie-lite-pro-128k' : models[0])) {
                    option.selected = true;
                }
                modelSelect.appendChild(option);
            });
            // 更新欢迎消息
            updateWelcomeMessage();
            return { url, apikey };
        }
 
    // 更新欢迎消息
        function updateWelcomeMessage() {
            const aiProduct = document.querySelector('input[name="ai_product"]:checked').value;
            const selectedModel = modelSelect.value;
            const providerLabel = aiProviders[aiProduct].label; // 使用 label 而不是 key
            
            aiProviderLabel.textContent = providerLabel;
            modelLabel.textContent = selectedModel || 'OPENAI'; // 如果模型还未加载，显示“未知”
                }
                
                // 初始加载时更新
                document.addEventListener('DOMContentLoaded', () => {
                    updateUrlAndModels(); // 页面加载时初始化
                    updateParams();       // 确保参数也初始化
                });
                
                // 监听模型变化
                modelSelect.addEventListener('change', () => {
                    updateWelcomeMessage();
                });
                 
 
    function updateParams() {
        const temperature = parseFloat(document.getElementById('temperature').value);
        document.getElementById('tempValue').textContent = temperature;
        
        // 默认参数
        currentParams = {
            temperature: temperature,
            top_k: parseInt(document.getElementById('top_k').value),
            max_tokens: parseInt(document.getElementById('max_tokens').value),
            presence_penalty: parseFloat(document.getElementById('presence_penalty').value),
            frequency_penalty: parseFloat(document.getElementById('frequency_penalty').value),
            response_format: { type: document.getElementById('response_format').value }
        };
        
        // 获取 tool_choice 和 web_search 的值
        const toolChoice = document.getElementById('tool_choice').value;
        const webSearchEnable = document.getElementById('web_search').value === 'true';
        
        // 如果 tool_choice 不为 "none"，添加 tools 和 tool_choice
        if (toolChoice !== 'none') {
            let tools = [
                {
                    type: "function",
                    function: {
                        name: "getWeatherData",
                        description: "Get weather of an location, the user should supply a location first",
                        parameters: {
                            type: "object",
                            properties: {
                                location: {
                                    type: "string",
                                    description: "The city and state, e.g. San Francisco, CA",
                                }
                            },
                            required: ["location"]
                        }
                    }
                }
            ];
            
            // 如果 web_search 为 true，添加 web_search 工具
            if (webSearchEnable) {
                tools.push({
                    type: "web_search",
                    web_search: { enable: "true" },
                    show_ref_label: "false"
                });
            }
            
            currentParams.tools = tools;
            currentParams.tool_choice = toolChoice;
        }
        
        // 如果 logprobs 为 true，添加 logprobs 和 top_logprobs
        if (document.getElementById('logprobs').value === 'true') {
            currentParams.logprobs = true;
            currentParams.top_logprobs = parseFloat(document.getElementById('top_logprobs').value);
        }
        
        paramsJson.value = JSON.stringify(currentParams, null, 2);
    }

    function updateParamsFromJson() {
            try {
                const jsonValue = paramsJson.value.trim();
                if (jsonValue === '') {
                    currentParams = [];
                } else {
                    currentParams = JSON.parse(jsonValue);
                    document.getElementById('temperature').value = currentParams.temperature || 0.5;
                    document.getElementById('tempValue').textContent = currentParams.temperature || 0.5;
                    document.getElementById('top_k').value = currentParams.top_k || 4;
                    document.getElementById('max_tokens').value = currentParams.max_tokens || 1024;
                    document.getElementById('presence_penalty').value = currentParams.presence_penalty || 1;
                    document.getElementById('frequency_penalty').value = currentParams.frequency_penalty || 1;
                    document.getElementById('web_search').value = currentParams.tools?.[0]?.web_search?.enable ? 'true' : 'false';
                }
            } catch (e) {
                alert('JSON 格式错误: ' + e.message);
            }
        }

        function clearParamsJson() {
            paramsJson.value = '';
        }

        function clearConversation() {
            conversationHistory = [];
            chatBox.innerHTML = '<p>欢迎使用 AI 聊天室！请输入消息开始对话。</p>';
            updateJson('');
            updateResponse('');
        }

        async function saveConversation() {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_conversation',
                    messages: conversationHistory
                })
            });
            const result = await response.json();
            if (result.status === 'success') {
                alert('会话已保存');
            } else {
                alert('保存会话失败');
            }
        }

        async function loadConversation() {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'load_conversation'
                })
            });
            const result = await response.json();
            if (result.status === 'success' && result.conversation.length > 0) {
                conversationHistory = result.conversation;
                chatBox.innerHTML = '';
                conversationHistory.forEach(msg => {
                    addMessage(msg.role, (msg.role === 'user' ? '你: ' : 'AI: ') + msg.content);
                });
                alert('已加载最新会话');
            } else {
                alert('没有可加载的会话');
            }
        }

        async function sendMessage() {
            const content = userInput.value.trim();
            if (!content) {
                alert('请输入消息！');
                return;
            }

            conversationHistory.push({ role: 'user', content: content });
            addMessage('user', '你: ' + content);
            userInput.value = '';
            responseBox.querySelector('pre').textContent = '在这里显示错误响应...';

            const { url, apikey } = updateUrlAndModels();
            const stream = streamSelect.value === 'true';
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    content: content,
                    url: url,
                    apikey: apikey,
                    model: modelSelect.value,
                    systemprompt: systemPrompt.value,
                    messages: conversationHistory,
                    stream: stream,
                    params: currentParams,
                    ajax: true
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                updateResponse(errorText);
                addMessage('ai', '错误: 服务器返回异常');
                return;
            }

            if (stream) {
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let aiMessage = '';
                let jsonParts = [];
                let fullResponse = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    fullResponse += chunk;
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const jsonData = line.replace('data: ', '').trim();
                            if (jsonData === '[DONE]') {
                                conversationHistory.push({ role: 'assistant', content: aiMessage });
                                continue;
                            }

                            try {
                                const parsed = JSON.parse(jsonData);
                                jsonParts.push(JSON.stringify(parsed, null, 2));
                                if (parsed.choices && parsed.choices[0].delta.content) {
                                    aiMessage += parsed.choices[0].delta.content;
                                    updateChat('ai', 'AI: ' + aiMessage);
                                }
                            } catch (e) {}
                        }
                    }
                    updateJson(jsonParts.join('\n\n'));
                }

                try {
                    const parsedFull = JSON.parse(fullResponse);
                    if (parsedFull.error || parsedFull.code) {
                        updateResponse(JSON.stringify(parsedFull, null, 2));
                        addMessage('ai', '错误: API 返回错误信息');
                        updateJson('');
                    }
                } catch (e) {}
            } else {
                const text = await response.text();
                try {
                    const parsed = JSON.parse(text);
                    if (parsed.error || parsed.code) {
                        updateResponse(JSON.stringify(parsed, null, 2));
                        addMessage('ai', '错误: ' + (parsed.error?.message || parsed.message || '未知错误'));
                    } else if (parsed.choices && parsed.choices[0].message.content) {
                        const aiMessage = parsed.choices[0].message.content;
                        updateChat('ai', 'AI: ' + aiMessage);
                        updateJson(JSON.stringify(parsed, null, 2));
                        conversationHistory.push({ role: 'assistant', content: aiMessage });
                    }
                } catch (e) {
                    updateResponse(text);
                    addMessage('ai', '错误: 响应格式无效');
                }
            }
        }

        function addMessage(role, text) {
            const p = document.createElement('p');
            p.className = role;
            p.textContent = text;
            chatBox.appendChild(p);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function updateChat(role, text) {
            const lastMessage = chatBox.lastElementChild;
            if (lastMessage && lastMessage.className === role) {
                lastMessage.textContent = text;
            } else {
                addMessage(role, text);
            }
        }

        function updateJson(text) {
            jsonBox.querySelector('pre').textContent = text || '暂无 JSON 数据';
            jsonBox.scrollTop = jsonBox.scrollHeight;
        }

        function updateResponse(text) {
            responseBox.querySelector('pre').textContent = text || '在这里显示错误响应...';
            responseBox.scrollTop = responseBox.scrollHeight;
        }

        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Initial setup
        updateUrlAndModels();
        updateParams();
    </script>
</body>
</html>