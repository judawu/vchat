<?php

/**
 * error code 说明.
 * <ul>
 *    <li>-40001: 签名验证错误</li>
 *    <li>-40002: xml解析失败</li>
 *    <li>-40003: sha加密生成签名失败</li>
 *    <li>-40004: encodingAesKey 非法</li>
 *    <li>-40005: appid 校验错误</li>
 *    <li>-40006: aes 加密失败</li>
 *    <li>-40007: aes 解密失败</li>
 *    <li>-40008: 解密后得到的buffer非法</li>
 *    <li>-40009: base64加密失败</li>
 *    <li>-40010: base64解密失败</li>
 *    <li>-40011: 生成xml失败</li>
 * </ul>
 */
class ErrorCode
{
    public static $OK = 0;
    public static $ValidateSignatureError = -40001;
    public static $ParseXmlError = -40002;
    public static $ComputeSignatureError = -40003;
    public static $IllegalAesKey = -40004;
    public static $ValidateAppidError = -40005;
    public static $EncryptAESError = -40006;
    public static $DecryptAESError = -40007;
    public static $IllegalBuffer = -40008;
    public static $EncodeBase64Error = -40009;
    public static $DecodeBase64Error = -40010;
    public static $GenReturnXmlError = -40011;
    public static $ParseJsonError = -40012;
    public static $GenReturnJsonError = -40013;
    public static $MissingJsonField = -40014;
}

class WechatMsgCrypt
{
    protected $token;
    protected $appId;
    protected $encodingAESKey;
    private $aesKey;
    private $logger;

    /**
     * 构造函数注入配置参数和 Logger 实例
     *
     * @param string $token 微信 Token
     * @param string $appId 微信 AppID
     * @param string $encodingAESKey 微信 EncodingAESKey
     * @param Logger $logger 日志记录器实例
     */
    public function __construct($token, $appId, $encodingAESKey,Logger $logger)
    {
        $this->token = $token;
        $this->appId = $appId;
        $this->encodingAESKey = $encodingAESKey;
        $this->logger=$logger;
       // $this->logger->info("创建成功：token={$this->token} appid={ $this->appId}}");
    }

    /**
     * 验证微信服务器 （明文模式或兼容模式）
     *
     * @param string $signature 微信加密签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机数
     * @return bool
     */
    public function valid($signature, $timestamp, $nonce)
    {
        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));

        if ($tmpStr === $signature) {
          //  $this->logger->info("签名验证成功,可以通讯：$signature");
            return true;
             
        } else {
            $this->logger->error("签名验证失败，拒绝通讯：$signature");
            return false;
              
        }
    }

/**
     * 新修改2025-11-10
	 * 验证微信服务器（安全模式，encrypt_type=aes）
     * 用于初次接入验证，返回解密的echostr
     *
     * @param string $msgSignature 消息签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机数
     * @param string $echostr 加密的echostr
     * @return string|bool 解密的echostr或false
     */
    public function verifyUrl($msgSignature, $timestamp, $nonce, $echostr)
    {
        $pc = new wxMsgCrypt();

        // 验证签名
        $array = $pc->getSHA1($this->token, $timestamp, $nonce, $echostr);
        $ret = $array[0];
        if ($ret != 0) {
            $this->logger->error("verifyUrl ComputeSignatureError: $ret");
            return false;
        }
        $signature = $array[1];
        if ($signature != $msgSignature) {
            $this->logger->error("verifyUrl ValidateSignatureError: computed $signature != $msgSignature");
            return false;
        }

        // 解密echostr
        $result = $pc->decrypt($this->encodingAESKey, $echostr, $this->appId, $this->logger);
        if ($result[0] != 0) {
            $this->logger->error("verifyUrl decrypt ERROR: $result[0]");
            return false;
        }
        return $result[1]; // 返回解密的明文echostr
    }
