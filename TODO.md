# TODO: Fix xe_oto image selection/upload - ✅ COMPLETE

## Steps:
- [x] 1. Edit admin/xe_oto.php: Add new file input for hinh_anh_chinh_file, preview JS.
- [x] 2. Edit api/xe_oto.php: Handle hinh_anh_chinh_file upload in add/update cases.
- [x] 3. Test add/edit xe_oto with image from arbitrary folder.
- [x] 4. Complete task.

Files changed:
- admin/xe_oto.php
- api/xe_oto.php

Test: http://localhost/garahung/admin/xe_oto.php → "Chọn ảnh chính từ máy" now works from any folder, saves to uploads/.
