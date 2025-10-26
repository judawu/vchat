#!/usr/bin/env bash

# NSX 安装管理加博爱
# Project directory: /usr/local/nsx
# Supports Docker and local installation, certificate management, and configuration management

# Set language
export LANG=en_US.UTF-8

# 输出颜色
echoContent() {
    case $1 in
        "red") echo -e "\033[31m${2}\033[0m" ;;
        "green") echo -e "\033[32m${2}\033[0m" ;;
        "yellow") echo -e "\033[33m${2}\033[0m" ;;
        "skyblue") echo -e "\033[1;36m${2}\033[0m" ;;
    esac
}

# 定义变量
BASE_DIR="/usr/local/nsx"
CERT_DIR="${BASE_DIR}/certs"
NGINX_DIR="${BASE_DIR}/nginx"
XRAY_DIR="${BASE_DIR}/xray"
LOG_DIR="${BASE_DIR}/log"
SINGBOX_DIR="${BASE_DIR}/sing-box"
WWW_DIR="${BASE_DIR}/www"
SUBSCRIBE_DIR="${BASE_DIR}/www/subscribe"
COMPOSE_FILE="${BASE_DIR}/docker/docker-compose.yml"
NGINX_CONF="${NGINX_DIR}/nginx.conf"
XRAY_CONF="${XRAY_DIR}/config.json"
SINGBOX_CONF="${SINGBOX_DIR}/config.json"
SHM_DIR="/dev/shm/nsx"
NGINX_CACHE_DIR="${NGINX_DIR}/cache"
NGINX_RUN_DIR="${NGINX_DIR}/run"
NGINX_CONF_DIR="${NGINX_DIR}/conf.d"
ACME_DIR="${BASE_DIR}/acme"
ACME_LOG="${LOG_DIR}/acme.log"


# 检查系统信息
checkSystem() {
    echoContent skyblue "检查系统..."
    if [[ -n $(find /etc -name "redhat-release") ]] || grep -q -i "centos" /etc/os-release || grep -q -i "rocky" /etc/os-release; then
        release="centos"
        installCmd='yum -y install'
        upgradeCmd='yum -y update'
        updateCmd='yum -y update'
        uninstallCmd='yum -y remove'
    elif grep -q -i "ubuntu" /etc/os-release; then
        release="ubuntu"
        installCmd='apt -y install'
        upgradeCmd='apt -y upgrade'
        updateCmde='apt update'
        uninstallCmd='apt -y remove'
        
    elif grep -q -i "debian" /etc/os-release; then
        release="debian"
        installCmd='apt -y install'
        upgradeCmd='apt -y upgrade'
        updateCmd='apt update'
        uninstallCmd='apt -y remove'
    else
        echoContent red "不支持的操作系统，脚本仅支持 CentOS、Rocky Linux、Ubuntu 或 Debian."
        exit 1
    fi
    
    if [[ -n $(which uname) ]]; then
        if [[ "$(uname)" == "Linux" ]]; then
            case "$(uname -m)" in
            'amd64' | 'x86_64')
                xrayCoreCPUVendor="Xray-linux-64"
                v2rayCoreCPUVendor="v2ray-linux-64"
                warpRegCoreCPUVendor="main-linux-amd64"
                singBoxCoreCPUVendor="-linux-amd64"
                ;;
            'armv8' | 'aarch64')
                cpuVendor="arm"
                xrayCoreCPUVendor="Xray-linux-arm64-v8a"
                v2rayCoreCPUVendor="v2ray-linux-arm64-v8a"
                warpRegCoreCPUVendor="main-linux-arm64"
                singBoxCoreCPUVendor="-linux-arm64"
                ;;
            *)
                echo "  不支持此CPU架构--->"
                exit 1
                ;;
            esac
        fi
    else
        echoContent red "  无法识别此CPU架构，默认amd64、x86_64--->"
        xrayCoreCPUVendor="Xray-linux-64"
        v2rayCoreCPUVendor="v2ray-linux-64"
    fi


    LOCAL_IP=$(ip addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v "127.0.0.1" | head -n 1)
    echoContent green "系统: $release"
    echoContent green "系统cpu: $(uname -m)"
    echoContent green "本地 IP: $LOCAL_IP"
}

# 检查 SELinux
checkCentosSELinux() {
    if [[ "$release" == "centos" ]] && [[ -f "/etc/selinux/config" ]] && ! grep -q "SELINUX=disabled" /etc/selinux/config; then
        echoContent skyblue "禁用 SELinux 以确保兼容性..."
        sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
        setenforce 0
    fi
}

# 安装工具
installTools() {
    echoContent skyblue "\n安装工具..."
    echoContent green "\n安装以下依赖curl wget git sudo lsof unzip ufw socat jq iputils-ping dnsutils qrencode.."
    ${installCmd} curl wget git sudo lsof unzip ufw socat jq iputils-ping dnsutils qrencode -y
  
    if [[ "$release" != "centos" ]]; then
        echoContent green "\n执行系统更新..."
        ${upgradeCmd}
        ${updateCmd}

    fi
}

# 安装 Docker 和 Docker Compose
installDocker() {
    echoContent skyblue "Docker 安装..."
    if ! command -v docker &> /dev/null; then
        echoContent yellow "安装 Docker..."
        curl -fsSL https://get.docker.com | bash
        if [ $? -ne 0 ]; then
            echoContent red "安装 Docker 失败，请参考 https://docs.docker.com/engine/install/."
            exit 1
        fi
        systemctl enable docker
        systemctl start docker
    else
        echoContent yellow "Docker 已安装."
    fi

    # 检查 Docker Compose 插件
    if ! docker compose version &> /dev/null; then
        echoContent yellow "安装 Docker Compose 插件..."
        if [[ "$release" == "ubuntu" || "$release" == "debian" ]]; then
            ${updateCmd}
            ${upgradeCmd}
            ${installCmd} docker-compose-plugin
            if [ $? -ne 0 ]; then
                echoContent red "通过 apt 安装 Docker Compose 插件失败."
                exit 1
            fi
        elif [[ "$release" == "centos" ]]; then
            # 为 CentOS/Rocky Linux 安装 Docker Compose 插件二进制文件
            mkdir -p /usr/libexec/docker/cli-plugins
            curl -SL "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/libexec/docker/cli-plugins/docker-compose
            chmod +x /usr/libexec/docker/cli-plugins/docker-compose
            if [ $? -ne 0 ]; then
                echoContent red "安装 Docker Compose 插件二进制文件失败."
                exit 1
            fi
        else
            echoContent red "不支持的操作系统，无法安装 Docker Compose 插件."
            exit 1
        fi
    else
        echoContent yellow "Docker Compose 插件已安装."
    fi

    # 验证 Docker Compose 版本
    docker compose version
    if [ $? -eq 0 ]; then
        echoContent green "Docker Compose 插件验证成功: $(docker compose version --short)"
    else
        echoContent red "Docker Compose 插件验证失败，请手动安装."
        exit 1
    fi
}

# 创建目录
createDirectories() {
    echoContent skyblue "\n创建目录..."
    for DIR in "$CERT_DIR" "$NGINX_DIR" "$LOG_DIR" "$NGINX_CACHE_DIR" "$NGINX_RUN_DIR" "$NGINX_CONF_DIR" "$XRAY_DIR"  "$SINGBOX_DIR" "$WWW_DIR"  "$SUBSCRIBE_DIR" "$WWW_DIR/wwwroot/blog" "$WWW_DIR/wwwroot/video" "$SHM_DIR" "$ACME_DIR"; do
        if [ ! -d "$DIR" ]; then
            echoContent yellow "创建目录 $DIR..."
            mkdir -p "$DIR"
        else
            echoContent green "目录 $DIR 已存在."
        fi
    done

    echoContent yellow "设置权限..."
    chown -R nobody:nogroup "$SHM_DIR" "$LOG_DIR" "$CERT_DIR" "$NGINX_CACHE_DIR" "$NGINX_RUN_DIR" "$NGINX_CONF_DIR" "$ACME_DIR"
    chmod -R 700  "$CERT_DIR" "$NGINX_CACHE_DIR" "$NGINX_RUN_DIR" "$NGINX_CONF_DIR" "$ACME_DIR"
    chmod -R 766  "$SHM_DIR" "$LOG_DIR"
}

# 安装 acme.sh
installAcme() {
    if [[ ! -d "$HOME/.acme.sh" ]] || [[ -d "$HOME/.acme.sh" && -z $(find "$HOME/.acme.sh/acme.sh") ]]; then
        echoContent skyblue "\n安装证书程序 acme.sh..."
        curl https://get.acme.sh | sh
        if [[ $? -ne 0 ]]; then
            echoContent red "安装 acme.sh 失败，请参考 https://github.com/acmesh-official/acme.sh."
            exit 1
        fi
    else
        echoContent yellow "acme.sh 已安装."
    fi
}
# 管理证书
manageCertificates() {
    # Define defaults
    ACME_LOG="${ACME_LOG:-/var/log/acme.log}"
    CERT_DIR="${CERT_DIR:-/etc/ssl/private}"
    CREDENTIALS_FILE="${HOME}/.acme.sh/credentials.conf"
    mkdir -p "$CERT_DIR" || { echoContent red "无法创建 $CERT_DIR"; exit 1; }
    touch "$CREDENTIALS_FILE" && chmod 600 "$CREDENTIALS_FILE" || { echoContent red "无法创建 $CREDENTIALS_FILE"; exit 1; }

    echoContent skyblue "\n证书管理菜单"
    echoContent yellow "1. 申请证书"
    echoContent yellow "2. 更新证书"
    echoContent yellow "3. 安装自签证书"
    echoContent yellow "4. 退出"
    read -r -p "请选择一个选项 [1-4]: " cert_option

    case $cert_option in
        1|2)
            local action="--issue"
            [[ "$cert_option" == "2" ]] && action="--renew"
            echoContent skyblue "${action##--}证书..."
            read -r -p "确认 SSL 类型为 letsencrypt 还是 zerossl (y=letsencrypt, n=zerossl): " selectSSLType
            if [[ -n "$selectSSLType" && "$selectSSLType" == "n" ]]; then
                sslType="zerossl"
                read -r -p "请输入你的邮箱注册zerossl(要和你的DNS邮箱一致)回车默认已注册zerossl: " regZeroSSLEmail
                if [[ -n "$regZeroSSLEmail" ]]; then
                 sudo "$HOME/.acme.sh/acme.sh" --register-account -m "$regZeroSSLEmail" --server zerossl
                fi
            else
                sslType="letsencrypt"
            fi
            echoContent yellow " SSL 类型为 $sslType."
            read -r -p "请输入证书域名 (例如: yourdomain.com 或 *.yourdomain.com，多个域名用逗号隔开): " DOMAIN
            if [[ -z "$DOMAIN" ]]; then
                echoContent red "请输入域名"
                return 1
            fi
            # 提取第一个域名用于证书命名
            FIRST_DOMAIN=$(echo "$DOMAIN" | cut -d',' -f1 | xargs)
            echoContent yellow " 证书域名为 $DOMAIN (使用 $FIRST_DOMAIN 作为证书文件名)."
            read -r -p "请输入DNS提供商: 1.Cloudflare, 2.阿里云, 3.手动DNS, 4.独立: " DNS_VENDOR

            if [[ "$cert_option" == "1" ]]; then
                # 清除此域名的先前凭据
                grep -v "^${FIRST_DOMAIN}:" "$CREDENTIALS_FILE" > "${CREDENTIALS_FILE}.tmp" && mv "${CREDENTIALS_FILE}.tmp" "$CREDENTIALS_FILE"
            fi
            echoContent yellow " DNS提供商选择 $DNS_VENDOR."
            if [[ "$DNS_VENDOR" == "1" ]]; then
 
                if [[ "$cert_option" == "2" && -s "$CREDENTIALS_FILE" ]] && grep -q "^${FIRST_DOMAIN}:Cloudflare:" "$CREDENTIALS_FILE"; then
                    # 为续订加载保存的 Cloudflare 凭据
                    IFS=':' read -r _ _ cf_type cf_value1 cf_value2 < <(grep "^${FIRST_DOMAIN}:Cloudflare:" "$CREDENTIALS_FILE")
                    if [[ "$cf_type" == "token" ]]; then
                        cfAPIToken="$cf_value1"
                    else
                        cfAPIEmail="$cf_value1"
                        cfAPIKey="$cf_value2"
                    fi
                    echoContent green "使用保存的 Cloudflare 凭据进行续订"
                else
                    read -r -p "请输入 Cloudflare API Token (推荐) 或按回车使用邮箱和API Key: " cfAPIToken
                    if [[ -n "$cfAPIToken" ]]; then
                        echoContent green "保存 Cloudflare API Token $cfAPIToken"
                        echo "${FIRST_DOMAIN}:Cloudflare:token:${cfAPIToken}" >> "$CREDENTIALS_FILE"
                    else
                        read -r -p "请输入 Cloudflare Email: " cfAPIEmail
                        read -r -p "请输入 Cloudflare Global API Key: " cfAPIKey
                        if [[ -z "${cfAPIEmail}" || -z "${cfAPIKey}" ]]; then
                            echoContent red "输入为空，请重试"
                            return 1
                        fi
                        echoContent green " 保存 Cloudflare Email $cfAPIEmail 和 Global API Key $cfAPIKey"
                        echo "${FIRST_DOMAIN}:Cloudflare:key:${cfAPIEmail}:${cfAPIKey}" >> "$CREDENTIALS_FILE"
                    fi
                fi
                echoContent yellow " Cloudflare DNS API ${action##--}证书中"
                if [[ -n "$cfAPIToken" ]]; then
                    if ! sudo CF_Token="${cfAPIToken}" "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --dns dns_cf -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                       sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                       echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                       exit 1
                    fi
                    unset CF_Token
                else
                    if ! sudo CF_Email="${cfAPIEmail}" CF_Key="${cfAPIKey}" "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --dns dns_cf -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                        sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                        echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                        exit 1
                    fi
                    unset CF_Email CF_Key
                fi
            elif [[ "$DNS_VENDOR" == "2" ]]; then
                if [[ "$cert_option" == "2" && -s "$CREDENTIALS_FILE" ]] && grep -q "^${FIRST_DOMAIN}:Alibaba:" "$CREDENTIALS_FILE"; then
                    # 为续订加载保存的 Alibaba 凭据
                    IFS=':' read -r _ _ aliKey aliSecret < <(grep "^${FIRST_DOMAIN}:Alibaba:" "$CREDENTIALS_FILE")
                    echoContent green " 使用保存的阿里云凭据进行续订"
                else
                    read -r -p "请输入阿里云 Key: " aliKey
                    read -r -p "请输入阿里云 Secret: " aliSecret
                    if [[ -z "${aliKey}" || -z "${aliSecret}" ]]; then
                        echoContent red " 输入为空，请重试"
                        return 1
                    fi
                    echoContent green "保存阿里云 Key 和 Secret"
                    echo "${FIRST_DOMAIN}:Alibaba:${aliKey}:${aliSecret}" >> "$CREDENTIALS_FILE"
                fi
                echoContent yellow " 阿里云 DNS API ${action##--}证书中"
                if ! sudo Ali_Key="${aliKey}" Ali_Secret="${aliSecret}" "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --dns dns_ali -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                    echoContent red "证书签发失败，清理残留数据并退出"
                    sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                    echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                    exit 1
                fi
                unset Ali_Key Ali_Secret
            elif [[ "$DNS_VENDOR" == "3" ]]; then
                echoContent yellow "手动 DNS 模式，请添加 TXT 记录:（例如在cloudware中在DNS下手动建立TXT文件，将下面的字符串输入）"
                if ! sudo "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --dns --yes-I-know-dns-manual-mode-enough-go-ahead-please -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                    echoContent red "证书签发失败，清理残留数据并退出"
                    sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                    echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                fi
                txtValue=$(tail -n 10 "$ACME_LOG" | grep "TXT value" | awk -F "'" '{print $2}' | head -1)
                if [[ -n "$txtValue" ]]; then
                    echoContent green "  名称: _acme-challenge"
                    echoContent green " 值: ${txtValue}"
                    echoContent yellow " 请添加 TXT 记录（例如在cloudware中在DNS下手动建立TXT文件，将下面的字符串${txtValue}输入）并等待 1-2 分钟"
                    read -r -p "是否已添加 TXT 记录? [y/n]: " addDNSTXTRecordStatus
                    if [[ "$addDNSTXTRecordStatus" == "y" ]]; then
                        txtAnswer=$(dig @1.1.1.1 +nocmd "_acme-challenge.${FIRST_DOMAIN}" txt +noall +answer | awk -F "[\"]" '{print $2}' | head -1)
                        if echo "$txtAnswer" | grep -q "^${txtValue}"; then
                            echoContent green "TXT 记录验证通过"
                            if ! sudo "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --dns --yes-I-know-dns-manual-mode-enough-go-ahead-please -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                                   echoContent red "证书签发失败，清理残留数据并退出"
                                   sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                                   echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                            fi
                        else
                            echoContent red "TXT 记录验证失败"
                            exit 1
                        fi
                    fi
                fi
            elif [[ "$DNS_VENDOR" == "4" ]]; then
                echoContent yellow " ---> 独立模式 ${action##--}证书中"
                if ! sudo "$HOME/.acme.sh/acme.sh" $action -d "${DOMAIN}" --standalone -k ec-256 --server "${sslType}" 2>&1 | tee -a "$ACME_LOG"; then
                    echoContent red "命令失败，请检查 $ACME_LOG 日志"
                    exit 1
                fi
            else
                echoContent red "无效 DNS 提供商"
                return 1
            fi

            echoContent yellow "安装证书..."
            if [[ ! -f "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc/fullchain.cer" ]]; then
                echoContent red "证书文件未生成，清理残留数据并退出"
                sudo rm -rf "$HOME/.acme.sh/${FIRST_DOMAIN}_ecc"
                echoContent red "请检查 $ACME_LOG 日志以获取详细信息"
                exit 1
            fi
            if ! sudo "$HOME/.acme.sh/acme.sh" --install-cert -d "${FIRST_DOMAIN}" --ecc \
                --fullchain-file "${CERT_DIR}/${FIRST_DOMAIN}.pem" \
                --key-file "${CERT_DIR}/${FIRST_DOMAIN}.key" 2>&1 | tee -a "$ACME_LOG"; then
                echoContent red "证书安装失败，请检查 $ACME_LOG 日志"
                exit 1
            fi
            chmod 644 "${CERT_DIR}/${FIRST_DOMAIN}.pem"
            chmod 644 "${CERT_DIR}/${FIRST_DOMAIN}.key"
            echoContent green "证书${action##--}并安装成功"
            ;;
        3)
            echoContent skyblue "安装自签证书..."
            if ! command -v openssl &>/dev/null; then
                echoContent yellow "安装 openssl..."
                ${installCmd:-apt install -y} openssl
                if [[ $? -ne 0 ]]; then
                    echoContent red "安装 openssl 失败，请手动安装"
                    exit 1
                fi
            fi
            read -r -p "请输入自签证书域名 (例如: sub.yourdomain.com): " DOMAIN
            if [[ -z "$DOMAIN" ]]; then
                echoContent red "请输入域名"
                return 1
            fi
            echoContent skyblue "为 ${DOMAIN} 生成自签证书..."
            touch /tmp/openssl-san.cnf || { echoContent red "无法写入 /tmp"; exit 1; }
            cat > /tmp/openssl-san.cnf << EOF
