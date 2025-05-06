
# ใช้ PHP เวอร์ชัน 8.1 พร้อม Apache
FROM php:8.1-apache

# คัดลอกไฟล์ทั้งหมดจากโฟลเดอร์ปัจจุบัน (เช่น index.php, analyze.php, png/)
COPY . /var/www/html/

# เปิดพอร์ต 80 เพื่อให้สามารถเข้าถึงผ่านเว็บได้
EXPOSE 80
