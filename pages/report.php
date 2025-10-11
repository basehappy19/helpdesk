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
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- ปัญหาการใช้งาน -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ปัญหาการใช้งาน <span class="text-red-500">*</span>
                            </label>
                            <select id="request_type" name="request_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                required>
                            </select>
                        </div>

                        <!-- ประเภท -->
                        <div id="categoryGroup" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ประเภท <span class="text-red-500">*</span>
                            </label>
                            <select id="issue_category" name="issue_category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                required>
                            </select>

                            <!-- ช่องพิมพ์ประเภท (อื่นๆ) -->
                            <input type="text" id="issue_category_other" name="issue_category_other"
                                placeholder="ระบุประเภท (อื่นๆ)"
                                class="mt-3 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary hidden"
                                disabled>
                        </div>
                    </div>

                    <!-- อาการปัญหา -->
                    <div id="symptomGroup" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อาการปัญหา <span class="text-red-500">*</span>
                        </label>

                        <!-- select อาการปัญหา -->
                        <select id="issue_symptom" name="issue_symptom"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                            required>
                        </select>

                        <!-- ช่องพิมพ์อาการปัญหา (อื่นๆ) -->
                        <input type="text" id="issue_symptom_other" name="issue_symptom_other"
                            placeholder="ระบุอาการปัญหา (อื่นๆ)"
                            class="mt-3 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary hidden"
                            disabled>
                    </div>


                    <!-- Location Details -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">หน่วยงาน <span class="text-red-500">*</span></label>
                            <input type="text" name="department" placeholder="ระบุหน่วยงาน" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">อาคาร <span class="text-red-500">*</span></label>
                            <input type="text" name="building" placeholder="ระบุอาคาร" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ชั้น <span class="text-red-500">*</span></label>
                            <input type="text" name="floor" placeholder="ระบุชั้น" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">จุดบริการ <span class="text-red-500">*</span></label>
                            <input type="text" name="service_point" placeholder="ระบุจุดบริการ" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
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


                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <button type="reset" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">ล้างข้อมูล</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">ส่งคำขอ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const requestTypeSelect = document.getElementById('request_type');
        const categoryGroup = document.getElementById('categoryGroup');
        const symptomGroup = document.getElementById('symptomGroup');

        const issueCategorySelect = document.getElementById('issue_category');
        const issueCategoryOther = document.getElementById('issue_category_other');

        const issueSymptomSelect = document.getElementById('issue_symptom');
        const issueSymptomOther = document.getElementById('issue_symptom_other');

        const OTHER_VALUE = '__other__';

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function show(el, yes) {
            if (!el) return;
            if (yes) el.classList.remove('hidden');
            else el.classList.add('hidden');
        }

        function enable(el, yes) {
            if (!el) return;
            el.disabled = !yes;
        }

        function requireField(el, yes) {
            if (!el) return;
            el.required = !!yes;
        }

        function resetIssueCategory() {
            if (!issueCategorySelect) return;
            issueCategorySelect.innerHTML = '<option value="">— เลือกประเภท —</option>';
            issueCategorySelect.disabled = true;
            // ซ่อน/ปิดช่อง other
            show(issueCategoryOther, false);
            enable(issueCategoryOther, false);
            issueCategoryOther.value = '';
        }

        function resetIssueSymptom() {
            if (!issueSymptomSelect) return;
            issueSymptomSelect.innerHTML = '<option value="">— เลือกอาการปัญหา —</option>';
            issueSymptomSelect.disabled = true;
            // ซ่อน/ปิดช่อง other
            show(issueSymptomOther, false);
            enable(issueSymptomOther, false);
            issueSymptomOther.value = '';
        }

        // เริ่มต้น: ซ่อนกลุ่ม Category/Symptom จนกว่าจะเลือก request_type
        show(categoryGroup, false);
        show(symptomGroup, false);
        resetIssueCategory();
        resetIssueSymptom();

        // โหลด request types
        if (requestTypeSelect) {
            requestTypeSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
            requestTypeSelect.disabled = true;

            fetch('/api/request_types/get.php', {
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
                            const id = item.id ?? '';
                            const code = item.code ?? '';
                            const name_th = item.name_th ?? `รายการ ${code}`;
                            opts.push(`<option value="${escapeHtml(code)}" data-id="${escapeHtml(id)}">${escapeHtml(name_th)}</option>`);
                        }
                        requestTypeSelect.innerHTML = opts.join('');
                        requestTypeSelect.disabled = false;
                    } else {
                        requestTypeSelect.innerHTML = '<option value="">ไม่พบข้อมูล</option>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    requestTypeSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                });

            // เมื่อเลือก request_type → แสดงกลุ่มประเภท และโหลด category
            requestTypeSelect.addEventListener('change', async function() {
                resetIssueCategory();
                resetIssueSymptom();
                show(symptomGroup, false); // ยังไม่แสดงจนกว่าจะเลือกประเภท
                const selected = this.selectedOptions[0];
                const requestTypeId = selected?.dataset.id || '';

                if (!requestTypeId) {
                    show(categoryGroup, false);
                    return;
                }

                // แสดงกลุ่มประเภท
                show(categoryGroup, true);
                issueCategorySelect.innerHTML = '<option value="">กำลังโหลด...</option>';
                try {
                    const res = await fetch(`/api/categories/get_by_request_type.php?request_type_id=${encodeURIComponent(requestTypeId)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const payload = await res.json();
                    const data = payload?.data ?? [];

                    const opts = ['<option value="">— เลือกประเภท —</option>'];
                    if (Array.isArray(data) && data.length) {
                        for (const item of data) {
                            const id = item.id ?? '';
                            const code = item.code ?? '';
                            const name_th = item.name_th ?? `ประเภท ${code}`;
                            opts.push(`<option value="${escapeHtml(code)}" data-id="${escapeHtml(id)}">${escapeHtml(name_th)}</option>`);
                        }
                    }
                    // เพิ่มตัวเลือก "อื่นๆ"
                    opts.push(`<option value="${OTHER_VALUE}" data-id="">อื่นๆ (พิมพ์ระบุเอง)</option>`);
                    issueCategorySelect.innerHTML = opts.join('');
                    issueCategorySelect.disabled = false;
                } catch (err) {
                    console.error(err);
                    issueCategorySelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                    issueCategorySelect.disabled = true;
                }
            });
        }

        // เมื่อเลือกประเภท → ถ้าเป็น "อื่นๆ" ให้โชว์ช่องพิมพ์ และโชว์อาการ (พิมพ์เองทันที)
        if (issueCategorySelect) {
            issueCategorySelect.addEventListener('change', async function() {
                resetIssueSymptom(); // เคลียร์อาการก่อน
                const selected = this.value;
                const selectedOpt = this.selectedOptions[0];
                const issueCategoryId = selectedOpt?.dataset.id || '';

                if (selected === OTHER_VALUE) {
                    // โชว์ช่องพิมพ์ประเภท
                    show(issueCategoryOther, true);
                    enable(issueCategoryOther, true);
                    requireField(issueCategoryOther, true);

                    // โชว์กลุ่มอาการทันที และแสดงช่องพิมพ์อาการ (ซ่อน select)
                    show(symptomGroup, true);
                    show(issueSymptomSelect, false);
                    enable(issueSymptomSelect, false);
                    requireField(issueSymptomSelect, false);

                    show(issueSymptomOther, true);
                    enable(issueSymptomOther, true);
                    requireField(issueSymptomOther, true);
                    return;
                } else {
                    // ซ่อนช่องพิมพ์ประเภท
                    show(issueCategoryOther, false);
                    enable(issueCategoryOther, false);
                    requireField(issueCategoryOther, false);
                }

                // เงื่อนไข: ต้องมี category id ถึงจะโหลด symptoms
                if (!issueCategoryId) {
                    show(symptomGroup, false);
                    return;
                }

                // โหลด symptoms
                issueSymptomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
                show(symptomGroup, true);
                show(issueSymptomSelect, true);
                enable(issueSymptomSelect, true);
                requireField(issueSymptomSelect, true);

                // เริ่มต้น: ซ่อนช่องพิมพ์อาการ
                show(issueSymptomOther, false);
                enable(issueSymptomOther, false);
                requireField(issueSymptomOther, false);

                try {
                    const res = await fetch(`/api/symptoms/get_by_issue_category.php?issue_category_id=${encodeURIComponent(issueCategoryId)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const payload = await res.json();
                    const data = payload?.data ?? [];

                    const opts = ['<option value="">— เลือกอาการปัญหา —</option>'];
                    if (Array.isArray(data) && data.length) {
                        for (const item of data) {
                            const id = item.id ?? '';
                            const code = item.code ?? '';
                            const name_th = item.name_th ?? `อาการ ${code}`;
                            opts.push(`<option value="${escapeHtml(code)}" data-id="${escapeHtml(id)}">${escapeHtml(name_th)}</option>`);
                        }
                    }
                    // เพิ่มตัวเลือก "อื่นๆ"
                    opts.push(`<option value="${OTHER_VALUE}" data-id="">อื่นๆ (พิมพ์ระบุเอง)</option>`);
                    issueSymptomSelect.innerHTML = opts.join('');
                    issueSymptomSelect.disabled = false;
                } catch (err) {
                    console.error(err);
                    issueSymptomSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                    issueSymptomSelect.disabled = true;
                }
            });
        }

        // เมื่อเลือกอาการ → ถ้าเป็น "อื่นๆ" ให้โชว์ช่องพิมพ์อาการ
        if (issueSymptomSelect) {
            issueSymptomSelect.addEventListener('change', function() {
                if (this.value === OTHER_VALUE) {
                    show(issueSymptomOther, true);
                    enable(issueSymptomOther, true);
                    requireField(issueSymptomOther, true);
                } else {
                    show(issueSymptomOther, false);
                    enable(issueSymptomOther, false);
                    requireField(issueSymptomOther, false);
                    issueSymptomOther.value = '';
                }
            });
        }
    });
</script>

</html>