[req]
default_bits = 256
prompt = no
default_md = sha256
req_extensions = req_ext
distinguished_name = dn

[dn]
CN = ${DOMAIN}

[req_ext]
subjectAltName = DNS:${DOMAIN}
EOF
            if ! openssl ecparam -name secp256r1 -genkey -out "${CERT_DIR}/${DOMAIN}.key" 2>>"$ACME_LOG"; then
                echoContent red "生成私钥失败，请检查 $ACME_LOG 日志"
                exit 1
            fi
            if ! openssl req -x509 -new -key "${CERT_DIR}/${DOMAIN}.key" -days 365 -out "${CERT_DIR}/${DOMAIN}.pem" \
                -config /tmp/openssl-san.cnf -extensions req_ext 2>>"$ACME_LOG"; then
                echoContent red "生成自签证书失败，请检查 $ACME_LOG 日志"
                exit 1
            fi
            rm -f /tmp/openssl-san.cnf
            chmod 644 "${CERT_DIR}/${DOMAIN}.pem"
            chmod 644 "${CERT_DIR}/${DOMAIN}.key"
            echoContent green "自签证书生成并安装成功，位于 ${CERT_DIR}/${DOMAIN}.pem"
            ;;
        4)
            return
            ;;
        *)
            echoContent red "无效选项，请重试"
            return 1
            ;;
    esac
    if [[ "$cert_option" == "1" || "$cert_option" == "2" ]]; then
        echoContent yellow "清除 TXT 记录..."
        if ! sudo "$HOME/.acme.sh/acme.sh" --remove -d "${FIRST_DOMAIN}" --dns 2>&1 | tee -a "$ACME_LOG"; then
            echoContent red "清除 TXT 记录失败，请检查 $ACME_LOG 日志"
        else
            echoContent green "TXT 记录已清除"
        fi
    fi
    # Schedule renewal for Cloudflare or Alibaba DNS if credentials were saved
    if [[ "$cert_option" == "1" && ("$DNS_VENDOR" == "0" || "$DNS_VENDOR" == "1") ]]; then
        echoContent yellow "设置每3个月自动续订证书..."
        local cron_cmd
        if [[ "$DNS_VENDOR" == "0" ]]; then
            if [[ -n "$cfAPIToken" ]]; then
                cron_cmd="CF_Token=\"\$(grep '^${FIRST_DOMAIN}:Cloudflare:token:' \"${CREDENTIALS_FILE}\" | cut -d':' -f4)\" \"$HOME/.acme.sh/acme.sh\" --renew -d "${DOMAIN}" --dns dns_cf -k ec-256 --server ${sslType} --install-cert -d \"${FIRST_DOMAIN}\" --ecc --fullchain-file \"${CERT_DIR}/${FIRST_DOMAIN}.pem\" --key-file \"${CERT_DIR}/${FIRST_DOMAIN}.key\" 2>&1 | tee -a \"$ACME_LOG\""
            else
                cron_cmd="CF_Email=\"\$(grep '^${FIRST_DOMAIN}:Cloudflare:key:' \"${CREDENTIALS_FILE}\" | cut -d':' -f4)\" CF_Key=\"\$(grep '^${FIRST_DOMAIN}:Cloudflare:key:' \"${CREDENTIALS_FILE}\" | cut -d':' -f5)\" \"$HOME/.acme.sh/acme.sh\" --renew -d "${DOMAIN}" --dns dns_cf -k ec-256 --server ${sslType} --install-cert -d \"${FIRST_DOMAIN}\" --ecc --fullchain-file \"${CERT_DIR}/${FIRST_DOMAIN}.pem\" --key-file \"${CERT_DIR}/${FIRST_DOMAIN}.key\" 2>&1 | tee -a \"$ACME_LOG\""
            fi
        elif [[ "$DNS_VENDOR" == "1" ]]; then
            cron_cmd="Ali_Key=\"\$(grep '^${FIRST_DOMAIN}:Alibaba:' \"${CREDENTIALS_FILE}\" | cut -d':' -f3)\" Ali_Secret=\"\$(grep '^${FIRST_DOMAIN}:Alibaba:' \"${CREDENTIALS_FILE}\" | cut -d':' -f4)\" \"$HOME/.acme.sh/acme.sh\" --renew -d "${DOMAIN}" --dns dns_ali -k ec-256 --server ${sslType} --install-cert -d \"${FIRST_DOMAIN}\" --ecc --fullchain-file \"${CERT_DIR}/${FIRST_DOMAIN}.pem\" --key-file \"${CERT_DIR}/${FIRST_DOMAIN}.key\" 2>&1 | tee -a \"$ACME_LOG\""
        fi
        (crontab -l 2>/dev/null | grep -v "${FIRST_DOMAIN}.*acme.sh --renew"; echo "0 3 1 */3 * $cron_cmd") | crontab -
        echoContent green "已为 ${FIRST_DOMAIN} 设置每3个月自动续订"
    fi
}

