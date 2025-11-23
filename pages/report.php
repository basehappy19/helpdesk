<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งปัญหา / บริการ | HelpDesk</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php include './components/navbar.php'; ?>
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen py-8 px-4">
        <div class="max-w-5xl mx-auto">
            <!-- Header Card -->
            <div class="bg-white md:rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 card-hover">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 rounded-xl p-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">แจ้งปัญหา</h1>
                        <p class="text-gray-500 mt-1">กรุณากรอกข้อมูลให้ครบถ้วนเพื่อความรวดเร็วในการให้บริการ</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white md:rounded-2xl shadow-sm border border-gray-100 p-8 card-hover">
                <form class="space-y-8" method="POST" action="/api/reports/create_ticket.php" novalidate>

                    <!-- Section 1: ข้อมูลปัญหา -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                            <div class="bg-blue-100 rounded-lg p-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">ข้อมูลปัญหา</h2>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- ปัญหาการใช้งาน -->
                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>ปัญหาการใช้งาน</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select id="request_type" name="request_type"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                                    <option value="">เลือกปัญหาการใช้งาน</option>
                                    <option value="hardware">ฮาร์ดแวร์</option>
                                    <option value="software">ซอฟต์แวร์</option>
                                    <option value="network">เครือข่าย</option>
                                    <option value="other">อื่นๆ</option>
                                </select>
                            </div>

                            <!-- ประเภท -->
                            <div id="categoryGroup" class="space-y-2 hidden">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>ประเภท</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select id="issue_category" name="issue_category"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                                    <option value="">เลือกประเภท</option>
                                    <option value="printer">เครื่องพิมพ์</option>
                                    <option value="computer">คอมพิวเตอร์</option>
                                    <option value="other">อื่นๆ</option>
                                </select>

                                <input type="text" id="issue_category_other" name="issue_category_other"
                                    placeholder="ระบุประเภท (อื่นๆ)"
                                    class="input-focus mt-3 w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200 hidden"
                                    disabled>
                            </div>
                        </div>

                        <!-- อาการปัญหา -->
                        <div id="symptomGroup" class="space-y-2 hidden">
                            <label class="flex items-center text-sm font-medium text-gray-700">
                                <span>อาการปัญหา</span>
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="issue_symptom" name="issue_symptom"
                                class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                                <option value="">เลือกอาการปัญหา</option>
                                <option value="not_working">ใช้งานไม่ได้</option>
                                <option value="slow">ช้า</option>
                                <option value="error">เกิด Error</option>
                                <option value="other">อื่นๆ</option>
                            </select>

                            <input type="text" id="issue_symptom_other" name="issue_symptom_other"
                                placeholder="ระบุอาการปัญหา (อื่นๆ)"
                                class="input-focus mt-3 w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200 hidden"
                                disabled>
                        </div>
                    </div>

                    <!-- Section 2: สถานที่ -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                            <div class="bg-green-100 rounded-lg p-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">สถานที่</h2>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>หน่วยงาน</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="department" placeholder="เช่น สำนักคอมพิวเตอร์"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>อาคาร</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="building" placeholder="เช่น อาคาร A"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>ชั้น</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="floor" placeholder="เช่น ชั้น 3"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>จุดบริการ</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="service_point" placeholder="เช่น ห้อง 301"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: ผู้แจ้ง -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                            <div class="bg-purple-100 rounded-lg p-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">ข้อมูลผู้แจ้ง</h2>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>ผู้แจ้ง</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="reporter" placeholder="ชื่อ-นามสกุล"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <span>เบอร์โทรศัพท์</span>
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="tel" name="phone" placeholder="เช่น 081-234-5678"
                                    class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button type="reset"
                            class="cursor-pointer px-6 py-3 border-2 border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            ล้างข้อมูล
                        </button>
                        <button type="submit"
                            class="cursor-pointer px-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ส่งคำขอ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-gray-500 text-sm">
                <p>หากมีปัญหาการใช้งาน กรุณาติดต่อผู้ดูแลระบบ</p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // ---------- Toastify helpers ----------
            const toast = {
                base(opt) {
                    return Toastify(Object.assign({
                        duration: 3500,
                        gravity: "bottom",
                        position: "right",
                        stopOnFocus: true,
                        close: true,
                    }, opt));
                },
                info(msg) {
                    this.base({
                        text: msg,
                        backgroundColor: "#2563eb"
                    }).showToast();
                },
                success(msg) {
                    this.base({
                        text: "✅ " + msg,
                        backgroundColor: "#16a34a"
                    }).showToast();
                },
                error(msg) {
                    this.base({
                        text: "❌ " + msg,
                        backgroundColor: "#dc2626"
                    }).showToast();
                },
                warn(msg) {
                    this.base({
                        text: "⚠️ " + msg,
                        backgroundColor: "#f59e0b"
                    }).showToast();
                },
                loading(msg = "กำลังส่งคำขอ…") {
                    return this.base({
                        text: "⏳ " + msg,
                        duration: -1, // ค้างไว้ จนกว่าเราจะ hide
                        backgroundColor: "#374151",
                        close: true
                    });
                }
            };

            function attachFieldListeners(form) {
                if (!form) return;

                form.addEventListener('input', handleClearError);
                form.addEventListener('change', handleClearError);

                function handleClearError(e) {
                    const el = e.target;
                    if (el.matches('input, select, textarea')) {
                        el.classList.remove("border-red-500", "bg-red-50");
                    }
                }
            }

            const form = document.querySelector("form[action='/api/reports/create_ticket.php']");
            attachFieldListeners(form);
            const submitBtn = form.querySelector("button[type='submit']");

            // ---------- DOM refs สำหรับ dynamic fields ----------
            const requestTypeSelect = document.getElementById('request_type');
            const categoryGroup = document.getElementById('categoryGroup');
            const symptomGroup = document.getElementById('symptomGroup');

            const issueCategorySelect = document.getElementById('issue_category');
            const issueCategoryOther = document.getElementById('issue_category_other');

            const issueSymptomSelect = document.getElementById('issue_symptom');
            const issueSymptomOther = document.getElementById('issue_symptom_other');

            const OTHER_VALUE = '__other__';

            // ---------- small utils ----------
            function show(el, yes) {
                if (!el) return;
                el.classList.toggle('hidden', !yes);
            }

            function enable(el, yes) {
                if (!el) return;
                el.disabled = !yes;
            }

            function requireField(el, yes) {
                if (!el) return;
                el.required = !!yes;
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function resetIssueCategory() {
                if (!issueCategorySelect) return;
                issueCategorySelect.innerHTML = '<option value="">— เลือกประเภท —</option>';
                enable(issueCategorySelect, false);
                // other
                show(issueCategoryOther, false);
                enable(issueCategoryOther, false);
                requireField(issueCategoryOther, false);
                issueCategoryOther.value = '';
            }

            function resetIssueSymptom() {
                if (!issueSymptomSelect) return;
                issueSymptomSelect.innerHTML = '<option value="">— เลือกอาการปัญหา —</option>';
                enable(issueSymptomSelect, false);
                // other
                show(issueSymptomOther, false);
                enable(issueSymptomOther, false);
                requireField(issueSymptomOther, false);
                issueSymptomOther.value = '';
            }

            function validatePhoneTH(p) {
                return /^0?\d{9,10}$/.test(String(p).replace(/\D/g, ''));
            }

            show(categoryGroup, false);
            show(symptomGroup, false);
            resetIssueCategory();
            resetIssueSymptom();

            if (requestTypeSelect) {


                requestTypeSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
                enable(requestTypeSelect, false);

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
                        const data = payload?.data ?? payload ?? [];
                        const opts = ['<option value="">— เลือกปัญหา —</option>'];
                        if (Array.isArray(data) && data.length) {
                            for (const item of data) {
                                const id = item.id ?? '';
                                const code = item.code ?? '';
                                const name_th = item.name_th ?? `รายการ ${code}`;
                                opts.push(`<option value="${escapeHtml(code)}" data-id="${escapeHtml(id)}">${escapeHtml(name_th)}</option>`);
                            }
                            requestTypeSelect.innerHTML = opts.join('');
                            enable(requestTypeSelect, true);
                        } else {
                            requestTypeSelect.innerHTML = '<option value="">ไม่พบข้อมูล</option>';
                            toast.warn('ไม่พบรายการปัญหาการใช้งาน');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        requestTypeSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                        toast.error('โหลดรายการปัญหาการใช้งานไม่สำเร็จ');
                    });

                requestTypeSelect.addEventListener('change', async function() {
                    resetIssueCategory();
                    resetIssueSymptom();
                    show(symptomGroup, false);

                    const selected = this.selectedOptions[0];
                    const requestTypeId = selected?.dataset.id || '';

                    if (!requestTypeId) {
                        show(categoryGroup, false);
                        return;
                    }

                    // แสดงกลุ่มประเภท และโหลดจาก API
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
                        // เพิ่ม "อื่นๆ"
                        opts.push(`<option value="${OTHER_VALUE}" data-id="">อื่นๆ (พิมพ์ระบุเอง)</option>`);
                        issueCategorySelect.innerHTML = opts.join('');
                        enable(issueCategorySelect, true);
                    } catch (err) {
                        console.error(err);
                        issueCategorySelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                        enable(issueCategorySelect, false);
                        toast.error('โหลดประเภทไม่สำเร็จ');
                    }
                });
            }

            // ---------- เปลี่ยนประเภท ----------
            if (issueCategorySelect) {
                issueCategorySelect.addEventListener('change', async function() {
                    resetIssueSymptom();
                    const selected = this.value;
                    const selectedOpt = this.selectedOptions[0];
                    const issueCategoryId = selectedOpt?.dataset.id || '';

                    if (selected === OTHER_VALUE) {
                        // เปิดช่องกรอกประเภทอื่นๆ
                        show(issueCategoryOther, true);
                        enable(issueCategoryOther, true);
                        requireField(issueCategoryOther, true);

                        // อาการ: เปิดกลุ่ม + ใช้กรอกเอง (ซ่อน select)
                        show(symptomGroup, true);
                        show(issueSymptomSelect, false);
                        enable(issueSymptomSelect, false);
                        requireField(issueSymptomSelect, false);

                        show(issueSymptomOther, true);
                        enable(issueSymptomOther, true);
                        requireField(issueSymptomOther, true);
                        return;
                    } else {
                        // ปิดช่องกรอกประเภทอื่นๆ
                        show(issueCategoryOther, false);
                        enable(issueCategoryOther, false);
                        requireField(issueCategoryOther, false);
                    }

                    if (!issueCategoryId) {
                        show(symptomGroup, false);
                        return;
                    }

                    // โหลดอาการ
                    issueSymptomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
                    show(symptomGroup, true);
                    show(issueSymptomSelect, true);
                    enable(issueSymptomSelect, true);
                    requireField(issueSymptomSelect, true);

                    // ปิดช่องกรอกอาการ other ไว้ก่อน
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
                        opts.push(`<option value="${OTHER_VALUE}" data-id="">อื่นๆ (พิมพ์ระบุเอง)</option>`);
                        issueSymptomSelect.innerHTML = opts.join('');
                        enable(issueSymptomSelect, true);
                    } catch (err) {
                        console.error(err);
                        issueSymptomSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
                        enable(issueSymptomSelect, false);
                        toast.error('โหลดอาการไม่สำเร็จ');
                    }
                });
            }

            // ---------- เปลี่ยนอาการ ----------
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

            function showError(msg, el) {
                if (el) {
                    el.classList.add("border-red-500", "bg-red-50");
                    el.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                    el.focus();
                }
                Toastify({
                    text: "❌ " + msg,
                    duration: 4000,
                    gravity: "bottom",
                    position: "right",
                    backgroundColor: "#dc2626"
                }).showToast();
                return false;
            }

            function validateClient() {
                form.querySelectorAll("input, select, textarea").forEach(el => {
                    el.classList.remove("border-red-500", "bg-red-50");
                });

                const rt = requestTypeSelect?.value || '';
                const cat = issueCategorySelect?.value || '';
                const sym = issueSymptomSelect?.value || '';
                const dept = (form.querySelector("[name='department']")?.value || '').trim();
                const bldg = (form.querySelector("[name='building']")?.value || '').trim();
                const flr = (form.querySelector("[name='floor']")?.value || '').trim();
                const serv = (form.querySelector("[name='service_point']")?.value || '').trim();
                const phone = (form.querySelector("[name='phone']")?.value || '').trim();
                const rep = (form.querySelector("[name='reporter']")?.value || '').trim();

                if (!rt) return showError("กรุณาเลือกปัญหาการใช้งาน", requestTypeSelect);
                if (!cat) return showError("กรุณาเลือกประเภท", issueCategorySelect);

                if (cat === OTHER_VALUE && !(issueCategoryOther?.value || '').trim())
                    return showError("กรุณาระบุประเภท (อื่นๆ)", issueCategoryOther);

                if (cat !== OTHER_VALUE) {
                    if (!sym)
                        return showError("กรุณาเลือกอาการปัญหา", issueSymptomSelect);
                    if (sym === OTHER_VALUE && !(issueSymptomOther?.value || '').trim())
                        return showError("กรุณาระบุอาการปัญหา (อื่นๆ)", issueSymptomOther);
                } else if (!(issueSymptomOther?.value || '').trim()) {
                    return showError("กรุณาระบุอาการปัญหา (อื่นๆ)", issueSymptomOther);
                }

                if (!dept) return showError("กรุณาระบุหน่วยงาน", form.querySelector("[name='department']"));
                if (!bldg) return showError("กรุณาระบุอาคาร", form.querySelector("[name='building']"));
                if (!flr) return showError("กรุณาระบุชั้น", form.querySelector("[name='floor']"));
                if (!serv) return showError("กรุณาระบุจุดบริการ", form.querySelector("[name='service_point']"));
                if (!rep) return showError("กรุณาระบุชื่อผู้แจ้ง", form.querySelector("[name='reporter']"));
                if (!phone) return showError("กรุณาระบุเบอร์โทรศัพท์", form.querySelector("[name='phone']"));
                if (!validatePhoneTH(phone)) return showError("รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง", form.querySelector("[name='phone']"));

                return true;
            }


            // ---------- Submit (no redirect) + Toastify ----------
            form.addEventListener("submit", (e) => {
                e.preventDefault();
                e.stopPropagation();

                const valid = validateClient();
                if (!valid) {
                    return false;
                }

                // ใช้ async IIFE สำหรับ fetch ภายใน
                (async () => {
                    const fd = new FormData(form);

                    // แปลง OTHER_VALUE เป็น 'other'
                    if (fd.get('issue_category') === OTHER_VALUE) fd.set('issue_category', 'other');
                    if (fd.get('issue_symptom') === OTHER_VALUE) fd.set('issue_symptom', 'other');

                    const loadingToast = toast.loading("กำลังส่งคำขอ…");
                    loadingToast.showToast();
                    submitBtn.disabled = true;
                    submitBtn.classList.add("opacity-60", "cursor-not-allowed");

                    try {
                        const res = await fetch(form.action, {
                            method: "POST",
                            body: fd
                        });
                        const data = await res.json().catch(() => ({}));

                        loadingToast.hideToast();
                        submitBtn.disabled = false;
                        submitBtn.classList.remove("opacity-60", "cursor-not-allowed");

                        if (res.ok && data?.ok) {
                            const code = data.ticket_code || data.ticket_id || "";
                            toast.success(`บันทึกสำเร็จ! ${code ? "รหัสการแจ้ง " + code : ""}`);
                            form.reset();

                            show(categoryGroup, false);
                            show(symptomGroup, false);
                            resetIssueCategory();
                            resetIssueSymptom();
                            requestTypeSelect.value = '';
                        } else {
                            const msg = data?.message || data?.error || `เกิดข้อผิดพลาด (HTTP ${res.status})`;
                            toast.error(msg);
                        }
                    } catch (err) {
                        console.error(err);
                        loadingToast.hideToast();
                        submitBtn.disabled = false;
                        submitBtn.classList.remove("opacity-60", "cursor-not-allowed");
                        toast.error("ไม่สามารถส่งข้อมูลได้ กรุณาลองใหม่อีกครั้ง");
                    }
                })();
            });
        });
    </script>
</body>

</html>