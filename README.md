# NovaHM – Hệ thống quản lý nhân sự (PHP + MySQL)

Bộ file này đã được chuyển từ HTML tĩnh sang PHP, kết nối trực tiếp với
cơ sở dữ liệu MySQL `novahm_db` (đúng theo file `novahm_database.sql`).

## 1. Cài đặt (dùng XAMPP + phpMyAdmin)

1. **Copy toàn bộ thư mục `novahm/`** vào `htdocs` của XAMPP
   (ví dụ: `C:\xampp\htdocs\novahm\`).
2. Bật **Apache** và **MySQL** trong XAMPP Control Panel.
3. Mở **phpMyAdmin** (`http://localhost/phpmyadmin`) → tab **Import** →
   chọn file `novahm_database.sql` → nhấn **Go**.
   → Thao tác này tự tạo database `novahm_db` với đầy đủ bảng, trigger,
   view và dữ liệu mẫu (3 nhân viên NV001–NV003 giống bản HTML gốc).
3b. **Import tiếp file `patch_phanquyen.sql`** (cùng cách: tab Import →
    chọn file → Go) — file này bổ sung bảng cấu hình hệ thống, KPI, đơn
    từ khác, và gán quyền hạn chi tiết cho 5 vai trò. Xem chi tiết ở
    mục 7 bên dưới.
3c. **Import tiếp file `patch_chotbangcong.sql`** — bổ sung cơ chế
    "chốt bảng công" theo tháng. Xem chi tiết ở mục 8 bên dưới.
4. Mở trình duyệt, truy cập:
   `http://localhost/novahm/cap_nhat_mk_admin.php`
   → Script này gán mật khẩu thật (mã hoá bcrypt) cho tài khoản admin mẫu,
   vì trong file .sql mật khẩu chỉ là chuỗi giả.
   → Sau khi thấy dòng "Đã cập nhật", **xoá file `cap_nhat_mk_admin.php` đi**.
5. Vào `http://localhost/novahm/login.php` và đăng nhập:
   - Email: `admin@novacompany.vn`
   - Mật khẩu: `123456`

Nếu MySQL của bạn có mật khẩu root hoặc chạy ở host khác, sửa 4 dòng đầu
trong `includes/db.php`.

## 2. Cấu trúc dự án

```
novahm/
├── includes/
│   ├── db.php        → cấu hình + kết nối PDO tới MySQL
│   ├── auth.php       → kiểm tra đăng nhập, bảo vệ mọi trang nội bộ
│   ├── header.php      → logo + topbar + menu sidebar dùng chung (tự active theo trang)
│   └── footer.php      → đóng layout + xử lý JS mở/đóng menu
├── login.php          → đăng nhập thật (kiểm tra bảng nguoi_dung, mật khẩu bcrypt)
├── logout.php         → đăng xuất, huỷ session
├── index.php          → Dashboard: 4 thẻ thống kê + đơn phép mới nhất (dữ liệu thật)
├── danhsachnhanvien.php → danh sách + tìm kiếm + xoá nhân viên
├── themnhanvien.php     → form thêm MỚI và SỬA nhân viên (dùng chung 1 file)
├── hopdong.php          → danh sách hợp đồng + form tạo hợp đồng mới + xoá
├── bangchamcong.php     → chấm công theo ngày + chấm công thủ công
├── lichsuravao.php      → lịch sử quét thẻ/vân tay theo ngày
├── bangtinhluongthang.php → TÍNH LƯƠNG tự động theo tháng (đọc chấm công + cấu hình lương) + duyệt lương
├── cauhinhluong.php     → cấu hình các tham số lương (lưu lịch sử theo ngày áp dụng)
├── duyetdonphep.php     → duyệt/từ chối đơn phép (trigger MySQL tự trừ quỹ phép khi duyệt) + tạo đơn mới
├── quyphepnam.php       → quỹ phép năm theo từng nhân viên, sửa tổng phép trực tiếp
└── novahm_database.sql  → file gốc bạn đã có, import vào phpMyAdmin
```

## 3. Các chức năng đã hoàn thiện (đấu nối thật với CSDL)

| Menu | Chức năng |
|---|---|
| Tổng quan | Thống kê tự động qua VIEW `vw_thong_ke_tong_quan`, danh sách đơn phép mới nhất |
| Danh sách nhân viên | Xem, tìm kiếm, sửa, xoá (JOIN phòng ban + chức vụ) |
| Thêm nhân viên mới | Thêm mới **và** sửa dùng chung 1 form, validate + báo lỗi trùng mã/email |
| Hợp đồng lao động | Xem, thêm hợp đồng mới, tự tính trạng thái "Sắp hết hạn/Hết hạn", xoá |
| Bảng chấm công ngày | Xem theo ngày (LEFT JOIN nên nhân viên chưa chấm công vẫn hiện "Vắng mặt"), chấm công thủ công |
| Lịch sử ra/vào | Xem lịch sử quét thẻ theo ngày, kèm thiết bị ghi nhận |
| Bảng tính lương tháng | Nút "Tính lương tháng" tính lương thật từ ngày công + cấu hình lương + BHXH/BHYT/BHTN + phạt trễ, và duyệt phiếu lương |
| Cấu hình lương | Sửa & lưu tham số lương (giữ lịch sử theo `ngay_ap_dung`) |
| Duyệt đơn nghỉ phép | Lọc theo trạng thái, Duyệt/Từ chối (trigger MySQL tự trừ quỹ phép khi duyệt), tạo đơn mới |
| Quỹ phép năm | Xem theo năm, sửa tổng số ngày phép trực tiếp trên bảng |
| Đăng nhập / Đăng xuất | Session PHP thật, mật khẩu bcrypt (không còn so sánh chuỗi thô như bản HTML gốc) |
| Chatbot AI (góc dưới phải) | Trả lời câu hỏi về lương/phép/nhân viên dựa trên dữ liệu CSDL thật, dùng Google Gemini API (miễn phí) |

## 5. Chatbot AI hỏi-đáp

Góc dưới bên phải mọi trang có nút 🤖 để mở khung chat. Chatbot dùng
**Google Gemini API (miễn phí, không cần thẻ tín dụng)**, trả lời dựa
trên dữ liệu THẬT hiện tại của công ty (danh sách nhân viên, đơn phép,
lương tháng, quỹ phép, hợp đồng sắp hết hạn...) chứ không bịa số liệu.

**Cách bật:**
1. Vào `https://aistudio.google.com` → đăng nhập bằng tài khoản Google.
2. Bấm **"Get API key"** (góc trái) → **"Create API key"** → chọn 1
   project (hoặc để mặc định) → copy key dạng `AIzaSy...`.
3. Mở file `includes/ai_config.php`, dán key vào dòng
   `define('GEMINI_API_KEY', '...')`.
4. Đảm bảo XAMPP đã bật extension `curl` (mặc định có sẵn) và máy có
   Internet — chatbot gọi ra API bên ngoài.
5. Mở lại trang bất kỳ trong hệ thống, bấm nút 🤖, thử hỏi ví dụ:
   *"Nhân viên nào sắp hết hạn hợp đồng?"*, *"NV002 còn bao nhiêu ngày
   phép?"*, *"Tổng lương thực lĩnh tháng này là bao nhiêu?"*

**Free tier của Gemini:** không cần nhập thẻ, giới hạn khoảng vài chục
request/phút — dư sức dùng để demo và bảo vệ đồ án. Nếu sau này bạn
gặp lỗi "model not found", model free có thể đã đổi tên — vào
`https://ai.google.dev/gemini-api/docs/models` xem tên model free mới
nhất rồi sửa dòng `GEMINI_MODEL` trong `includes/ai_config.php`.

**Lưu ý bảo mật:** file `includes/ai_config.php` chứa API key thật —
KHÔNG đẩy file này lên GitHub public hoặc chia sẻ cho người khác.


## 6. Ghi chú


- Nút "Xuất Excel" / "Xuất Báo Cáo" hiện chỉ là liên kết chưa nối logic
  xuất file — nếu bạn cần, mình có thể bổ sung xuất `.xlsx` thật bằng
  PhpSpreadsheet ở bước sau.
- Mọi thao tác quan trọng (thêm/sửa/xoá/duyệt) đều được ghi vào bảng
  `nhat_ky_hoat_dong` để phục vụ truy vết.
- File `login.html`, `index.html` (bản gốc) không còn dùng nữa — hãy dùng
  `login.php`, `index.php` mới.

## 7. Phân quyền chi tiết theo 5 vai trò

Sau khi import `novahm_database.sql`, **bắt buộc phải import thêm
`patch_phanquyen.sql`** (Import trong phpMyAdmin, làm sau file gốc) để
có đủ bảng và dữ liệu phân quyền. File patch này:
- Đổi tên vai trò "Nhân sự" → **"Nhân viên Hành chính - Nhân sự tổng hợp"**
- Tạo bảng `cau_hinh_he_thong` (luật chấm công + quy tắc tính phép)
- Tạo bảng `danh_gia_kpi` (đánh giá hiệu suất)
- Tạo bảng `don_tu_khac` (giải trình đi muộn / đăng ký tăng ca)
- Gán quyền hạn chi tiết cho từng vai trò vào bảng `vai_tro_quyen`

### Ma trận quyền

| | **Admin** | **Nhân sự tổng hợp** | **Kế toán** | **Trưởng phòng** | **Nhân viên** |
|---|---|---|---|---|---|
| Hồ sơ nhân viên | Toàn quyền (thêm/sửa/xoá, mọi phòng ban) | Toàn quyền (thêm/sửa/xoá, mọi phòng ban) | ✗ | Chỉ **xem** nhân viên phòng mình | Chỉ xem hồ sơ **của chính mình** |
| Hợp đồng lao động | Toàn quyền | Toàn quyền | ✗ | ✗ | Chỉ xem hợp đồng của mình |
| Chấm công | Toàn quyền (sửa) | Toàn quyền (sửa, kéo dữ liệu) | **Chỉ xem** (read-only) | ✗ | Chỉ xem chấm công của mình |
| Bảng lương / Tính lương | Toàn quyền | ✗ | Toàn quyền (tính, duyệt) | ✗ | Chỉ xem phiếu lương của mình |
| Cấu hình lương (BH, phụ cấp) | Toàn quyền | ✗ | Toàn quyền | ✗ | ✗ |
| Duyệt đơn nghỉ phép | Toàn công ty | ✗ | ✗ | Chỉ nhân viên phòng mình | Tạo đơn cho chính mình |
| Duyệt đơn đi muộn/tăng ca | Toàn công ty | ✗ | ✗ | Chỉ nhân viên phòng mình | Tạo đơn cho chính mình |
| Quỹ phép năm | Sửa toàn công ty | ✗ | ✗ | Chỉ **xem** phòng mình | Chỉ xem quỹ phép của mình |
| Đánh giá KPI | Xem toàn bộ | ✗ | ✗ | Chấm điểm nhân viên phòng mình | Chỉ xem KPI của mình |
| Quản lý phòng ban / chức vụ | Toàn quyền | ✗ | ✗ | ✗ | ✗ |
| Cấu hình hệ thống (luật chấm công, quy tắc phép) | Toàn quyền | ✗ | ✗ | ✗ | ✗ |
| Tạo tài khoản & phân quyền | Toàn quyền | ✗ | ✗ | ✗ | ✗ |
| Chatbot AI | Thấy toàn bộ dữ liệu | Thấy hồ sơ NV + chấm công (không thấy lương) | Thấy chấm công + lương (không thấy hồ sơ cá nhân) | Chỉ thấy dữ liệu phòng mình quản lý | Chỉ thấy dữ liệu của chính mình |

### Cách gán "Trưởng phòng quản lý phòng ban nào"

Vào **Admin → Danh mục & Cấu hình → Quản lý phòng ban** → sửa phòng ban
→ chọn nhân viên ở ô **"Trưởng phòng"**. Khi nhân viên đó đăng nhập bằng
tài khoản có vai trò **"Trưởng phòng"**, hệ thống tự nhận diện họ đang
quản lý phòng ban nào (dựa vào cột `truong_phong_id` trong bảng
`phong_ban`) và giới hạn toàn bộ dữ liệu họ thấy/thao tác theo đúng
phòng ban đó — kể cả trong chatbot AI.

### Menu theo từng vai trò (sau khi đăng nhập)

- **Admin** → `index.php`: Tổng quan, Hồ sơ nhân viên (+ Quản lý tài
  khoản), Chấm công, Tính lương, Ngày phép & Đơn từ, Phòng ban & KPI,
  Danh mục & Cấu hình.
- **Nhân sự tổng hợp** → `index.php`: Tổng quan, Hồ sơ nhân viên, Chấm công.
- **Kế toán** → `index.php`: Tổng quan, Chấm công (xem), Tính lương.
- **Trưởng phòng** → `index.php`: Tổng quan, Nhân viên phòng tôi, Duyệt
  đơn từ (phép + đi muộn/tăng ca), Quỹ phép phòng tôi, Đánh giá KPI.
- **Nhân viên** → `trangcanhan.php`: Trang cá nhân, Hồ sơ của tôi, Chấm
  công của tôi, Lương của tôi, Đơn từ của tôi (phép + đi muộn/tăng ca),
  KPI của tôi.

Truy cập chéo quyền (gõ thẳng URL trang không được phép) đều bị chặn ở
tầng server (`includes/auth.php` — hàm `yeuCauQuyen()` / `yeuCauVaiTro()`),
không chỉ ẩn ở giao diện.

### Chưa làm trong đợt này (đề xuất follow-up riêng)

M� tả vai trò **Nhân sự tổng hợp** có nhắc "quản lý tuyển dụng và đào
tạo" — đây là 2 mảng nghiệp vụ hoàn toàn mới (tuyển dụng: đăng tin /
hồ sơ ứng viên / lịch phỏng vấn; đào tạo: khoá học / lịch học / kết quả
đào tạo), cần thêm bảng CSDL và giao diện riêng, quy mô tương đương
một module lớn. Phần này **chưa được xây dựng** trong bản này để tránh
làm loãng và ảnh hưởng đến các phần đã hoàn thiện — có thể làm thành
đợt phát triển tiếp theo nếu bạn cần cho đồ án.