url_encode() {
    local input="$1"
    if ! command -v jq &> /dev/null; then
        echoContent red "Error: jq is not installed" >&2
        return 1
    fi
    local encoded
    encoded=$(printf '%s' "$input" | jq -nr --arg v "$input" '$v | @uri' | sed 's/%23/#/' 2>/dev/null) || {
        echoContent red "Error: URL encoding failed" >&2
        return 1
    }
    printf '%s' "$encoded"
}
xray_config() {
    echoContent skyblue "\nxray配置文件修改"
    echoContent green "xray是非常强大的代理，这个config文件包括了api，内网穿透，多协议配置，加密设置，网络分流等功能\n关于xray的命令可以用xray help进行命令查看"
    # 定义文件路径
  
    TEMP_FILE="/tmp/xray_config_temp.json"

    # 检查 jq 和 xray 是否已安装
    if ! command -v jq &> /dev/null; then
        echoContent red "jq 未安装，请先安装 jq"
        exit 1
    fi

    if ! command -v xray &> /dev/null; then
        echoContent red "xray 未安装或未创建软链接，请先安装 xray 或使用 ln -sf /usr/local/xray/xray /usr/bin/xray 创建软链接"
        exit 1
    fi

    echoContent green "临时文件位置: $TEMP_FILE"

    # 检查 config.json 是否存在
    if [[ ! -f "$XRAY_CONF" ]]; then
        echoContent red "$XRAY_CONF 不存在"
        exit 1
    fi

    # 创建订阅目录
    if [[ ! -d "$SUBSCRIBE_DIR" ]]; then
        mkdir -p "$SUBSCRIBE_DIR"
        chown nobody:nogroup "$SUBSCRIBE_DIR" 2>/dev/null || echoContent yellow "警告: chown 失败，可能需要检查权限"
        chmod 755 "$SUBSCRIBE_DIR" 2>/dev/null || echoContent yellow "警告: chmod 失败，可能需要检查权限"
    fi

    # 生成 Xray 订阅文件
    XRAY_SUB_FILE="${SUBSCRIBE_DIR}/xray_sub.txt"
    if [[ -f "$XRAY_CONF" ]]; then
        echoContent green "创建 Xray 订阅文件于 ${SUBSCRIBE_DIR}..."
        > "$XRAY_SUB_FILE"
    fi

  

    # 备份原始文件
    cp "$XRAY_CONF" "${XRAY_CONF}.bak" || {
        echoContent red "错误: 无法创建备份 ${XRAY_CONF}.bak"
        exit 1
    }
    echoContent green "创建备份: ${XRAY_CONF}.bak"

    generate_short_ids() {
        local short_id1=$(openssl rand -hex 4)  # 8 字节
        local short_id2=$(openssl rand -hex 8)  # 16 字节
        echo "[\"$short_id1\", \"$short_id2\"]"
    }

    echoContent green "\n创建一个临时 JSON 文件 $TEMP_FILE，复制原始内容 $XRAY_CONF"
    cp "$XRAY_CONF" "$TEMP_FILE" || {
        echoContent red "错误: 无法创建临时文件 $TEMP_FILE"
        exit 1
    }
    # Update inbounds configuration

     read -p "是否设置outbounds shadowsocks分流: " split_ss < /dev/tty
    if [[ -z "$split_ss" ]]; then
        echoContent green "不设置ss分流，xray配置文件里面的shadowsocks保持默认"
    else
        echoContent green "设置ss分流，更新shadowsocks的密码"
        ss_in=$(jq -c '.inbounds[] | select(.protocol == "shadowsocks")' "$TEMP_FILE")
        if [[ -z "$ss_in" ]]; then
            echoContent red "错误: 未找到Shadowsocks inbound配置"
            exit 1
        fi
        ss_in_tag=$(echo "$ss_in" | jq -r '.tag')
        ss_protocol=$(echo "$ss_in" | jq -r '.protocol')
        ss_port=$(echo "$ss_in" | jq -r '.port')
        ss_method=$(echo "$ss_in" | jq -r '.settings.method')
        ss_password=$(echo "$ss_in" | jq -r '.settings.password')

        read -p "输入 shadowsocks method 默认($ss_method) 0=2022-blake3-aes-128-gcm, 1=2022-blake3-aes-256-gcm, 2=2022-blake3-chacha20-poly1305: " ss_method_option < /dev/tty
        case "$ss_method_option" in
            0) ss_method="2022-blake3-aes-128-gcm" ;;
            1) ss_method="2022-blake3-aes-256-gcm" ;;
            2) ss_method="2022-blake3-chacha20-poly1305" ;;
            *) echoContent green "默认($ss_method)" ;;
        esac

        read -p "输入 shadowsocks password 默认($ss_password): " ss_new_password < /dev/tty
        if [[ -z "$ss_new_password" ]]; then
            ss_new_password="$ss_password"
        elif [[ ${#ss_new_password} -lt 6 ]]; then
            ss_new_password=$(openssl rand -base64 16)
            echoContent green "密码太短，自动生成: $ss_new_password"
        fi

        read -p "输入 shadowsocks port 默认($ss_port): " ss_new_port < /dev/tty
        if [[ -z "$ss_new_port" ]]; then
            ss_new_port="$ss_port"
        elif ! [[ "$ss_new_port" =~ ^[0-9]+$ ]] || [[ "$ss_new_port" -lt 1 || "$ss_new_port" -gt 65535 ]]; then
            echoContent red "错误: 端口号必须在1-65535之间"
            exit 1
        fi

      
        echoContent green "\n更新 $ss_in_tag:\n"
        jq --arg tag "$ss_in_tag" --arg ss_new_port "$ss_new_port" --arg ss_method "$ss_method" --arg ss_new_password "$ss_new_password" \
            '(.inbounds[] | select(.tag == $tag)) |= (.port = ($ss_new_port | tonumber) | .settings.password = $ss_new_password | .settings.method = $ss_method)' \
            "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
            echoContent red "错误: 无法更新 shadowsocks inbound"
            exit 1
        }

        # Extract VPS outbounds configurations
        echoContent green "\n更新 vps,保持和 inbound $ss_in_tag 配置一致:\n"
        vps1=$(jq -c '.outbounds[] | select(.tag == "vps1")' "$TEMP_FILE")
        vps1_address=$(echo "$vps1" | jq -r '.settings.servers[0].address // empty')
        vps2=$(jq -c '.outbounds[] | select(.tag == "vps2")' "$TEMP_FILE")
        vps2_address=$(echo "$vps2" | jq -r '.settings.servers[0].address // empty')
        vps3=$(jq -c '.outbounds[] | select(.tag == "vps3")' "$TEMP_FILE")
        vps3_address=$(echo "$vps3" | jq -r '.settings.servers[0].address // empty')
        vps4=$(jq -c '.outbounds[] | select(.tag == "vps4")' "$TEMP_FILE" )
        vps4_address=$(echo "$vps4" | jq -r '.settings.servers[0].address // empty')

        # Prompt for VPS addresses
        read -p "输入 vps1的IP 默认($vps1_address): " vps1_new_address < /dev/tty
        [[ -z "$vps1_new_address" ]] && vps1_new_address="$vps1_address"
        read -p "输入 vps2的IP 默认($vps2_address): " vps2_new_address < /dev/tty
        [[ -z "$vps2_new_address" ]] && vps2_new_address="$vps2_address"
        read -p "输入 vps3的IP 默认($vps3_address): " vps3_new_address < /dev/tty
        [[ -z "$vps3_new_address" ]] && vps3_new_address="$vps3_address"
        read -p "输入 vps4的IP 默认($vps4_address): " vps4_new_address < /dev/tty
        [[ -z "$vps4_new_address" ]] && vps4_new_address="$vps4_address"

        # Update outbounds configurations
        for vps_tag in "vps1" "vps2" "vps3" "vps4"; do
            vps_new_address_var="${vps_tag}_new_address"
            if [[ -z "${!vps_new_address_var}" ]]; then
                echoContent red "错误: VPS地址未设置 ($vps_tag)"
                exit 1
            fi
            jq --arg tag "$vps_tag" --arg ss_new_address "${!vps_new_address_var}" --arg ss_new_port "$ss_new_port" --arg ss_method "$ss_method" --arg ss_new_password "$ss_new_password" \
                '(.outbounds[] | select(.tag == $tag)) |= (.settings.servers[0].address = $ss_new_address | .settings.servers[0].port = ($ss_new_port | tonumber) | .settings.servers[0].password = $ss_new_password | .settings.servers[0].method = $ss_method)' \
                "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                echoContent red "错误: 无法更新 $vps_tag"
                exit 1
            }
        done
        echoContent green "Shadowsocks configuration updated successfully"
    fi
    # 替换 yourdomain 为用户输入的域名
    # 获取用户输入的域名
    echoContent yellow "请手动输入订阅域名\n"
    read -p "请输入订阅域名 (e.g., yourdomain.com): " YOURDOMAIN < /dev/tty
    if [[ -z "$YOURDOMAIN" ]]; then
        YOURDOMAIN=$REALITY_YOURDOMAIN
        echoContent green "订阅链接地址为：$YOURDOMAIN "
    else
        jq --arg domain "$YOURDOMAIN" \
            'walk(if type == "string" then gsub("yourdomain"; $domain) else . end)' \
            "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
            echoContent red "错误: 无法更新域名"
        }
    fi
    
  
    echoContent yellow "提取所有 inbounds\n"
    # 遍历每个 inbound
    jq -c '.inbounds[] | select(.settings.clients)' "$TEMP_FILE" | while IFS= read -r inbound; do
        url=""
        tag=$(echo "$inbound" | jq -r '.tag')
        protocol=$(echo "$inbound" | jq -r '.protocol')
        port=$(echo "$inbound" | jq -r '.port // "443"')
        echoContent yellow "\n处理 inbound tag: $tag, protocol: $protocol"
        network=$(echo "$inbound" | jq -r '.streamSettings.network // "tcp"')
        url="$url?type=$network"

        case "$network" in
            "grpc")
                serviceName=$(echo "$inbound" | jq -r '.streamSettings.grpcSettings.serviceName')
                serviceName=$(url_encode "$serviceName")
                url="$url&serviceName=$serviceName"
                ;;
            "ws")
                path=$(echo "$inbound" | jq -r '.streamSettings.wsSettings.path')
                path=$(url_encode "$path")
                url="$url&path=$path"
                ;;
            "xhttp")
                xhttpSettings=$(echo "$inbound" | jq -r '.streamSettings.xhttpSettings')
                host=$(echo "$xhttpSettings" | jq -r '.host // empty')
                path=$(echo "$xhttpSettings" | jq -r '.path')
                host=$(url_encode "$host")
                path=$(url_encode "$path")
                url="$url&host=$host&path=$path"
                ;;
            "splithttp")
                path=$(echo "$inbound" | jq -r '.streamSettings.splithttpSettings.path')
                path=$(url_encode "$path")
                url="$url&path=$path"
                ;;
            "httpupgrade")
                path=$(echo "$inbound" | jq -r '.streamSettings.httpupgradeSettings.path')
                path=$(url_encode "$path")
                url="$url&path=$path"
                ;;
            "kcp")
                seed=$(echo "$inbound" | jq -r '.streamSettings.kcpSettings.seed')
                url="$url&seed=$seed"
                ;;
            *)
                ;;
        esac

        # 检查 streamSettings.security
        security=$(echo "$inbound" | jq -r '.streamSettings.security // "none"')
        if [[ "$security" == "reality" ]]; then
            echoContent green "\n检查 streamSettings: reality security for $tag, updating keys and settings..."

            # 生成公私密钥对
            echoContent green "\n用 xray x25519 生成公私钥\n用 openssl rand -hex 4 生成随机的 shortIds"
            key_pair=$(xray x25519 2>/dev/null) || {
                echoContent red "错误: 无法生成 x25519 密钥对"
                exit 1
            }
            private_key=$(echo "$key_pair" | grep "PrivateKey" | awk '{print $2}')
            public_key=$(echo "$key_pair" | grep "Password" | awk '{print $2}')
            new_short_ids=$(generate_short_ids)
            echoContent yellow "\n生成新 privateKey: $private_key"
            echoContent yellow "\n生成新 publicKey: $public_key"
            echoContent yellow "\n生成新 shortIds: $new_short_ids"

            # 检查 JSON 中是否存在 mldsa65Seed
            if jq -e "(.inbounds[] | select(.tag == \"$tag\") | .streamSettings.realitySettings | has(\"mldsa65Seed\"))" "$TEMP_FILE" > /dev/null; then
                echoContent green "\n用 xray mldsa65 生成 mldsa65 seed 和 verify"
                new_mldsa65_key_pair=$(xray mldsa65 2>/dev/null) || {
                    echoContent red "错误: 无法生成 mldsa65 密钥对"
                    exit 1
                }
                mldsa65_seed=$(echo "$new_mldsa65_key_pair" | grep "Seed" | awk '{print $2}')
                mldsa65_verify=$(echo "$new_mldsa65_key_pair" | grep "Verify" | awk '{print $2}')
                echoContent yellow "\n生成新 mldsa65Seed: $mldsa65_seed"
                echoContent yellow "\n生成新 mldsa65Verify: $mldsa65_verify"
            else
                mldsa65_seed=""
                mldsa65_verify=""
                echoContent yellow "\nmldsa65Seed 未找到，跳过 ML-DSA65 密钥生成"
            fi

            # 更新 reality 设置
            jq --arg tag "$tag" --arg private_key "$private_key" --arg public_key "$public_key" --argjson short_ids "$new_short_ids" --arg mldsa65_seed "$mldsa65_seed" --arg mldsa65_verify "$mldsa65_verify" \
                '(.inbounds[] | select(.tag == $tag) | .streamSettings.realitySettings) |=
                    (.privateKey = $private_key | .password = $public_key | .shortIds = $short_ids
                    | if has("mldsa65Seed") then .mldsa65Seed = $mldsa65_seed else . end
                    | if has("mldsa65Seed") then .mldsa65Verify = $mldsa65_verify else . end)' \
                "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                echoContent red "错误: 无法更新 reality 设置"
                exit 1
            }

            # 更新 URL
            short_id=$(echo "$new_short_ids" | jq -r '.[0]')
            reality_url="&security=reality&pbk=$public_key&fp=chrome&sni=$YOURDOMAIN&sid=$short_id"
            url="$url$reality_url"
            if [[ -n "$mldsa65_seed" ]]; then
                url="$url&pqv=$mldsa65_verify"
            fi
        elif [[ "$security" == "tls" ]]; then
            tlsSettings=$(echo "$inbound" | jq -r '.streamSettings.tlsSettings')
            sni=$(echo "$tlsSettings" | jq -r '.serverName // "'"$YOURDOMAIN"'"')
            alpn=$(echo "$inbound" | jq -r '.streamSettings.tlsSettings.alpn // "h2"')
               # 处理 alpn
            if [[ "$alpn" == \[*\] ]]; then
                alpn=$(echo "$alpn" | jq -r 'join(",")')
            fi
            alpn=$(url_encode "$alpn")
            read -p "是否开启tls Encrypted Client Hello？: " tls_ech < /dev/tty
            if [[  "$tls_ech" == "y" ]]; then
                 echServerKeys_Config=$(xray tls ech --serverName "$sni" 2>/dev/null) || {
                    echoContent red "错误: 无法生成 ECH 配置"
                    exit 1
                }
                echServerKeys=$(echo "$echServerKeys_Config" | sed -n '4p')
                echConfigList=$(echo "$echServerKeys_Config" | sed -n '2p')

                # 更新 echServerKeys
                jq --arg tag "$tag" --arg echServerKeys "$echServerKeys" --arg echConfigList "$echConfigList" \
                    '(.inbounds[] | select(.tag == $tag) | .streamSettings.tlsSettings) |= (.echServerKeys = $echServerKeys | .echConfigList = $echConfigList)' \
                    "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "错误: 无法更新 echServerKeys"
                    exit 1
                }
                url="$url&security=tls&fp=chrome&sni=$sni&alpn=$alpn&ech=$echConfigList"           
            else
                echoContent green "\n不启用 Encrypted Client Hello"
                url="$url&security=tls&fp=chrome&sni=$sni&alpn=$alpn" 
            fi
        else
            if [[ "$network" == "xhttp" && -n "$reality_url" ]]; then
                    url="$url$reality_url"
            elif [[ "$protocol" == "trojan" && -n "$reality_url" ]]; then
                    url="$url$reality_url"
            else
                    url="$url&security=tls&fp=${fp:-chrome}&sni=${YOURDOMAIN:-example.com}"
            fi
        fi

        # 处理 vless 和 vmess 的 id 替换
        if [[ "$protocol" == "vless" || "$protocol" == "vmess" ]]; then
            vless_decryption=$(echo "$inbound" | jq -r '.settings.decryption // "none"')
            vless_fallback=$(echo "$inbound" | jq -r '.settings.fallbacks[0].dest // empty')
            if [[ -z "$vless_fallback" ]]; then
                
                read -p "是否启用 encrytion（y/n)，服务器decryption目前不能和fallback同时使用: " enable_vless_encrytion < /dev/tty
                if [[ "$enable_vless_encrytion" == "y" ]]; then
                    echoContent green "\nvless 服务器decryption 开启"
                    read -p "选择流量外观 (1=native, 2=xorpub, 3=random): " vless_flowview < /dev/tty
                    case "$vless_flowview" in
                        1)
                            new_vless_decryption="mlkem768x25519plus.native.600s."
                            new_vless_encryption="mlkem768x25519plus.native.0rtt."
                            ;;
                        2)
                            new_vless_decryption="mlkem768x25519plus.xorpub.600s."
                            new_vless_encryption="mlkem768x25519plus.xorpub.0rtt."
                            ;;
                        *)
                            new_vless_decryption="mlkem768x25519plus.random.600s."
                            new_vless_encryption="mlkem768x25519plus.random.0rtt."
                            ;;
                    esac
                    read -p "选择加密方式 (mlkem768/x25519): " vless_Authentication < /dev/tty
                    if [[ -z "$vless_Authentication" || "$vless_Authentication" == "x25519" ]]; then
                        echoContent green "选择了 x25519"
                        x25519_key_pair=$(xray x25519 2>/dev/null) || {
                            echoContent red "错误: 无法生成 x25519 密钥"
                            exit 1
                        }
                        new_vless_decryption="$new_vless_decryption$(echo "$x25519_key_pair" | grep "PrivateKey" | awk '{print $2}')"
                        new_vless_encryption="$new_vless_encryption$(echo "$x25519_key_pair" | grep "Password" | awk '{print $2}')"
                    else
                        echoContent green "选择了 mlkem768"
                        mlkem768_key_pair=$(xray mlkem768 2>/dev/null) || {
                            echoContent red "错误: 无法生成 mlkem768 密钥"
                            exit 1
                        }
                        new_vless_decryption="$new_vless_decryption$(echo "$mlkem768_key_pair" | grep "Seed" | awk '{print $2}')"
                        new_vless_encryption="$new_vless_encryption$(echo "$mlkem768_key_pair" | grep "Client" | awk '{print $2}')"
                    fi
                    echoContent yellow "\n替换 decryption, $tag: $vless_decryption -> $new_vless_decryption\n"
                    # 更新 decryption
                    jq --arg tag "$tag" --arg new_decryption "$new_vless_decryption" \
                        '(.inbounds[] | select(.tag == $tag) | .settings).decryption = $new_decryption' \
                        "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                        echoContent red "错误: 无法更新 decryption"
                        exit 1
                    }
                else
                    echoContent green "\n不启用vless encryption"
                    new_vless_encryption="none"
                fi
            fi
            echoContent green "\n处理 vless 和 vmess 的 id 替换，用 xray uuid 生成新 UUID"
            clients=$(echo "$inbound" | jq -c '.settings.clients[]')
            client_index=0
            echo "$clients" | while IFS= read -r client; do
                old_id=$(echo "$client" | jq -r '.id')
                new_id=$(xray uuid 2>/dev/null) || {
                    echoContent red "错误: 无法生成 UUID"
                    exit 1
                }
                
                flow=$(echo "$client" | jq -r '.flow // empty')
                reverse_tag=$(echo "$client" | jq -r '.reverse.tag // empty')
                if [[ -n "$reverse_tag" ]]; then
                 tag=$reverse_tag
                fi
                if [[ "$security" == "reality" ]]; then
                sub_your_domain=$LOCAL_IP
                else
                sub_your_domain=$YOURDOMAIN
                fi
                new_url="$protocol://$new_id@$sub_your_domain:$port$url&flow=$flow&encryption=$new_vless_encryption#$tag"
                echoContent yellow "\n替换 $client_index UUID, $tag: $old_id -> $new_id\n"
                # 更新 id
                jq --arg tag "$tag" --arg old_id "$old_id" --arg new_id "$new_id" \
                    '(.inbounds[] | select(.tag == $tag) | .settings.clients[] | select(.id == $old_id)).id = $new_id' \
                    "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "错误: 无法更新 UUID"
                    exit 1
                }
                echo "$new_url" >> "$XRAY_SUB_FILE"
                echoContent skyblue "\n生成 $protocol 订阅链接: $new_url"
                if command -v qrencode &> /dev/null; then
                    qrencode -t ANSIUTF8 "$new_url" || echoContent red "生成二维码失败: $new_url"
                else
                    echoContent yellow "警告: qrencode 未安装，跳过二维码生成"
                fi
                ((client_index++))
            done
        fi

        # 处理 trojan 和 shadowsocks 的 password 替换
        if [[ "$protocol" == "trojan"  ]]; then
            echoContent green "\n处理 trojan  的 password 替换，用 openssl rand -base64 16 生成新密码"
            clients=$(echo "$inbound" | jq -c '.settings.clients[]')
            client_index=0
            echo "$clients" | while IFS= read -r client; do
                old_password=$(echo "$client" | jq -r '.password')
                new_password=$(openssl rand -base64 16)  
                new_url="$protocol://$new_password@$YOURDOMAIN:$port$url#$tag"      
                echoContent yellow "\n替换 $client_index password $tag: $old_password -> $new_password\n"
                # 更新 password
                jq --arg tag "$tag" --arg old_password "$old_password" --arg new_password "$new_password" \
                    '(.inbounds[] | select(.tag == $tag) | .settings.clients[] | select(.password == $old_password)).password = $new_password' \
                    "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "错误: 无法更新 password"
                    exit 1
                }
                echo "$new_url" >> "$XRAY_SUB_FILE"
                echoContent skyblue "\n生成 $protocol 订阅链接: $new_url"
                if command -v qrencode &> /dev/null; then
                    qrencode -t ANSIUTF8 "$new_url" || echoContent red "生成二维码失败: $new_url"
                else
                    echoContent yellow "警告: qrencode 未安装，跳过二维码生成"
                fi
                ((client_index++))
            done
        fi
    done

    # 替换原始文件
    mv "$TEMP_FILE" "$XRAY_CONF" || {
        echoContent red "错误: 无法替换 $XRAY_CONF"
        exit 1
    }
    echoContent skyblue "已为 $XRAY_CONF 更新了新的 UUIDs、passwords、reality settings，并更新了域名 $YOURDOMAIN"

    # 验证 JSON 文件是否有效
    if jq empty "$XRAY_CONF" &> /dev/null; then
        echoContent skyblue "JSON 有效，可以进行服务重启"
    else
        echoContent red "错误: 更新后的 JSON 文件无效，恢复备份"
        mv "${XRAY_CONF}.bak" "$XRAY_CONF"
        exit 1
    fi
}
singbox_config() {
    echoContent skyblue "\nsingbox配置文件修改"

    # 定义默认变量
    TEMP_FILE="/tmp/singbox_config_temp.json"

    # 清理临时文件
    trap 'rm -f "$TEMP_FILE" "${TEMP_FILE}.tmp" 2>/dev/null' EXIT

    # 检查变量
    if [[ -z "$SINGBOX_CONF" || -z "$SUBSCRIBE_DIR" ]]; then
        echoContent red "Error: SINGBOX_CONF or SUBSCRIBE_DIR is not set."
        exit 1
    fi

    # 检查文件权限
    if [[ ! -r "$SINGBOX_CONF" || ! -w "$SINGBOX_CONF" ]]; then
        echoContent red "Error: $SINGBOX_CONF is not readable or writable."
        exit 1
    fi

    # 检查 jq 和 sing-box
    if ! command -v jq &> /dev/null; then
        echoContent red "Error: jq 没有安装. 请先安装."
        exit 1
    fi
    if ! command -v sing-box &> /dev/null; then
        echoContent red "Error: sing-box 没有安装. 请先安装."
        exit 1
    fi

    # 检查 qrencode 依赖（用于生成二维码）
    if ! command -v qrencode &> /dev/null; then
        echoContent yellow "Warning: qrencode is not installed, skipping QR code generation."
        QRENCODE_AVAILABLE=false
    else
        QRENCODE_AVAILABLE=true
    fi

    # 检查 config.json
    if [[ ! -f "$SINGBOX_CONF" ]]; then
        echoContent red "Error: $SINGBOX_CONF不存在"
        exit 1
    fi

    # 创建订阅目录
    if [ ! -d "$SUBSCRIBE_DIR" ]; then
        mkdir -p "$SUBSCRIBE_DIR" || {
            echoContent red "Error: Failed to create directory $SUBSCRIBE_DIR"
            exit 1
        }
        chown nobody:nogroup "$SUBSCRIBE_DIR"
        chmod 755 "$SUBSCRIBE_DIR"
    fi

    # 生成订阅文件
    echoContent green "创建 Sing-box 订阅文件于${SUBSCRIBE_DIR}..."
    SINGBOX_SUB_FILE="${SUBSCRIBE_DIR}/singbox_sub.txt"
    > "$SINGBOX_SUB_FILE"

   
   
    # 备份原始文件
    cp "$SINGBOX_CONF" "${SINGBOX_CONF}.bak" || {
        echoContent red "Error: Failed to create backup ${SINGBOX_CONF}.bak"
        exit 1
    }
    echoContent green "Backup created: ${SINGBOX_CONF}.bak"
  
    generate_short_ids() {
    short_id=$(openssl rand -hex 8)  # 16 字节
    echo "[\"\", \"$short_id\"]"
    }

    # 创建临时 JSON 文件
    cp "$SINGBOX_CONF" "$TEMP_FILE" || {
        echoContent red "Error: Failed to create temporary file $TEMP_FILE"
        exit 1
    }

    echoContent yellow "请手动输入订阅域名\n"
    read -p "请输入订阅域名 (e.g., yourdomain.com): " SINGBOXDOMAIN
    if [[ -z "$SINGBOXDOMAIN" ]]; then
        SINGBOXDOMAIN=$SING_YOURDOMAIN
        echoContent green "订阅链接地址为：$SINGBOXDOMAIN "
    else
        jq --arg domain "$SINGBOXDOMAIN" \
            'walk(if type == "string" then gsub("sing.yourdomain"; $domain) else . end)' \
            "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
            echoContent red "错误: 无法更新sing.yourdomain域名"
        }
    fi
    # 提取所有 inbounds
    echoContent skyblue "\n提取所有 inbounds里的users\n"
    jq -c '.inbounds[] | select(.users)' "$TEMP_FILE" | while IFS= read -r inbound; do
        # 初始化 URL
        url=""

        tag=$(echo "$inbound" | jq -r '.tag')
        type=$(echo "$inbound" | jq -r '.type')
        port=$(echo "$inbound" | jq -r '.listen_port // "443"')
        echoContent yellow "\nProcessing inbound with tag: $tag, type: $type, port: $port"
         # 添加传输协议参数
        transport=$(echo "$inbound" | jq -r '.transport.type // "tcp"')
        url="?type=$transport"
        case "$transport" in
            "grpc")
                serviceName=$(echo "$inbound" | jq -r '.transport.service_name // empty')
                if [[ -n "$serviceName" ]]; then
                    serviceName=$(url_encode "$serviceName")
                    url="$url&serviceName=$serviceName"
                fi
                ;;
            "ws")
                path=$(echo "$inbound" | jq -r '.transport.path // empty')
                if [[ -n "$path" ]]; then
                    path=$(url_encode "$path")
                    url="$url&path=$path"
                fi
                ;;
            "http")
                path=$(echo "$inbound" | jq -r '.transport.path // empty')
                host=$(echo "$inbound" | jq -r '.transport.header.host // empty')
                if [[ -n "$path" ]]; then
                    path=$(url_encode "$path")
                    url="$url&path=$path"
                fi
                if [[ -n "$host" ]]; then
                    host=$(url_encode "$host")
                    url="$url&host=$host"
                fi
                ;;
            "httpupgrade")
                path=$(echo "$inbound" | jq -r '.transport.path // empty')
                if [[ -n "$path" ]]; then
                    path=$(url_encode "$path")
                    url="$url&path=$path"
                fi
                ;;
            *)
                ;;
        esac

        # 检查 TLS 设置
        tls_enabled=$(echo "$inbound" | jq -r '.tls.enabled // false')
        if [[ "$tls_enabled" == "true" ]]; then
            reality_enabled=$(echo "$inbound" | jq -r '.tls.reality.enabled // false')
            if [[ "$reality_enabled" == "true" ]]; then
                echoContent green "\nDetected reality TLS for $tag, updating keys and settings..."
                key_pair=$(sing-box generate reality-keypair) || {
                    echoContent red "Error: Failed to generate reality key pair."
                    exit 1
                }
                private_key=$(echo "$key_pair" | grep "PrivateKey" | awk '{print $2}')
                public_key=$(echo "$key_pair" | grep "PublicKey" | awk '{print $2}')
                new_short_ids=$(generate_short_ids)
                short_id=$(echo "$new_short_ids" | jq -r '.[1]') # 取第二个 short_id

                echoContent yellow "\nGenerated new private_key: $private_key"
                echoContent yellow "\nGenerated new public_key: $public_key"
                echoContent yellow "\nGenerated new short_id: $new_short_ids"

                # 更新 private_key, short_id
                jq --arg tag "$tag" --arg private_key "$private_key"  --argjson short_ids "$new_short_ids" \
                   '(.inbounds[] | select(.tag == $tag) | .tls.reality) |=
                    (.private_key = $private_key  | .short_id = $short_ids)' \
                   "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "Error: Failed to update reality settings."
                    exit 1
                }

                url="$url&security=reality&pbk=$public_key&fp=chrome&sni=$SINGBOXDOMAIN&sid=$short_id"
            else
                fp=$(echo "$inbound" | jq -r '.tls.fingerprint // "chrome"')
                sni=$(echo "$inbound" | jq -r '.tls.server_name // "'"$SINGBOXDOMAIN"'"')
                alpn=$(echo "$inbound" | jq -r '.tls.alpn // "http/1.1"')

                # 如果 alpn 是数组，则将其转换为逗号分隔的字符串
                if [[ "$alpn" == \[*\] ]]; then
                    alpn=$(echo "$alpn" | jq -r 'join(",")')
                fi
                alpn=$(url_encode "$alpn")
                url="$url&security=tls&fp=$fp&sni=$sni&alpn=$alpn"
            fi
        else
            url="$url"
        fi

          if [[ "$type" == "tuic" ]]; then
                echoContent green "\n处理uuid 替换,用sing-box generate uuid 生成uuid\n"
                user_index=0
                echo "$inbound" | jq -c '.users[]' | while IFS= read -r user; do
                    old_uuid=$(echo "$user" | jq -r '.uuid')
                    old_password=$(echo "$user" | jq -r '.password')
                    new_uuid=$(sing-box generate uuid) || {
                        echoContent red "Error: Failed to generate UUID."
                        exit 1
                    }
                    new_password=$(openssl rand -base64 16)

                    echoContent yellow "\nReplacing UUID and password for user $user_index in $tag: $old_uuid -> $new_uuid"

                    # 更新 uuid 和 password
                    jq --arg tag "$tag" \
                    --arg old_uuid "$old_uuid" \
                    --arg new_uuid "$new_uuid" \
                    --arg old_password "$old_password" \
                    --arg new_password "$new_password" \
                    '(.inbounds[]
                        | select(.tag == $tag)
                        | .users[]
                        | select(.uuid == $old_uuid and .password == $old_password))
                        |= (.uuid = $new_uuid | .password = $new_password)' \
                    "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                        echoContent red "Error: Failed to update UUID."
                        exit 1
                    }

                    # 构造 URL
                    #new_url="tuic://$new_uuid:$new_password@$SINGBOXDOMAIN:$port?congestion_control=bbr&sni=$SINGBOXDOMAIN#$tag"
                     new_url="tuic://$new_uuid:$new_password@$SINGBOXDOMAIN:$port$url#$tag"
                
                    echo "$new_url" >> "$SINGBOX_SUB_FILE"
                    echoContent skyblue "\n生成 $type 订阅链接: $new_url"
                    qrencode -t ANSIUTF8 "$new_url" 2>/dev/null
                
                    ((user_index++))
                done
            fi


        # 处理 vmess、vless 和 tuic 的 uuid 替换
        if [[ "$type" == "vmess" || "$type" == "vless" ]]; then
            echoContent green "\n处理 vmess、vless 的 uuid 替换,用sing-box generate uuid 生成uuid\n"
            user_index=0
            echo "$inbound" | jq -c '.users[]' | while IFS= read -r user; do
                old_uuid=$(echo "$user" | jq -r '.uuid')
                new_uuid=$(sing-box generate uuid) || {
                    echoContent red "Error: Failed to generate UUID."
                    exit 1
                }
                echoContent yellow "\nReplacing UUID for user $user_index in $tag: $old_uuid -> $new_uuid"

                # 更新 uuid
                jq --arg tag "$tag" --arg old_uuid "$old_uuid" --arg new_uuid "$new_uuid" \
                   '(.inbounds[] | select(.tag == $tag) | .users[] | select(.uuid == $old_uuid)).uuid = $new_uuid' \
                   "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "Error: Failed to update UUID."
                    exit 1
                }

                # 构造 URL
               if [[ "$type" == "vless" ]]; then
                    flow=$(echo "$user" | jq -r '.flow // empty')
                    new_url="$type://$new_uuid@$SINGBOXDOMAIN:$port$url&flow=$flow#$tag"
                else 
                   new_url="$type://$new_uuid@$SINGBOXDOMAIN:$port$url#$tag"
                fi
                echo "$new_url" >> "$SINGBOX_SUB_FILE"
                echoContent skyblue "\n生成 $type 订阅链接: $new_url"
                qrencode -t ANSIUTF8 "$new_url" 2>/dev/null
                #qrencode -o "${SUBSCRIBE_DIR}/${type}_${tag//[@\/]/_}.png" "$url" 2>/dev/null 
               
                ((user_index++))
            done
        fi

        # 处理 trojan、shadowsocks、shadowtls 和 hysteria2 的 password 替换
        if [[ "$type" == "trojan" || "$type" == "shadowsocks" || "$type" == "shadowtls" || "$type" == "hysteria2" || "$type" == "naive" ]]; then
            echoContent green "\n处理 trojan、shadowsocks、shadowtls、naive 和 hysteria2 的 password 替换\n"
            user_index=0
            echo "$inbound" | jq -c '.users[]' | while IFS= read -r user; do
                old_password=$(echo "$user" | jq -r '.password')
                if [[ "$type" == "shadowsocks" || "$type" == "shadowtls" ]]; then
                    new_password=$(openssl rand -base64 16)
                else
                    new_password=$(sing-box generate uuid)
                fi
                echoContent yellow "\nReplacing password for user $user_index in $tag: $old_password -> $new_password"

                # 更新 password
                jq --arg tag "$tag" --arg old_password "$old_password" --arg new_password "$new_password" \
                   '(.inbounds[] | select(.tag == $tag) | .users[] | select(.password == $old_password)).password = $new_password' \
                   "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                    echoContent red "Error: Failed to update password."
                    exit 1
                }

                
            # 更新 shadowsocks 或 shadowtls 的顶层 password
                if [[ "$type" == "shadowsocks" || "$type" == "shadowtls" ]]; then
                    echoContent green "\n shadowsocks 或 shadowtls，更新顶层的 password 字段"
                    top_password=$(echo "$inbound" | jq -r '.password // empty')
                    if [[ -n "$top_password" ]]; then
                        new_top_password=$(openssl rand -base64 16)
                        echoContent yellow "Replacing top-level password in $tag: $top_password -> $new_top_password"
                        jq --arg tag "$tag" --arg new_password "$new_top_password" \
                        '(.inbounds[] | select(.tag == $tag)).password = $new_password' \
                        "$TEMP_FILE" > "${TEMP_FILE}.tmp" && mv "${TEMP_FILE}.tmp" "$TEMP_FILE" || {
                            echoContent red "Error: Failed to update top-level password."
                            exit 1
                        }
                    fi
                fi

                if  [[ "$type" == "shadowsocks" ]]; then
                    new_url="ss://2022-blake3-aes-128-gcm:$new_top_password:$new_password@$SINGBOXDOMAIN:$port$url#$tag"
                elif  [[ "$type" == "shadowtls" ]]; then
                    new_url="ss://2022-blake3-aes-256-gcm:$new_password@$SINGBOXDOMAIN:443?plugin=shadow-tls&host=$SINGBOXDOMAIN&port=$port&password=$new_password&version=3#$tag"
                elif  [[ "$type" == "naive" ]]; then
                   username=$(echo "$user" | jq -r '.username')
                   new_url="HTTP2+naive://$username:$new_password@$SINGBOXDOMAIN:$port$url#$tag"
                  # new_url="naive+quic://$username:$new_password@$SINGBOXDOMAIN:$port$url#$tag"
                else
                    new_url="$type://$new_password@$SINGBOXDOMAIN:$port$url#$tag"
                fi
                echo "$new_url" >> "$SINGBOX_SUB_FILE"
                 echoContent skyblue "\n生成 $type 订阅链接: $new_url"
                 qrencode -t ANSIUTF8 "$new_url" 2>/dev/null
                # qrencode -o "${SUBSCRIBE_DIR}/${type}_${tag//[@\/]/_}.png" "$url" 2>/dev/null
             
                ((user_index++))
            done

        fi

       
        
           
          
      \
    done

    # 替换原始文件
    mv "$TEMP_FILE" "$SINGBOX_CONF" || {
        echoContent red "Error: Failed to replace $SINGBOX_CONF"
        exit 1
    }
    echoContent skyblue "Updated $SINGBOX_CONF with new UUIDs, passwords, reality settings, and domain as $SINGBOXDOMAIN."

    # 验证 JSON 文件是否有效
    if jq empty "$SINGBOX_CONF" &> /dev/null; then
        echoContent skyblue "JSON file is valid. Restarting sing-box service..."
        systemctl restart sing-box || {
            echoContent red "Error: Failed to restart sing-box service."
            exit 1
        }
    else
        echoContent red "Error: Updated JSON file is invalid. Restoring backup."
        mv "${SINGBOX_CONF}.bak" "$SINGBOX_CONF"
        exit 1
    fi
}
configNginx() {
    echoContent green "nginx的TCP/IP layer4层stream模块分流:包括tls,reality,pre,sing等前缀域名进行sni分流 .\nnginx的layer 7层http模块可以用于path分流,在http模块 nginx还可以进行http_user_agent和ip block来过滤恶意攻击"
            read -r -p "请输入 nginx.conf 配置中替换tls.yourdomain的新域名 (后端xray tls解密,需要ssl证书): " TLS_YOURDOMAIN
            read -r -p "请输入 nginx.conf 配置中替换reality.yourdomain的新域名 (后端xray reality解密,该域名可以不用申请SSL证书，但是需要与IP绑定): " REALITY_YOURDOMAIN
            read -r -p "请输入 nginx.conf 配置中替换pre.yourdomain的新域名 (前端nginx解密，用nginx path分流，需要ssl证书): " PRE_YOURDOMAIN
            read -r -p "请输入 nginx.conf 配置中替换sing.yourdomain的新域名 (后端singbox解密，需要ssl证书): " SING_YOURDOMAIN
            read -r -p "请输入 nginx.conf 配置中替换www.yourdomain的新域名 (前端nginx正常网站，需要ssl证书): " WWW_YOURDOMAIN
            read -r -p "请输入 nginx.conf 配置中替换mid.yourdomain的新域名 (转发给xray 的VLESS-ENCRYPTION-REALITY-MIDSA65端口): " MID_YOURDOMAIN
            read -r -p "请输入 nginx.conf 的新 IP 地址 (例如: $LOCAL_IP): " NEW_IP
            if [[ -z "$NEW_IP" ]]; then
                NEW_IP="$LOCAL_IP"
            fi
            read -r -p "请输入 nginx.conf 的新端口 (默认 443): " NEW_PORT
            if [[ -z "$NEW_PORT" ]]; then
                NEW_PORT="443"
            fi
            sed -i "s/tls\.yourdomain/$TLS_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/reality\.yourdomain/$REALITY_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/pre\.yourdomain/$PRE_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/sing\.yourdomain/$SING_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/www\.yourdomain/$WWW_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/mid\.yourdomain/$MID_YOURDOMAIN/g" "$NGINX_CONF"
            sed -i "s/tls\.yourdomain/$TLS_YOURDOMAIN/g" "$XRAY_CONF" 
            sed -i "s/reality\.yourdomain/$REALITY_YOURDOMAIN/g" "$XRAY_CONF" 
            sed -i "s/pre\.yourdomain/$PRE_YOURDOMAIN/g" "$XRAY_CONF"
            sed -i "s/www\.yourdomain/$WWW_YOURDOMAIN/g" "$XRAY_CONF"
            sed -i "s/mid\.yourdomain/$MID_YOURDOMAIN/g" "$XRAY_CONF"
            sed -i "s/sing\.yourdomain/$SING_YOURDOMAIN/g" "$SINGBOX_CONF"           
            sed -i "s/yourIP/$NEW_IP/g" "$NGINX_CONF"
            sed -i "s/yourIP/$NEW_IP/g" "$XRAY_CONF"
            sed -i "s/listen 443/listen $NEW_PORT/g" "$NGINX_CONF"
            echoContent skyblue "nsx 配置文件$NGINX_CONF $XRAY_CONF $SINGBOX_CONF更新域名成功."        
}
# Manage configurations
manageConfigurations() {
    echoContent green "配置nsx服务 只适用本地安装\n如果通过docker ，可以用nano编辑\n usr/local/nsx/nginx.conf\nusr/local/nsx/xray/config.json\nusr/local/nsx/sing-box/config.json"
    echoContent skyblue "\n配置管理菜单"
    echoContent yellow "1. 复杂配置nsx服务（修改uuid，password和生成订阅等，适合本地）"
    echoContent yellow "2. 简单配置nsx服务(只修改域名，适合Docker)"
    echoContent yellow "3. 退出"
    read -r -p "请选择一个选项 [1-3]: " config_option

    case $config_option in
       1)   
            configNSX
            ;;
       2)
            configNginx
            # Reload Nginx if running
            if  pgrep nginx > /dev/null; then
                nginx -s reload
                echoContent green "Nginx 已重载以应用新配置."
            fi
            if systemctl is-active --quiet xray; then
                systemctl restart xray
                echoContent green "Xray 已重启以应用新配置."
            fi
            if systemctl is-active --quiet sing-box; then
                systemctl restart sing-box
                echoContent green "Sing-box 已重启以应用新配置."
           
            fi
            if docker ps | grep -q xray; then
                docker compose -f "$COMPOSE_FILE" restart
                echoContent green "Docker Compose 已重启以应用新配置."
            fi
            ;;
       
    
        3)
            return
            ;;
        *)
            echoContent red "无效选项."
            manageConfigurations
            ;;
    esac
}
generateSubscriptions() {
    echoContent skyblue "\n订阅生成..."

    # 检查变量
    if [[ -z "$XRAY_CONF" || -z "$SINGBOX_CONF"  || -z "$COMPOSE_FILE" ]]; then
        echoContent red "Error: XRAY_CONF, SINGBOX_CONF,  or COMPOSE_FILE is not set."
        return 1
    fi

    # 检查依赖
    if ! command -v jq &> /dev/null; then
        echoContent red "Error: jq is not installed."
        return 1
    fi
    if ! command -v qrencode &> /dev/null; then
        echoContent yellow "Warning: qrencode is not installed, skipping QR code generation."
        QRENCODE_AVAILABLE=false
    else
        QRENCODE_AVAILABLE=true
    fi
      # 检查并读取订阅文件
    if [[ -f "/etc/systemd/system/xray.service" ]]; then
        if [[ -f "${SUBSCRIBE_DIR}/xray_sub.txt" ]]; then
            echoContent skyblue "\n读取 Xray 订阅文件..."
            while IFS= read -r line; do
                if [[ -n "$line" ]]; then
                    echoContent green "\nXray 订阅链接: $line"
                    if [[ "$QRENCODE_AVAILABLE" == "true" ]]; then
                        qrencode -t ANSIUTF8 "$line" 2>/dev/null
                    fi
                fi
            done < "${SUBSCRIBE_DIR}/xray_sub.txt"
        fi

        if [[ -f "${SUBSCRIBE_DIR}/singbox_sub.txt" ]]; then
            echoContent skyblue "\n读取 Sing-box 订阅文件..."
            while IFS= read -r line; do
                if [[ -n "$line" ]]; then
                    echoContent green "\nSing-box 订阅链接: $line"
                    if [[ "$QRENCODE_AVAILABLE" == "true" ]]; then
                        qrencode -t ANSIUTF8 "$line" 2>/dev/null
                    fi
                fi
            done < "${SUBSCRIBE_DIR}/singbox_sub.txt"
        fi

    # 如果订阅文件存在，则不再执行后续生成逻辑
  
        if [[ -f "${SUBSCRIBE_DIR}/xray_sub.txt" && -f "${SUBSCRIBE_DIR}/singbox_sub.txt" ]]; then
            read -p "订阅文件已存在并处理完成，可通过 http://yourdomain/subscribe/ 访问.是否继续:" gen_sub
            if [[ -z $gen_sub ]]; then
            return 0
            fi
        fi
    fi
    # 获取用户输入的域名
    read -r -p "请输入订阅域名 (例如: sing.yourdomain): " SUB_DOMAIN
    if [[ -z "$SUB_DOMAIN" ]]; then
        SUB_DOMAIN=$(ip addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v "127.0.0.1" | head -n 1)
        
    fi

    # Generate Xray subscription
    if [ -f "$XRAY_CONF" ]; then
          if [ ! -d "$SUBSCRIBE_DIR" ]; then
                    mkdir -p "$SUBSCRIBE_DIR" || {
                        echoContent red "Error: Failed to create directory $SUBSCRIBE_DIR"
                        exit 1
                    }
                    chown nobody:nogroup "$SUBSCRIBE_DIR"
                    chmod 755 "$SUBSCRIBE_DIR"
            fi

                # 生成订阅文件
                echoContent green "创建 xray 订阅文件于${SUBSCRIBE_DIR}..."
                XRAY_SUB_FILE="${SUBSCRIBE_DIR}/xray_sub.txt"
                > "$XRAY_SUB_FILE"
        # 提取所有 inbounds
        jq -c '.inbounds[] | select(.settings.clients)' "$XRAY_CONF" | while IFS= read -r inbound; do
            tag=$(echo "$inbound" | jq -r '.tag')
            protocol=$(echo "$inbound" | jq -r '.protocol')
            port=$(echo "$inbound" | jq -r '.port // "443"')
            encryption=$(echo "$inbound" | jq -r '.settings.decryption // "none"')
            network=$(echo "$inbound" | jq -r '.streamSettings.network // "tcp"')
            security=$(echo "$inbound" | jq -r '.streamSettings.security // "none"')

            # 构造传输参数
            params="type=$network"
            case "$network" in
                "grpc")
                    serviceName=$(echo "$inbound" | jq -r '.streamSettings.grpcSettings.serviceName // empty')
                    if [[ -n "$serviceName" ]]; then
                        serviceName=$(url_encode "$serviceName")
                        params="$params&serviceName=$serviceName"
                    fi
                    ;;
                "ws")
                    path=$(echo "$inbound" | jq -r '.streamSettings.wsSettings.path // empty')
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                "xhttp")
                    xhttpSettings=$(echo "$inbound" | jq -r '.streamSettings.xhttpSettings')
                    host=$(echo "$xhttpSettings" | jq -r '.host // empty')
                    path=$(echo "$xhttpSettings" | jq -r '.path // empty')
                    if [[ -n "$host" ]]; then
                        host=$(url_encode "$host")
                        params="$params&host=$host"
                    fi
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                "splithttp")
                    path=$(echo "$inbound" | jq -r '.streamSettings.splithttpSettings.path // empty')
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                "httpupgrade")
                    path=$(echo "$inbound" | jq -r '.streamSettings.httpupgradeSettings.path // empty')
                    if [[ -n "$path" ]]; then
                       # path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                "kcp")
                    seed=$(echo "$inbound" | jq -r '.streamSettings.kcpSettings.seed // empty')
                    if [[ -n "$seed" ]]; then
                       # seed=$(url_encode "$seed")
                        params="$params&seed=$seed"
                    fi
                    ;;
                *)
                    ;;
            esac

            # 处理安全设置
            if [[ "$security" == "reality" ]]; then
                realitySettings=$(echo "$inbound" | jq -r '.streamSettings.realitySettings')
                private_key=$(echo "$realitySettings" | jq -r '.privateKey // empty')
                pbk=$(echo "$realitySettings" | jq -r '.password // empty')
                sid=$(echo "$realitySettings" | jq -r '.shortIds[0] // empty')
                pqv=$(echo "$realitySettings" | jq -r '.mldsa65Verify // empty')
                params="$params&security=reality&pbk=$pbk&sid=$sid&pqv=$pqv&fp=chrome&sni=$SUB_DOMAIN"
            elif [[ "$security" == "tls" ]]; then
                tlsSettings=$(echo "$inbound" | jq -r '.streamSettings.tlsSettings')
                fp=$(echo "$tlsSettings" | jq -r '.fingerprint // "chrome"')
                sni=$(echo "$tlsSettings" | jq -r '.serverName // "'"$SUB_DOMAIN"'"')
                ech=$(echo "$tlsSettings" | jq -r '.echConfigList // empty')
                alpn=$(echo "$inbound" | jq -r '.tls.alpn // "http/1.1"')
                # 如果 alpn 是数组，则将其转换为逗号分隔的字符串
                if [[ "$alpn" == \[*\] ]]; then
                    alpn=$(echo "$alpn" | jq -r 'join(",")')
                fi
                params="$params&security=tls&fp=$fp&sni=$sni&alpn=$alpn&ech=$ech"
            else
                params="$params&security=tls&fp=chrome&sni=$SUB_DOMAIN"
            fi

            # 处理 clients
            clients=$(echo "$inbound" | jq -c '.settings.clients[]')
            echo "$clients" | while IFS= read -r client; do
                email=$(echo "$client" | jq -r '.email // "unknown"')
                SUB_LINK=""
                case "$protocol" in
                    "vmess")
                        id=$(echo "$client" | jq -r '.id')
                        vmess_json=$(jq -n --arg id "$id" --arg add "$SUB_DOMAIN" --arg port "$port" --arg ps "$email" --arg enc "$encryption" \
                            '{v:"2",ps:$ps,add:$add,port:$port,id:$id,aid:0,net:(.network // "tcp"),type:"none",tls:(.security // "none"),enc:$enc}')
                        SUB_LINK="vmess://$(echo -n "$vmess_json" | base64 -w 0)?$params#$tag"
                        ;;
                    "vless")
                        id=$(echo "$client" | jq -r '.id')
                        flow=$(echo "$client" | jq -r '.flow // empty')
                       
                        SUB_LINK="vless://$id@$SUB_DOMAIN:$port?$params&flow=$flow#$tag"
                        ;;
                    "trojan")
                        password=$(echo "$client" | jq -r '.password')
                        SUB_LINK="trojan://$password@$SUB_DOMAIN:$port?$params#$tag"
                        ;;
                    "shadowsocks")
                        password=$(echo "$client" | jq -r '.password')
                        method=$(echo "$inbound" | jq -r '.settings.method // "aes-256-gcm"')
                        SUB_LINK="ss://$(echo -n "$method:$password" | base64 -w 0)@$SUB_DOMAIN:$port#$tag"
                        ;;
                    *)
                        echoContent yellow "Unsupported protocol: $protocol for tag: $tag, skipping."
                        continue
                        ;;
                esac

                if [[ -n "$SUB_LINK" ]]; then           
                    echoContent green "\n生成 Xray $protocol 订阅链接:\n $SUB_LINK"
                    echoContent green "\n写入$XRAY_SUB_FILE"
                    echo "$SUB_LINK" >> "$XRAY_SUB_FILE"
                    if [[ "$QRENCODE_AVAILABLE" == "true" ]]; then
                        qrencode -t ANSIUTF8 "$SUB_LINK" 2>/dev/null       
                    fi
                fi
            done
        done    
    else
        echoContent red "Xray 配置文件 ${XRAY_CONF} 不存在."
    fi

    # Generate Sing-box subscription
    if [ -f "$SINGBOX_CONF" ]; then
         

            if [ ! -d "$SUBSCRIBE_DIR" ]; then
                    mkdir -p "$SUBSCRIBE_DIR" || {
                        echoContent red "Error: Failed to create directory $SUBSCRIBE_DIR"
                        exit 1
                    }
                    chown nobody:nogroup "$SUBSCRIBE_DIR"
                    chmod 755 "$SUBSCRIBE_DIR"
            fi

                # 生成订阅文件
                echoContent green "创建 Sing-box 订阅文件于${SUBSCRIBE_DIR}..."
                SINGBOX_SUB_FILE="${SUBSCRIBE_DIR}/singbox_sub.txt"
                > "$SINGBOX_SUB_FILE"
        # 提取所有 inbounds
        jq -c '.inbounds[] | select(.users)' "$SINGBOX_CONF" | while IFS= read -r inbound; do
            tag=$(echo "$inbound" | jq -r '.tag')
            type=$(echo "$inbound" | jq -r '.type')
            port=$(echo "$inbound" | jq -r '.listen_port // "443"')
            transport=$(echo "$inbound" | jq -r '.transport.type // "tcp"')
            tls_enabled=$(echo "$inbound" | jq -r '.tls.enabled // false')
            # 构造传输参数
            params="type=$transport"
            case "$transport" in
                "grpc")
                    serviceName=$(echo "$inbound" | jq -r '.transport.service_name // empty')
                    if [[ -n "$serviceName" ]]; then
                        serviceName=$(url_encode "$serviceName")
                        params="$params&serviceName=$serviceName"
                    fi
                    ;;
                "ws")
                    path=$(echo "$inbound" | jq -r '.transport.path // empty')
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                "http")
                    path=$(echo "$inbound" | jq -r '.transport.path // empty')
                    host=$(echo "$inbound" | jq -r '.transport.header.host // empty')
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    if [[ -n "$host" ]]; then
                        host=$(url_encode "$host")
                        params="$params&host=$host"
                    fi
                    ;;
                "httpupgrade")
                    path=$(echo "$inbound" | jq -r '.transport.path // empty')
                    if [[ -n "$path" ]]; then
                        path=$(url_encode "$path")
                        params="$params&path=$path"
                    fi
                    ;;
                *)
                    ;;
            esac

            # 处理 TLS 设置
            if [[ "$tls_enabled" == "true" ]]; then
                reality_enabled=$(echo "$inbound" | jq -r '.tls.reality.enabled // false')
                if [[ "$reality_enabled" == "true" ]]; then
                    short_id=$(echo "$inbound" | jq -r '.tls.reality.short_id[0] // empty')    
                    public_key=$(echo "$inbound" | jq -r '.tls.reality.password // empty')
                    params="$params&security=reality&pbk=$public_key&sid=$short_id&fp=chrome&sni=$SUB_DOMAIN"
                else
                    fp=$(echo "$inbound" | jq -r '.tls.fingerprint // "chrome"')
                    sni=$(echo "$inbound" | jq -r '.tls.server_name // "'"$SUB_DOMAIN"'"')
                    alpn=$(echo "$inbound" | jq -r '.tls.alpn // "http/1.1"')

                    # 如果 alpn 是数组，则将其转换为逗号分隔的字符串
                    if [[ "$alpn" == \[*\] ]]; then
                        alpn=$(echo "$alpn" | jq -r 'join(",")')
                    fi
                    params="$params&security=tls&fp=$fp&sni=$sni&alpn=$alpn"
                fi
            fi

            # 处理 users
            users=$(echo "$inbound" | jq -c '.users[]')
            echo "$users" | while IFS= read -r user; do
                name=$(echo "$user" | jq -r '.name // "unknown"')
                SUB_LINK=""
                case "$type" in
                    "vless")
                        uuid=$(echo "$user" | jq -r '.uuid')
                        flow=$(echo "$user" | jq -r '.flow')
                        if [[ -z "$uuid" || -z "$name" ]]; then
                            echoContent red "跳过无效 VLESS 配置: UUID 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        reverse_tag=$(echo "$user" | jq -r '.reverse.tag // empty')
                        if [[ -n "$reverse_tag" ]]; then
                        name=$reverse_tag
                        fi
                        SUB_LINK="vless://$uuid@$SUB_DOMAIN:$port?$params&flow=$flow#$name"
                        ;;
                    "vmess")
                        uuid=$(echo "$user" | jq -r '.uuid')
                        if [[ -z "$uuid" || -z "$name" ]]; then
                            echoContent red "跳过无效 VMess 配置: UUID 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        vmess_json=$(jq -n --arg id "$uuid" --arg add "$SUB_DOMAIN" --arg port "$port" --arg ps "$name" \
                            '{v:"2",ps:$ps,add:$add,port:$port,id:$id,aid:0,net:"'$transport'",type:"none",tls:(.security // "none")}')
                        SUB_LINK="vmess://$(echo -n "$vmess_json" | base64 -w 0)?$params#$name"
                        ;;
                    "trojan")
                        password=$(echo "$user" | jq -r '.password')
                        if [[ -z "$password" || -z "$name" ]]; then
                            echoContent red "跳过无效 Trojan 配置: password 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        SUB_LINK="trojan://$password@$SUB_DOMAIN:$port?$params#$name"
                        ;;
                    "shadowsocks")
                        password=$(echo "$user" | jq -r '.password')
                        method=$(echo "$user" | jq -r '.method // "aes-256-gcm"')
                        if [[ -z "$password" || -z "$name" ]]; then
                            echoContent red "跳过无效 Shadowsocks 配置: password 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        SUB_LINK="ss://$(echo -n "$method:$password" | base64 -w 0)@$SUB_DOMAIN:$port#$name"
                        ;;
                    "hysteria2")
                        password=$(echo "$user" | jq -r '.password')
                        if [[ -z "$password" || -z "$name" ]]; then
                            echoContent red "跳过无效 Hysteria2 配置: password 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        SUB_LINK="hysteria2://$password@$SUB_DOMAIN:$port?insecure=0&$params#$name"
                        ;;
                    "tuic")
                        uuid=$(echo "$user" | jq -r '.uuid')
                        password=$(echo "$user" | jq -r '.password')
                        if [[ -z "$uuid" || -z "$password" || -z "$name" ]]; then
                            echoContent red "跳过无效 TUIC 配置: UUID, password 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        SUB_LINK="tuic://$uuid:$password@$SUB_DOMAIN:$port?alpn=h3&congestion_control=bbr&udp_relay_mode=native$params#$name"
                        ;;
                    "naive")
                        username=$(echo "$user" | jq -r '.username')
                        password=$(echo "$user" | jq -r '.password')
                        if [[ -z "$username" || -z "$password" || -z "$name" ]]; then
                            echoContent red "跳过无效 Naive 配置: username, password 或 name 为空 (tag: $tag)"
                            continue
                        fi
                        SUB_LINK="naive+https://$username:$password@$SUB_DOMAIN:$port?insecure=0&$params#$name"
                        ;;
                    *)
                        echoContent yellow "Unsupported protocol: $type for tag: $tag, skipping."
                        continue
                        ;;
                esac

                if [[ -n "$SUB_LINK" ]]; then
                    echoContent green "\n生成 Sing-box $type 订阅链接: $SUB_LINK"
                    echoContent green "\n写入$SINGBOX_SUB_FILE"
                    echo "$SUB_LINK" >> "$SINGBOX_SUB_FILE"
                    if [[ "$QRENCODE_AVAILABLE" == "true" ]]; then
                        qrencode -t ANSIUTF8 "$SUB_LINK" 2>/dev/null
                    fi

                     # 创建订阅目录
   

                fi
            done
        done

      
    else
        echoContent red "Sing-box 配置文件 ${SINGBOX_CONF} 不存在."
    fi

    echoContent green "订阅生成完成，可通过 http://${SUB_DOMAIN}/subscribe/ 访问."
}
# Manage logs
manageLogs() {
    echoContent skyblue "\n日志管理菜单"
    echoContent yellow "1. 查看 Nginx 访问日志"
    echoContent yellow "2. 查看 Nginx 错误日志"
    echoContent yellow "3. 查看 Nginx stream访问日志"
    echoContent yellow "4. 查看 Nginx stream错误日志"
    echoContent yellow "5. 查看 Nginx webpage访问日志"
    echoContent yellow "6. 查看 Nginx webpage错误日志"
    echoContent yellow "7. 查看 Xray 访问日志"
    echoContent yellow "8. 查看 Xray 错误日志"
    echoContent yellow "9. 查看 Sing-box 日志"
    echoContent yellow "10. 查看证书日志"
    echoContent yellow "11. 清除所有日志"
    echoContent yellow "12. 查看nginx服务记录(journalctl -u nginx.service)"
    echoContent yellow "13. 查看xray服务记录(journalctl -u xray.service)"
    echoContent yellow "14. 查看sing-box服务记录(journalctl -u sing-box.service)"
    echoContent yellow "15. 清除nsx服务记录 (journalctl --vacuum-time=1m -u xray.service)"
    echoContent yellow "16. 退出"
    read -r -p "请选择一个选项 [1-7]: " log_option

    case $log_option in
        1) tail -f "${LOG_DIR}/nginx_access.log" ;;
        2) tail -f "${LOG_DIR}/nginx_error.log" ;;
        3) tail -f "${LOG_DIR}/nginx_stream_access.log" ;;
        4) tail -f "${LOG_DIR}/nginx_stream_error.log" ;;
        5) tail -f "${LOG_DIR}/nginx_webpage_access.log" ;;
        6) tail -f "${LOG_DIR}/nginx_webpage_error.log" ;;
        7) tail -f "${LOG_DIR}/xray_access.log" ;;
        8) tail -f "${LOG_DIR}/xray_error.log" ;;
        9) tail -f "${LOG_DIR}/singbox.log" ;;
        10) tail -n 100 "${ACME_LOG}" ;;
        11)
            echo > "${LOG_DIR}/nginx_access.log"
            echo > "${LOG_DIR}/nginx_error.log"
            echo > "${LOG_DIR}/nginx_stream_access.log"
            echo > "${LOG_DIR}/nginx_stream_error.log"
            echo > "${LOG_DIR}/nginx_prextls_access.log"
            echo > "${LOG_DIR}/nginx_proxy_access.log"
            echo > "${LOG_DIR}/nginx_xhttpproxy_access.log"
            echo > "${LOG_DIR}/nginx_webpage_access.log"
            echo > "${LOG_DIR}/nginx_webpage_error.log"
            echo > "${LOG_DIR}/xray_access.log"
            echo > "${LOG_DIR}/xray_error.log"
            echo > "${LOG_DIR}/singbox.log"
            echoContent green "所有日志已清除."
            ;;
        12)
          sudo journalctl -u nginx.service
           ;;
        13)
          sudo journalctl -u xray.service
           ;;
        14)
          sudo journalctl -u sing-box.service
           ;;
        15)
        sudo journalctl --vacuum-time=1m -u xray.service
        sudo journalctl --vacuum-time=1m -u sing-box.service
        sudo journalctl --vacuum-time=1m -u nginx.service
           ;;
        16) return ;;
        *) echoContent red "无效选项." ; manageLogs ;;
    esac
}

