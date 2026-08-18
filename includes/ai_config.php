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

define('GEMINI_API_KEY', 'AQ.Ab8RN6JuPsT_7Ig0KKDMCnQBzbuAp9vWDofG3jE2YUNfG3cRSQ');


define('GEMINI_MODEL', 'gemini-3.6-flash');