/**
	 * 提取出xml数据包中的加密消息
	 * @param string $xmltext 待提取的xml字符串
	 * @return string 提取出的加密消息字符串
	 */
	public function XMLEncryptextract($xmltext)
	{
		try {
			$xml = new DOMDocument();
			$xml->loadXML($xmltext);
			$array_e = $xml->getElementsByTagName('Encrypt');
			if ($array_e->length === 0) {
            $this->logger->error("XMLEncryptextract: 'Encrypt' node not found.");
            return array(ErrorCode::$MissingEncryptNode, null, null);
             }
            $encrypt = $array_e->item(0)->nodeValue;
		 	$array_a = $xml->getElementsByTagName('ToUserName');
		    if ($array_a->length === 0) {
		        
		  // https://mp.weixin.qq.com/debug?token=1834246867&lang=zh_CN 调试中发现ToUserName  需要9位字符以上调试才会成功
            $this->logger->info("XMLEncryptextract: 'ToUserName' node not found. Using default value 'System'.");
            $tousername = null;
             } else {
            
            $tousername = $array_a->item(0)->nodeValue;
        
                    }
			$array_b = $xml->getElementsByTagName('MsgSignature');
		    if ($array_b->length === 0) {
          
            $msgSignature = null;
             } else {
            $msgSignature = $array_b->item(0)->nodeValue;
                    }
			return array(0, $encrypt, $tousername, $msgSignature);
		} catch (Exception $e) {
			//print $e . "\n";
			$this->logger->error("XMLEncryptextract:$ParseXmlError");
			return array(ErrorCode::$ParseXmlError, null, null);
		}
	}

   
	/**
	 * 生成xml消息
	 * @param string $encrypt 加密后的消息密文
	 * @param string $signature 安全签名
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 */
	public function XMLEncryptgenerate($encrypt, $signature, $timestamp, $nonce)
	{
       		$format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
		return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
	}
	
	
// 新增JSON加密消息提取
    public function JSONEncryptextract($jsontext) {
        try {
            $data = json_decode($jsontext, true);
            
            if (!$data) {
                $this->logger->error("JSON解析失败,包含特殊字符 $data");
                return array(ErrorCode::$ParseJsonError, null, null);
            }

            $requiredKeys = ['Encrypt'];
            foreach ($requiredKeys as $key) {
                if (!isset($data[$key])) {
                    $this->logger->error("缺少必要字段: $key");
                    return array(ErrorCode::$MissingJsonField, null, null);
                }
            }
            
           
            
            return array(
                0,
                $data['Encrypt'],
                isset($data['ToUserName'])? $data['ToUserName'] : null,
                isset($data['MsgSignature']) ?$data['MsgSignature'] : null
            );
        } catch (Exception $e) {
            $this->logger->error("JSON处理异常: ".$e->getMessage());
            return array(ErrorCode::$ParseJsonError, null, null);
        }
    }	
	
	    // 新增JSON消息生成
    public function JSONEncryptgenerate($encrypt, $signature, $timestamp, $nonce) {
        return json_encode([
            'Encrypt' => $encrypt,
            'MsgSignature' => $signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce
        ], JSON_UNESCAPED_UNICODE);
    }
	
	