# Install alias
aliasInstall() {
    if [[ -f "$BASE_DIR/install.sh" ]] && [[ -d "$BASE_DIR" ]]; then
        ln -sf "$BASE_DIR/install.sh" /usr/bin/nsx  
        chmod 700 "$BASE_DIR/install.sh"
        echoContent green "已创建别名 'nsx'，运行 'nsx' 以执行脚本."
    fi
      
}


# Update script
updateConfig() {
   echoContent yellow "更新配置文件..."
        # Backup existing configuration files if they exist
        for file in "$COMPOSE_FILE" "$NGINX_CONF" "$XRAY_CONF" "$SINGBOX_CONF"; do
            if [[ -f "$file" ]]; then
                mv "$file" "$file.bak" || {
                    echoContent red "无法备份 $file."
                    exit 1
                }
            fi
        done

        # Ensure source configuration files exist in the repository
        for src in "$TEMP_DIR/docker/docker-compose.yml" "$TEMP_DIR/nginx/nginx.conf" \
                   "$TEMP_DIR/xray/config.json" "$TEMP_DIR/sing-box/config.json"; do
            if [[ ! -f "$src" ]]; then
                echoContent red "仓库中缺少配置文件: $src."
                exit 1
            fi
        done

        # Ensure destination directories exist
        for dest in "$COMPOSE_FILE" "$NGINX_CONF" "$XRAY_CONF" "$SINGBOX_CONF"; do
            mkdir -p "$(dirname "$dest")" || {
                echoContent red "无法创建目录 $(dirname "$dest")."
                exit 1
            }
        done


   # Copy configuration files
        if ! cp "$TEMP_DIR/docker/docker-compose.yml" "$COMPOSE_FILE"; then
            echoContent red "无法复制 docker-compose.yml 到 $COMPOSE_FILE."
            exit 1
        fi
        if ! cp "$TEMP_DIR/nginx/nginx.conf" "$NGINX_CONF"; then
            echoContent red "无法复制 nginx.conf 到 $NGINX_CONF."
            exit 1
        fi
        if ! cp "$TEMP_DIR/xray/config.json" "$XRAY_CONF"; then
            echoContent red "无法复制 xray/config.json 到 $XRAY_CONF."
            exit 1
        fi
        if ! cp "$TEMP_DIR/sing-box/config.json" "$SINGBOX_CONF"; then
            echoContent red "无法复制 sing-box/config.json 到 $SINGBOX_CONF."
            exit 1
        fi

        

        # Set permissions
        chmod 644 "$COMPOSE_FILE" || {
            echoContent red "无法设置 $COMPOSE_FILE 权限."
            exit 1
        }
        chmod 644 "$NGINX_CONF" || {
            echoContent red "无法设置 $NGINX_CONF 权限."
            exit 1
        }
        chmod 644 "$XRAY_CONF" || {
            echoContent red "无法设置 $XRAY_CONF 权限."
            exit 1
        }
        chmod 644 "$SINGBOX_CONF" || {
            echoContent red "无法设置 $SINGBOX_CONF 权限."
            exit 1
        }

        echoContent yellow "配置文件更新成功."

}

