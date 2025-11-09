#!/bin/bash
# setup_vchat.sh
# 自动化部署 vchat（含交互：是否构建镜像、是否清理等）
set -euo pipefail

ROOT_DIR="/usr/local/nsx/www/wwwroot"
REPO="https://github.com/judawu/vchat.git"
PROJECT_DIR="$ROOT_DIR/vchat"
MYSQL_DIR="$ROOT_DIR/mysql"
IMAGE_NAME="vchat-php"
COMPOSE_FILE_DIR="$PROJECT_DIR"  # 假设 docker-compose.yml 在项目根

ask_yesno() {
  # $1 = prompt, returns 0 for yes, 1 for no
  local prompt="$1"
  local default="${2:-}" # optional default "y" or "n"
  local ans
  while true; do
    if [ -n "$default" ]; then
      read -rp "$prompt [$default] " ans
      ans="${ans:-$default}"
    else
      read -rp "$prompt [y/n] " ans
    fi
    case "${ans,,}" in
      y|yes) return 0 ;;
      n|no)  return 1 ;;
      *) echo "请输入 y 或 n。" ;;
    esac
  done
}

echo "=== 切换目录到 $ROOT_DIR ==="
cd "$ROOT_DIR"

echo "=== 克隆或更新 vchat 项目 ==="
if [ ! -d "$PROJECT_DIR" ]; then
  git clone "$REPO"
else
  echo "vchat 目录已存在，尝试拉取最新代码（git pull）"
  cd "$PROJECT_DIR"
  git pull --ff-only || echo "git pull 失败，保持本地版本。"
  cd "$ROOT_DIR"
fi

echo "=== 创建 MySQL 数据目录并设置权限 ==="
sudo mkdir -p "$MYSQL_DIR"
sudo chown -R 1001:1001 "$MYSQL_DIR" || echo "chown 失败（请检查权限）"
sudo chmod -R 755 "$MYSQL_DIR"

########################
# 构建镜像选项
########################
if ask_yesno "是否要在继续前构建镜像 ${IMAGE_NAME}? (y=构建, n=跳过构建) " "y"; then
  echo "=== 清理旧的 ${IMAGE_NAME} 镜像（如果存在） ==="
  old_image_ids=$(docker images -q "$IMAGE_NAME" || true)
  if [ -n "$old_image_ids" ]; then
    echo "发现旧镜像："
    docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.ID}}\t{{.CreatedSince}}" "$IMAGE_NAME"
    echo "正在删除旧镜像..."
    docker rmi -f $old_image_ids || echo "删除镜像失败（可能被容器占用）"
  else
    echo "未发现旧的 ${IMAGE_NAME} 镜像。"
  fi

  echo "=== 构建 Docker 镜像 ${IMAGE_NAME} ==="
  cd "$PROJECT_DIR"
  docker build -t "$IMAGE_NAME" .
  cd "$ROOT_DIR"
else
  echo "跳过镜像构建，直接继续。"
fi

########################
# 启动前检查运行中的容器/镜像
########################
cd "$COMPOSE_FILE_DIR"

# 检查是否有同名镜像被运行为容器（通过镜像名或容器名 vchat-php / vchat-db）
running_conflict=false

# 如果有以该镜像启动的容器在运行
if docker ps --filter "ancestor=$IMAGE_NAME" --format '{{.ID}}' | grep -q .; then
  echo "检测到使用镜像 ${IMAGE_NAME} 的运行中的容器。"
  running_conflict=true
fi

# 检查常见服务容器名（可根据 docker-compose.yml 修改）
for cname in vchat-php vchat-db; do
  if docker ps --format '{{.Names}}' | grep -xq "$cname"; then
    echo "检测到运行中的容器名：$cname"
    running_conflict=true
  fi
done

if $running_conflict; then
  echo "=== 发现冲突容器，先执行 docker compose down 以确保干净启动 ==="
  docker compose down || echo "docker compose down 返回非零（继续尝试启动）"
else
  echo "未检测到冲突容器，可以直接启动。"
fi

echo "=== 使用 docker compose up -d 启动服务 ==="
docker compose up -d

echo "=== 列出运行中的容器 ==="
docker ps

echo "=== 查看 MySQL 初始化日志（最近 200 行） ==="
docker compose logs db | tail -n 200 || echo "获取 db 日志失败或服务未输出日志"

echo "=== 查看 PHP 服务日志（最近 200 行） ==="
docker compose logs php | tail -n 200 || echo "获取 php 日志失败或服务未输出日志"

########################
# 日志后清理选项
########################
if ask_yesno "是否现在执行 docker compose down 并进行清理 (删除容器/镜像/可选卷)? (y=清理, n=不清理) " "n"; then
  echo "执行 docker compose down ..."
  docker compose down

  if ask_yesno "是否同时删除本地镜像 ${IMAGE_NAME}? (y=删除镜像, n=保留镜像) " "n"; then
    img_ids=$(docker images -q "$IMAGE_NAME" || true)
    if [ -n "$img_ids" ]; then
      echo "删除镜像 ${IMAGE_NAME} ..."
      docker rmi -f $img_ids || echo "删除镜像失败（可能被其他镜像或容器占用）"
    else
      echo "未找到 ${IMAGE_NAME} 镜像可删除。"
    fi
  fi

  if ask_yesno "是否删除 docker compose 卷（volumes）以清除数据？**注意：会丢失数据库数据** (y=删除卷, n=保留卷) " "n"; then
    echo "删除与 compose 相关的匿名/命名卷..."
    # 下面命令会移除当前目录 compose 定义下未被使用的卷
    docker volume prune -f || echo "volume prune 失败"
    echo "如需要删除特定命名卷请手动执行 docker volume rm <name>"
  fi

  echo "清理完成。"
else
  echo "保持当前运行状态，未执行额外清理。"
fi

echo "=== 脚本执行完毕 ==="