/**
 * 将公众平台回复用户的消息加密打包.
 * <ol>
 * <li>对要发送的消息进行AES-CBC加密</li>
 * <li>生成安全签名</li>
 * <li>将消息密文和安全签名打包成xml格式</li>
 * </ol>
 *
 * @param $replyMsg string 公众平台待回复用户的消息，xml格式的字符串（必须是有效XML，否则微信解析失败）
 * @param $timeStamp string 时间戳，可以自己生成，也可以用URL参数的timestamp（官方建议回填原请求的timestamp）
 * @param $nonce string 随机串，可以自己生成，也可以用URL参数的nonce（官方建议回填原请求的nonce）
 * @param &$encryptMsg string 加密后的可以直接回复用户的密文，包括msg_signature, timestamp, nonce, encrypt的xml格式的字符串,
 * 当return返回0时有效
 *
 * @return int 成功0，失败返回对应的错误码
 */
	public function encryptMsg($replyMsg, $timeStamp, $nonce, &$encryptMsg, $XmlOrJson = false)
	{

   
			
		$pc = new wxMsgCrypt();

		//加密
	
		$array = $pc->encrypt($this->encodingAESKey,$replyMsg, $this->appId, $this->logger);
		$ret = $array[0];
		if ($ret != 0) {
		    $this->logger->error("加密错误encryptMsg Error:$ret ");
			return $ret;
		}

		if ($timeStamp == null) {
			$timeStamp = time();
			$this->logger->warning("encryptMsg: 未传timeStamp，使用新生成值，但官方建议回填原请求的timestamp以避免回包验证失败");
		}
		if ($nonce == null) {
		    $nonce = $this->getRandomStr(12); // 新增类方法getRandomStr
		    $this->logger->warning("encryptMsg: 未传nonce，使用新生成值，但官方建议回填原请求的nonce以避免回包验证失败");
		}
		$encrypt = $array[1];

		//生成安全签名
	
		$array = $pc->getSHA1($this->token, $timeStamp, $nonce, $encrypt);
		$ret = $array[0];
		if ($ret != 0) {
		    $this->logger->error("验证加密信息SHA错误 encryptMsg SHA Error:$ret ");
			return $ret;
		}
		$signature = $array[1];

		//生成发送的xml或者Json
	    
        if ($XmlOrJson === true) {
            $encryptMsg = $this->JSONEncryptgenerate($encrypt, $signature, $timeStamp, $nonce);
        } else {
            $encryptMsg = $this->XMLEncryptgenerate($encrypt, $signature, $timeStamp, $nonce);
        }

	    
	    
	   
	//	  $this->logger->info("加密成功：	$encryptMsg ");
		return ErrorCode::$OK;
	}
	
	/**
	 * 检验消息的真实性，并且获取解密后的明文.
	 * <ol>
	 *    <li>利用收到的密文生成安全签名，进行签名验证</li>
	 *    <li>若验证通过，则提取xml中的加密消息</li>
	 *    <li>对消息进行解密</li>
	 * </ol>
	 *
	 * @param $msgSignature string 签名串，对应URL参数的msg_signature
	 * @param $timestamp string 时间戳 对应URL参数的timestamp
	 * @param $nonce string 随机串，对应URL参数的nonce
	 * @param $postData string 密文，对应POST请求的数据
	 * @param &$msg string 解密后的原文，当return返回0时有效
	 *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptMsg($msgSignature,  $nonce, $postData, &$msg,$timestamp = null,$XmlOrJson = false )
	{
		if (strlen($this->encodingAESKey) != 43) {
		    $this->logger->error("无效的encodingAESKey，IllegalAesKey");
			return ErrorCode::$IllegalAesKey;
		}

	

		//提取密文
	       if ($XmlOrJson === true) {
            $array = $this->JSONEncryptextract($postData);
        } else {
            $array = $this->XMLEncryptextract($postData);
        }
	
		$ret = $array[0];

		if ($ret != 0) {
		  
			return $ret;
		}

		if ($timestamp == null) {
			$timestamp = time();
		}

		$encrypt = $array[1];
		$touser_name = $array[2];

		//验证安全签名
	    $pc = new wxMsgCrypt();
		$array = $pc->getSHA1($this->token, $timestamp, $nonce, $encrypt);
		$ret = $array[0];

		if ($ret != 0) {
		    $this->logger->error("验证解密信息SHA错 decryptMsg SHA Error:$ret ");
			return $ret;
		}

		$signature = $array[1];
		if ($signature != $msgSignature) {
		    $this->logger->error("decryptMsg ValidateSignatureError ");
			return ErrorCode::$ValidateSignatureError;
		}
       // $this->logger->info("签名验证正确，说明token ，msgSignature，timestamp和密文匹配，数据完整性验证通过。现在开始解密 ");
		$result = $pc->decrypt($this->encodingAESKey,$encrypt, $this->appId, $this->logger);
		if ($result[0] != 0) {
		    $this->logger->error("解密错误:-400012是签名错误-40005是appid验证错误-40001是signature错误，目前只能解密xml的加密数据!!! decryptMsg decrypt ERROR: $result[0] ");
			return $result[0];
		}
		$msg = $result[1];
        // $this->logger->info("解密成功：$msg  ");
		return ErrorCode::$OK;
	    }
}





/**
 * 1.第三方回复加密消息给公众平台；
 * 2.第三方收到公众平台发送的消息，验证消息的安全性，并对消息进行解密。
 */
class wxMsgCrypt
{
    public static $block_size = 32;