updateNSX() {
    echoContent skyblue "\n更新 NSX 脚本..."
    # Check if git is installed
    if ! command -v git &> /dev/null; then
        echoContent yellow "安装 git..."
        ${installCmd} git
        if [ $? -ne 0 ]; then
            echoContent red "安装 git 失败，请手动安装."
            exit 1
        fi
    fi

    # Create a temporary directory for cloning
    TEMP_DIR=$(mktemp -d)
    if [ $? -ne 0 ]; then
        echoContent red "创建临时目录失败."
        exit 1
    fi
    # Ensure temporary directory is cleaned up on exit
    trap 'rm -rf "$TEMP_DIR"' EXIT
    # Clone the repository
    # Clone the repository
    if ! git clone https://github.com/judawu/nsx.git "$TEMP_DIR"; then
        echoContent red "克隆 Git 仓库失败，请检查网络或仓库地址 https://github.com/judawu/nsx."
        exit 1
    fi

    # Remove old install.sh
    rm -f "$BASE_DIR/install.sh"

    # Copy install.sh from cloned repository
    if [ -f "$TEMP_DIR/install.sh" ]; then
        cp "$TEMP_DIR/install.sh" "$BASE_DIR/install.sh"
        chmod 700 "$BASE_DIR/install.sh"
        echoContent green "脚本更新成功."
    else
        echoContent red "克隆的仓库中未找到 install.sh 文件."
        rm -rf "$TEMP_DIR"
        exit 1
    fi

    read -r -p "是否用 GitHub 仓库替换当前配置文件？(y/n): " keep_config
    if [[ "$keep_config" == "n" ]]; then
        echoContent green "保留现有配置文件，不进行更新."
    elif [[ "$keep_config" == "y" ]]; then
       updateConfig
    else
        echoContent green "保留现有配置文件，不进行更新."
    fi
    
    read -r -p "是否用 GitHub 仓库网站替换当前网站？(y/n): " keep_webpage
    if [[ "$keep_webpage" == "n" ]]; then
        echoContent green "保留现有网站，不进行更新."
    elif [[ "$keep_webpage" == "y" ]]; then
        if ! cp -r "$TEMP_DIR/www/" "$BASE_DIR"; then
            echoContent red "无法复制仓库网站 到 $WWW_DIR."
            exit 1
        fi
    else
        echoContent green "保留现有配置文件，不进行更新."
    fi
    



    # Call aliasInstall (assuming it's defined elsewhere)
    if type aliasInstall >/dev/null 2>&1; then
        aliasInstall || {
            echoContent red "执行 aliasInstall 失败."
            exit 1
        }
    else
        echoContent yellow "警告: aliasInstall 函数未定义，跳过."
    fi


}

