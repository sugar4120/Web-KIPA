#!/usr/bin/env bash

# Script quản lý dự án WordPress KIPA
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$DIR"

ACTION=${1:-help}

case "$ACTION" in
  start|up)
    echo "🚀 Đang khởi động hệ thống WordPress KIPA..."
    docker-compose up -d
    echo ""
    echo "✅ Hệ thống đã sẵn sàng!"
    echo "👉 Website WordPress: http://localhost:8080"
    echo "👉 phpMyAdmin:        http://localhost:8081"
    ;;
  stop|down)
    echo "🛑 Đang dừng hệ thống..."
    docker-compose down
    echo "✅ Đã dừng các container."
    ;;
  restart)
    echo "🔄 Đang khởi động lại..."
    docker-compose restart
    echo "✅ Hoàn tất!"
    ;;
  logs)
    docker-compose logs -f ${2:-wordpress}
    ;;
  ps|status)
    docker-compose ps
    ;;
  *)
    echo "Sử dụng: ./manage.sh [lệnh]"
    echo ""
    echo "Các lệnh hỗ trợ:"
    echo "  start     Khởi động WordPress, MySQL và phpMyAdmin"
    echo "  stop      Dừng toàn bộ hệ thống"
    echo "  restart   Khởi động lại các container"
    echo "  logs      Xem logs (vd: ./manage.sh logs wordpress)"
    echo "  status    Kiểm tra trạng thái container"
    echo ""
    ;;
esac
