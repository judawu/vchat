<?php
return [
    'USERNAME' => getenv('USERNAME') ?: 'admin',
    'PASSWORD' => getenv('PASSWORD') ?: '12345678',
    'TOKEN' => getenv('WEIXIN_TOKEN') ?: 'weixin',
    'APPID' => getenv('WEIXIN_APPID') ?: '',
    'APPSECRET' =>  getenv('WEIXIN_SECRET') ?: '',
    'ENCODING_AES_KEY' => getenv('WEIXIN_ENCODING_AES_KEY') ?: '',
       // 其他配置项，如数据库连接信息
    'db' => [
        'host' => getenv('MYSQL_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('MYSQL_DB_PORT') ?: '3306',
        'dbname' => getenv('MYSQL_DB_NAME') ?: 'vchat',
        'username' => getenv('MYSQL_DB_USER') ?: 'root',
        'password' => getenv('MYSQL_DB_PASS') ?: '',
    ],
    
     'openweathermap' => getenv('OPENWEATHER_APIKEY') ?: '',
    
    
 'ai_providers' => [
        'spark' => [
            'url' => 'https://spark-api-open.xf-yun.com/v1/chat/completions',
            'apikey' => getenv('SPARK_APIKEY') ?: '',
            'models' => [
                'lite',
                'generalv3',
                'pro-128k',
                'generalv3.5',
                'max-32k',
                '4.0Ultra'
            ],
            'label' => '星火讯飞',
            'playground'=> 'https://xinghuo.xfyun.cn/desk'
        ],
        'baidu' => [
            'url' => 'https://qianfan.baidubce.com/v2/chat/completions', 
            'apikey' => getenv('BAIDU_APIKEY') ?: '',
            'models' => [
                'ernie-4.0-8k',
                'ernie-4.0-turbo-8k',
                'ernie-3.5-128K',
                'ernie-3.5-8k',
                'ernie-speed-8k',
                'ernie-speed-128k',
                'ernie-lite-8K',
                'ernie-lite-pro-128k',
                'ernie-tiny-8k',
                'deepseek-r1',
                'deepseek-v3',
                'yi-34b-chat',
                'fuyu-8b',
                'bge-large-en',
                'bge-large-zh',
                'tao-8k',
                'embedding-v1',
                'bce-reranker-base',
                'deepseek-r1-distill-qwen-32b',
                'deepseek-r1-distill-qwen-14b'
            ],
            'label' => '百度文心',
            'playground'=> 'https://yiyan.baidu.com/'
        ],
          'volcengine' => [
            'url' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions', // 替换为实际URL
            'apikey' => getenv('DOUBAO_APIKEY') ?: '',
            'models' => [
                'ep-20250204113129-w6bjb',  // 替换为你的模型endpoint
                'ep-20250302162922-rpbz4'   //https://console.volcengine.com/ark/region:ark+cn-beijing/endpoint?config=%7B%7D
            
             
            ],
            'label' => '火山豆包',
            'playground'=> 'https://www.doubao.com/chat/'
        ],
        
              'kimi' => [
            'url' => 'https://api.moonshot.cn/v1/chat/completions', 
            'apikey' => getenv('KIMI_APIKEY') ?: '',
            'models' => [
                'moonshot-v1-8k',
                'moonshot-v1-32k' ,  
                'moonshot-v1-128k'   ,
                'moonshot-v1-8k-vision-preview',
                'moonshot-v1-32k-vision-preview' ,  
                'moonshot-v1-128k-vision-preview'   
             
            ],
            'label' => '月之暗面',
            'playground'=> 'https://kimi.moonshot.cn/'
        ],
        
                 'deepseek' => [
            'url' => 'https://api.deepseek.com/v1/chat/completions', // 替换为实际 URL
            'apikey' => getenv('DEEPSEEK_APIKEY') ?: '',
            'models' => [
                'deepseek-chat',
                'deepseek-reasoner'  
               
         
             
            ],
            'label' => '深度探索',
            'playground'=> 'https://www.deepseek.com/'
        ],
        
              'hunyuan' => [
            'url' => 'https://api.hunyuan.cloud.tencent.com/v1/chat/completions', // 替换为实际URL
            'apikey' => getenv('HUNYUAN_APIKEY') ?: '',
            'models' => [
                'hunyuan-lite',
                'hunyuan-turbo',
                'hunyuan-standard',
                'hunyuan-standard-256K',
                'hunyuan-code',
                'hunyuan-role',
                'hunyuan-functioncall',
                'hunyuan-vision',
                'hunyuan-turbo-latest',
                'hunyuan-large',
                'hunyuan-large-longcontext',
                'hunyuan-turbo-vision',
                'hunyuan-standard-vision',
                'hunyuan-lite-vision',
                'hunyuan-turbos-20250226',
                'hunyuan-turbos-latest',
                'hunyuan-turbo-20241223',
                'hunyuan-turbo-20241120',
                'hunyuan-turbos-latest',
                'deepseek-r1',
                'deepseek-v3',
                'deepseek-r1-distill-qwen-1.5b',
                'deepseek-r1-distill-qwen-7b',
                'deepseek-r1-distill-qwen-14b',
                'deepseek-r1-distill-qwen-32b',
                'deepseek-r1-distill-llama-8b',
                'deepseek-r1-distill-llama-70b',
                'hunyuan-embedding'  
            
             
            ],
            'label' => '腾讯混元',
            'playground'=> 'https://yuanbao.tencent.com/'
        ],
        
        
          'Qwen' => [
            'url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', // 替换为实际 URL
            'apikey' =>  getenv('TONGYI_APIKEY') ?: '',
            'models' => [
                'deepseek-r1',
                'deepseek-v3',
                'deepseek-r1-distill-qwen-1.5b',
                'deepseek-r1-distill-qwen-7b',
                'deepseek-r1-distill-qwen-14b',
                'deepseek-r1-distill-qwen-32b',
                'deepseek-r1-distill-llama-8b',
                'deepseek-r1-distill-llama-70b',
                'llama3.3-70b-instruct',
                'llama3.2-3b-instruct',
                'llama3.2-1b-instruct',
                'llama3.1-405b-instruct',
                'llama3.1-70b-instruct',
                'llama3.1-8b-instruct',
                'llama3.2-90b-vision-instruct',
                'llama3.2-11b-vision',
                'baichuan2-turbo',
                'baichuan2-13b-chat-v1t',
                'baichuan2-7b-chat-v1',
                'baichuan-7b-v1',
                'chatglm3-6b',
                'chatglm-6b-v2',
                'yi-large',
                'yi-medium',
                'yi-large-rag',
                'yi-large-turbo',
                'abab6.5g-chat',
                'abab6.5t-chat',
                'abab6.5s-chat',
                'ziya-llama-13b-v1',
                'belle-llama-13b-2m-v1',
                'chatyuan-large-v2',
                'billa-7b-sft-v1',
                'abab6.5g-chat',
                'abab6.5t-chat',
                'abab6.5s-chat',
                'qwen-max',
                'qwen-max-latest',
                'qwen-max-0125',
                'qwen-turbo',
                'qwen-turbo-latest',
                'qwen-long',
                'qwen-omni-turbo',
                'qwen-plus',
                'qwen-plus-latest',
                'qwen-plus-0125',
                'qwen-omni-turbo-latest',
                'qwen-vl-max',
                'qwen-vl-max-latest',
                'qwen-vl-plus',
                'qwen-vl-plus-latest',
                'qwen-vl-ocr',
                'qwen-vl-ocr-latest',
                'qwen-audio-turbo',
                'qwen-audio-turbo-latest',
                'qwen-audio-asr',
                'qwen-audio-asr-latest',
                'qwen-math-plus',
                'qwen-math-plus-latest',
                'qwen-math-turbo',
                'qwen-math-turbo-latest',    
                'qwen2.5-math-72b-instruct',
                'qwen2.5-math-7b-instruct',
                'qwen2.5-math-1.5b-instruct',
                'qwen2-math-72b-instruct',    
                'qwen2-math-7b-instruct',  
                'qwen2-math-1.5b-instruct',  
                'qwen-coder-plus-latest',
                'qwen-coder-plus', 
                'qwen2.5-coder-32b-instruct',
                'qwen2.5-coder-14b-instruct',
                'qwen2.5-coder-7b-instruct',
                'qwen2.5-coder-3b-instruct',    
                'qwen2.5-coder-1.5b-instruct',  
                'qwen2.5-coder-0.5b-instruct',  
                'qwen-coder-plus-latest',
                'qwen-mt-plus',    
                'qwen-mt-turbo',
                'qwq-32b-preview',
                'qwen2.5-14b-instruct-1m',
                'qwen2.5-7b-instruct-1m',
                'qwen2.5-72b-instruct',  
                'qwen2.5-32b-instruct', 
                'qwen2.5-14b-instruct',  
                'qwen2.5-3b-instruct', 
                'qwen2.5-1.5b-instruct',
                'qwen2.5-0.5b-instruct',
                'qwen2-72b-instruct',  
                'qwen2-57b-a14b-instruct', 
                'qwen2-7b-instruct',  
                'qwen2-1.5b-instruct',
                'qwen2-0.5b-instruct',
                'qwen1.5-110b-chat',
                'qwen1.5-72b-chat',
                'qwen1.5-32b-chat',  
                'qwen1.5-14b-chat', 
                'qwen1.5-7b-chat',  
                'qwen1.5-1.8b-chat', 
                'qwen1.5-0.5b-chat',
                'qvq-72b-preview',
                'qwen2.5-vl-72b-instruct ',
                'qwen2.5-vl-7b-instruct',
                'qwen2.5-vl-3b-instruct',
                'qwen2-vl-72b-instruct',  
                'qwen2-vl-7b-instruct', 
                'qwen2-vl-2b-instruct',  
                'qwen-vl-v1', 
                'qwen-vl-chat-v1',
                'qwen2-audio-instruct',
                'qwen-audio-chat',  
                'qwen-72b-chat',
                'qwen-14b-chat',
                'qwen-7b-chat',
                'qwen-1.8b-chat',
                'qwen-1.8b-longcontext-chat',
                'qwen2.5-0.5b-instruct',
                'qwen2.5-0.5b-instruct',
                'wanx-v1',
                'wanx2.0-t2i-turbo',
                'wanx2.1-t2i-plus',
                'wanx2.1-t2i-turbo',
                'wanx-sketch-to-image-lite',
                'wanx-x-painting',
                'wanx-style-repaint-v1',
                'wanx-background-generation-v2',
                'image-out-painting',
                'image-instance-segmentation',
                'image-erase-completion',
                'wanx-style-cosplay-v1',
                'wanx-virtualmodel',
                'virtualmodel-v2',
                'shoemodel-v1',
                'wanx-poster-generation-v1',
                'wanx-ast',
                'facechain-facedetect',
                'facechain-finetune',
                'facechain-generation',
                'wordart-texture',
                'wordart-semantic',
                'wordart-surnames',
                'aitryon',
                'aitryon-refiner',
                'stable-diffusion-3.5-large',
                'stable-diffusion-3.5-large-turbo',
                'stable-diffusion-xl',
                'stable-diffusion-v1.5',
                'flux-merged',
                'flux-dev',
                'flux-schnell',
                'cosyvoice-v1',
                'sambert-zhinan-v1',
                'paraformer-v2',
                'paraformer-realtime-v2',
                'sensevoice-v1',
                'wanx2.1-i2v-turbo',
                'wanx2.1-i2v-plus',
                'animate-anyone-detect-gen2',
                'animate-anyone-template-gen2',
                'animate-anyone-gen2',
                'animate-anyone-detect',
                'animate-anyone',
                'emo-detect-v1',
                'emo-v1',
                'emo-detect',
                'emo',
                'liveportrait-detect',
                'liveportrait',
                'videoretalk',
                'motionshop-video-detect',
                'motionshop-gen3d',
                'motionshop-synthesis',
                'emoji-detect-v1',
                'emoji-v1',
                'video-style-transform',
                'text-embedding-v3',
                'text-embedding-v2',
                'text-embedding-v1',
                'text-embedding-async-v2',
                'text-embedding-async-v1',
                'multimodal-embedding-v1',
                'opennlu-v1',
                'gte-rerank',
                'farui-plus',
                'tongyi-intent-detect-v3'
           
                
                
            ],
            'label' => '阿里千问',
            'playground'=> 'https://tongyi.aliyun.com/'
        ],
        
        
        'stepfun' => [
            'url' => 'https://api.stepfun.com/v1/chat/completions',
            'apikey' => getenv('YUEWEN_APIKEY') ?: '',
            'models' => [
                'step-2-mini',
                'step-2-16k',
                'step-1.5v-mini',
                'step-1o-turbo-vision',
                'step-asr',
                'step-tts-mini',
                 'step-1x-medium',
                'step-1-flash',
                'step-1v-8k',
                'step-1v-32k',
                'step-1-8k',
                'step-1-32k',
                'step-1-128k',
                'step-1-256k',
                'step-1o-vision-32k',
                'step-2-16k-202411',
                'step-2-16k-exp'
           
            ],
            'label' => '阶跃星辰',
             'playground'=> 'https://yuewen.cn/chats/new'
        ],
        
        
              'bigmodel' => [
            'url' => 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
            'apikey' => getenv('ZHIPU_APIKEY') ?: '',
            'models' => [
                'glm-4-flash',
                'GLM-4-Assistant',
                'glm-4-plus',
                'glm-4-air',
                'glm-4-air-0111',
                'glm-4-airx',
                'glm-4-long',
                'glm-4-flashx',
                'glm-4v-plus-0111',
                'glm-4v-plus',
                'glm-4v',
                'glm-4v-flash',
                'glm-zero-preview',
                'glm-4-realtime',
                'glm-4-voice',
                'cogview-4',
                'cogview-3-flash',
                'cogvideox-flash',
                 'cogvideox-2',
                 'glm-4-alltools',
                 'codegeex-4',
                 'embedding-2',
                  'charglm-4',
                  'embedding-3',
                  'emohaa'
           
            ],
            'label' => '智谱',
             'playground'=> 'https://chatglm.cn/'
        ],
             'minimax' => [
            'url' => 'https://api.minimax.chat/v1/text/chatcompletion_v2?GroupId=1895527701121339726',  
             'url1' => 'https://api.minimax.chat/v1/t2a_v2',  
              'url2' => 'https://api.minimax.chat/v1/t2a_async_v2',  
               'url3' => 'https://api.minimax.chat/v1/voice_clone',  
                'url4' => 'https://api.minimax.chat/v1/video_generation',  
            'apikey' => getenv('HAILUO_APIKEY') ?: '',
            'models' => [
                'minimax-text-01',
                'abab6.5s-chat',
                'deepseek-r1',
                'abab6.5-chat',
                'abab6.5g-chat',
                'abab6.5t-chat',
                'abab5.5-chat',
                'abab6.5-chat',
                'speech-01-turbo',
                'speech-01-240228',
                'speech-01-turbo-240228',
                'speech-01',
                'speech-02',
                'embo-01'
           
           
            ],
            'label' => '海螺',
            'playground'=> 'https://hailuoai.com/'
        ],
        
          'grok' => [
            'url' => 'https://api.x.ai/v1/chat/completions',
            'apikey' => getenv('GORK_APIKEY') ?: '',
            'models' => [
                'grok-beta',
                'grok-vision-beta',
                'grok-2-vision-1212',
                'grok-2-1212'  
         
            ],
            'label' => 'GROK',
             'playground'=> 'https://grok.com/'
        ],
        
          'openai' => [
            'url' => 'https://api.openai.com/v1/chat/completions', // 替换为实际URL
            'apikey' => getenv('OPENAI_APIKEY') ?: '',
            'models' => [
                'gpt-4o-mini',
                'gpt-4.5-preview',
                'gpt-4o',
                'chatgpt-4o-latest',
                'gpt-4-turbo',
                'gpt-3.5-turbo',
                'o1',
                'o1-mini' ,
                'o3-mini'  
         
            ],
            'label' => 'OPENAI',
            'playground'=> 'https://chatgpt.com/'
        ]
        
        
       
        // 可添加更多供应商，例如：
        // 'google' => [
        //     'url' => 'https://google-ai-api.example.com/v1/chat',
        //     'apikey' => 'your-google-api-key-here',
        //     'models' => ['gemini-1.0', 'gemini-1.5'],
        //     'label' => 'Google'
        // ]
    ],
    
    
  // 默认参数配置
    'default_params' => [
        'temperature' => 0.5,           // 核采样阈值，范围 [0, 2]
        'top_k' => 4,                   // 从 k 个中随机选择，范围 [1, 6]
        'max_tokens' => 1024,           // 最大 token 数，范围 [1, 8192]（根据模型调整）
        'presence_penalty' => 1.0,      // 重复词惩罚，范围 [-2.0, 2.0]
        'frequency_penalty' => 1.0,     // 频率惩罚，范围 [-2.0, 2.0]
       
   
    ],
  
   
];