# Docker installation
dockerInstall() {

    installTools
    echoContent skyblue "\nDocker 安装..."
    installDocker
    createDirectories
    installAcme
    updateNSX
    # Check certificates
    if [ ! -d "${CERT_DIR}" ] || [ -z "$(ls -A "${CERT_DIR}"/*.pem 2>/dev/null)" ]; then
        echoContent yellow "未找到证书，运行证书管理..."
        manageCertificates
    fi
    configNginx
    
    # Check Nginx configuration
    echoContent yellow "检查 Nginx 配置语法..."
    docker run --rm -v "${NGINX_CONF}:/etc/nginx/nginx.conf:ro" -v "${CERT_DIR}:/usr/local/nsx/certs/:ro" -v "${LOG_DIR}:/usr/local/nsx/log/:rw" -v "${SHM_DIR}:/dev/shm/nsx" nginx:alpine nginx -t
    if [ $? -ne 0 ]; then
        echoContent red "错误：Nginx 配置语法检查失败！"
        exit 1
    fi
    
    # Start Docker Compose
    echoContent yellow "启动 Docker 容器..."
    docker compose -f "$COMPOSE_FILE" up -d
    if [ $? -ne 0 ]; then
        echoContent red "启动 Docker Compose 失败，请检查配置或日志."
        exit 1
    fi

    # Set permissions for log files
    find "$SHM_DIR"  -name "*.sock" -exec chown nobody:nogroup {} \; -exec chmod 666 {} \;
    if [ $? -eq 0 ]; then
        echoContent yellow "Successfully changed permissions to 666 for all socket files in $SHM_DIR"
    else
        echoContent red "Error: Failed to change permissions for some or all socket files."
        exit 1
    fi
    find "$LOG_DIR"  -type f -name "*.log" -exec chown nobody:nogroup {} \; -exec chmod 644 {} \;

    echoContent green "Docker 容器启动成功."

    # Check container status
    echoContent yellow "检查容器状态..."
    docker ps -f name=nginx-stream -f name=xray -f name=sing-box

    echoContent green "请使用systemctl enable ufw 和systemctl start ufw开启防火墙，用ufw allow port 开启端口访问..."
    aliasInstall
}
createSystemdServices() {
    echoContent skyblue "\n配置 systemd 服务..."

    # Nginx 服务文件
    if [[ "${release}" == "debian" || "${release}" == "ubuntu" ]]; then
        echoContent green "创建 Nginx systemd 服务..."
        cat <<EOF >/etc/systemd/system/nginx.service
[Unit]
Description=The NGINX HTTP and reverse proxy server
After=network.target remote-fs.target nss-lookup.target

[Service]
Type=forking
ExecStart=/usr/sbin/nginx -c /usr/local/nsx/nginx/nginx.conf -g "daemon on; master_process on;"
ExecReload=/usr/sbin/nginx -s reload
ExecStop=/usr/sbin/nginx -s quit
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF
    elif [[ "${release}" == "centos" ]]; then
        echoContent green "创建 Nginx systemd 服务..."
        cat <<EOF >/etc/systemd/system/nginx.service
[Unit]
Description=The NGINX HTTP and reverse proxy server
After=network.target remote-fs.target nss-lookup.target

[Service]
Type=forking
ExecStart=/usr/sbin/nginx -c /usr/local/nsx/nginx/nginx.conf -g "daemon on; master_process on;"
ExecReload=/usr/sbin/nginx -s reload
ExecStop=/usr/sbin/nginx -s quit
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF
    fi

    # Xray 服务文件
    echoContent green "创建 Xray systemd 服务..."
    cat <<EOF >/etc/systemd/system/xray.service
[Unit]
Description=Xray Service
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/nsx/xray/xray -c /usr/local/nsx/xray/config.json
ExecStop=/bin/kill -s QUIT \$MAINPID
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    # Sing-box 服务文件
    echoContent green "创建 Sing-box systemd 服务..."
    cat <<EOF >/etc/systemd/system/sing-box.service
[Unit]
Description=Sing-box Service
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/nsx/sing-box/sing-box run -c /usr/local/nsx/sing-box/config.json
ExecStop=/bin/kill -s QUIT \$MAINPID
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    # 重新加载 systemd 配置
    echoContent green "重新加载 systemd 配置..."
    systemctl daemon-reload

    # 设置文件权限
    chmod 644 /etc/systemd/system/nginx.service
    chmod 644 /etc/systemd/system/xray.service
    chmod 644 /etc/systemd/system/sing-box.service
}