    /**
     * 对明文进行加密
     * @param string $key 密钥
     * @param string $text 需要加密的明文
     * @param string $appid 公众号的AppID
     * @return array 返回加密后的数据和状态码
     */
    public function encrypt($key, $text, $appid, $logger)
    {
        
        //参考 https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Message_encryption_and_decryption_instructions.html
        try {
            // 获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();
            $aesKey = base64_decode($key .'='); // 第二参数确保正确解码
          //  $aesKey = base64_decode($key, true);
            $text = $random . pack("N", strlen($text)) . $text . $appid;
          
            $iv =substr($aesKey, 0, 16); // 确保 IV 是前 16 字节
            // 使用PKCS7填充
            $text = $this->encode($text);

            // 初始化加密
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr($aesKey, 0, 32), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
      
            if ($encrypted === false) {
                throw new Exception("加密失败, 请检查openssl_decrypt  TEXT= $text 用AESKEY= $key  加密。另外而openssl_encrypt默认启用自动填充，导致双重填充。需禁用自动填充，需要增加 OPENSSL_ZERO_PADDING");
              
            }

            // 使用BASE64对加密后的字符串进行编码
            return array(ErrorCode::$OK, base64_encode($encrypted));
        } catch (Exception $e) {
             $logger->error("请检查  $e");
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * 对密文进行解密
     * @param string $key 密钥
     * @param string $encrypted 需要解密的密文
     * @param string $appid 公众号的AppID
     * @return array 返回解密后的数据和状态码
     */
    public function decrypt($key, $encrypted, $appid, $logger)
    {
        try {
            // 使用BASE64对需要解密的字符串进行解码
          //  $logger->info("打印一下密文$encrypted");
          
            $ciphertext_dec = base64_decode($encrypted);
            
         
            
        
            if ($ciphertext_dec === false) {
                  $logger->error("请检查encrypted");
               }
            $ciper_len=strlen($ciphertext_dec);
            $aesKey = base64_decode($key .'='); // 第二参数确保正确解码
            //$aesKey = base64_decode($key, true);
            $aesKey_len = strlen($aesKey);
              if ( $aesKey  === false) {
                  $logger->error("请检查 AesKey ");
               }
            $iv =substr($aesKey, 0, 16); // 确保 IV 是前 16 字节
          
       
         
         
           $decrypted = openssl_decrypt($ciphertext_dec, 'AES-256-CBC',  substr($aesKey, 0, 32), OPENSSL_RAW_DATA |  OPENSSL_ZERO_PADDING, $iv);
         
            if ($decrypted === false) {
                throw new Exception("  AESKEY= $key 在deocode后长度(默认32）为 $aesKey_len 字节， 密文在deocode后长度为 $ciper_len 字节， iv为aesKey进行decode后的前16个字节，encrypted在用openssl_decrypt通过aesKey和iv进行解密时失败，请分析原因,参考https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Message_encryption_and_decryption_instructions.html，我发现从https://mp.weixin.qq.com/debug?token=1834246867&lang=zh_CN 调试中发现ToUserName  需要9位字符以上openssl解密才会成功,CreateTime太大也会出问题不能超过过8位，很奇怪，另外而openssl_encrypt默认启用自动填充，导致双重填充。需禁用自动填充，需要增加 OPENSSL_ZERO_PADDING ");
               
            }
            // else{
            //     $logger->info("openssl_decrypt 的AES-256-CBC方法解密AESKEY和IV成功，AESKEY在deocode后长度(默认32)为 $aesKey_len 字节， 而密文在deocode后长度为 $ciper_len 字节");
              
            //     }
                
    
      
           
             // 编码转换
       
           
           $result = $this->decode($decrypted);
             
          
            // 去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16) {
                return array(ErrorCode::$IllegalBuffer, null);
            }

            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
        
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            
         
            
        
           
           
           
            if ($from_appid != $appid) {
                $test_log1= mb_convert_encoding($xml_content,'UTF-8','auto');
                 $test_log2= mb_convert_encoding($from_appid,'UTF-8','auto');
                $logger->error(" 验证appid $appid 错误，解密的ID是 $test_log2, contents是$test_log1");
                return array(ErrorCode::$ValidateAppidError, null);
                
            }

            return array(ErrorCode::$OK, $xml_content);
        } catch (Exception $e) {
            $logger->error("请检查  $e");
            return array(ErrorCode::$DecryptAESError, null);
        }
    }

    /**
     * 对需要加密的明文进行填充补位
     * @param string $text 需要进行填充补位操作的明文
     * @return string 补齐明文字符串
     */
    function encode($text)
    {
        $block_size = 32;
        $text_length = strlen($text);
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $block_size;
        }
        $pad_chr = chr($amount_to_pad);
        return $text . str_repeat($pad_chr, $amount_to_pad);
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
    

    


    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    function getRandomStr($length = 16)  // 修改为类方法，支持nonce的12位
    {
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 生成签名
     * @param string $token
     * @param string $timestamp
     * @param string $nonce
     * @param string $encrypt_msg
     * @return array 返回签名和状态码
     */
    public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
    {
        try {
            $array = array($encrypt_msg, $token, $timestamp, $nonce);
            sort($array, SORT_STRING);
            $str = implode($array);
            return array(ErrorCode::$OK, sha1($str));
        } catch (Exception $e) {
            return array(ErrorCode::$ComputeSignatureError, null);
        }
    }
}
?>
