<?php
/**
 * CẤU HÌNH AI CHATBOT — dùng Google Gemini API (MIỄN PHÍ, không cần thẻ tín dụng)
 *
 * 1. Vào https://aistudio.google.com -> đăng nhập bằng tài khoản Google
 * 2. Bấm "Get API key" (góc trái) -> "Create API key" -> chọn 1 project (hoặc để mặc định)
 * 3. Copy key (dạng AIzaSy...) dán vào dòng GEMINI_API_KEY bên dưới
 *
 * Free tier: không cần nhập thẻ, giới hạn khoảng vài chục request/phút —
 * quá đủ cho việc demo/bảo vệ đồ án.
 *
 * KHÔNG chia sẻ file này hoặc đẩy lên GitHub public.
 */

define('GEMINI_API_KEY', 'AIzaSy_DAN_KEY_CUA_BAN_VAO_DAY');

// Model free-tier ổn định tại thời điểm viết code này.
// Nếu sau này Google đổi tên model và bạn bị lỗi 404 "model not found",
// vào https://ai.google.dev/gemini-api/docs/models để lấy tên model free mới nhất
// rồi thay vào dòng dưới (ví dụ: 'gemini-2.5-flash', 'gemini-2.5-flash-lite'...).
define('GEMINI_MODEL', 'gemini-2.5-flash');
