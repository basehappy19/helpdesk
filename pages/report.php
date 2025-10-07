<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งปัญหา / บริการ | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php include './components/navbar.php'; ?>
    <div id="reportPage">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">แจ้งปัญหา/ขอรับบริการ</h2>

                <form class="space-y-6" method="POST" action="#">
                    <!-- Problem Usage Type -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ปัญหาการใช้งาน <span class="text-red-500">*</span>
                            </label>
                            <select id="usage_problem" name="usage_problem"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                required>
                            </select>
                            <script>
                                (function() {
                                    const select = document.getElementById('usage_problem');

                                    function escapeHtml(str) {
                                        return String(str)
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;')
                                            .replace(/"/g, '&quot;')
                                            .replace(/'/g, '&#039;');
                                    }

                                    select.innerHTML = '<option value="">กำลังโหลด...</option>';
                                    select.disabled = true;

                                    fetch('/api/usage_problems/get.php', {
                                            headers: {
                                                'Accept': 'application/json'
                                            }
                                        })
                                        .then(res => {
                                            if (!res.ok) throw new Error('HTTP ' + res.status);
                                            return res.json();
                                        })
                                        .then(payload => {
                                            const data = payload?.data ?? payload;
                                            const opts = ['<option value="">— เลือกปัญหา —</option>'];

                                            if (Array.isArray(data) && data.length) {
                                                for (const item of data) {
                                                    const id = item.id ?? item.ID ?? '';
                                                    const name = item.name ?? item.title ?? `รายการ ${id}`;
                                                    opts.push(`<option value="${escapeHtml(id)}">${escapeHtml(name)}</option>`);
                                                }
                                                select.innerHTML = opts.join('');
                                                select.disabled = false;
                                            } else {
                                                select.innerHTML = '<option value="">ไม่พบข้อมูล</option>';
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            select.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                                        });
                                })();
                            </script>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท <span class="text-red-500">*</span></label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                                <option value="">เลือกประเภท</option>
                                <option value="urgent">เร่งด่วน</option>
                                <option value="normal">ปกติ</option>
                                <option value="maintenance">บำรุงรักษา</option>
                                <option value="installation">ติดตั้งใหม่</option>
                                <option value="repair">ซ่อมแซม</option>
                            </select>
                        </div>
                    </div>

                    <!-- Problem Symptoms -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">อาการปัญหา <span class="text-red-500">*</span></label>
                        <select name="symptoms" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                            <option value="">เลือกอาการปัญหา</option>
                            <option value="not_working">ไม่ทำงาน</option>
                            <option value="slow">ทำงานช้า</option>
                            <option value="error_message">ขึ้นข้อความผิดพลาด</option>
                            <option value="cannot_connect">เชื่อมต่อไม่ได้</option>
                            <option value="print_problem">พิมพ์ไม่ออก</option>
                            <option value="system_crash">ระบบขัดข้อง</option>
                            <option value="other_symptom">อื่นๆ</option>
                        </select>
                    </div>

                    <!-- Location Details -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">หน่วยงาน <span class="text-red-500">*</span></label>
                            <input type="text" name="department" placeholder="ระบุหน่วยงาน" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">อาคาร <span class="text-red-500">*</span></label>
                            <select name="building" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                                <option value="">เลือกอาคาร</option>
                                <option value="building_a">อาคาร A</option>
                                <option value="building_b">อาคาร B</option>
                                <option value="building_c">อาคาร C</option>
                                <option value="building_d">อาคาร D</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ชั้น <span class="text-red-500">*</span></label>
                            <select name="floor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                                <option value="">เลือกชั้น</option>
                                <option value="1">ชั้น 1</option>
                                <option value="2">ชั้น 2</option>
                                <option value="3">ชั้น 3</option>
                                <option value="4">ชั้น 4</option>
                                <option value="5">ชั้น 5</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">จุดบริการ</label>
                            <input type="text" name="service_point" placeholder="ระบุจุดบริการ (ห้อง/โต๊ะ)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" placeholder="เบอร์โทรศัพท์" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ผู้แจ้ง <span class="text-red-500">*</span></label>
                            <input type="text" name="reporter" placeholder="ชื่อ-นามสกุล ผู้แจ้ง" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดเพิ่มเติม</label>
                        <textarea name="details" rows="4" placeholder="อธิบายปัญหาหรือความต้องการเพิ่มเติม..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <button type="reset" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">ล้างข้อมูล</button>
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-blue-700 transition-colors">ส่งคำขอ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>