# 修改后的服务启动部分
startServices() {

    echoContent skyblue "\n启动服务..."

    # 启用并启动服务
    sudo systemctl enable nginx
    sudo systemctl start nginx
     # 检查服务状态
    if sudo systemctl is-active --quiet nginx; then
        echoContent green "Nginx启动成功！"
    else
        echoContent red "Nginx服务启动失败，请检查日志："
        echoContent red "Nginx: journalctl -u nginx.service"
   
    fi
    sudo systemctl enable xray 
    sudo systemctl start  xray 
    if sudo systemctl is-active --quiet xray; then
        echoContent green "xray启动成功！"
    else
        echoContent red "xray服务启动失败，请检查日志："
        echoContent red "Xray: journalctl -u xray.service"
    fi

    sudo systemctl enable sing-box
    sudo systemctl start sing-box

    # 检查服务状态
    if sudo systemctl is-active --quiet sing-box; then
        echoContent green  "Sing-box启动成功！"
    else
        echoContent red "Sing-box服务启动失败，请检查日志："     
        echoContent red "Sing-box: journalctl -u sing-box.service"

    fi
    echoContent green  "设置$SHM_DIR 下的socks文件权限！"
    find "$SHM_DIR" -name "*.sock" -exec chown nobody:nogroup {} \; -exec chmod 666 {} \;
    find "$LOG_DIR"  -type f -name "*.log" -exec chown nobody:nogroup {} \; -exec chmod 644 {} \;
    # Check if the find command was successful
    if [ $? -eq 0 ]; then
        echoContent yellow "Successfully changed permissions to 666 for all socket files in $SHM_DIR"
    else
        echoContent red "Error: Failed to change permissions for some or all socket files."
        exit 1
    fi
}