## 8. Chốt bảng công theo tháng

Phản ánh đúng thực tế: Kế toán chỉ được xem/tính lương trên dữ liệu
chấm công **đã được Nhân sự chốt** — tránh việc tính lương trên số
liệu còn có thể bị sửa.

**Luồng hoạt động:**
1. Admin hoặc Nhân sự tổng hợp vào **Chấm công → Bảng chấm công ngày**,
   chọn 1 ngày bất kỳ trong tháng cần chốt, bấm **"Chốt bảng công tháng
   x/y"**.
2. Sau khi chốt:
   - Không ai sửa được chấm công tháng đó nữa (kể cả Nhân sự) — nút
     "Chấm công thủ công" và form nhập tự ẩn.
   - Kế toán mới **xem được** bảng chấm công tháng đó (trước khi chốt,
     Kế toán vào sẽ thấy thông báo "chưa được chốt", không thấy dữ liệu).
   - Nút **"Tính lương tháng"** ở trang Bảng tính lương tháng mới bấm
     được (bị vô hiệu hoá nếu tháng chưa chốt).
3. Nếu chốt nhầm hoặc cần sửa lại, **chỉ Admin** thấy nút **"Mở khoá
   tháng"** ngay trong banner cảnh báo màu vàng — bấm vào sẽ mở khoá,
   Nhân sự sửa được chấm công trở lại (và phải chốt lại từ đầu sau khi sửa).

Cơ chế này áp dụng bảng `chot_bang_cong` (mỗi dòng = 1 tháng đã chốt,
kèm người chốt và thời điểm chốt) — được kiểm tra ở cả `bangchamcong.php`
và `bangtinhluongthang.php` thông qua hàm `thangDaChot()` /
`thongTinChotThang()` trong `includes/auth.php`.