restartServices() {
    echoContent skyblue "\重启服务..."

    # 启用并启动服务
    echoContent yellow "停止服务."
    sudo systemctl stop nginx xray sing-box
    if [ ! -d "$SHM_DIR" ]; then
            echoContent yellow "创建目录 $SHM_DIR..."
            mkdir -p "$SHM_DIRR"
        fi
    echoContent yellow "清理$SHM_DIR/."
    sudo rm -rf "$SHM_DIR"/*
    echoContent yellow "启动服务."
    sudo systemctl start nginx xray sing-box

    echoContent green  "设置$SHM_DIR 下的socks文件权限！"
    find "$SHM_DIR"  -name "*.sock" -exec chown nobody:nogroup {} \; -exec chmod 666 {} \;
    find "$LOG_DIR"  -type f -name "*.log" -exec chown nobody:nogroup {} \; -exec chmod 644 {} \;
    # Check if the find command was successful
    if [ $? -eq 0 ]; then
        echoContent yellow "Successfully changed permissions to 666 for all socket files in $SHM_DIR"
    else
        echoContent red "Error: Failed to change permissions for some or all socket files."
        exit 1
    fi
    # 检查服务状态
    if sudo systemctl is-active --quiet nginx && sudo systemctl is-active --quiet xray && sudo systemctl is-active --quiet sing-box; then
        echoContent green "所有服务（Nginx, Xray, Sing-box）启动成功！"
    else
        echoContent red "部分或全部服务启动失败，请检查日志："
        echoContent red "Nginx: journalctl -u nginx.service"
        echoContent red "Xray: journalctl -u xray.service"
        echoContent red "Sing-box: journalctl -u sing-box.service"
        exit 1
    fi
}
# Local installation
localInstall() {
   
    echoContent skyblue "\n本地安装..."
    read -r -p "是否安装工具包？500M 内存VPS 建议手动安装(y/n): " if_tools
    if [[ "$if_tools"=="y" ]]; then
       installTools
    else
      echoContent yellow "工具包没有安装，请手动安装curl wget git sudo lsof unzip ufw socat jq iputils-ping dnsutils qrencode..."
    fi
    checkCentosSELinux
    createDirectories
    installAcme

    # Check certificates
    if [ ! -d "${CERT_DIR}" ] || [ -z "$(ls -A "${CERT_DIR}"/*.pem 2>/dev/null)" ]; then
        echoContent yellow "未找到证书，运行证书管理..."
        manageCertificates
    fi
   
    # Install Nginx

    echoContent skyblue "\n 安装nginx..."
    if [[ "${release}" == "debian" || "${release}" == "ubuntu" ]]; then
        echoContent green "\n 安装nginx依赖..."
        sudo apt update
        sudo apt install -y gnupg2 ca-certificates lsb-release
        echo "deb http://nginx.org/packages/mainline/${release} $(lsb_release -cs) nginx" | sudo tee /etc/apt/sources.list.d/nginx.list
        if ! curl -fsSL https://nginx.org/keys/nginx_signing.key | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/nginx_signing.gpg; then
            echoContent red "\n 错误: 无法下载Nginx签名密钥!"
            exit 1
        fi
        sudo apt update
        sudo apt install -y nginx
        if [ $? -eq 0 ]; then
            echoContent skyblue "\n nginx安装完成..."
         
        else
            echoContent red "\n nginx安装失败!"
            exit 1
        fi
    elif [[ "${release}" == "centos" ]]; then
        echoContent green "\n 安装nginx依赖..."
        sudo yum install -y yum-utils
        cat <<EOF | sudo tee /etc/yum.repos.d/nginx.repo
[nginx-mainline]
name=nginx mainline repo
baseurl=http://nginx.org/packages/mainline/centos/\$releasever/\$basearch/
gpgcheck=1
enabled=1
gpgkey=https://nginx.org/keys/nginx_signing.key
module_hotfixes=true
EOF
        sudo yum install -y nginx
        if [ $? -eq 0 ]; then
            echoContent skyblue "\n nginx安装完成..."
           
            
        else
            echoContent red "\n nginx安装失败!"
            exit 1
        fi
    else
        echoContent red "\n 错误: 不支持的操作系统: ${release}"
        exit 1
    fi
    
  

    # Install Xray and Sing-box
    echoContent skyblue "\n 安装xray..."
   
    version=$(curl -s "https://api.github.com/repos/XTLS/Xray-core/releases?per_page=5" | jq -r ".[]|select (.prerelease==false)|.tag_name" | head -1)
    echoContent green " Xray-core版本:${version}"
    echoContent green "\n 安装目录/usr/local/nsx/xray/，下载中..."
    if [[ "${release}" == "alpine" ]]; then
        wget -c -q -P /usr/local/nsx/xray/ "https://github.com/XTLS/Xray-core/releases/download/${version}/${xrayCoreCPUVendor}.zip"
    else
        wget -c -q  -P /usr/local/nsx/xray/ "https://github.com/XTLS/Xray-core/releases/download/${version}/${xrayCoreCPUVendor}.zip"
    fi

    if [[ ! -f "/usr/local/nsx/xray/${xrayCoreCPUVendor}.zip" ]]; then
        read -r -p "核心下载失败，请重新尝试安装" 
        exit 1
    else
        echoContent skyblue "开始安装xray..."
        unzip -o "/usr/local/nsx/xray/${xrayCoreCPUVendor}.zip" -d /usr/local/nsx/xray >/dev/null
        rm -rf "/usr/local/nsx/xray/${xrayCoreCPUVendor}.zip"
        chmod 655 /usr/local/nsx/xray/xray
        ln -sf /usr/local/nsx/xray/xray /usr/bin/xray
        echoContent skyblue "安装xray成功..."
    fi
   
    
    echoContent skyblue "安装singbox..."
    version=$(curl -s "https://api.github.com/repos/SagerNet/sing-box/releases?per_page=20" | jq -r ".[]|select (.prerelease==false)|.tag_name" | head -1)

    echoContent green " sing-box版本:${version}"
    echoContent green "\n 安装目录/usr/local/nsx/sing-box/，下载中..."
    if [[ "${release}" == "alpine" ]]; then
        wget -c -q -P /usr/local/nsx/sing-box/ "https://github.com/SagerNet/sing-box/releases/download/${version}/sing-box-${version/v/}${singBoxCoreCPUVendor}.tar.gz"
    else
        wget -c -q -P /usr/local/nsx/sing-box/ "https://github.com/SagerNet/sing-box/releases/download/${version}/sing-box-${version/v/}${singBoxCoreCPUVendor}.tar.gz"
    fi

    if [[ ! -f "/usr/local/nsx/sing-box/sing-box-${version/v/}${singBoxCoreCPUVendor}.tar.gz" ]]; then
        echoContent red "核心下载失败，请重新尝试安装" 
        exit 1
    else
        echoContent skyblue "开始安装singbox..."
        tar zxvf "/usr/local/nsx/sing-box/sing-box-${version/v/}${singBoxCoreCPUVendor}.tar.gz" -C "/usr/local/nsx/sing-box/" >/dev/null 2>&1
        mv "/usr/local/nsx/sing-box/sing-box-${version/v/}${singBoxCoreCPUVendor}/sing-box" /usr/local/nsx/sing-box
        rm -rf /usr/local/nsx/sing-box/sing-box-*
        chmod 655 /usr/local/nsx/sing-box/sing-box
        ln -sf /usr/local/nsx/sing-box/sing-box /usr/bin/sing-box
        echoContent green "singbox安装成功"
    fi
    read -r -p "本地安装已经完成，是否继续配置？500M VPS 建议手动配置 (y/n):"  nsx_config
    if [[ "$nsx_config" == "n" ]]; then
        echoContent green "选择手动配置，nginx，xray singbox安装完成"
    elif [[ "$nsx_config" == "y" ]]; then
        configNSX
    else 
        echoContent green "nginx，xray singbox安装完成，请手动配置"
    fi  
}
configNSX() {
    updateNSX
    echoContent skyblue "进行nginx的配置修改..."
    configNginx
    echoContent skyblue "\n 删除安装的nginx配置文件，拷贝/usr/local/nsx/nginx/nginx.conf配置文件到/etc/nginx..."
    sudo rm /etc/nginx/conf.d/default.conf
    sudo rm /etc/nginx/nginx.conf
    sudo cp /usr/local/nsx/nginx/nginx.conf /etc/nginx/nginx.conf
    sudo chmod 644 /etc/nginx/nginx.conf
    
    echoContent skyblue "开始创建服务..."
    # Start services
    createSystemdServices

    echoContent skyblue "清理log文件和遗留sock文件..."
    echoContent yellow "清理$SHM_DIR."
    sudo rm -rf "$SHM_DIR"/*
    echoContent yellow "清理${LOG_DIR}."
    sudo rm -rf "$LOG_DIR"/*
    echoContent skyblue "开始启动服务..."
   
    startServices
    echoContent skyblue "进行xray的配置修改..."
    xray_config

    echoContent skyblue "进行singbox的配置修改..."
    singbox_config

    restartServices
    read  -p "是否继续配置ufw？(y/n): " ufw_config
      if [[ $ufw_config == "y" ]]; then
        configufw
      else 
        echoContent yellpw "请使用systemctl enable ufw 和systemctl start ufw开启防火墙，用ufw allow port 开启端口访问..."
      fi
    aliasInstall


}
configufw(){
    sudo systemctl enable ufw
    sudo ufw allow 80
    sudo ufw allow 443
    sudo ufw allow 53
    sudo ufw allow 853
    sudo ufw allow 5353
    sudo ufw allow 6753
    sudo ufw allow 8071,8072,8073,8074,8075,8076,8077,8078,8079,8080,8081,8082,8083,8084,8085,8086,8087,8088,8089,8090/tcp
    sudo ufw allow 8071,8072,8073,8074,8075,8076,8077,8078,8079,8080,8081,8082,8083,8084,8085,8086,8087,8088,8089,8090/udp
    sudo ufw allow 10801,10802,10803,10804,10805,10806,10807,10808,10809,10810,10830,10831,10832,10833,10834,10835,10836,10837,10838,10839,10840/tcp
    sudo ufw allow 10801,10802,10803,10804,10805,10806,10807,10808,10809,10810,10830,10831,10832,10833,10834,10835,10836,10837,10838,10839,10840/udp
    sudo ufw allow 3344,3443,4443,4434,8443,4433/tcp
    sudo ufw allow 3344,3443,4443,4434,8443,4433/udp
    sudo systemctl start ufw
    sudo ufw enable
    sudo ufw status
    
}
restartNSXdocker() {
   stopNSXdocker
   echoContent yellow "启动 Docker 容器..."
    docker compose -f "$COMPOSE_FILE" up -d
    if [ $? -ne 0 ]; then
        echoContent red "启动 Docker Compose 失败，请检查配置或日志."
        exit 1
    fi

    # Set permissions for log files
    find "$SHM_DIR"  -name "*.sock" -exec chown nobody:nogroup {} \; -exec chmod 666 {} \;
    if [ $? -eq 0 ]; then
        echoContent yellow "Successfully changed permissions to 666 for all socket files in $SHM_DIR"
    else
        echoContent red "Error: Failed to change permissions for some or all socket files."
        exit 1
    fi
    find "$LOG_DIR"  -type f -name "*.log" -exec chown nobody:nogroup {} \; -exec chmod 644 {} \;

    echoContent green "Docker 容器启动成功."

    # Check container status
    echoContent yellow "检查容器状态..."
    docker ps -f name=nginx-stream -f name=xray -f name=sing-box
}

restartNSXlocal() {
    restartServices
    echoContent yellow "清理logs ..."
    echo > "${LOG_DIR}/nginx_access.log"
    echo > "${LOG_DIR}/nginx_error.log"
    echo > "${LOG_DIR}/nginx_stream_access.log"
    echo > "${LOG_DIR}/nginx_stream_error.log"
    echo > "${LOG_DIR}/xray_access.log"
    echo > "${LOG_DIR}/xray_error.log"
    echo > "${LOG_DIR}/singbox.log"
    echoContent yellow "清理journalctl ..."
    sudo journalctl --vacuum-time=1h -u xray.service
    sudo journalctl --vacuum-time=1h -u sing-box.service
    sudo journalctl --vacuum-time=1h -u nginx.service
    echoContent yellow "重启nsx本地服务，用journalctl -u xray.service查看xray日志..."
     
   
}
# Stop NSX

stopNSXdocker() {
    echoContent skyblue "停止 NSX 容器并清理..."
    # Check if Docker and docker-compose.yml exist
    if ! command -v docker &> /dev/null || [ ! -f "$COMPOSE_FILE" ]; then
        echoContent red "未找到 Docker 或 docker-compose.yml 文件，如果是本地安装，请手动停止服务."
        exit 1
    fi
    # Check if Docker service is running
    if ! systemctl is-active --quiet docker; then
        echoContent red "Docker 服务未运行"
        exit 1
    fi
    # Stop and remove containers
    echoContent yellow "运行 docker compose down..."
    docker compose -f "$COMPOSE_FILE" down
    if [ $? -ne 0 ]; then
        echoContent red "停止 Docker Compose 失败，请检查配置或日志."
        exit 1
    fi

    # Clean up /dev/shm/nsx if empty
    if [ -d "$SHM_DIR" ] && [ -z "$(ls -A "$SHM_DIR")" ]; then
        echoContent yellow "目录 $SHM_DIR 为空，删除..."
        if ! rm -rf "$SHM_DIR"; then
                echoContent red "无法删除 $SHM_DIR，请检查权限."
                exit 1
         fi
    elif [ -d "$SHM_DIR" ]; then
        echoContent yellow "清理 $SHM_DIR 中的文件..."
        if ! rm -rf "$SHM_DIR"/*; then
                echoContent red "无法清理 $SHM_DIR 中的文件，请检查权限."
                exit 1
        fi
    fi

    echoContent green "NSX 容器已停止并清理完成."
}

uninstallNSX() {
    # Define defaults
 

    echoContent skyblue "卸载 NSX 服务..."

    # Stop NSX containers
    if command -v docker &>/dev/null && [[ -f "$COMPOSE_FILE" ]]; then
       # Check if Docker service is running
        if sudo systemctl is-active --quiet docker; then
           stopNSXdocker
        else
            echoContent yellow "docker 没有运行..."
        fi
       
    fi

    # Uninstall Xray
   
        read -r -p "确认卸载 Xray？(y/n): " uninstallXray
        if [[ "$uninstallXray" == "y" ]]; then
            echoContent yellow "停止并卸载 Xray..."
            
            if [[ -f "/etc/systemd/system/xray.service" ]]; then
                sudo systemctl stop xray 2>/dev/null
                sudo systemctl disable xray 2>/dev/null
                sudo rm -f /etc/systemd/system/xray.service || {
                    echoContent red "无法删除 xray.service，请检查权限."
                    exit 1
                }
            fi
               if [[ -f "/usr/bin/xray" ]]; then
                sudo rm -rf /usr/bin/xray* || {
                    echoContent red "无法清理 /usr/bin/xray，请检查权限."
                    exit 1
                }
              
            fi
            if [[ -d "/usr/local/nsx/xray" ]]; then
                sudo rm -rf /usr/local/bin/xray/* || {
                    echoContent red "无法清理 /usr/local/bin/xray，请检查权限."
                    exit 1
                }
              
            fi
         
            
            if ! command -v xray &>/dev/null; then
                echoContent green "Xray 卸载完成."
            else
                echoContent red "Xray 卸载失败，xray 命令仍存在."
                exit 1
            fi
        fi
    

    # Uninstall Sing-box
  
        read -r -p "确认卸载 Sing-box？(y/n): " uninstallSingbox
        if [[ "$uninstallSingbox" == "y" ]]; then
            echoContent yellow "停止并卸载 Sing-box..."
          
            if [[ -f "/etc/systemd/system/sing-box.service" ]]; then
                sudo systemctl stop sing-box 2>/dev/null
                sudo systemctl disable sing-box 2>/dev/null
                sudo rm -f /etc/systemd/system/sing-box.service || {
                    echoContent red "无法删除 sing-box.service，请检查权限."
                    exit 1
                }
            fi
               if [[ -f "/usr/bin/sing-box" ]]; then
                sudo rm -rf /usr/bin/sing-box* || {
                    echoContent red "无法清理 /usr/bin/sing-box，请检查权限."
                    exit 1
                }
              
            fi
            if [[ -d "/usr/local/nsx/sing-box" ]]; then
                sudo rm -rf /usr/local/nsx/sing-box/* || {
                    echoContent red "无法清理 /usr/local/nsx/sing-box，请检查权限."
                    exit 1
                }
                sudo rmdir /usr/local/nsx/sing-box 2>/dev/null || true
            fi
        
            if ! command -v sing-box &>/dev/null; then
                echoContent green " Sing-box 卸载完成."
            else
                echoContent red "Sing-box 卸载失败，sing-box 命令仍存在."
                exit 1
            fi
        fi
   

    # Uninstall Nginx
   
        read -r -p "确认卸载 Nginx？(y/n): " uninstallNginx
        if [[ "$uninstallNginx" == "y" ]]; then
            echoContent yellow "停止并卸载 Nginx..."
          
            if [[ -f "/etc/systemd/system/nginx.service" ]]; then
                sudo systemctl stop nginx 2>/dev/null
                sudo systemctl disable nginx 2>/dev/null
                sudo rm -f /etc/systemd/system/nginx.service || {
                    echoContent red "无法删除 nginx.service，请检查权限."
                    exit 1
                }
            fi
            # 卸载 Nginx 软件包
             $uninstallCmd --purge nginx nginx-common nginx-full -y
    
                # 删除残留的配置文件和日志
            sudo rm -rf /etc/nginx /var/log/nginx /var/cache/nginx
            if ! command -v nginx &>/dev/null; then
                echoContent green " Nginx 卸载完成."
            else
                echoContent red "Nginx 卸载失败，nginx 命令仍存在."
                exit 1
            fi
        fi
    

    # Uninstall Docker
    if command -v docker &>/dev/null; then
        read -r -p "确认清理 Docker 容器？(y/n): " uninstallDocker
        if [[ "$uninstallDocker" == "y" ]]; then
            echoContent yellow "停止并卸载 Docker..."
           
            if [[ -f "/etc/systemd/system/docker.service" ]]; then
                sudo systemctl stop docker 2>/dev/null
                sudo systemctl disable docker 2>/dev/null
                sudo rm -f /etc/systemd/system/docker.service || {
                    echoContent red "无法删除 docker.service，请检查权限."
                    exit 1
                }
            fi
          
            # Clean up Docker data (images, containers, volumes)
            if sudo docker system prune -a -f --volumes; then
                echoContent green "Docker 数据清理完成."
            else
                echoContent yellow "警告: Docker 数据清理失败，部分数据可能仍存在."
            fi
            if ! command -v docker &>/dev/null; then
                echoContent green "  Docker 卸载完成."
            else
                echoContent yellow "Docker 数据清理完成，docker 命令仍存在系统中，如果需要卸载，请手动卸载."
                exit 1
            fi
        fi
    fi
    echoContent yellow "清理/dev/shm/nsx/."
    sudo rm -rf "$SHM_DIR"/*
    # Clean up NSX configuration and certificate files
    read -r -p "是否删除 NSX 配置文件和证书？(y/n): " removeConfigs
    if [[ "$removeConfigs" == "y" ]]; then
        echoContent yellow "清理 NSX 配置文件和证书..."
        for file in "$COMPOSE_FILE" "$NGINX_CONF" "$XRAY_CONF" "$SINGBOX_CONF" "$CERT_DIR"/* "$CREDENTIALS_FILE"; do
            if [[ -f "$file" || -d "$file" ]]; then
                sudo rm -rf "$file" || {
                    echoContent red "无法删除 $file，请检查权限."
                    exit 1
                }
            fi
        done
        if [[ -d "/usr/local/nsx" ]]; then
            sudo rmdir /usr/local/nsx 2>/dev/null || true
        fi
        echoContent green "NSX 配置文件和证书清理完成."
    else
        echoContent yellow "保留 NSX 配置文件和证书."
    fi

    # Reload systemd daemon
    if ! sudo systemctl daemon-reload; then
        echoContent red "无法重新加载 systemd 配置，请检查."
        exit 1
    fi

    echoContent green "NSX 卸载完成."
}
# Main menu
menu() {
    clear
    echoContent red "\n=============================================================="
    echoContent green "NSX 安装管理脚本"
    echoContent green "作者: JudaWu"
    echoContent green "版本: v0.0.5"
    echoContent green "Github: https://github.com/judawu/nsx"
    echoContent green "描述: 一个集成 Nginx、Sing-box 和 Xray 的代理环境"
    echoContent red "\n=============================================================="
   
    echoContent yellow "1. 阅读说明"   
    echoContent yellow "2. 使用 Docker 安装 NSX"
    echoContent yellow "3. 本地安装 NSX"
    echoContent yellow "4. 证书管理"
    echoContent yellow "5. 配置管理"
    echoContent yellow "6. 日志管理"
    echoContent yellow "7. 更新脚本"
    echoContent yellow "8. 停止 NSX Docker"
    echoContent yellow "9. 生成订阅"
    echoContent yellow "10. 卸载nsx"
    echoContent yellow "11. 重启 NSX Docker"
    echoContent yellow "12. 重启 NSX 本地"
    echoContent yellow "13. linux使用帮助"
    echoContent yellow "14. 退出"
    read -r -p "请选择一个选项 [1-9]: " option

    case $option in
        1)
        echoContent green "输入nsx启动脚本\n选择2 安装Docker版服务用docker启动\n选择3安装XRAY,SINGBOX,NINGX到本机\n选择4进行证书申请，包括3种证书申请方式\n选择5进行nsx配置文件修改，进入5之后选择1进行本机的所有配置的重新设置\n选择6进行日志管理，可以查看和清理日志\n选择7进行脚本更新...\n这是一个复合脚本，设计了多种功能，说明参考https://github.com/judawu/nsx"
        exit 1;;
        2)dockerInstall ;;
        3) localInstall ;;
        4) manageCertificates ;;
        5) manageConfigurations ;;
        6) manageLogs ;;
        7) updateNSX ;;
        8) stopNSXdocker ;;
        9) generateSubscriptions ;;
        10)uninstallNSX ;;
        11)restartNSXdocker ;;
        12)restartNSXlocal ;;
        13) echoContent green "\n 1.修改ssh文件: nano /etc/ssh/sshd_config \n 2.ufw启动端口命令：sudo ufw allow port/tcp \n 3.重启ssh命令： systemctl restart ssh\n 4.添加vpsadmin账号: adduser vpsadmin\n 5.设置sudo权限:visudo 在 User Privilege Specification 下加入一行 vpsadmin ALL=(ALL) NOPASSWD: ALL\n 6.ssh禁用root远程登录: PermitRootLogin:yes\n 7.xray命令：xray help"
      ;;
        14) exit 0 ;;
        *) echoContent red "无效选项." ; menu ;;
    esac
}

# Start script
checkSystem
menu